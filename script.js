const menuButton = document.querySelector('.menu-toggle');
const siteNav = document.querySelector('#site-nav');
const yearNode = document.querySelector('#year');

if (yearNode) yearNode.textContent = new Date().getFullYear();

menuButton?.addEventListener('click', () => {
  const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
  menuButton.setAttribute('aria-expanded', String(!isOpen));
  menuButton.querySelector('.sr-only').textContent = isOpen ? 'Open navigation' : 'Close navigation';
  siteNav?.classList.toggle('is-open', !isOpen);
  document.body.classList.toggle('menu-open', !isOpen);
});

siteNav?.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => {
    menuButton?.setAttribute('aria-expanded', 'false');
    if (menuButton?.querySelector('.sr-only')) menuButton.querySelector('.sr-only').textContent = 'Open navigation';
    siteNav.classList.remove('is-open');
    document.body.classList.remove('menu-open');
  });
});

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[char]));
}

/* The registry already carries a hand-picked accent per project and a `status`
   that says which builds are browser tools. Both were being ignored: every
   project rendered as the same card in the same lime. `layout` is honoured when
   a project declares it, so the band a project sits in stays editable from
   projects.js rather than being hard-coded here. */
const FEATURED = ['rentjury', 'trinity-lightings', 'hotel-miramar'];

function layoutFor(project) {
  if (project.layout) return project.layout;
  if (FEATURED.includes(project.slug)) return 'feature';
  if (project.status === 'Live tool') return 'row';
  return 'half';
}

/* Two thumbnails are 960x600 while the rest are 1200x750. Declaring the wrong
   intrinsic size makes the browser reserve the wrong box and jump on load. */
const THUMB_SIZES = {
  'assets/projects/city-of-gold.jpg': [960, 600],
  'assets/projects/currensee.jpg': [960, 600]
};

function thumbSize(thumb) {
  return THUMB_SIZES[thumb] || [1200, 750];
}

function cardVars(project, pos) {
  const accent = window.PALETTE.accent(project.accent);
  const parts = ['--i:' + pos];
  if (accent) parts.push('--p-accent:' + accent, '--p-on:' + window.PALETTE.on(accent));
  return parts.join(';');
}

function cardImage(project) {
  const [w, h] = thumbSize(project.thumb);
  const href = project.demo || project.live || 'project.html?p=' + encodeURIComponent(project.slug);
  const isLive = Boolean(project.demo || project.live);
  const linkAttrs = isLive ? ' target="_blank" rel="noopener noreferrer"' : '';
  const alt = project.name + ' — ' + project.tagline;
  const label = isLive ? 'Open ' + escapeHtml(project.name) + ' live site' : 'Read the ' + escapeHtml(project.name) + ' case study';
  return '<a class="project-image" href="' + escapeHtml(href) + '"' + linkAttrs + ' aria-label="' + label + '">' +
    '<img src="' + escapeHtml(project.thumb) + '" alt="' + escapeHtml(alt) +
    '" width="' + w + '" height="' + h + '" loading="lazy">' +
    '<span class="image-open" aria-hidden="true">' + (isLive ? '↗' : '→') + '</span></a>';
}

function cardBody(project, index, kind) {
  const caseUrl = 'project.html?p=' + encodeURIComponent(project.slug);
  const isLive = Boolean(project.demo || project.live);
  const statusClass = project.demo ? 'project-status is-live' : (isLive ? 'project-status is-live' : 'project-status');
  const meta = '<div class="project-meta"><span>' + String(index + 1).padStart(2, '0') + ' / ' +
    escapeHtml(project.categoryLabel).toUpperCase() + '</span><span class="' + statusClass + '">' +
    escapeHtml(project.status.toUpperCase()) + '</span></div>';
  const title = '<div class="project-title-row"><h3>' + escapeHtml(project.name) + '</h3>' +
    '<a class="project-arrow" href="' + escapeHtml(caseUrl) + '" aria-label="' + escapeHtml(project.name) + ' case study">↗</a></div>';
  const tags = '<div class="project-tags">' + project.stack.map((item) => '<span>' + escapeHtml(item) + '</span>').join('') + '</div>';
  const caseLink = '<a class="project-case" href="' + caseUrl + '">Case study <span aria-hidden="true">→</span></a>';

  if (kind === 'row') {
    return [
      cardImage(project),
      '<div class="row-text">',
      '  ' + meta,
      '  <h3>' + escapeHtml(project.name) + '</h3>',
      '  <p>' + escapeHtml(project.tagline) + '</p>',
      '</div>',
      '<div class="row-side">',
      '  ' + tags,
      '  ' + caseLink,
      '</div>'
    ].join('\n');
  }

  const copy = [
    meta,
    title,
    '<p>' + escapeHtml(kind === 'feature' ? project.tagline + ' ' + project.summary : project.summary) + '</p>',
    tags,
    caseLink
  ].join('\n');

  /* Feature cards pair artwork and copy side by side, which needs the copy to be a
     single grid item; the half cards stay a plain vertical stack. */
  return kind === 'feature'
    ? cardImage(project) + '\n<div class="card-copy">' + copy + '</div>'
    : cardImage(project) + '\n' + copy;
}

