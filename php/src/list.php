<?php
/**
 * ============================================================
 * Файл: list.php
 * Назначение: Просмотр записей из MongoDB с пагинацией,
 *              сортировкой и детальным просмотром
 * ============================================================
 */

require __DIR__ . '/db.php';

// ------------------------------------------------------------
// 1. Параметры запроса
// ------------------------------------------------------------
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$skip = ($page - 1) * $limit;

// Сортировка (только из выпадающих списков)
$sortField = $_GET['sort'] ?? '_id';
$sortOrder = $_GET['order'] ?? 'desc';
$sortDirection = $sortOrder === 'asc' ? 1 : -1;

// Поиск
$search = trim($_GET['search'] ?? '');
$filter = [];

if ($search !== '') {
    $filter['$or'] = [
        ['account_number' => ['$regex' => $search, '$options' => 'i']],
        ['full_name' => ['$regex' => $search, '$options' => 'i']],
        ['address.settlement' => ['$regex' => $search, '$options' => 'i']],
        ['address.street' => ['$regex' => $search, '$options' => 'i']],
        ['address.house' => ['$regex' => $search, '$options' => 'i']],
    ];
}

$collection = getMongoCollection();

// ------------------------------------------------------------
// 2. Получаем данные
// ------------------------------------------------------------
$total = $collection->countDocuments($filter);
$records = $collection->find(
    $filter,
    [
        'sort' => [$sortField => $sortDirection],
        'skip' => $skip,
        'limit' => $limit,
    ]
)->toArray();

$totalPages = ceil($total / $limit);

