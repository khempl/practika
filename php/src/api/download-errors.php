<?php
require __DIR__ . '/../JobStore.php';

$jobId = $_GET['job_id'] ?? '';

if (!JobStore::isValidId($jobId)) {
    http_response_code(400);
    echo 'Некорректный job_id';
    exit;
}

$errorsDir = __DIR__ . '/../errors';
$files = glob($errorsDir . '/errors_*_' . $jobId . '.txt');

if (!$files) {
    http_response_code(404);
    echo 'Файлы с ошибками для этой задачи не найдены';
    exit;
}

if (count($files) === 1) {
    $f = $files[0];
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($f) . '"');
    header('Content-Length: ' . filesize($f));
    readfile($f);
    unlink($f);
    exit;
}

if (!class_exists('ZipArchive')) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="errors_' . $jobId . '.txt"');
    foreach ($files as $f) {
        echo '===== ' . basename($f) . ' =====' . PHP_EOL;
        readfile($f);
        echo PHP_EOL;
    }
    foreach ($files as $f) {
        unlink($f);
    }
    exit;
}

$zipPath = sys_get_temp_dir() . '/errors_' . $jobId . '_' . uniqid() . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
foreach ($files as $f) {
    $zip->addFile($f, basename($f));
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="errors_' . $jobId . '.zip"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
unlink($zipPath);
foreach ($files as $f) {
    unlink($f);
}