function projectCard(project, index, kind, pos) {
  return '<article class="project-card project-card--' + kind + ' reveal" data-category="' +
    escapeHtml(project.category) + '" data-slug="' + escapeHtml(project.slug) +
    '" style="' + cardVars(project, pos) + '">\n' +
    cardBody(project, index, kind) + '\n</article>';
}

const BANDS = [
  { kind: 'feature', label: 'Flagship cases', note: 'Three builds with the most surface area: a paid client site, a catalogue and a working booking system.' },
  { kind: 'half', label: 'Products and platforms', note: 'Full-stack builds and live demos — each one runs on PHP and MySQL or ships as a hosted app.' },
  { kind: 'row', label: 'Browser tools', note: 'Nine calculators that solve one economic problem each. No account, no install, open in any tab.' }
];

const grid = document.querySelector('#projectGrid');
if (grid && Array.isArray(window.PROJECTS)) {
  const numbered = window.PROJECTS.map((project, index) => ({ project, index }));
  grid.innerHTML = BANDS.map((band) => {
    const members = numbered.filter((item) => layoutFor(item.project) === band.kind);
    if (!members.length) return '';
    return '<section class="work-band work-band--' + band.kind + '" data-band="' + band.kind + '">' +
      '<header class="band-head"><h3>' + escapeHtml(band.label) + '</h3>' +
      '<p>' + escapeHtml(band.note) + '</p><span class="band-count">' +
      String(members.length).padStart(2, '0') + '</span></header>' +
      '<div class="band-inner">' +
      members.map((item, pos) => projectCard(item.project, item.index, band.kind, pos)).join('\n') +
      '</div></section>';
  }).join('\n');
}

const filterButtons = document.querySelectorAll('.filter-button');

/* The proof row and the filter counts were typed by hand and drifted the moment a
   project was added — the page claimed 26 while the registry held 28. The HTML keeps
   a plausible number for no-JS, and this corrects every one of them from the data. */
function fillCounts() {
  const list = window.PROJECTS || [];
  const counts = {
    total: list.length,
    open: list.filter((project) => project.demo || project.live).length,
    paid: list.filter((project) => /paid client/i.test(project.categoryLabel || '')).length
  };
  document.querySelectorAll('[data-proof]').forEach((node) => {
    const value = counts[node.dataset.proof];
    if (value === undefined) return;
    const text = String(value).padStart(2, '0');
    node.textContent = text;
    node.dataset.count = text;
  });
  filterButtons.forEach((button) => {
    const filter = button.dataset.filter;
    const n = filter === 'all' ? list.length : list.filter((project) => project.category === filter).length;
    const badge = document.createElement('span');
    badge.textContent = n;
    button.append(badge);
  });
}

if (Array.isArray(window.PROJECTS)) fillCounts();

/* Cross-document view transitions morph the clicked thumbnail into the case study's
   own artwork. Only the element that was actually clicked may claim the name, because
   duplicate view-transition names on one page cancel the transition outright, and a
   new-tab link never transitions at all. */
document.querySelector('#projectGrid')?.addEventListener('click', (event) => {
  const image = event.target.closest('.project-image');
  if (!image || image.target) return;
  document.querySelectorAll('.project-image img').forEach((node) => { node.style.viewTransitionName = ''; });
  const shot = image.querySelector('img');
  if (shot) shot.style.viewTransitionName = 'case-art';
});

filterButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const filter = button.dataset.filter;
    filterButtons.forEach((item) => {
      const active = item === button;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-pressed', String(active));
    });
    document.querySelectorAll('.project-card').forEach((card) => {
      card.hidden = filter !== 'all' && card.dataset.category !== filter;
    });
    /* A band with nothing left in it would show a heading over an empty grid. */
    document.querySelectorAll('.work-band').forEach((band) => {
      band.hidden = !band.querySelector('.project-card:not([hidden])');
    });
  });
});
