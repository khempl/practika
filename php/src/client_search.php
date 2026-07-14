<?php
require_once __DIR__ . '/db.php';

$collection = getMongoCollection();
$results = [];
$searchPerformed = false;

$account = trim($_GET['account'] ?? '');
$address = trim($_GET['address'] ?? '');
$period  = trim($_GET['period'] ?? '');

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

    if (!empty($filter)) {
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
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', system-ui, sans-serif; }
        .main-card { border-radius: 1.25rem; border: 0; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.06); overflow: hidden; }
        .main-card .card-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white; border: 0; padding: 1.25rem 2rem;
        }
        .btn-primary-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: 0; padding: 0.6rem 2rem; font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37,99,235,0.35);
        }
        .btn-outline-secondary { border: 1px solid #e2e8f0; background: white; color: #475569 !important; padding: 8px 20px; border-radius: 8px; font-weight: 500; transition: all 0.2s; }
        .btn-outline-secondary:hover { background: #f1f5f9; color: #0f172a !important; border-color: #cbd5e1; }
        .table th { background: #f1f5f9; font-weight: 600; }
        .table td { vertical-align: middle; }
        .badge-info { background: #dbeafe; color: #1e3a8a; }

        .btn-search {
            padding: 8px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-search:hover {
            background: #1d4ed8;
        }
        .btn-sm { padding: 4px 12px; font-size: 13px; }
        .btn-outline-danger { border: 1px solid #fca5a5; background: white; color: #dc2626 !important; padding: 6px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; transition: all 0.2s; }
        .btn-outline-danger:hover { background: #fef2f2; color: #b91c1c !important; border-color: #f87171; }
        .btn-filter-reset {
            padding: 8px 16px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            white-space: nowrap;
            text-decoration: none;
        }
        .btn-filter-reset:hover {
            background: #cbd5e1;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <!-- Шапка с кнопками -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="fw-bold h2 mb-0">
                    <i class="fas fa-search text-primary me-3"></i>Поиск начислений
                </h1>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list me-2"></i>Просмотр записей
                    </a>
                    <a href="clear.php" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('Удалить все записи из БД?')">
                        <i class="fas fa-trash me-2"></i>Очистить БД
                    </a>
                </div>
            </div>

            <div class="card main-card">
                <div class="card-header">
                    <span class="fw-semibold"><i class="fas fa-filter me-2"></i>Фильтры</span>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="client_search.php" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="account" class="form-label">Лицевой счёт</label>
                            <input type="text" class="form-control" id="account" name="account"
                                   value="<?= htmlspecialchars($account) ?>" placeholder="Например: 900046075">
                        </div>
                        <div class="col-md-3">
                            <label for="address" class="form-label">Адрес (часть)</label>
                            <input type="text" class="form-control" id="address" name="address"
                                   value="<?= htmlspecialchars($address) ?>" placeholder="Например: Село, Улица">
                        </div>
                        <div class="col-md-2">
                            <label for="period" class="form-label">Период (номер)</label>
                            <input type="text" class="form-control" id="period" name="period"
                                   value="<?= htmlspecialchars($period) ?>" placeholder="Например: 519">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search me-2"></i>Найти
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="client_search.php" class="btn-filter-reset">
                                <i class="fas fa-undo me-2"></i>Сбросить
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($searchPerformed): ?>
                <div class="mt-4">
                    <h5><i class="fas fa-list me-2"></i>Результаты (найдено: <?= count($results) ?>)</h5>
                    <?php if (empty($results)): ?>
                        <div class="alert alert-warning mt-3">Ничего не найдено. Попробуйте изменить условия поиска.</div>
                    <?php else: ?>
                        <div class="table-responsive mt-3">
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
                                                    <span class="badge badge-info me-1">
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
                <a href="index.html" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>На главную
                </a>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
