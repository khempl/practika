<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("worker.php запускается только из командной строки\n");
}

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Parser.php';
require_once __DIR__ . '/JobStore.php';


$fileId = $argv[1] ?? '';
$jobId = $argv[2] ?? '';

$baseDir = __DIR__;
$uploadsDir = $baseDir . '/uploads';
$jobsDir = $baseDir . '/jobs';
$errorsDir = $baseDir . '/errors';

foreach ([$uploadsDir, $jobsDir, $errorsDir] as $d) {
    if (!is_dir($d)) {
        mkdir($d, 0777, true);
    }
}

if (!JobStore::isValidId($jobId) || !preg_match('/^[a-f0-9]{32}$/', $fileId)) {
    fwrite(STDERR, "некорректные идентификаторы\n");
    exit(1);
}

$filePath = $uploadsDir . '/' . $fileId . '.txt';

$fieldLabels = [
    'account' => 'Лицевой счёт',
    'fio' => 'ФИО',
    'address' => 'Адрес',
    'period' => 'Период начисления',
    'amount' => 'Сумма начисления',
    'meter' => 'Прибор учёта',
    'format' => 'Формат строки',
];

function recalcErrorGroups(array $errorCounts, array $fieldLabels): array
{
    $groups = [];
    foreach ($errorCounts as $type => $count) {
        if ($count > 0) {
            $groups[] = ['field' => $fieldLabels[$type] ?? $type, 'count' => $count];
        }
    }
    return $groups;
}

if (!is_file($filePath)) {
    $state = [
        'status' => 'processing',
        'total' => $total,
        'processed' => 0,
        'success' => 0,
        'errors' => 0,
        'duplicates' => 0,
        'progress' => 0,
        'error_groups' => [],
        'logs' => [['message' => "начата обработка файла: {$total} строк", 'type' => 'info']],
        'started_at' => time(),
    ];
    JobStore::save($jobId, $jobsDir, $state);
    exit(1);
}


// считаем общее количество строк, нужно для прогресс-бара
$total = 0;
$fh = fopen($filePath, 'r');
while (!feof($fh)) {
    $chunk = fread($fh, 1 << 20);
    if ($chunk === false) {
        break;
    }
    $total += substr_count($chunk, "\n");
}
rewind($fh);

$state = [
    'status' => 'processing',
    'total' => $total,
    'processed' => 0,
    'success' => 0,
    'errors' => 0,
    'progress' => 0,
    'error_groups' => [],
    'logs' => [['message' => "начата обработка файла: {$total} строк", 'type' => 'info']],
    'started_at' => time(),
];
JobStore::save($jobId, $jobsDir, $state);


// подключаемся с несколькими попытками - если база на секунду "моргнёт",
// воркер не должен падать сразу
$collection = null;
$attempts = 5;
$lastError = null;

for ($i = 1; $i <= $attempts; $i++) {
    try {
        $collection = getMongoCollection();
        $collection->countDocuments([], ['limit' => 1]);
        break;
    } catch (\Throwable $e) {
        $lastError = $e;
        if ($i < $attempts) {
            sleep(2);
        }
    }
}

if ($collection === null) {
    $state['logs'][] = ['message' => 'не удалось подключиться к mongodb: ' . $lastError->getMessage(), 'type' => 'error'];
    $state['status'] = 'error';
    $state['message'] = 'ошибка подключения к mongodb';
    JobStore::save($jobId, $jobsDir, $state);
    exit(1);
}

// индексы: уникальный - для дедупликации, обычные - для быстрого поиска
$collection->createIndex(['dedup_hash' => 1], ['unique' => true]);
$collection->createIndex(['account_number' => 1]);
$collection->createIndex(['address.settlement' => 1]);

$batch = [];
$batchSize = 1000;
$lineNo = 0;

$errorCounts = ['account' => 0, 'fio' => 0, 'address' => 0, 'period' => 0, 'amount' => 0, 'meter' => 0, 'format' => 0, 'empty' => 0];

