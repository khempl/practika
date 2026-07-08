<?php
/**
 * ============================================================
 * Файл: api/clear.php
 * Назначение: Полная очистка базы данных
 * 
 * Этот API-эндпоинт удаляет ВСЕ записи из коллекции платежных документов.
 * Используется для тестирования и отладки, чтобы можно было
 * начать работу с "чистого листа".
 * 
 * Ожидает: POST-запрос (без параметров)
 * Возвращает: JSON { success: true/false, deleted_count: int, message: string }
 * 
 * Внимание! Это необратимая операция, все данные будут потеряны!
 * ============================================================
 */

// Устанавливаем заголовок, чтобы ответ всегда был в JSON
header('Content-Type: application/json; charset=utf-8');

// Подключаем файл с функцией подключения к MongoDB
// Путь: ../src/db.php (поднимаемся на один уровень выше из папки api/)
require __DIR__ . '/../db.php';

// Проверяем, что запрос выполняется методом POST
// Очистка базы — деструктивная операция, поэтому используем POST,
// чтобы случайно не очистить базу через GET-запрос
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается. Используйте POST.'
    ]);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

try {
    // Получаем коллекцию MongoDB для работы с платежными документами
    $collection = getMongoCollection();
    
    // deleteMany([]) с пустым фильтром удаляет ВСЕ документы в коллекции
    // Это эквивалентно TRUNCATE TABLE в SQL
    $result = $collection->deleteMany([]);
    
    // getDeletedCount() возвращает количество удаленных документов
    $deletedCount = $result->getDeletedCount();
    
    // Возвращаем успешный ответ с количеством удаленных записей
    echo json_encode([
        'success' => true,
        'deleted_count' => $deletedCount,
        'message' => 'Удалено записей: ' . $deletedCount
    ]);
    
} catch (\Throwable $e) {
    // Обработка ошибок (например, если MongoDB недоступна)
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при очистке базы данных: ' . $e->getMessage()
    ]);
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