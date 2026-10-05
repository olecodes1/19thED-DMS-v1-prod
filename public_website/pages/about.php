<?php
?>
<h4 class="fw-bold text-success mb-3">About the 19th Episcopal District</h4>

<!-- About Section -->
<div class="row g-4 mb-5">
  <div class="col-lg-8">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="fw-bold text-success mb-3">Young People's Department (YPD)</h5>
        <p>The Young People's Department (YPD) is the youth organization of the African Methodist Episcopal Church, dedicated to empowering young people through faith, service, and leadership development. Our mission is to nurture spiritual growth, promote academic excellence, and foster community service among our youth.</p>
        <p>The YPD provides opportunities for young people to develop their talents, build lasting friendships, and become active participants in the life of the church and community.</p>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="fw-bold text-success mb-3">Women's Missionary Society (WMS)</h5>
        <p>The Women's Missionary Society (WMS) is a vital organization within the AME Church that focuses on missionary work, Christian education, and community service. Women in the WMS are dedicated to spreading the gospel, supporting local and international missions, and addressing social issues that affect families and communities.</p>
        <p>Through various programs and initiatives, the WMS empowers women to become leaders in the church and community while making a positive impact on society.</p>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="fw-bold text-success mb-3">African Methodist Episcopal Church (AME)</h5>
        <p>The African Methodist Episcopal Church is a historically African American Christian denomination founded in 1816. The AME Church has a rich history of social justice advocacy, education, and community development. With millions of members across the United States and around the world, the AME Church continues to be a beacon of hope and a force for positive change.</p>
        <p>The church is organized into episcopal districts, each led by a bishop, with conferences, areas, and local churches providing structure and support for spiritual growth and community service.</p>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold text-success">Leadership Structure</div>
      <div class="card-body">
        <ul class="list-unstyled">
          <li class="mb-3">
            <strong>Bishop</strong>
            <p class="small text-muted mb-0">The spiritual head of the episcopal district, providing oversight and guidance to conferences and churches.</p>
          </li>
          <li class="mb-3">
            <strong>Conference Leadership</strong>
            <p class="small text-muted mb-0">Conference Presidents and Directors who oversee multiple areas and coordinate district-wide activities.</p>
          </li>
          <li class="mb-3">
            <strong>Area Leadership</strong>
            <p class="small text-muted mb-0">Area Presidents and Directors who manage local church clusters and regional initiatives.</p>
          </li>
          <li class="mb-3">
            <strong>Local Church Leadership</strong>
            <p class="small text-muted mb-0">Local Church Presidents and Directors who lead individual church YPD and WMS programs.</p>
          </li>
        </ul>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold text-success">District Infographic</div>
      <div class="card-body">
        <?php
        $infographic = $pdo->query("SELECT file_path, title FROM media_items WHERE category = 'infographic' AND deleted_at IS NULL ORDER BY uploaded_at DESC LIMIT 1")->fetch();
        ?>
        <?php if ($infographic): ?>
          <img src="../<?= h($infographic['file_path']) ?>" alt="<?= h($infographic['title'] ?? 'District Infographic') ?>" class="img-fluid mb-2" loading="lazy">
          <p class="small text-muted mb-0"><?= h($infographic['title'] ?? 'District Overview') ?></p>
        <?php else: ?>
          <div class="text-center text-muted py-4">
            <i class="fas fa-image fa-2x mb-2"></i>
            <p class="small">No infographic uploaded yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
