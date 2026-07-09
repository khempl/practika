<?php
/**
 * ============================================================
 * Файл: api/lock.php
 * Назначение: Управление глобальной блокировкой системы
 * 
 * Методы:
 *   GET  - проверить статус блокировки (возвращает также job_id для read-only режима)
 *   POST - создать блокировку (lock)
 *   DELETE - снять блокировку (unlock)
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

$lockFile = __DIR__ . '/../../locks/system.lock.json';
$locksDir = dirname($lockFile);

if (!is_dir($locksDir)) {
    mkdir($locksDir, 0777, true);
}

function getLockData($lockFile) {
    if (!file_exists($lockFile)) {
        return null;
    }
    $content = file_get_contents($lockFile);
    $data = json_decode($content, true);
    if (!$data || !is_array($data)) {
        return null;
    }
    return $data;
}

// ------------------------------------------------------------
// GET — проверка статуса блокировки
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lockData = getLockData($lockFile);
    if ($lockData) {
        // Проверяем, не истекла ли блокировка (старше 2 часов)
        if (isset($lockData['started_at']) && (time() - $lockData['started_at'] > 7200)) {
            unlink($lockFile);
            echo json_encode(['locked' => false]);
            exit;
        }
        echo json_encode([
            'locked' => true,
            'user' => $lockData['user'] ?? 'Неизвестный пользователь',
            'started_at' => $lockData['started_at'] ?? null,
            'file_name' => $lockData['file_name'] ?? null,
            'job_id' => $lockData['job_id'] ?? null,
            'session_id' => $lockData['session_id'] ?? null,
        ]);
    } else {
        echo json_encode(['locked' => false]);
    }
    exit;
}

// ------------------------------------------------------------
// POST — создать блокировку
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    $existing = getLockData($lockFile);
    if ($existing) {
        $startedAt = $existing['started_at'] ?? 0;
        // Если блокировка старше 2 часов — снимаем
        if (time() - $startedAt > 7200) {
            unlink($lockFile);
        } else {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Система уже занята другим пользователем',
                'user' => $existing['user'] ?? 'Неизвестный пользователь',
                'started_at' => $existing['started_at'] ?? null
            ]);
            exit;
        }
    }
    
    $lockData = [
        'user' => $input['user'] ?? 'Пользователь',
        'file_name' => $input['file_name'] ?? 'Неизвестный файл',
        'job_id' => $input['job_id'] ?? null,
        'started_at' => time(),
        'session_id' => $input['session_id'] ?? session_id() ?: md5(uniqid('', true)),
    ];
    
    file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'success' => true,
        'message' => 'Блокировка создана',
        'lock' => $lockData
    ]);
    exit;
}

// ------------------------------------------------------------
// DELETE — снять блокировку
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $sessionId = $input['session_id'] ?? '';
    
    $lockData = getLockData($lockFile);
    if (!$lockData) {
        echo json_encode(['success' => true, 'message' => 'Блокировка уже снята']);
        exit;
    }
    
    // Если передан session_id — проверяем, что снимает тот же пользователь
    if ($sessionId && isset($lockData['session_id']) && $lockData['session_id'] !== $sessionId) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Вы не можете снять блокировку, созданную другим пользователем'
        ]);
        exit;
    }
    
    unlink($lockFile);
    echo json_encode(['success' => true, 'message' => 'Блокировка снята']);
    exit;
}