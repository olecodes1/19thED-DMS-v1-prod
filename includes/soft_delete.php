<?php

function soft_delete_row(PDO $pdo, string $table, string $idColumn, int $id, ?string $sourcePath = null): ?int
{
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $insert = $pdo->prepare("INSERT INTO deleted_items (entity_table, entity_id, source_path, data_json) VALUES (?, ?, ?, ?)");
    $insert->execute([$table, $id, $sourcePath, json_encode($row, JSON_UNESCAPED_UNICODE)]);
    $deletedId = (int)$pdo->lastInsertId();

    $del = $pdo->prepare("DELETE FROM {$table} WHERE {$idColumn} = ?");
    $del->execute([$id]);
    return $deletedId;
}

function get_deleted_item(PDO $pdo, int $deletedId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM deleted_items WHERE deleted_id = ? LIMIT 1");
    $stmt->execute([$deletedId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function restore_deleted_row(PDO $pdo, int $deletedId): bool
{
    $stmt = $pdo->prepare("SELECT * FROM deleted_items WHERE deleted_id = ? AND restored_at IS NULL LIMIT 1");
    $stmt->execute([$deletedId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) return false;

    $table = $item['entity_table'];
    $data = json_decode((string)$item['data_json'], true);
    if (!is_array($data) || empty($data)) return false;

    $columns = array_keys($data);
    $placeholders = array_map(fn($c) => ':' . $c, $columns);
    $updates = array_map(fn($c) => "{$c}=VALUES({$c})", $columns);

    $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
            VALUES (" . implode(',', $placeholders) . ")
            ON DUPLICATE KEY UPDATE " . implode(',', $updates);
    $ins = $pdo->prepare($sql);
    $ins->execute($data);

    $done = $pdo->prepare("UPDATE deleted_items SET restored_at = NOW() WHERE deleted_id = ?");
    $done->execute([$deletedId]);
    return true;
}