$errorHandles = [];
function getErrorHandle(string $bucket, string $jobId, string $errorsDir, array &$errorHandles)
{
    if (!isset($errorHandles[$bucket])) {
        $fname = "errors_{$bucket}_{$jobId}.txt";
        $errorHandles[$bucket] = fopen($errorsDir . '/' . $fname, 'a');
    }
    return $errorHandles[$bucket];
}

// вставляет пачку записей, отдельно считает сколько реально добавилось,
// а сколько отсеялось как дубликат уже существующей в базе записи
// (ordered => false значит: даже если часть пачки - дубли, остальные всё равно вставятся)
function insertBatch($collection, array &$batch, array &$state): void
{
    if (empty($batch)) {
        return;
    }

    try {
        $result = $collection->insertMany($batch, ['ordered' => false]);
    } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
        $writeResult = $e->getWriteResult();
        foreach ($writeResult->getWriteErrors() as $writeError) {
            if ($writeError->getCode() === 11000) {
                // 11000 - код ошибки mongo "дубликат по уникальному индексу"
                $state['duplicates']++;
            } else {
                $state['errors']++;
            }
        }
    }

    $batch = [];
}

$seenHashes = []; // хеши записей, уже встреченных в этом файле

while (($line = fgets($fh)) !== false) {
    if (trim($line) === '') {
        continue;
    }
    $lineNo++;

    // выгрузки из 1с/excel часто приходят в windows-1251
    $raw = $line;
    if (!mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
    }

    $result = Parser::parseLine($raw);

    if ($result['ok']) {
        $hash = Parser::computeHash($result['data']);

        // дубликат внутри самого файла - пропускаем, не доходя до базы
        if (isset($seenHashes[$hash])) {
            $state['duplicates']++;
        } else {
            $seenHashes[$hash] = true;
            $result['data']['dedup_hash'] = $hash;
            $batch[] = $result['data'];
            $state['success']++;
        }

        if (count($batch) >= $batchSize) {
            insertBatch($collection, $batch, $state);
        }
    } 
    else {
        $state['errors']++;
        $type = $result['error_type'] ?? 'format';
        $errorCounts[$type] = ($errorCounts[$type] ?? 0) + 1;

        // фио и приборы - в свои отдельные файлы, всё остальное - в "прочие"
        $bucket = in_array($type, ['fio', 'meter'], true) ? $type : 'other';
        $fh2 = getErrorHandle($bucket, $jobId, $errorsDir, $errorHandles);
        fwrite($fh2, rtrim($raw, "\r\n") . '  =>  ОШИБКА: ' . $result['error'] . PHP_EOL);
    }

    $state['processed'] = $lineNo;

    $now = microtime(true);
    if ($lineNo % 2000 === 0 || ($now - $lastSave) > 1.0) {
        $state['progress'] = $total > 0 ? min(99, (int) floor(($lineNo / $total) * 100)) : 0;
        $state['error_groups'] = recalcErrorGroups($errorCounts, $fieldLabels);
        if ($lineNo % 20000 === 0) {
            $state['logs'][] = ['message' => "обработано {$lineNo} из {$total} строк (успешно: {$state['success']}, ошибок: {$state['errors']})", 'type' => 'info'];
        }
        JobStore::save($jobId, $jobsDir, $state);
        $lastSave = $now;
    }
}

if (!empty($batch)) {
    insertBatch($collection, $batch, $state);
}

fclose($fh);

$state['processed'] = $lineNo;
$state['progress'] = 100;
$state['status'] = 'completed';
$state['error_groups'] = recalcErrorGroups($errorCounts, $fieldLabels);
$state['finished_at'] = time();
$state['logs'][] = ['message' => "обработка завершена. успешно: {$state['success']}, отклонено: {$state['errors']}, дублей: {$state['duplicates']}", 'type' => 'success'];
JobStore::save($jobId, $jobsDir, $state);

@unlink($filePath);