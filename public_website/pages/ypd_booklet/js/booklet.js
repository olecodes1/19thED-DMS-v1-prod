/**
 * booklet.js
 * Renders the History Booklet as a flippable two-page spread on desktop,
 * and a single scrolling page on mobile (CSS hides the left page under
 * 760px; this file tracks a separate mobile index so cover/foreword aren't
 * skipped when the layout collapses to one column).
 *
 * Flat page order: [cover, foreword, history, officers, timeline,
 *                    achievements, statistics, gallery]
 * Desktop pairs them into spreads of 2 (indices 0/1, 2/3, 4/5, 6/7).
 */

const PAGE_IDS = ['cover', 'foreword', 'history', 'officers', 'mother_directors', 'timeline', 'achievements', 'statistics', 'gallery'];
const TAB_LABELS = {
  history: 'History', officers: 'Officers', mother_directors: 'Mother Directors', timeline: 'Timeline',
  achievements: 'Achievements', statistics: 'Statistics', gallery: 'Gallery',
};

let state = {
  data: {},          // fetched content keyed by resource name
  failed: {},        // §5.2 — resource name -> error message for failed loads
  spreadStart: 0,     // desktop: even index into PAGE_IDS
  mobileIndex: 0,      // mobile: index into PAGE_IDS
};

// Resolve the public URL of a booklet photo relative to this page.
// booklet.php lives in /public_website/pages/, so uploads are two levels up.
// §5.4 — only plain generated filenames are accepted; anything else is ignored.
function safePhotoFilename(filename) {
  if (!filename || typeof filename !== 'string') return '';
  if (filename.includes('..')) return '';
  return /^[A-Za-z0-9._-]+$/.test(filename) ? filename : '';
}

function photoUrl(filename) {
  const safe = safePhotoFilename(filename);
  if (!safe) return '';
  return '../assets/uploads/ypd_photos/' + encodeURIComponent(safe);
}

function isMobile() {
  return window.matchMedia('(max-width: 760px)').matches;
}

async function loadAllData() {
  // §5.1 — allSettled instead of Promise.all: one failed chapter must not
  // blank the whole booklet. Failed chapters fall back to [] / null.
  const results = await Promise.allSettled([
    YpdApi.meta.get(),
    YpdApi.list('history'),
    YpdApi.list('officers'),
    YpdApi.list('mother_directors'),
    YpdApi.list('timeline'),
    YpdApi.list('achievements'),
    YpdApi.list('statistics'),
    YpdApi.list('photos'),
  ]);
  const [meta, history, officers, motherDirectors, timeline, achievements, statistics, photos] =
    results.map(r => (r.status === 'fulfilled' ? r.value : (Array.isArray(r.value) ? [] : null)));

  // Track which resources failed so chapters can show a retry state (§5.2)
  const failed = {};
  const keys = ['meta', 'history', 'officers', 'mother_directors', 'timeline', 'achievements', 'statistics', 'photos'];
  results.forEach((r, i) => {
    if (r.status === 'rejected') {
      failed[keys[i]] = r.reason instanceof Error ? r.reason.message : 'Request failed';
    }
  });
  state.failed = failed;

  state.data = {
    meta,
    history: history || [],
    officers: officers || [],
    mother_directors: motherDirectors || [],
    timeline: timeline || [],
    achievements: achievements || [],
    statistics: statistics || [],
    photos: photos || [],
  };
}

// ---------- Page content builders ----------

function pageCover() {
  const m = state.data.meta || {};
  const coverPhoto = (state.data.photos || []).find(p => String(p.id) === String(m.cover_photo_id));
  const coverSrc = coverPhoto ? photoUrl(coverPhoto.filename) : '';
  const coverHtml = coverSrc
    ? `<div class="cover-photo" style="background-image:url('${coverSrc}')" role="img" aria-label="${escapeHtml(coverPhoto.caption || 'Booklet cover photo')}"></div>`
    : '';
  return `
    <div class="cover-page h-100">
      ${coverHtml}
      <div class="district-label">${escapeHtml(m.district_name || '19th Episcopal District')} &middot; AME Church</div>
      <h1>${escapeHtml(m.booklet_title || 'History of the YPD')}</h1>
      <div class="subtitle">${escapeHtml(m.subtitle || '')}</div>
      <div class="motto">Grow &middot; Glow &middot; Go for Christ</div>
    </div>`;
}

