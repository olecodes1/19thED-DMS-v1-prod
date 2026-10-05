<?php
/**
 * Historical Records — centralisation drive page.
 * Lists Google Drive upload links so members can contribute scattered
 * historical records (photos, programmes, certificates, minutes, etc.)
 * in one place.
 *
 * TO ADD OR EDIT LINKS: update the $recordsLinks array below. Each entry
 * needs a title, a description, and the full Google Drive (folder or form) URL.
 * Later this can move into the database with an admin editor if needed.
 */
$recordsLinks = [
    [
        'title' => 'Photos & Videos',
        'icon'  => 'fa-camera',
        'description' => 'Convention photos, choir performances, sports days, district tours — any YPD moment captured on camera.',
        'url'   => 'https://drive.google.com/drive/folders/YOUR_FOLDER_ID',
    ],
    [
        'title' => 'Programmes & Orders of Service',
        'icon'  => 'fa-scroll',
        'description' => 'Convention programmes, anniversary booklets, funeral and ordination orders of service.',
        'url'   => 'https://drive.google.com/drive/folders/YOUR_FOLDER_ID',
    ],
    [
        'title' => 'Certificates & Awards',
        'icon'  => 'fa-certificate',
        'description' => 'Merit awards, long-service certificates, competition placements and recognitions.',
        'url'   => 'https://drive.google.com/drive/folders/YOUR_FOLDER_ID',
    ],
    [
        'title' => 'Minutes, Reports & Registers',
        'icon'  => 'fa-file-lines',
        'description' => 'Meeting minutes, annual reports, membership registers and attendance records.',
        'url'   => 'https://drive.google.com/drive/folders/YOUR_FOLDER_ID',
    ],
];
?>

<h4 class="fw-bold text-success mb-3"><i class="fas fa-box-archive me-2"></i>Historical Records Drive</h4>

<div class="card shadow-sm mb-4 border-start border-warning border-4">
  <div class="card-body">
    <h5 class="card-title fw-semibold">Why your records matter</h5>
    <p class="mb-2">
      The history of the Young People's Division in the 19th Episcopal District lives in
      <strong>cupboards, shoeboxes, old phones and fading memories</strong> — scattered across
      conferences, areas and local churches. Every year that passes, more of it is lost:
      prints yellow and stick together, files are deleted when phones are replaced, and the
      people who can explain a photo or a name are no longer with us.
    </p>
    <p class="mb-2">
      We are working to <strong>centralise and preserve</strong> our district's history in one
      safe, organised digital archive — the same archive that powers the YPD History Booklet.
      Your contribution, whether a single 1970s photograph or a complete set of conference
      minutes, becomes part of a permanent record for the generations who come after us.
    </p>
    <p class="mb-0 text-muted small">
      <i class="fas fa-lightbulb me-1 text-warning"></i>
      Tip: phone photos of old documents and prints are perfectly fine — good lighting and a
      flat surface are all you need. Don't worry about perfect scans; we would rather have the
      record than lose it.
    </p>
  </div>
</div>

<div class="row g-3">
  <?php if (!$recordsLinks): ?>
    <div class="col-12"><div class="alert alert-light border">Upload links will be published here soon.</div></div>
  <?php else: foreach ($recordsLinks as $link): ?>
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <h6 class="card-title fw-semibold mb-1"><i class="fas <?= h($link['icon']) ?> me-2 text-success"></i><?= h($link['title']) ?></h6>
          <p class="card-text small text-muted flex-grow-1"><?= h($link['description']) ?></p>
          <a href="<?= h($link['url']) ?>" class="btn btn-outline-success btn-sm align-self-start" target="_blank" rel="noopener">
            <i class="fab fa-google-drive me-1"></i>Open Upload Folder
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>
