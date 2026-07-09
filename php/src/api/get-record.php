<?php
/**
 * ============================================================
 * Файл: api/get-record.php
 * Назначение: Получение детальной информации о записи по ID
 * 
 * Ожидает: GET-запрос с параметром id
 * Возвращает: JSON с полными данными записи
 * ============================================================
 */

// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 0); // Не выводим ошибки в ответ, только логируем
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// ------------------------------------------------------------
// 1. Подключаем db.php (с проверкой существования)
// ------------------------------------------------------------
$dbPath = __DIR__ . '/../db.php';

if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Файл db.php не найден по пути: ' . $dbPath]);
    exit;
}

require $dbPath;

// ------------------------------------------------------------
// 2. Проверяем наличие ID
// ------------------------------------------------------------
$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID не указан']);
    exit;
}

// ------------------------------------------------------------
// 3. Проверяем формат ID (24 hex символа для MongoDB ObjectId)
// ------------------------------------------------------------
if (!preg_match('/^[a-f0-9]{24}$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный формат ID. Ожидается 24 hex символа.']);
    exit;
}

// ------------------------------------------------------------
// 4. Получаем запись из базы
// ------------------------------------------------------------
try {
    $collection = getMongoCollection();
    $record = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

    if (!$record) {
        http_response_code(404);
        echo json_encode(['error' => 'Запись с ID ' . $id . ' не найдена']);
        exit;
    }

    // ------------------------------------------------------------
    // 5. Преобразуем BSON в массив
    // ------------------------------------------------------------
    $recordArray = json_decode(json_encode($record), true);
    $recordArray['_id'] = (string) $recordArray['_id'];

    // ------------------------------------------------------------
    // 6. Получаем историю по лицевому счету
    // ------------------------------------------------------------
    $accountNumber = $recordArray['account_number'] ?? '';
    $history = [];

    if ($accountNumber !== '') {
        $historyCursor = $collection->find(
            [
                'account_number' => $accountNumber,
                '_id' => ['$ne' => new MongoDB\BSON\ObjectId($id)]
            ],
            [
                'sort' => ['period' => -1],
                'limit' => 12
            ]
        );

        $history = $historyCursor->toArray();
    }

    // Преобразуем историю в массив
    $historyArray = array_map(function($h) {
        $h = json_decode(json_encode($h), true);
        unset($h['_id']);
        return $h;
    }, $history);

    $recordArray['history'] = $historyArray;

    // ------------------------------------------------------------
    // 7. Возвращаем результат
    // ------------------------------------------------------------
    echo json_encode($recordArray);

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Критическая ошибка: ' . $e->getMessage()]);
}