function pageForeword() {
  const m = state.data.meta || {};
  return `
    <span class="eyebrow">Foreword</span>
    <h2>From the Office of the Historiographer</h2>
    <p>${escapeHtml(m.foreword || '')}</p>
    ${m.historiographer_name ? `<p style="margin-top:2rem;font-style:italic;">— ${escapeHtml(m.historiographer_name)}, Historiographer/Statistician</p>` : ''}
    <p class="empty-note" style="margin-top:1.5rem;">Compiled ${m.published_year || ''}. Use the tabs on the right edge to jump to any chapter.</p>`;
}

function pageHistory() {
  const rows = state.data.history || [];
  if (!rows.length) return emptyChapter('History', 'No history entries have been added yet.');
  const items = rows.map(r => `
    <div class="entry-block">
      ${r.era_label ? `<span class="era">${escapeHtml(r.era_label)}</span>` : ''}
      <h3 style="font-size:1.05rem;margin:0.2rem 0 0.4rem;">${escapeHtml(r.title)}</h3>
      <p>${escapeHtml(r.body)}</p>
    </div>`).join('');
  return `<span class="eyebrow">Chapter</span><h2>Our History</h2>${items}`;
}

function pageOfficers() {
  const rows = state.data.officers || [];
  if (!rows.length) return emptyChapter('Officers', 'No leadership records have been added yet.');
  const items = rows.map(o => {
    const thumb = photoUrl((o.photo_url || '').split('/').pop());
    return `
    <div class="officer-card">
      <div class="thumb" ${thumb ? `style="background-image:url('${thumb}')"` : ''}></div>
      <div>
        <div class="name">${escapeHtml(o.full_name)}</div>
        <div class="position">${escapeHtml(o.position)}</div>
        <div class="term">${escapeHtml(o.term_start || '')}${o.term_end ? ' – ' + escapeHtml(o.term_end) : (o.term_start ? ' – present' : '')}</div>
        ${o.bio ? `<p style="margin:0.4rem 0 0;font-size:0.85rem;">${escapeHtml(o.bio)}</p>` : ''}
      </div>
    </div>`;
  }).join('');
  return `<span class="eyebrow">Chapter</span><h2>Leadership</h2>${items}`;
}

function pageMotherDirectors() {
  const rows = state.data.mother_directors || [];
  if (!rows.length) return emptyChapter('Mother Directors', 'No mother director records have been added yet.');
  const items = rows.map(md => {
    const thumb = photoUrl((md.photo_url || '').split('/').pop());
    return `
    <div class="officer-card">
      <div class="thumb" ${thumb ? `style="background-image:url('${thumb}')"` : ''}></div>
      <div>
        <div class="name">${escapeHtml(md.full_name)}</div>
        ${md.conference_name ? `<div class="position">${escapeHtml(md.conference_name)}</div>` : ''}
        ${md.years_of_service ? `<div class="term">${escapeHtml(md.years_of_service)}</div>` : ''}
        ${md.bio ? `<p style="margin:0.4rem 0 0;font-size:0.85rem;">${escapeHtml(md.bio)}</p>` : ''}
        ${md.achievements ? `<p style="margin:0.3rem 0 0;font-size:0.8rem;font-style:italic;">${escapeHtml(md.achievements)}</p>` : ''}
      </div>
    </div>`;
  }).join('');
  return `<span class="eyebrow">Chapter</span><h2>Our Mother Directors</h2>${items}`;
}

function pageTimeline() {
  const rows = state.data.timeline || [];
  if (!rows.length) return emptyChapter('Timeline', 'No milestones have been added yet.');
  const items = rows.map(t => `
    <div class="timeline-item">
      <div class="date">${escapeHtml(t.event_date)}</div>
      <div class="dot-line">
        <strong>${escapeHtml(t.title)}</strong>
        ${t.description ? `<p style="margin:0.2rem 0 0;">${escapeHtml(t.description)}</p>` : ''}
      </div>
    </div>`).join('');
  return `<span class="eyebrow">Chapter</span><h2>Timeline of Milestones</h2>${items}`;
}

