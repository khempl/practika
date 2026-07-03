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
        'logs' => [['message' => '❌ файл не найден на сервере (' . $fileId . ')', 'type' => 'error']],
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
    'logs' => [['message' => "🚀 начата обработка файла: {$total} строк", 'type' => 'info']],
    'started_at' => time(),
];
JobStore::save($jobId, $jobsDir, $state);

fclose($fh);