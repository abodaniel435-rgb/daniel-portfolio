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

function projectCard(project, index) {
  const caseUrl = 'project.html?p=' + encodeURIComponent(project.slug);
  const isLive = Boolean(project.live);
  const href = isLive ? project.live : caseUrl;
  const linkAttrs = isLive ? ' target="_blank" rel="noopener noreferrer"' : '';
  const statusClass = isLive ? 'project-status is-live' : 'project-status';
  const alt = project.name + ' — ' + project.tagline;
  return [
    '<article class="project-card reveal" data-category="' + escapeHtml(project.category) + '">',
    '  <a class="project-image" href="' + escapeHtml(href) + '"' + linkAttrs + ' aria-label="' + (isLive ? 'Open ' + escapeHtml(project.name) + ' live site' : 'Read the ' + escapeHtml(project.name) + ' case study') + '">',
    '    <img src="' + escapeHtml(project.thumb) + '" alt="' + escapeHtml(alt) + '" width="1200" height="750" loading="lazy">',
    '    <span class="image-open" aria-hidden="true">' + (isLive ? '↗' : '→') + '</span>',
    '  </a>',
    '  <div class="project-meta"><span>' + String(index + 1).padStart(2, '0') + ' / ' + escapeHtml(project.categoryLabel).toUpperCase() + '</span><span class="' + statusClass + '">' + escapeHtml(project.status.toUpperCase()) + '</span></div>',
    '  <div class="project-title-row"><h3>' + escapeHtml(project.name) + '</h3><a class="project-arrow" href="' + escapeHtml(href) + '"' + linkAttrs + ' aria-label="' + escapeHtml(project.name) + '">↗</a></div>',
    '  <p>' + escapeHtml(project.summary) + '</p>',
    '  <div class="project-tags">' + project.stack.map((item) => '<span>' + escapeHtml(item) + '</span>').join('') + '</div>',
    '  <a class="project-case" href="' + caseUrl + '">Case study <span aria-hidden="true">→</span></a>',
    '</article>'
  ].join('\n');
}

const grid = document.querySelector('#projectGrid');
if (grid && Array.isArray(window.PROJECTS)) {
  grid.innerHTML = window.PROJECTS.map(projectCard).join('\n');
}

const filterButtons = document.querySelectorAll('.filter-button');
const projectCards = document.querySelectorAll('.project-card');

filterButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const filter = button.dataset.filter;
    filterButtons.forEach((item) => {
      const active = item === button;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-pressed', String(active));
    });
    projectCards.forEach((card) => {
      card.hidden = filter !== 'all' && card.dataset.category !== filter;
    });
  });
});

const revealItems = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12 });
  revealItems.forEach((item) => revealObserver.observe(item));
} else {
  revealItems.forEach((item) => item.classList.add('is-visible'));
}
