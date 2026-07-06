<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

try {
    $collection = getMongoCollection();
    $result = $collection->deleteMany([]);

    echo json_encode([
        'success' => true,
        'deleted_count' => $result->getDeletedCount(),
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}