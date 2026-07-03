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

if (!is_file($filePath)) {
    $state = [
        'status' => 'error',
        'message' => 'загруженный файл не найден на сервере',
        'total' => 0,
        'processed' => 0,
        'success' => 0,
        'errors' => 0,
        'progress' => 0,
        'error_groups' => [],
        'logs' => [['message' => 'файл не найден на сервере (' . $fileId . ')', 'type' => 'error']],
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


$collection = null;
try {
    $collection = getMongoCollection();
} catch (\Throwable $e) {
    $state['logs'][] = ['message' => 'не удалось подключиться к mongodb: ' . $e->getMessage(), 'type' => 'error'];
    $state['status'] = 'error';
    $state['message'] = 'ошибка подключения к mongodb';
    JobStore::save($jobId, $jobsDir, $state);
    exit(1);
}

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
        $batch[] = $result['data'];
        $state['success']++;
        if (count($batch) >= $batchSize) {
            $collection->insertMany($batch);
            $batch = [];
        }
    } else {
        $state['errors']++;
        $type = $result['error_type'] ?? 'format';
        $errorCounts[$type] = ($errorCounts[$type] ?? 0) + 1;

        // фио и приборы - в свои отдельные файлы, всё остальное - в "прочие"
        $bucket = in_array($type, ['fio', 'meter'], true) ? $type : 'other';
        $fh2 = getErrorHandle($bucket, $jobId, $errorsDir, $errorHandles);
        fwrite($fh2, rtrim($raw, "\r\n") . '  =>  ОШИБКА: ' . $result['error'] . PHP_EOL);
    }

    $state['processed'] = $lineNo;
}

if (!empty($batch)) {
    $collection->insertMany($batch);
}

fclose($fh);

$state['progress'] = 100;
$state['status'] = 'completed';
$state['finished_at'] = time();
$state['logs'][] = ['message' => "обработка завершена. успешно: {$state['success']}, отклонено: {$state['errors']}", 'type' => 'success'];
JobStore::save($jobId, $jobsDir, $state);

fclose($fh);

foreach ($errorHandles as $handle) {
    fclose($handle);
}