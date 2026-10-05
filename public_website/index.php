<?php
// Simple router for public website
$page = $_GET['page'] ?? 'landing';
$allowedPages = ['landing', 'dashboard', 'events', 'event', 'media', 'stories', 'story', 'history', 'about', 'areas', 'churches', 'conferences', 'records', 'ypd_booklet', 'ypd_booklet_print'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'landing';
}

// Special handling for booklet pages - full-page layouts with their own topbar, no header/footer
if ($page === 'ypd_booklet_print') {
    require_once __DIR__ . '/ypd_booklet_print.php';
    exit;
}
if ($page === 'ypd_booklet') {
    require_once __DIR__ . '/pages/ypd_booklet.php';
    exit;
}

require_once 'includes/header.php';

// Route to appropriate page
$pageFile = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($pageFile)) {
    include $pageFile;
} else {
    echo '<div class="alert alert-danger">Page not found.</div>';
}

require_once 'includes/footer.php';
