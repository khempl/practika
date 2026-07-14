<?php
require_once __DIR__ . '/db.php';

$collection = getMongoCollection();
$results = [];
$searchPerformed = false;
$account = trim($_GET['account'] ?? '');
$address = trim($_GET['address'] ?? '');
$period = trim($_GET['period'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($account || $address || $period)) {
    $searchPerformed = true;
    $filter = [];

    if ($account !== '') {
        $filter['account_number'] = $account;
    }

    if ($address !== '') {
        $filter['$or'] = [
            ['address.settlement' => ['$regex' => $address, '$options' => 'i']],
            ['address.street'     => ['$regex' => $address, '$options' => 'i']],
            ['address.house'      => ['$regex' => $address, '$options' => 'i']],
        ];
    }

    if ($period !== '') {
        $filter['period'] = $period;
    }

    // Если нет ни одного фильтра, покажем все (или ничего)
    if (empty($filter)) {
        $results = [];
    } else {
        $options = ['sort' => ['_id' => -1], 'limit' => 100];
        $cursor = $collection->find($filter, $options);
        $results = iterator_to_array($cursor);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск начислений и показаний</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="fw-bold mb-4">
                <i class="fas fa-search text-primary me-3"></i>Поиск начислений
            </h1>

            <div class="card main-card">
                <div class="card-header bg-dark text-white">
                    <span class="fw-semibold"><i class="fas fa-filter me-2"></i>Фильтры</span>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="client_search.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="account" class="form-label">Лицевой счёт</label>
                            <input type="text" class="form-control" id="account" name="account" value="<?= htmlspecialchars($account) ?>" placeholder="Например: 900046075">
                        </div>
                        <div class="col-md-4">
                            <label for="address" class="form-label">Адрес (часть)</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($address) ?>" placeholder="Например: Село, Улица">
                        </div>
                        <div class="col-md-3">
                            <label for="period" class="form-label">Период (номер)</label>
                            <input type="text" class="form-control" id="period" name="period" value="<?= htmlspecialchars($period) ?>" placeholder="Например: 519">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($searchPerformed): ?>
                <div class="mt-4">
                    <h5><i class="fas fa-list me-2"></i>Результаты (найдено: <?= count($results) ?>)</h5>
                    <?php if (empty($results)): ?>
                        <div class="alert alert-warning">Ничего не найдено. Попробуйте изменить условия поиска.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Лицевой счёт</th>
                                        <th>ФИО</th>
                                        <th>Адрес</th>
                                        <th>Период</th>
                                        <th>Сумма</th>
                                        <th>Приборы учёта</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($results as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['account_number'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($doc['full_name'] ?? '') ?></td>
                                        <td>
                                            <?= htmlspecialchars($doc['address']['settlement'] ?? '') ?>,
                                            <?= htmlspecialchars($doc['address']['street'] ?? '') ?>,
                                            <?= htmlspecialchars($doc['address']['house'] ?? '') ?>
                                            <?php if (!empty($doc['address']['apartment'])): ?>, кв. <?= htmlspecialchars($doc['address']['apartment']) ?><?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($doc['period'] ?? '') ?></td>
                                        <td><?= number_format($doc['total_amount'] ?? 0, 2) ?></td>
                                        <td>
                                            <?php if (!empty($doc['meters'])): ?>
                                                <?php foreach ($doc['meters'] as $m): ?>
                                                    <span class="badge bg-info text-dark me-1">
                                                        <?= htmlspecialchars($m['meter_id']) ?>: <?= number_format($m['reading'], 4) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">нет</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="index.html" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>На главную</a>
                <a href="list.php" class="btn btn-outline-info ms-2"><i class="fas fa-list me-2"></i>Все записи</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