function pageAchievements() {
  const rows = state.data.achievements || [];
  if (!rows.length) return emptyChapter('Achievements', 'No achievements have been added yet.');
  const items = rows.map(a => `
    <div class="entry-block">
      ${a.category ? `<span class="era">${escapeHtml(a.category)}</span>` : ''}
      <h3 style="font-size:1.05rem;margin:0.2rem 0 0.4rem;">${escapeHtml(a.title)}${a.achievement_date ? ` <small style="color:var(--gold);font-family:var(--font-utility);font-size:0.7rem;">(${escapeHtml(a.achievement_date)})</small>` : ''}</h3>
      ${a.description ? `<p>${escapeHtml(a.description)}</p>` : ''}
    </div>`).join('');
  return `<span class="eyebrow">Chapter</span><h2>Achievements &amp; Recognitions</h2>${items}`;
}

function pageStatistics() {
  const rows = state.data.statistics || [];
  if (!rows.length) return emptyChapter('Statistics', 'No statistics have been recorded yet.');
  const cards = rows.map(s => `
    <div class="stat-card">
      <div class="value">${formatNumber(s.value)}${s.unit ? `<span style="font-size:0.9rem;"> ${escapeHtml(s.unit)}</span>` : ''}</div>
      <div class="label">${escapeHtml(s.label)}${s.year ? ' · ' + s.year : ''}</div>
    </div>`).join('');
  return `<span class="eyebrow">Chapter</span><h2>By the Numbers</h2><div class="stat-grid">${cards}</div>`;
}

function pageGallery() {
  const rows = state.data.photos || [];
  if (!rows.length) return emptyChapter('Gallery', 'No photos have been uploaded yet.');
  // Determine base path based on current page context
  const imgs = rows
    .map(p => ({ src: photoUrl(p.filename), alt: p.caption || '' }))
    .filter(p => p.src)
    .map(p => `<img src="${p.src}" alt="${escapeHtml(p.alt)}" loading="lazy" onerror="this.style.display='none'">`)
    .join('');
  return `<span class="eyebrow">Chapter</span><h2>Photo Gallery</h2><div class="gallery-grid">${imgs}</div>`;
}

function emptyChapter(name, note) {
  return `<span class="eyebrow">Chapter</span><h2>${name}</h2><div class="empty-note">${escapeHtml(note)}</div>`;
}

// §5.2 — per-chapter error state with a retry button
function errorChapter(name, message) {
  return `<span class="eyebrow">Chapter</span><h2>${name}</h2>
    <div class="empty-note">Couldn't load this chapter: ${escapeHtml(message)}<br>
      <button type="button" class="retry-btn" onclick="retryLoad()">Retry</button>
    </div>`;
}

const BUILDERS = {
  cover: pageCover, foreword: pageForeword, history: pageHistory, officers: pageOfficers,
  mother_directors: pageMotherDirectors,
  timeline: pageTimeline, achievements: pageAchievements, statistics: pageStatistics, gallery: pageGallery,
};

// Map chapter page ids to the data resource they depend on (for error states)
const CHAPTER_RESOURCE = {
  cover: 'meta', foreword: 'meta', history: 'history', officers: 'officers',
  mother_directors: 'mother_directors', timeline: 'timeline', achievements: 'achievements',
  statistics: 'statistics', gallery: 'photos',
};

const CHAPTER_NAMES = {
  cover: 'Cover', foreword: 'Foreword', history: 'Our History', officers: 'Leadership',
  mother_directors: 'Our Mother Directors', timeline: 'Timeline of Milestones',
  achievements: 'Achievements & Recognitions', statistics: 'By the Numbers', gallery: 'Photo Gallery',
};

function renderPageContent(pageId, pageNumber) {
  // A failed resource shows a per-chapter error instead of fake-empty content (§5.2)
  const resource = CHAPTER_RESOURCE[pageId];
  if (resource && state.failed && state.failed[resource]) {
    return errorChapter(CHAPTER_NAMES[pageId] || pageId, state.failed[resource]) + `<div class="page-number">${pageNumber}</div>`;
  }
  const html = BUILDERS[pageId] ? BUILDERS[pageId]() : '';
  return html + `<div class="page-number">${pageNumber}</div>`;
}

// ---------- Layout / navigation ----------

