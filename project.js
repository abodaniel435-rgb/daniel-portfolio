const projects = Array.isArray(window.PROJECTS) ? window.PROJECTS : [];
const params = new URLSearchParams(window.location.search);
const slug = params.get('p');
const index = projects.findIndex((item) => item.slug === slug);
const project = index >= 0 ? projects[index] : null;

const yearNode = document.querySelector('#year');
if (yearNode) yearNode.textContent = new Date().getFullYear();

function setText(id, value) {
  const node = document.getElementById(id);
  if (node) node.textContent = value;
}

if (!project) {
  document.title = 'Project not found — Daniel';
  setText('caseMeta', 'Case study');
  setText('caseTitle', 'That project could not be found.');
  setText('caseTagline', 'The link may be out of date.');
  setText('caseSummary', 'Every project is listed on the main portfolio page.');
  const actions = document.getElementById('caseActions');
  if (actions) {
    actions.innerHTML = '<a class="button button-dark" href="index.html#work">Back to selected work</a>';
  }
  const body = document.querySelector('.case-body');
  if (body) body.hidden = true;
  const shot = document.querySelector('.case-shot');
  if (shot) shot.hidden = true;
} else {
  document.title = project.name + ' — case study — Daniel';
  const description = document.querySelector('meta[name="description"]');
  if (description) description.setAttribute('content', project.summary);

  setText('caseMeta', project.categoryLabel.toUpperCase() + ' · ' + project.year + ' · ' + project.status.toUpperCase());
  setText('caseTitle', project.name);
  setText('caseTagline', project.tagline);
  setText('caseSummary', project.summary);
  setText('caseProblem', project.problem);
  setText('caseApproach', project.approach);

  const shot = document.getElementById('caseShot');
  if (shot) {
    shot.src = project.thumb;
    shot.alt = project.name + ' — ' + project.tagline;
  }

  const featureList = document.getElementById('caseFeatures');
  if (featureList) {
    featureList.innerHTML = project.features.map((item) => '<li>' + item + '</li>').join('');
  }

  const stackList = document.getElementById('caseStack');
  if (stackList) {
    stackList.innerHTML = project.stack.map((item) => '<li>' + item + '</li>').join('');
  }

  const links = document.getElementById('caseLinks');
  if (links) {
    const parts = [];
    if (project.live) {
      parts.push('<a class="case-link case-link-primary" href="' + project.live + '" target="_blank" rel="noopener noreferrer">Open the live site <span aria-hidden="true">↗</span></a>');
    } else {
      parts.push('<p class="case-note">This build is packaged for deployment. The source and cPanel package are available on request.</p>');
    }
    if (project.source) {
      parts.push('<a class="case-link" href="' + project.source + '" target="_blank" rel="noopener noreferrer">View the source <span aria-hidden="true">↗</span></a>');
    } else {
      parts.push('<a class="case-link" href="mailto:abodaniel435@gmail.com?subject=' + encodeURIComponent('Source request: ' + project.name) + '">Request the source <span aria-hidden="true">→</span></a>');
    }
    links.innerHTML = parts.join('');
  }

  const actionBar = document.getElementById('caseActions');
  if (actionBar) {
    actionBar.innerHTML = project.live
      ? '<a class="button button-dark" href="' + project.live + '" target="_blank" rel="noopener noreferrer">Open live site <span aria-hidden="true">↗</span></a><a class="text-link case-text-link" href="index.html#work">All work <span aria-hidden="true">←</span></a>'
      : '<a class="button button-dark" href="mailto:abodaniel435@gmail.com?subject=' + encodeURIComponent('Deployment: ' + project.name) + '">Ask about this build <span aria-hidden="true">↗</span></a><a class="text-link case-text-link" href="index.html#work">All work <span aria-hidden="true">←</span></a>';
  }

  const next = projects[(index + 1) % projects.length];
  const nextLink = document.getElementById('caseNext');
  if (nextLink) {
    nextLink.href = 'project.html?p=' + encodeURIComponent(next.slug);
    nextLink.querySelector('strong').textContent = next.name;
  }
  const theme = document.querySelector('meta[name="theme-color"]');
  if (theme) theme.setAttribute('content', '#17231f');
}