// ------------------------------------------------------------
// 3. Карта полей для сортировки
// ------------------------------------------------------------
$sortFields = [
    '_id' => 'ID записи',
    'account_number' => 'Лицевой счёт',
    'full_name' => 'ФИО',
    'address.settlement' => 'Населенный пункт',
    'period' => 'Период',
    'total_amount' => 'Сумма',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Записи в базе</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 20px;
        }
        .container-wide {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .header-bar h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .header-bar .badge-total {
            background: #e2e8f0;
            color: #475569;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        /* Форма поиска и сортировки */
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-form .search-input-wrapper {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        .search-form .search-input-wrapper input {
            width: 100%;
            padding: 8px 16px;
            padding-right: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            background: white;
        }
        .search-form .search-input-wrapper input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .search-form .search-input-wrapper .clear-search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            display: none;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .search-form .search-input-wrapper .clear-search-btn:hover {
            color: #ef4444;
        }
        .search-form .search-input-wrapper .clear-search-btn.visible {
            display: block;
        }

        .search-form .btn-search {
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
        .search-form .btn-search:hover {
            background: #1d4ed8;
        }

        /* Элементы управления сортировкой */
        .sort-controls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .sort-controls .sort-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .sort-controls select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            min-width: 160px;
            appearance: auto;
        }
        .sort-controls select:focus {
            outline: none;
            border-color: #2563eb;
        }
        .sort-controls .btn-filter-reset {
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
        .sort-controls .btn-filter-reset:hover {
            background: #cbd5e1;
            text-decoration: none;
        }

        /* Таблица */
        .table-responsive {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        table td {
            padding: 10px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        table tr:nth-child(even) {
            background: #fafbfc;
        }
        table tr:hover {
            background: #f1f5f9;
        }
        .clickable-row {
            cursor: pointer;
            transition: background 0.15s;
        }
        .clickable-row:hover {
            background: #f1f5f9 !important;
        }

        .meter-item {
            display: inline-block;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            margin: 2px 4px 2px 0;
            color: #334155;
        }
        .meter-item:last-child {
            margin-right: 0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 48px;
            display: block;
            margin-bottom: 12px;
            color: #cbd5e1;
        }

        /* Пагинация */
        .pagination-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }
        .pagination-nav .info {
            color: #64748b;
            font-size: 14px;
        }
        .pagination-nav .pagination {
            margin: 0;
        }
        .pagination-nav .page-link {
            color: #475569;
            border-color: #e2e8f0;
            padding: 6px 14px;
            font-size: 14px;
        }
        .pagination-nav .page-link:hover {
            background: #f1f5f9;
            color: #2563eb;
        }
        .pagination-nav .page-item.active .page-link {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }
        .pagination-nav .page-item.disabled .page-link {
            color: #cbd5e1;
        }

        /* Модальное окно */
        .detail-modal .modal-header {
            background: #0f172a;
            color: white;
            border-bottom: none;
        }
        .detail-modal .modal-header .btn-close {
            filter: invert(1) brightness(10);
            opacity: 1;
        }
        .detail-modal .modal-header .btn-close:hover {
            opacity: 0.8;
        }
        .detail-modal .modal-body {
            padding: 24px;
        }
        .detail-modal .modal-footer {
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
        }

        .detail-section {
            margin-bottom: 16px;
        }
        .detail-section:last-child {
            margin-bottom: 0;
        }
        .detail-section .label {
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .detail-section .value {
            font-size: 16px;
            font-weight: 500;
            color: #0f172a;
        }
        .detail-section .value .amount {
            color: #2563eb;
            font-size: 20px;
        }

        .history-table {
            font-size: 13px;
            margin-top: 8px;
        }
        .history-table thead th {
            background: #f8fafc;
            padding: 8px 12px;
            font-size: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .history-table tbody td {
            padding: 6px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .history-table .meter-small {
            font-size: 12px;
            color: #64748b;
        }
        .history-table .meter-small .meter-id {
            font-family: 'Courier New', monospace;
            color: #334155;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px 0;
        }
        .loading-spinner .spinner-border {
            width: 40px;
            height: 40px;
            color: #2563eb;
        }

        .meters-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            body { padding: 12px; }
            .header-bar h1 { font-size: 20px; }
            .search-form .search-input-wrapper { min-width: 150px; }
            .sort-controls select { min-width: 120px; }
            table { font-size: 12px; }
            table th, table td { padding: 6px 10px; }
            .meter-item { font-size: 11px; padding: 1px 8px; }
            .pagination-nav .info { font-size: 12px; width: 100%; text-align: center; }
            .pagination-nav .pagination { margin: 0 auto; }
            .detail-modal .modal-body { padding: 16px; }
        }
        @media (max-width: 576px) {
            .search-form { flex-direction: column; }
            .search-form .search-input-wrapper { min-width: 100%; }
            .search-form .btn-search { width: 100%; text-align: center; }
            .sort-controls { width: 100%; flex-wrap: wrap; }
            .sort-controls select { flex: 1; min-width: 80px; }
            .sort-controls .btn-filter-reset { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>

<div class="container-wide">

    <!-- Заголовок -->
    <div class="header-bar">
        <div>
            <h1>Записи в базе данных</h1>
        </div>
        <div>
            <a href="index.html" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> На главную
            </a>
            <span class="badge-total ms-2">Всего: <?= number_format($total) ?></span>
        </div>
    </div>

    <!-- ------------------------------------------------------------
    ФОРМА ПОИСКА И СОРТИРОВКИ
    ------------------------------------------------------------ -->
    <form class="search-form" method="GET" id="searchForm" autocomplete="off">

        <!-- Поле поиска -->
        <div class="search-input-wrapper">
            <input
                type="text"
                id="searchInput"
                name="search"
                placeholder="Введите текст для поиска..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="button" class="clear-search-btn <?= $search !== '' ? 'visible' : '' ?>" id="clearSearchBtn" title="Очистить">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <!-- Кнопка поиска -->
        <button type="submit" class="btn-search">
            <i class="fas fa-search me-1"></i> Найти
        </button>

        <!-- Элементы управления сортировкой -->
        <div class="sort-controls">
            <span class="sort-label">Сортировка по:</span>

            <!-- Выпадающий список: поле для сортировки -->
            <select name="sort" id="filterSort">
                <?php foreach ($sortFields as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $sortField === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Выпадающий список: порядок сортировки -->
            <select name="order" id="filterOrder">
                <option value="desc" <?= $sortOrder === 'desc' ? 'selected' : '' ?>>По убыванию</option>
                <option value="asc" <?= $sortOrder === 'asc' ? 'selected' : '' ?>>По возрастанию</option>
            </select>

            <!-- Кнопка сброса -->
            <a href="?page=1" class="btn-filter-reset">
                <i class="fas fa-undo me-1"></i> Сбросить
            </a>
        </div>

        <input type="hidden" name="page" value="1">
    </form>

    <!-- ------------------------------------------------------------
    ТАБЛИЦА
    ------------------------------------------------------------ -->
    <div class="table-responsive">
        <table>
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
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <div>Нет записей</div>
                                <?php if ($search !== ''): ?>
                                    <div style="font-size:13px; margin-top:4px;">Попробуйте изменить условия поиска</div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr class="clickable-row" data-id="<?= (string) $r['_id'] ?>">
                            <td><strong><?= htmlspecialchars($r['account_number']) ?></strong></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($r['address']['settlement'] ?? '') ?>,
                                <?= htmlspecialchars($r['address']['street'] ?? '') ?>,
                                <?= htmlspecialchars($r['address']['house'] ?? '') ?>
                                <?php if (!empty($r['address']['apartment'])): ?>,
                                кв. <?= htmlspecialchars($r['address']['apartment']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['period']) ?></td>
                            <td><strong><?= number_format($r['total_amount'] ?? 0, 2) ?> ₽</strong></td>
                            <td>
                                <?php if (!empty($r['meters'])): ?>
                                    <?php foreach ($r['meters'] as $m): ?>
                                        <span class="meter-item"><?= htmlspecialchars($m['meter_id']) ?>: <?= number_format($m['reading'] ?? 0, 2) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ------------------------------------------------------------
    ПАГИНАЦИЯ
    ------------------------------------------------------------ -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-nav">
        <div class="info">
            Показано <?= count($records) ?> из <?= number_format($total) ?> записей
            <?php if ($search !== ''): ?>
                <span class="text-muted">(поиск: "<?= htmlspecialchars($search) ?>")</span>
            <?php endif; ?>
        </div>

        <nav>
            <ul class="pagination">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1&sort=<?= urlencode($sortField) ?>&order=<?= urlencode($sortOrder) ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= urlencode($sortField) ?>&order=<?= urlencode($sortOrder) ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                </li>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&sort=<?= urlencode($sortField) ?>&order=<?= urlencode($sortOrder) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>

                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= urlencode($sortField) ?>&order=<?= urlencode($sortOrder) ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $totalPages ?>&sort=<?= urlencode($sortField) ?>&order=<?= urlencode($sortOrder) ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

</div>

<!-- ------------------------------------------------------------
МОДАЛЬНОЕ ОКНО
------------------------------------------------------------ -->
<div class="modal fade detail-modal" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle">Детали записи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="loading-spinner">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <div style="color:#94a3b8; margin-top:8px;">Загрузка данных...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ------------------------------------------------------------
    // 1. Очистка поля поиска
    // ------------------------------------------------------------
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');

    function showClearButton(show) {
        clearSearchBtn.classList.toggle('visible', show);
    }

    // Показываем/скрываем крестик
    searchInput.addEventListener('input', function() {
        showClearButton(this.value.length > 0);
    });

    // Очистка поля поиска
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        showClearButton(false);
        searchInput.focus();
        // Отправляем форму с пустым поиском
        document.getElementById('searchForm').submit();
    });

    // Инициализация: показываем крестик если есть текст
    showClearButton(searchInput.value.length > 0);

    // ------------------------------------------------------------
    // 2. Применение сортировки при изменении select
    // ------------------------------------------------------------
    const filterSort = document.getElementById('filterSort');
    const filterOrder = document.getElementById('filterOrder');
    const searchForm = document.getElementById('searchForm');

    function submitFilter() {
        searchForm.submit();
    }

    filterSort.addEventListener('change', submitFilter);
    filterOrder.addEventListener('change', submitFilter);

    // ------------------------------------------------------------
    // 3. Поиск по Enter (без перезагрузки курсора)
    // ------------------------------------------------------------
    // Enter уже работает стандартно, т.к. это форма.
    // Дополнительно ничего не нужно.

    // ------------------------------------------------------------
    // 4. Клик по строке — открываем модальное окно
    // ------------------------------------------------------------
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    const modalBody = document.getElementById('detailModalBody');
    const modalTitle = document.getElementById('detailModalTitle');

    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            const id = this.dataset.id;

            modalTitle.textContent = 'Детали записи';
            modalBody.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <div style="color:#94a3b8; margin-top:8px;">Загрузка данных...</div>
                </div>
            `;
            modal.show();

            fetch('api/get-record.php?id=' + id)
                .then(function(res) {
                    if (!res.ok) {
                        throw new Error('Ошибка загрузки (' + res.status + ')');
                    }
                    return res.json();
                })
                .then(function(data) {
                    renderDetail(data);
                })
                .catch(function(err) {
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Ошибка загрузки: ${err.message}
                            <br><small class="text-muted">Попробуйте обновить страницу</small>
                        </div>
                    `;
                });
        });
    });

    function renderDetail(data) {
        const address = data.address || {};
        const meters = data.meters || [];
        const history = data.history || [];

        let metersHtml = '';
        if (meters.length > 0) {
            metersHtml = '<div class="meters-list">';
            meters.forEach(function(m) {
                metersHtml += '<span class="meter-item">' + m.meter_id + ': ' + Number(m.reading || 0).toFixed(2) + '</span>';
            });
            metersHtml += '</div>';
        } else {
            metersHtml = '<span style="color:#94a3b8; font-size:14px;">Нет приборов учёта</span>';
        }

        let historyHtml = '';
        if (history.length > 0) {
            let rows = '';
            history.forEach(function(h) {
                const hMeters = h.meters || [];
                let hMetersStr = '';
                if (hMeters.length > 0) {
                    hMetersStr = hMeters.map(function(m) {
                        return '<span class="meter-id">' + m.meter_id + ':</span> ' + Number(m.reading || 0).toFixed(2);
                    }).join(' | ');
                } else {
                    hMetersStr = '<span style="color:#94a3b8;">—</span>';
                }
                rows += '<tr>' +
                    '<td>' + (h.period || '') + '</td>' +
                    '<td>' + Number(h.total_amount || 0).toFixed(2) + ' ₽</td>' +
                    '<td class="meter-small">' + hMetersStr + '</td>' +
                '</tr>';
            });

            historyHtml = `
                <hr>
                <div class="detail-section">
                    <div class="label">История начислений (последние 12)</div>
                    <div class="table-responsive" style="margin-top:8px;">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Период</th>
                                    <th>Сумма</th>
                                    <th>Приборы учёта</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-section">
                        <div class="label">Лицевой счёт</div>
                        <div class="value">${data.account_number || '—'}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-section">
                        <div class="label">Период начисления</div>
                        <div class="value">${data.period || '—'}</div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="label">ФИО плательщика</div>
                <div class="value">${data.full_name || '—'}</div>
            </div>

            <div class="detail-section">
                <div class="label">Адрес</div>
                <div class="value">
                    ${address.settlement || ''}
                    ${address.settlement && address.street ? ', ' : ''}
                    ${address.street || ''}
                    ${(address.settlement || address.street) && address.house ? ', ' : ''}
                    ${address.house ? 'д. ' + address.house : ''}
                    ${address.apartment ? ', кв. ' + address.apartment : ''}
                    ${!address.settlement && !address.street && !address.house ? '—' : ''}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="detail-section">
                        <div class="label">Сумма начисления</div>
                        <div class="value">
                            <span class="amount">${Number(data.total_amount || 0).toFixed(2)} ₽</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-section">
                        <div class="label">Приборы учёта</div>
                        ${metersHtml}
                    </div>
                </div>
            </div>

            ${historyHtml}
        `;
    }

});
</script>

</body>
</html>
