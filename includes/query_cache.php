<?php

/** Small file-backed cache for read-only, database-backed page/API results. */
function cached_query(PDO $pdo, string $sql, array $params = [], int $ttl = 60): array
{
    $key = hash('sha256', $sql . "\0" . serialize($params));
    $file = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'admndash_' . $key . '.cache';
    if (is_file($file) && (filemtime($file) + $ttl) > time()) {
        $cached = json_decode((string)file_get_contents($file), true);
        if (is_array($cached)) return $cached;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    @file_put_contents($file, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $rows;
}