function render() {
  const left = document.getElementById('page-left');
  const right = document.getElementById('page-right');

  if (isMobile()) {
    const idx = state.mobileIndex;
    right.innerHTML = renderPageContent(PAGE_IDS[idx], idx + 1);
    left.innerHTML = '';
    document.getElementById('prevBtn').disabled = idx === 0;
    document.getElementById('nextBtn').disabled = idx === PAGE_IDS.length - 1;
    updateRibbon(idx, PAGE_IDS.length - 1);
    updateActiveTab(PAGE_IDS[idx]);
  } else {
    const s = state.spreadStart;
    left.innerHTML = renderPageContent(PAGE_IDS[s], s + 1);
    right.innerHTML = PAGE_IDS[s + 1] ? renderPageContent(PAGE_IDS[s + 1], s + 2) : '';
    document.getElementById('prevBtn').disabled = s === 0;
    document.getElementById('nextBtn').disabled = s + 2 >= PAGE_IDS.length;
    updateRibbon(s, PAGE_IDS.length - 2);
    updateActiveTab(PAGE_IDS[s], PAGE_IDS[s + 1]);
  }
}

function updateRibbon(current, max) {
  const ribbon = document.getElementById('ribbon');
  const pct = max > 0 ? current / max : 0;
  const stage = document.querySelector('.book');
  const usable = stage.clientWidth - 60;
  ribbon.style.left = `${20 + pct * usable}px`;
}

function updateActiveTab(...activeIds) {
  document.querySelectorAll('.chapter-tab').forEach(btn => {
    const isActive = activeIds.includes(btn.dataset.page);
    btn.classList.toggle('active', isActive);
    btn.setAttribute('aria-selected', isActive ? 'true' : 'false'); // §5.3
  });
}

function goTo(pageId) {
  const idx = PAGE_IDS.indexOf(pageId);
  if (idx === -1) return;
  if (isMobile()) {
    state.mobileIndex = idx;
  } else {
    state.spreadStart = idx % 2 === 0 ? idx : idx - 1;
  }
  render();
}

function next() {
  if (isMobile()) {
    state.mobileIndex = Math.min(state.mobileIndex + 1, PAGE_IDS.length - 1);
  } else {
    state.spreadStart = Math.min(state.spreadStart + 2, PAGE_IDS.length - (PAGE_IDS.length % 2 === 0 ? 2 : 1));
  }
  render();
}

function prev() {
  if (isMobile()) {
    state.mobileIndex = Math.max(state.mobileIndex - 1, 0);
  } else {
    state.spreadStart = Math.max(state.spreadStart - 2, 0);
  }
  render();
}

// ---------- Utilities ----------

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function formatNumber(n) {
  const num = Number(n);
  return Number.isInteger(num) ? num.toLocaleString() : num.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

function buildTabs() {
  const container = document.getElementById('chapterTabs');
  container.innerHTML = Object.entries(TAB_LABELS).map(([id, label]) =>
    `<button class="chapter-tab" data-page="${id}" role="tab" aria-selected="false" aria-controls="page-right">${label}</button>`).join('');
  container.querySelectorAll('.chapter-tab').forEach(btn => {
    btn.addEventListener('click', () => goTo(btn.dataset.page));
  });
}

async function retryLoad() {
  document.getElementById('page-right').innerHTML = '<div class="empty-note">Reloading booklet…</div>';
  try {
    await loadAllData();
    render();
  } catch (err) {
    document.getElementById('page-right').innerHTML =
      `<div class="empty-note">Still couldn't load booklet content: ${escapeHtml(err.message)}<br><button type="button" class="retry-btn" onclick="retryLoad()">Retry</button></div>`;
  }
}

async function init() {
  buildTabs();
  document.getElementById('nextBtn').addEventListener('click', next);
  document.getElementById('prevBtn').addEventListener('click', prev);
  // §5.3 — arrow keys must not hijack typing in form controls
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
    const tag = (e.target && e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable)) return;
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
  });
  window.addEventListener('resize', render);

  // §5.2 — initial loading state while data is fetched
  document.getElementById('page-right').innerHTML = '<div class="empty-note">Loading booklet…</div>';

  try {
    await loadAllData();
  } catch (err) {
    console.error(err);
    document.getElementById('page-right').innerHTML =
      `<div class="empty-note">Couldn't load booklet content: ${escapeHtml(err.message)}<br><button type="button" class="retry-btn" onclick="retryLoad()">Retry</button></div>`;
  }
  render();
}

document.addEventListener('DOMContentLoaded', init);
