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