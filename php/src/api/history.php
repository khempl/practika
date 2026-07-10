<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../db.php';

try {
    $collection = getHistoryCollection();
    $records = $collection->find([], [
        'sort' => ['created_at' => -1],
        'limit' => 50,
    ]);

    $result = [];
    foreach ($records as $r) {
        $result[] = [
            'created_at' => $r['created_at'] ?? null,
            'file_name' => $r['file_name'] ?? '—',
            'total' => $r['total'] ?? 0,
            'success' => $r['success'] ?? 0,
            'errors' => $r['errors'] ?? 0,
            'duration_seconds' => $r['duration_seconds'] ?? null,
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
