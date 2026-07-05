<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../JobStore.php';

$jobId = $_GET['job_id'] ?? '';
$since = max(0, (int) ($_GET['since'] ?? 0));

if (!JobStore::isValidId($jobId)) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректный job_id']);
    exit;
}

$jobsDir = __DIR__ . '/../jobs';
$state = JobStore::load($jobId, $jobsDir);

if ($state === null) {
    echo json_encode(['status' => 'error', 'message' => 'Задача не найдена']);
    exit;
}

$allLogs = $state['logs'] ?? [];
$newLogs = array_slice($allLogs, $since);

echo json_encode([
    'status'       => $state['status'] ?? 'processing',
    'total'        => $state['total'] ?? 0,
    'success'      => $state['success'] ?? 0,
    'errors'       => $state['errors'] ?? 0,
    'progress'     => $state['progress'] ?? 0,
    'error_groups' => $state['error_groups'] ?? [],
    'logs'         => array_map(fn($l) => ['message' => $l['message'], 'type' => $l['type'] ?? 'info'], $newLogs),
    'logs_total'   => count($allLogs),
    'message'      => $state['message'] ?? null,
]);
