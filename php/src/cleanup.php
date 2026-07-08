<?php
/**
 * Запуск вручную:
 *   docker exec php_app php /var/www/html/cleanup.php
 * Запуск по расписанию (на сервере добавить через crontab -e):
 *   0 3 * * * docker exec php_app php /var/www/html/cleanup.php >> /var/log/zhkh-cleanup.log 2>&1
 *   (каждый день в 3 ночи)
 */

$maxAgeHours = 24; // старше скольки часов удалять

$now = time();

$targets = [
    __DIR__ . '/uploads' => '*.txt',
    __DIR__ . '/jobs'    => '*.json',
    __DIR__ . '/errors'  => '*.txt',
];

$deletedCount = 0;
$freedBytes = 0;

foreach ($targets as $dir => $pattern) {
    if (!is_dir($dir)) {
        continue;
    }

    foreach (glob($dir . '/' . $pattern) as $file) {
        if (basename($file) === '.gitkeep') {
            continue;
        }

        $ageHours = ($now - filemtime($file)) / 3600;
        if ($ageHours > $maxAgeHours) {
            $freedBytes += filesize($file);
            unlink($file);
            $deletedCount++;
        }
    }
}

$freedMb = round($freedBytes / 1024 / 1024, 2);
echo "[" . date('Y-m-d H:i:s') . "] Удалено файлов: {$deletedCount}, освобождено: {$freedMb} МБ" . PHP_EOL;