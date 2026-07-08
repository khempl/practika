<?php
/**
 * ============================================================
 * Файл: list.php
 * Назначение: Просмотр записей из MongoDB
 * 
 * Этот файл подключается к MongoDB и выводит последние 200
 * успешно обработанных записей в виде таблицы.
 * 
 * Что показывает:
 * - Номер лицевого счета
 * - ФИО плательщика
 * - Полный адрес (населенный пункт, улица, дом, квартира)
 * - Период начисления
 * - Сумма начисления
 * - Приборы учета (ID и показания)
 * 
 * Используется для проверки корректности работы парсера.
 * ============================================================
 */

// Подключаем файл с функцией подключения к MongoDB
require __DIR__ . '/db.php';

/**
 * Получаем коллекцию MongoDB для работы с записями платежных документов
 * Функция getMongoCollection() определена в db.php
 */
$collection = getMongoCollection();

// Считаем общее количество записей в базе
$total = $collection->countDocuments();

// Получаем последние 200 записей, сортируя по убыванию ID (новые сверху)
$records = $collection->find(
    [], // Пустой фильтр = все записи
    ['sort' => ['_id' => -1], 'limit' => 200] // Сортировка: новые сверху, лимит 200
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Записи в базе</title>
    <!-- Подключаем общий файл стилей -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container wide">
    <!-- Заголовок с количеством записей -->
    <h1>Записи в базе (последние 200 из <?= $total ?>)</h1>
    
    <!-- Ссылка для возврата на главную -->
    <p><a href="index.php">← Назад</a></p>
    
    <!-- Таблица со списком записей -->
    <table>
        <thead>
            <tr>
                <th>Лиц. счёт</th>
                <th>ФИО</th>
                <th>Адрес</th>
                <th>Период</th>
                <th>Сумма</th>
                <th>Приборы учёта</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $r): ?>
            <tr>
                <!-- Номер лицевого счета -->
                <td><?= htmlspecialchars($r['account_number']) ?></td>
                
                <!-- ФИО плательщика -->
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                
                <!-- Адрес: населенный пункт, улица, дом, квартира (если есть) -->
                <td>
                    <?= htmlspecialchars($r['address']['settlement']) ?>,
                    <?= htmlspecialchars($r['address']['street']) ?>,
                    <?= htmlspecialchars($r['address']['house']) ?>
                    <?php if (!empty($r['address']['apartment'])): ?>,
                    <?= htmlspecialchars($r['address']['apartment']) ?>
                    <?php endif; ?>
                </td>
                
                <!-- Период начисления -->
                <td><?= htmlspecialchars($r['period']) ?></td>
                
                <!-- Сумма начисления (форматирование с 2 знаками после запятой) -->
                <td><?= number_format($r['total_amount'], 2) ?></td>
                
                <!-- Приборы учета: перебираем все и выводим ID и показания -->
                <td>
                    <?php foreach ($r['meters'] as $m): ?>
                        <?= htmlspecialchars($m['meter_id']) ?>: <?= number_format($m['reading'], 4) ?><br>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>