/**
 * ForgeStack console client.
 * Only the brief wizard needs scripting: staged navigation, live blueprint
 * preview and the generation request. Everything else is server-rendered.
 */
(function () {
  'use strict';

  var form = document.getElementById('brief-form');
  if (!form) { return; }

  var stages = Array.prototype.slice.call(document.querySelectorAll('.wizard-stage'));
  var dots = Array.prototype.slice.call(document.querySelectorAll('[data-stage-dot]'));
  var current = 1;
  var previewTimer = 0;
  var lastPreview = null;
  var previewSeq = 0;

  var statusEl = document.querySelector('[data-preview-status]');
  var overlay = document.querySelector('[data-gen-overlay]');
  var genError = document.querySelector('[data-gen-error]');
  var genHint = document.querySelector('[data-gen-hint]');
  var genClose = document.querySelector('[data-gen-close]');
  var generateBtn = document.querySelector('[data-generate]');
  var formError = document.querySelector('[data-form-error]');

  function q(name) { return document.querySelector('[name="' + name + '"]'); }
  function val(name) {
    var el = q(name);
    return el ? String(el.value || '').trim() : '';
  }
  function checked(name) {
    return Array.prototype.slice
      .call(form.querySelectorAll('[name="' + name + '"]:checked'))
      .map(function (el) { return el.value; });
  }

  function payload() {
    return {
      business_name: val('business_name'),
      industry: val('industry'),
      tone: val('tone'),
      idea: val('idea'),
      problem: val('problem'),
      audience: val('audience'),
      pages: checked('pages[]'),
      features: checked('features[]'),
      references: val('references'),
      colour_direction: (function () {
        var el = form.querySelector('[name="colour_direction"]:checked');
        return el ? el.value : '';
      })(),
      contact_name: val('contact_name'),
      contact_email: val('contact_email'),
      contact_phone: val('contact_phone'),
      address: val('address'),
      admin_email: val('admin_email'),
      admin_password: val('admin_password'),
      preview_port: val('preview_port'),
      mysql_available: q('mysql_available') && q('mysql_available').checked,
      install_now: q('install_now') && q('install_now').checked
    };
  }

  /* ---------------- staged navigation ---------------- */

  function showStage(n) {
    current = Math.max(1, Math.min(stages.length, n));
    stages.forEach(function (section) {
      var active = Number(section.getAttribute('data-stage')) === current;
      section.classList.toggle('is-active', active);
      section.hidden = !active;
    });
    dots.forEach(function (dot) {
      var index = Number(dot.getAttribute('data-stage-dot'));
      dot.classList.toggle('is-active', index === current);
      dot.classList.toggle('is-done', index < current);
    });
    if (current === 4) { renderReview(); }
    var heading = stages[current - 1];
    if (heading) {
      var target = heading.querySelector('.stage-heading');
      if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }
  }

  var RULES = {
    business_name: function (v) { return v.length >= 2 || 'Give the business a name.'; },
    industry: function (v) { return v !== '' || 'Choose an industry.'; },
    idea: function (v) { return v.length >= 10 || 'Describe the offering in one line.'; },
    problem: function (v) { return v.length >= 20 || 'Explain the problem in a little more detail.'; },
    audience: function (v) { return v.length >= 5 || 'Say who experiences the problem.'; },
    contact_email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Enter a valid email address.'; },
    contact_phone: function (v) { return v.length >= 7 || 'Enter a contact phone number.'; }
  };

  var STAGE_FIELDS = {
    1: ['business_name', 'industry', 'idea', 'problem', 'audience'],
    2: [],
    3: ['contact_email', 'contact_phone'],
    4: []
  };

  function markInvalid(name, message) {
    var input = q(name);
    if (!input) { return; }
    var field = input.closest('.field');
    if (field) { field.classList.add('is-invalid'); }
    input.setAttribute('aria-invalid', 'true');
    var note = field && field.querySelector('.field-error');
    if (note) { note.remove(); }
    if (message && field) {
      note = document.createElement('p');
      note.className = 'help field-error';
      note.style.color = 'var(--bad)';
      note.textContent = message;
      field.appendChild(note);
    }
  }

  function clearInvalid(name) {
    var input = q(name);
    if (!input) { return; }
    var field = input.closest('.field');
    if (field) {
      field.classList.remove('is-invalid');
      var note = field.querySelector('.field-error');
      if (note) { note.remove(); }
    }
    input.removeAttribute('aria-invalid');
  }

  function validateStage(n) {
    var ok = true;
    var first = null;
    (STAGE_FIELDS[n] || []).forEach(function (name) {
      var result = RULES[name](val(name));
      if (result !== true) {
        ok = false;
        markInvalid(name, result);
        if (!first) { first = q(name); }
      } else {
        clearInvalid(name);
      }
    });
    if (!ok && first) { first.focus(); }
    return ok;
  }

  form.addEventListener('input', function (event) {
    var name = event.target.getAttribute('name');
    if (name && RULES[name]) { clearInvalid(name); }
  });

  form.addEventListener('click', function (event) {
    var next = event.target.closest('[data-next]');
    var prev = event.target.closest('[data-prev]');
    if (next) {
      if (validateStage(current)) { showStage(current + 1); }
      return;
    }
    if (prev) { showStage(current - 1); }
  });

  dots.forEach(function (dot) {
    dot.addEventListener('click', function () {
      var target = Number(dot.getAttribute('data-stage-dot'));
      if (target <= current) { showStage(target); return; }
      for (var i = current; i < target; i++) {
        if (!validateStage(i)) { showStage(i); return; }
      }
      showStage(target);
    });
  });

  /* ---------------- live blueprint ---------------- */

  function setText(selector, text) {
    var el = document.querySelector(selector);
    if (el) { el.textContent = text; }
  }

  function setStatus(text, tone) {
    if (!statusEl) { return; }
    statusEl.textContent = text;
    statusEl.style.color = tone || '';
  }

  function renderPreview(data) {
    lastPreview = data;
    setStatus('live', 'var(--ok)');
    setText('[data-preview-profile]', data.profile_label || 'Blueprint');
    setText('[data-preview-name]', val('business_name') || 'Your business');
    setText('[data-preview-tagline]', data.tagline || '');
    setText('[data-preview-palette]', data.palette ? data.palette.name : 'Palette');
    setText('[data-preview-type]', data.typography || '');
    setText('[data-preview-note]', data.palette ? data.palette.description : '');

    if (data.palette) {
      var map = { brand: data.palette.brand, deep: data.palette.deep, accent: data.palette.accent, paper: data.palette.paper };
      Object.keys(map).forEach(function (key) {
        var swatch = document.querySelector('[data-swatch="' + key + '"]');
        if (swatch && map[key]) { swatch.style.background = map[key]; }
      });
    }

    var pageList = document.querySelector('[data-preview-pages]');
    if (pageList) {
      pageList.innerHTML = '';
      (data.pages || []).forEach(function (page) {
        var chip = document.createElement('span');
        chip.className = 'chip';
        chip.textContent = page.title || page.path;
        pageList.appendChild(chip);
      });
      if (!pageList.children.length) {
        var empty = document.createElement('span');
        empty.className = 'chip';
        empty.textContent = 'No pages selected yet';
        pageList.appendChild(empty);
      }
    }

    var featureList = document.querySelector('[data-preview-features]');
    if (featureList) {
      featureList.innerHTML = '';
      (data.features || []).forEach(function (feature) {
        var li = document.createElement('li');
        li.textContent = feature.label || feature.key;
        featureList.appendChild(li);
      });
    }

    setText('[data-preview-pagecount]', String(data.page_count || (data.pages || []).length));
    setText('[data-preview-entities]', String((data.entities || []).length || '—'));
    setText('[data-preview-routes]', String(data.route_count || '—'));
    setText('[data-preview-automation]', String((data.automation || []).length || '—'));

    if (current === 4) { renderReview(); }
  }

  function requestPreview() {
    var body = payload();
    if (!body.industry) {
      setStatus('waiting');
      return;
    }
    var seq = ++previewSeq;
    setStatus('deriving…');
    fetch('/api/preview', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    }).then(function (response) {
      return response.json().then(function (data) { return { ok: response.ok, data: data }; });
    }).then(function (result) {
      if (seq !== previewSeq) { return; }
      if (!result.ok || !result.data.ok) {
        setStatus('needs detail', 'var(--warn)');
        return;
      }
      renderPreview(result.data.preview);
    }).catch(function () {
      if (seq !== previewSeq) { return; }
      setStatus('offline', 'var(--bad)');
    });
  }

  function schedulePreview() {
    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(requestPreview, 350);
  }

  ['business_name', 'industry', 'idea', 'problem', 'audience', 'colour_direction', 'tone'].forEach(function (name) {
    form.addEventListener('input', schedulePreview);
    form.addEventListener('change', schedulePreview);
  });
  form.addEventListener('change', function (event) {
    var name = event.target.getAttribute('name') || '';
    if (name === 'pages[]' || name === 'features[]') { schedulePreview(); }
  });

  /* ---------------- review ---------------- */

  function renderReview() {
    var body = payload();
    var pages = checked('pages[]').length;
    var features = checked('features[]').length;
    setText('[data-review="business"]', body.business_name || '—');
    setText('[data-review="profile"]', lastPreview ? lastPreview.profile_label : (body.industry || '—'));
    setText('[data-review="pages"]', pages ? pages + ' pages' : '—');
    setText('[data-review="features"]', features ? features + ' selected + auth & admin' : 'auth & admin only');
    setText('[data-review="contact"]', [body.contact_email, body.contact_phone].filter(Boolean).join(' · ') || '—');
    setText('[data-review="design"]', lastPreview
      ? (lastPreview.palette ? lastPreview.palette.name + ' · ' + lastPreview.typography : lastPreview.typography)
      : '—');
    setText('[data-review="port"]', body.preview_port || 'Not set');
    var mysql = q('mysql_available') && q('mysql_available').checked;
    var install = q('install_now') && q('install_now').checked;
    setText('[data-review="database"]', mysql
      ? (install ? 'MySQL detected — database will be created and seeded' : 'MySQL detected — schema only')
      : 'MySQL unavailable — database step skipped');
  }

  /* ---------------- generation ---------------- */

  function showOverlay() {
    if (!overlay) { return; }
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    if (genError) { genError.hidden = true; genError.textContent = ''; }
    if (genClose) { genClose.hidden = true; }
    if (genHint) { genHint.textContent = 'Calling the generator…'; }
    if (generateBtn) { generateBtn.disabled = true; }
  }

  function hideOverlay() {
    if (!overlay) { return; }
    overlay.hidden = true;
    document.body.style.overflow = '';
    if (generateBtn) { generateBtn.disabled = false; }
  }

  function failGeneration(message) {
    if (genError) {
      genError.hidden = false;
      genError.textContent = message;
    }
    if (genClose) { genClose.hidden = false; }
    if (generateBtn) { generateBtn.disabled = false; }
    if (genHint) { genHint.textContent = 'Generation stopped — nothing was published as complete.'; }
  }

  if (genClose) { genClose.addEventListener('click', hideOverlay); }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!validateStage(1)) { showStage(1); return; }
    if (!validateStage(3)) { showStage(3); return; }
    if (formError) { formError.hidden = true; formError.textContent = ''; }
    showOverlay();

    fetch('/api/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload())
    }).then(function (response) {
      return response.json().then(function (data) { return { ok: response.ok, data: data }; });
    }).then(function (result) {
      if (result.ok && result.data.ok && result.data.redirect) {
        if (genHint) { genHint.textContent = 'Build complete — opening the project…'; }
        window.location.assign(result.data.redirect);
        return;
      }
      var data = result.data || {};
      var message = data.error || 'The generator refused this brief.';
      if (data.errors && typeof data.errors === 'object') {
        var lines = Object.keys(data.errors).map(function (key) {
          return data.errors[key];
        });
        if (lines.length) { message = lines.join(' '); }
      }
      failGeneration(message);
    }).catch(function (error) {
      failGeneration('The generator could not be reached. Check that PHP is running, then try again. (' + error.message + ')');
    });
  });

  /* ---------------- boot ---------------- */

  showStage(1);
  if (val('industry')) { schedulePreview(); }
})();
