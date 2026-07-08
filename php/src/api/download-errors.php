<?php
/**
 * ============================================================
 * Файл: api/download-errors.php
 * Назначение: Скачивание файлов с ошибками
 * 
 * Этот API-эндпоинт находит все файлы ошибок для данной задачи
 * и отдает их пользователю:
 * - Если один файл — отдается как text/plain
 * - Если несколько файлов — упаковываются в ZIP-архив
 * 
 * После скачивания файлы удаляются с сервера.
 * 
 * Ожидает: GET-запрос с параметром job_id
 * Возвращает: файл (plain text или ZIP)
 * ============================================================
 */

// Подключаем JobStore для валидации job_id
require __DIR__ . '/../JobStore.php';

// ------------------------------------------------------------
// 1. Получаем и проверяем job_id
// ------------------------------------------------------------
$jobId = $_GET['job_id'] ?? '';

if (!JobStore::isValidId($jobId)) {
    http_response_code(400);
    echo 'Некорректный job_id';
    exit;
}

// ------------------------------------------------------------
// 2. Ищем все файлы ошибок для этой задачи
// ------------------------------------------------------------
$errorsDir = __DIR__ . '/../errors';
$files = glob($errorsDir . '/errors_*_' . $jobId . '.txt');

// Если файлов нет — сообщаем об этом
if (!$files) {
    http_response_code(404);
    echo 'Файлы с ошибками для этой задачи не найдены';
    exit;
}

// ------------------------------------------------------------
// 3. Если файл один — отдаем его как plain text
// ------------------------------------------------------------
if (count($files) === 1) {
    $f = $files[0];
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($f) . '"');
    header('Content-Length: ' . filesize($f));
    readfile($f);
    unlink($f); // Удаляем файл после скачивания
    unlink($f);
    exit;
}

// ------------------------------------------------------------
// 4. Если файлов несколько — упаковываем в ZIP
// ------------------------------------------------------------

// Проверяем, доступно ли расширение ZipArchive
if (!class_exists('ZipArchive')) {
    // Если ZIP недоступен — отдаем все файлы слитно в один текстовый файл
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

// Создаем ZIP-архив во временной папке
$zipPath = sys_get_temp_dir() . '/errors_' . $jobId . '_' . uniqid() . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Добавляем все файлы ошибок в архив
foreach ($files as $f) {
    $zip->addFile($f, basename($f));
}
$zip->close();

// Отдаем ZIP-архив на скачивание
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="errors_' . $jobId . '.zip"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);

// Удаляем временные файлы
unlink($zipPath);
foreach ($files as $f) {
    unlink($f);
}
}
