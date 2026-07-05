<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../JobStore.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$fileId = $input['file_id'] ?? '';

if (!preg_match('/^[a-f0-9]{32}$/', $fileId)) {
    echo json_encode(['success' => false, 'message' => 'Некорректный file_id']);
    exit;
}

$uploadsDir = __DIR__ . '/../uploads';
$filePath = $uploadsDir . '/' . $fileId . '.txt';

if (!is_file($filePath)) {
    echo json_encode(['success' => false, 'message' => 'Файл не найден на сервере, загрузите его заново']);
    exit;
}

$jobsDir = __DIR__ . '/../jobs';
if (!is_dir($jobsDir)) {
    mkdir($jobsDir, 0777, true);
}

$jobId = bin2hex(random_bytes(16));

// сразу создаём "заготовку" job-файла, чтобы status.php не отвечал "не найдена задача",
// пока фоновый процесс только запускается (посчитать общее число строк тоже требует времени)
JobStore::save($jobId, $jobsDir, [
    'status' => 'processing',
    'total' => 0,
    'processed' => 0,
    'success' => 0,
    'errors' => 0,
    'progress' => 0,
    'error_groups' => [],
    'logs' => [['message' => '📤 Задача принята, запускается обработка...', 'type' => 'info']],
]);

if (!function_exists('exec')) {
    echo json_encode(['success' => false, 'message' => 'Функция exec() отключена на сервере (disable_functions в php.ini) — фоновая обработка невозможна']);
    exit;
}

$phpBin = PHP_BINARY ?: '/usr/local/bin/php';
$workerScript = __DIR__ . '/../worker.php';

// запускаем полностью отдельный процесс (детач через &, вывод в /dev/null),
// поэтому он не связан с лимитом времени текущего HTTP-запроса
$cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($workerScript)
    . ' ' . escapeshellarg($fileId)
    . ' ' . escapeshellarg($jobId)
    . ' > /dev/null 2>&1 &';

exec($cmd);

echo json_encode(['success' => true, 'job_id' => $jobId]);
