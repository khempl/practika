<?php
require __DIR__ . '/db.php';

$collection = getMongoCollection();
$total = $collection->countDocuments();
$records = $collection->find([], ['sort' => ['_id' => -1], 'limit' => 200]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Записи в базе</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container wide">
    <h1>Записи в базе (последние 200 из <?= $total ?>)</h1>
    <p><a href="index.php">← Назад</a></p>
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
                <td><?= htmlspecialchars($r['account_number']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td>
                    <?= htmlspecialchars($r['address']['settlement']) ?>,
                    <?= htmlspecialchars($r['address']['street']) ?>,
                    <?= htmlspecialchars($r['address']['house']) ?><?php if (!empty($r['address']['apartment'])): ?>,
                    <?= htmlspecialchars($r['address']['apartment']) ?><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['period']) ?></td>
                <td><?= number_format($r['total_amount'], 2) ?></td>
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
