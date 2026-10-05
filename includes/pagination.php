<?php

function paginate(PDO $pdo, string $countSql, array $params = [], int $perPage = 20): array
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) { $page = $totalPages; }

    return ['page' => $page, 'perPage' => $perPage, 'offset' => ($page - 1) * $perPage, 'total' => $total, 'totalPages' => $totalPages];
}

function render_pagination(array $pager): string
{
    if (($pager['totalPages'] ?? 1) <= 1) { return ''; }
    $query = $_GET;
    unset($query['page']);
    $tail = $query ? '&' . http_build_query($query) : '';
    $page = (int)$pager['page'];
    $totalPages = (int)$pager['totalPages'];
    $prev = max(1, $page - 1);
    $next = min($totalPages, $page + 1);

    return '<nav><ul class="pagination pagination-sm mb-0">'
        . '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="?page=' . $prev . $tail . '">Prev</a></li>'
        . '<li class="page-item disabled"><span class="page-link">Page ' . $page . ' of ' . $totalPages . '</span></li>'
        . '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '"><a class="page-link" href="?page=' . $next . $tail . '">Next</a></li>'
        . '</ul></nav>';
}

