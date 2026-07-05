<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Файл не получен']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'Файл превышает максимально допустимый размер (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE  => 'Файл превышает максимально допустимый размер формы',
        UPLOAD_ERR_PARTIAL    => 'Файл был загружен только частично',
        UPLOAD_ERR_NO_FILE    => 'Файл не был загружен',
    ];
    echo json_encode(['success' => false, 'message' => $map[$file['error']] ?? ('Код ошибки загрузки: ' . $file['error'])]);
    exit;
}

$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$fileId = bin2hex(random_bytes(16));
$dest = $uploadsDir . '/' . $fileId . '.txt';

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Не удалось сохранить файл на сервере']);
    exit;
}

echo json_encode(['success' => true, 'file_id' => $fileId]);
