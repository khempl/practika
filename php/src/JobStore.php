<?php
class JobStore
{
    public static function save(string $jobId, string $dir, array $state): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . '/' . $jobId . '.json';
        $tmp  = $path . '.' . uniqid('', true) . '.tmp';

        file_put_contents($tmp, json_encode($state, JSON_UNESCAPED_UNICODE));
        rename($tmp, $path);
    }

    public static function load(string $jobId, string $dir): ?array
    {
        $path = $dir . '/' . $jobId . '.json';
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $id);
    }
}
