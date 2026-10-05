<?php
/**
 * YPD History Booklet — Public Viewer
 * Full-page booklet layout matching the reference ypd-booklet app.
 * Rendered standalone (no site header/footer) by public_website/index.php.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>YPD 19th Episcopal District — History Booklet</title>
<link rel="icon" type="image/png" href="../19thDistrict.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Source+Serif+4:ital,wght@0,400;0,600;1,400&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="pages/ypd_booklet/css/booklet.css">
</head>
<body>

<header class="ypd-topbar">
  <div class="brand">YPD <small>19th Episcopal District &middot; AME Church</small></div>
  <div class="actions">
    <a href="?page=landing" class="me-2">&larr; Back to Website</a>
    <a href="?page=ypd_booklet_print" target="_blank" rel="noopener">Print / Save as PDF</a>
  </div>
</header>

<main class="book-stage">
  <div class="book" role="group" aria-roledescription="carousel" aria-label="YPD History Booklet pages">
    <button id="prevBtn" class="turn-btn prev" aria-label="Previous page">&#8249;</button>
    <div id="ribbon" class="ribbon" aria-hidden="true"></div>
    <div id="page-left" class="page left"></div>
    <div id="page-right" class="page right" role="tabpanel" aria-live="polite"></div>
    <div id="chapterTabs" class="chapter-tabs" role="tablist" aria-label="Booklet chapters" aria-orientation="vertical"></div>
    <button id="nextBtn" class="turn-btn next" aria-label="Next page">&#8250;</button>
  </div>
</main>

<script>
const YPD_API_BASE = '../api_ypd';
</script>
<script src="pages/ypd_booklet/js/api.js" defer></script>
<script src="pages/ypd_booklet/js/booklet.js" defer></script>
</body>
</html>
