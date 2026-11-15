// ============================================================
// Trinity Lightings — shared.js
// Brand constants, product cards, product dialog (lightbox),
// mobile navigation, image fallbacks and the enquiry form.
// ============================================================

"use strict";

// ====== CONFIGURE THIS ======
// WhatsApp number in international format, no + or spaces.
const WHATSAPP_NUMBER = "2348069041422";
// TikTok handle, no @ symbol — getTiktokUrl() adds it.
const TIKTOK_HANDLE = "rosacruis";
// Enquiries by email (used for the mailto fallback on the visit page).
const CONTACT_EMAIL = "hello@trinitylightings.ng";
// =============================

const SHOWROOM_ADDRESS =
  "Plot 129, Amuwo Odofin, Lagos, Nigeria — FCMB Plaza, beside Diamond Estate gate";
const SHOWROOM_HOURS = "Mon – Sat, 10am – 7pm";

const whatsappIcon = `<svg class="ico" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.6 6.32A8.86 8.86 0 0 0 12.04 4c-4.9 0-8.9 4-8.9 8.9 0 1.57.42 3.05 1.15 4.34L3 21l3.9-1.24a8.9 8.9 0 0 0 5.13 1.6h.01c4.9 0 8.9-4 8.9-8.9 0-2.38-.93-4.6-2.34-6.14ZM12.04 19.7h-.01a7.4 7.4 0 0 1-4.21-1.34l-.3-.18-3.13 1 1-2.95-.2-.31a7.36 7.36 0 0 1-1.15-3.97c0-4.08 3.32-7.4 7.4-7.4 1.98 0 3.84.77 5.23 2.17a7.32 7.32 0 0 1 2.17 5.23c0 4.08-3.32 7.4-7.4 7.4Zm4.05-5.55c-.22-.11-1.3-.64-1.5-.71-.2-.07-.35-.11-.5.11-.15.22-.57.71-.7.86-.13.15-.26.16-.48.05-.22-.1-.93-.34-1.77-1.09-.65-.58-1.09-1.3-1.22-1.52-.13-.22-.01-.34.11-.46.11-.11.25-.28.37-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.03-.41-.07-.11-.5-1.2-.69-1.65-.18-.43-.37-.37-.5-.38-.13-.01-.28-.01-.43-.01-.15 0-.39.06-.6.28-.21.22-.81.79-.81 1.93s.83 2.24.95 2.4c.11.15 1.55 2.36 3.76 3.22 1.87.74 2.25.59 2.65.55.4-.04 1.3-.53 1.48-1.04.18-.51.18-.95.13-1.04-.05-.09-.2-.15-.42-.26Z"/></svg>`;

const tiktokIcon = `<svg class="ico" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true" focusable="false"><path d="M16.6 5.82s.51.5 0 0A4.278 4.278 0 0 1 15.54 3h-3.09v12.4a2.592 2.592 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.15 0 5.71-2.55 5.71-5.7V9.01a7.35 7.35 0 0 0 4.32 1.39V7.32s-1.88.09-3.27-1.5Z"/></svg>`;

function getTiktokUrl() {
  return `https://www.tiktok.com/@${TIKTOK_HANDLE}`;
}

function buildWhatsAppLink(productName) {
  const message = `Hi, I'm interested in the ${productName} I saw on your website. Is it still available?`;
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
}

function buildGeneralWhatsAppLink() {
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(
    "Hi, I'd like to know more about your chandeliers — I saw your TikTok."
  )}`;
}

function buildEnquiryText(payload) {
  const lines = [
    "Hello Trinity Lightings, I'd like to arrange a showroom visit.",
    "",
    `Name: ${payload.name}`,
    `Phone: ${payload.phone}`,
    `Email: ${payload.email}`,
    `Preferred visit: ${payload.dateLabel}`,
  ];
  if (payload.piece) lines.push(`Piece of interest: ${payload.piece}`);
  lines.push("", "Message:", payload.message);
  return lines.join("\n");
}

function buildEnquiryWhatsApp(payload) {
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(buildEnquiryText(payload))}`;
}

function buildEnquiryMailto(payload) {
  const subject = `Showroom enquiry — ${payload.name}`;
  return `mailto:${CONTACT_EMAIL}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(
    buildEnquiryText(payload)
  )}`;
}

function escapeAttr(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

/* ── image fallback ─────────────────────────────────────── */
const FALLBACK_IMG =
  "data:image/svg+xml;charset=utf-8," +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 500" width="400" height="500">
      <defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#1b1815"/><stop offset="1" stop-color="#0d0c0b"/>
      </linearGradient></defs>
      <rect width="400" height="500" fill="url(#g)"/>
      <g fill="none" stroke="#c7a56c" stroke-width="2" opacity=".72">
        <path d="M200 54v54"/>
        <path d="M118 176a82 62 0 0 1 164 0"/>
        <path d="M136 176v32M200 176v52M264 176v32"/>
        <circle cx="200" cy="90" r="7"/>
        <path d="M150 214a11 11 0 1 0 18-1M232 214a11 11 0 1 0 18-1"/>
      </g>
      <g fill="#e6cb98" opacity=".6">
        <circle cx="136" cy="216" r="5"/><circle cx="200" cy="236" r="6"/><circle cx="264" cy="216" r="5"/>
      </g>
      <text x="200" y="330" text-anchor="middle" font-family="Georgia, serif" font-size="17"
        fill="#c7a56c" letter-spacing="3" opacity=".9">PHOTOGRAPHY ON REQUEST</text>
    </svg>`
  );

function setupImageFallback() {
  document.addEventListener(
    "error",
    (event) => {
      const img = event.target;
      if (!(img instanceof HTMLImageElement)) return;
      if (img.dataset.fallback === "on") return;
      img.dataset.fallback = "on";
      img.classList.add("is-fallback");
      img.src = FALLBACK_IMG;
    },
    true
  );
}

/* ── product cards ──────────────────────────────────────── */
function productCardHTML(p, index) {
  const alt = `${p.name} — ${p.category.toLowerCase()} chandelier at Trinity Lightings, Lagos`;
  return `
    <article class="piece">
      <button class="piece-media" type="button" data-open-product="${index}"
              aria-haspopup="dialog" aria-label="View ${escapeAttr(p.name)}, ${escapeAttr(p.category)}">
        ${p.badge ? `<span class="piece-badge">${escapeAttr(p.badge)}</span>` : ""}
        <img src="${escapeAttr(p.img)}" alt="${escapeAttr(alt)}" width="1024" height="1024" loading="lazy" decoding="async">
        <span class="piece-zoom" aria-hidden="true">View piece</span>
      </button>
      <div class="piece-info">
        <p class="piece-cat">${escapeAttr(p.category)}</p>
        <h3 class="piece-name">${escapeAttr(p.name)}</h3>
        <p class="piece-desc">${escapeAttr(p.desc)}</p>
        <div class="piece-foot">
          <span class="piece-price">${escapeAttr(p.price)}</span>
          <a class="piece-link" href="visit.html?piece=${encodeURIComponent(p.name)}#enquire">Enquire</a>
        </div>
      </div>
    </article>`;
}

/* ── product dialog (lightbox) ──────────────────────────── */
function setupLightbox() {
  if (document.getElementById("pieceDialog")) return;

  const dialog = document.createElement("dialog");
  dialog.className = "piece-dialog";
  dialog.id = "pieceDialog";
  dialog.setAttribute("aria-labelledby", "dlgTitle");
  dialog.innerHTML = `
    <div class="dialog-grid">
      <div class="dialog-media">
        <img id="dlgImg" src="${FALLBACK_IMG}" alt="" width="1024" height="1024">
      </div>
      <div class="dialog-body">
        <button class="dialog-close" id="dlgClose" type="button" aria-label="Close product details">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
        <p class="piece-cat" id="dlgCat"></p>
        <h2 class="dialog-title" id="dlgTitle"></h2>
        <p class="dialog-desc" id="dlgDesc"></p>
        <p class="dialog-detail" id="dlgDetail"></p>
        <dl class="dialog-specs">
          <div><dt>Finish</dt><dd id="dlgFinish"></dd></div>
          <div><dt>Price</dt><dd id="dlgPrice"></dd></div>
          <div><dt>Viewing</dt><dd>Walk-in, Mon – Sat · 10am – 7pm</dd></div>
        </dl>
        <div class="dialog-actions">
          <a class="btn btn--dark" id="dlgEnquire" target="_blank" rel="noopener"></a>
          <a class="btn btn--line" href="visit.html#enquire">Plan a visit</a>
        </div>
      </div>
    </div>`;
  document.body.appendChild(dialog);

  const img = dialog.querySelector("#dlgImg");

  function open(index) {
    const p = PRODUCTS[index];
    if (!p) return;
    img.dataset.fallback = "";
    img.classList.remove("is-fallback");
    img.src = p.img;
    img.alt = `${p.name} — ${p.category.toLowerCase()} chandelier, photographed in the Lagos showroom`;
    dialog.querySelector("#dlgCat").textContent = p.category;
    dialog.querySelector("#dlgTitle").textContent = p.name;
    dialog.querySelector("#dlgDesc").textContent = p.desc;
    dialog.querySelector("#dlgDetail").textContent = p.detail;
    dialog.querySelector("#dlgFinish").textContent = p.finish;
    dialog.querySelector("#dlgPrice").textContent = p.price;
    const cta = dialog.querySelector("#dlgEnquire");
    cta.href = buildWhatsAppLink(p.name);
    decorate(cta, whatsappIcon, "Enquire on WhatsApp");
    dialog.showModal();
    document.documentElement.classList.add("dialog-open");
  }

  function close() {
    if (dialog.open) dialog.close();
  }

  document.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-open-product]");
    if (trigger) {
      open(Number(trigger.dataset.openProduct));
    }
  });

  dialog.querySelector("#dlgClose").addEventListener("click", close);
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) close();
  });
  dialog.addEventListener("close", () => {
    document.documentElement.classList.remove("dialog-open");
  });
}

/* ── mobile navigation ──────────────────────────────────── */
function setupNav() {
  const toggle = document.getElementById("navToggle");
  const nav = document.getElementById("siteNav");
  if (!toggle || !nav) return;

  const setOpen = (open) => {
    document.body.classList.toggle("nav-open", open);
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
  };

  toggle.addEventListener("click", () => setOpen(!document.body.classList.contains("nav-open")));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && document.body.classList.contains("nav-open")) {
      setOpen(false);
      toggle.focus();
    }
  });

  document.addEventListener("click", (event) => {
    if (!document.body.classList.contains("nav-open")) return;
    if (event.target.closest("#siteNav") || event.target.closest("#navToggle")) return;
    setOpen(false);
  });

  nav.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => setOpen(false)));

  window.addEventListener("resize", () => {
    if (window.innerWidth > 900) setOpen(false);
  });
}

/* ── shared links ───────────────────────────────────────── */
// Puts a brand glyph in front of the link's own words. The label already in
// the markup wins, so the footer still reads "WhatsApp" / "TikTok".
function decorate(el, icon, fallbackLabel) {
  const label = (el.textContent || "").trim() || fallbackLabel;
  el.innerHTML = icon;
  const span = document.createElement("span");
  span.textContent = label;
  el.appendChild(span);
}

function wireSharedLinks() {
  const tiktokUrl = getTiktokUrl();
  const generalWa = buildGeneralWhatsAppLink();

  document.querySelectorAll("[data-tiktok-link]").forEach((el) => {
    el.href = tiktokUrl;
    if (!el.querySelector("svg")) decorate(el, tiktokIcon, "Follow on TikTok");
  });

  document.querySelectorAll("[data-whatsapp-link]").forEach((el) => {
    el.href = generalWa;
    if (!el.querySelector("svg")) decorate(el, whatsappIcon, "Message on WhatsApp");
  });

  const visitTiktokLink = document.getElementById("visitTiktok");
  if (visitTiktokLink) {
    visitTiktokLink.href = tiktokUrl;
    visitTiktokLink.textContent = `@${TIKTOK_HANDLE}`;
  }

  document.querySelectorAll("[data-year]").forEach((el) => {
    el.textContent = String(new Date().getFullYear());
  });

  document.querySelectorAll("[data-hours]").forEach((el) => { el.textContent = SHOWROOM_HOURS; });
  document.querySelectorAll("[data-address]").forEach((el) => { el.textContent = SHOWROOM_ADDRESS; });
}

/* ── enquiry form ───────────────────────────────────────── */
const FIELD_RULES = {
  name: {
    test: (v) => v.trim().length >= 2,
    message: "Please tell us your name.",
  },
  phone: {
    test: (v) => {
      const digits = v.replace(/[^\d]/g, "");
      return digits.length >= 7 && /^[+\d][\d\s().-]*$/.test(v.trim());
    },
    message: "Enter a phone number we can reach you on.",
  },
  email: {
    test: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()),
    message: "That email address doesn't look complete.",
  },
  visitDate: {
    test: (v) => {
      if (!v) return false;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const chosen = new Date(`${v}T12:00:00`);
      return !Number.isNaN(chosen.getTime()) && chosen >= today;
    },
    message: "Choose a date from today onwards.",
  },
  message: {
    test: (v) => v.trim().length >= 10,
    message: "A sentence or two about what you're looking for helps us prepare.",
  },
};

function fancyDate(iso) {
  const d = new Date(`${iso}T12:00:00`);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString("en-GB", {
    weekday: "long", day: "numeric", month: "long", year: "numeric",
  });
}

function setupEnquiryForm() {
  const form = document.getElementById("enquiryForm");
  if (!form) return;

  const success = document.getElementById("enquirySuccess");
  const dateInput = form.querySelector("#visitDate");
  if (dateInput) dateInput.min = new Date().toISOString().slice(0, 10);

  const params = new URLSearchParams(window.location.search);
  const piece = params.get("piece");
  const messageField = form.querySelector("#message");
  const pieceNote = document.getElementById("pieceNote");
  if (piece && messageField && !messageField.value) {
    messageField.value = `I'd like to see the ${piece} in the showroom. `;
  }
  if (piece && pieceNote) {
    pieceNote.textContent = `Enquiring about: ${piece}`;
    pieceNote.hidden = false;
  }

  const fields = ["name", "phone", "email", "visitDate", "message"];

  const clearFieldError = (id) => {
    const input = form.querySelector(`#${id}`);
    const error = form.querySelector(`#err-${id}`);
    if (input) {
      input.removeAttribute("aria-invalid");
      input.classList.remove("is-invalid");
    }
    if (error) {
      error.textContent = "";
      error.hidden = true;
    }
  };

  const showFieldError = (id, message) => {
    const input = form.querySelector(`#${id}`);
    const error = form.querySelector(`#err-${id}`);
    if (input) {
      input.setAttribute("aria-invalid", "true");
      input.classList.add("is-invalid");
    }
    if (error) {
      error.textContent = message;
      error.hidden = false;
    }
  };

  fields.forEach((id) => {
    const input = form.querySelector(`#${id}`);
    if (!input) return;
    const handler = () => clearFieldError(id);
    input.addEventListener("input", handler);
    input.addEventListener("change", handler);
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const values = {
      name: form.querySelector("#name")?.value || "",
      phone: form.querySelector("#phone")?.value || "",
      email: form.querySelector("#email")?.value || "",
      visitDate: form.querySelector("#visitDate")?.value || "",
      message: form.querySelector("#message")?.value || "",
    };

    let firstInvalid = null;
    fields.forEach((id) => {
      const rule = FIELD_RULES[id];
      const value = values[id] || "";
      if (!rule.test(value)) {
        showFieldError(id, rule.message);
        if (!firstInvalid) firstInvalid = form.querySelector(`#${id}`);
      } else {
        clearFieldError(id);
      }
    });

    if (firstInvalid) {
      firstInvalid.focus({ preventScroll: false });
      return;
    }

    const payload = {
      ...values,
      dateLabel: fancyDate(values.visitDate),
      piece: piece || "",
    };

    const wa = document.getElementById("successWhatsapp");
    const mail = document.getElementById("successEmail");
    const nameSlot = document.getElementById("successName");
    const summary = document.getElementById("successSummary");

    if (wa) wa.href = buildEnquiryWhatsApp(payload);
    if (mail) mail.href = buildEnquiryMailto(payload);
    if (nameSlot) nameSlot.textContent = values.name.split(" ")[0];
    if (summary) {
      summary.textContent = `Visit requested for ${payload.dateLabel}. We'll confirm by WhatsApp or email.`;
    }

    form.hidden = true;
    if (success) {
      success.hidden = false;
      success.focus({ preventScroll: true });
      success.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });

  const editBtn = document.getElementById("editEnquiry");
  if (editBtn) {
    editBtn.addEventListener("click", () => {
      if (success) success.hidden = true;
      form.hidden = false;
      form.querySelector("#name")?.focus();
    });
  }
}

/* ── motion ─────────────────────────────────────────────── */
// Everything below is opt-in: the tl-mo class only lands on <html> when the
// visitor has not asked for reduced motion, and the hidden start states are
// scoped to that class. No script, no animation, no lost content.
const EASE = "cubic-bezier(.76,0,.24,1)";

const REVEAL_SEL = [
  ".page-head", ".hero-copy", ".hero-figure", ".section-head", ".collection",
  ".piece", ".why-item", ".teaser-media", ".teaser-copy", ".form-panel",
  ".info-block", ".visit-strip", ".filters-inner", "[data-reveal]",
].join(",");

let revealObserver = null;

function setupRevealObserver() {
  if (!("IntersectionObserver" in window)) {
    document.querySelectorAll(REVEAL_SEL).forEach((el) => el.classList.add("is-in"));
    return;
  }
  revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add("is-in");
      revealObserver.unobserve(entry.target);
    });
  }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
}

// Called again after the catalogue renders, so injected cards animate too.
function revealScan(root) {
  const scope = root || document;
  const seenPerParent = new Map();
  scope.querySelectorAll(REVEAL_SEL).forEach((el) => {
    if (el.dataset.rvDone) return;
    el.dataset.rvDone = "1";
    const parent = el.parentElement || document.body;
    const i = seenPerParent.get(parent) || 0;
    seenPerParent.set(parent, i + 1);
    el.style.setProperty("--rv", String(i % 9));
    if (el.getBoundingClientRect().top < window.innerHeight) {
      el.classList.add("is-in");
    } else if (revealObserver) {
      revealObserver.observe(el);
    } else {
      el.classList.add("is-in");
    }
  });
}

function splitWords(el) {
  const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);
  let n = 0;
  nodes.forEach((node) => {
    const frag = document.createDocumentFragment();
    node.textContent.split(/(\s+)/).forEach((chunk) => {
      if (!chunk) return;
      if (/^\s+$/.test(chunk)) { frag.appendChild(document.createTextNode(chunk)); return; }
      const span = document.createElement("span");
      span.className = "fx-w";
      span.style.setProperty("--n", n++);
      span.textContent = chunk;
      frag.appendChild(span);
    });
    if (node.parentNode) node.parentNode.replaceChild(frag, node);
  });
}

const GLYPHS = "ABCDEFGHIJKLMNOPQRSTUVWXYZ·—/0123456789";

function decode(el) {
  const final = el.textContent;
  if (!final.trim()) return;
  const steps = 22;
  let frame = 0;
  let done = false;
  const finish = () => { if (!done) { done = true; el.textContent = final; } };
  const tick = () => {
    if (done) return;
    frame += 1;
    const settled = Math.floor((frame / steps) * final.length);
    let out = final.slice(0, settled);
    for (let i = settled; i < final.length; i += 1) {
      out += final[i] === " " ? " " : GLYPHS[Math.floor(Math.random() * GLYPHS.length)];
    }
    el.textContent = out;
    if (frame < steps) requestAnimationFrame(tick);
    else finish();
  };
  requestAnimationFrame(tick);
  // If the tab is hidden rAF stops; the real words must not wait for it.
  setTimeout(finish, 1600);
}

function setupScrollRail() {
  const rail = document.createElement("div");
  rail.className = "tl-rail";
  rail.setAttribute("aria-hidden", "true");
  document.body.appendChild(rail);
  const cssTimeline = window.CSS && CSS.supports && CSS.supports("animation-timeline", "scroll(root)");
  if (cssTimeline) rail.classList.add("tl-rail--css");

  let queued = false;
  const paint = () => {
    queued = false;
    const span = document.documentElement.scrollHeight - window.innerHeight;
    if (!cssTimeline) {
      rail.style.setProperty("--p", span > 0 ? (window.scrollY / span).toFixed(4) : "0");
    }
    document.body.classList.toggle("is-scrolled", window.scrollY > 24);
  };
  window.addEventListener("scroll", () => {
    if (!queued) { queued = true; requestAnimationFrame(paint); }
  }, { passive: true });
  window.addEventListener("resize", paint, { passive: true });
  paint();
}

function setupSpotlight() {
  let queued = false;
  let last = null;
  window.addEventListener("pointermove", (event) => {
    last = event;
    if (queued) return;
    queued = true;
    requestAnimationFrame(() => {
      queued = false;
      const el = last && last.target.closest && last.target.closest(".piece, .collection, .hero-figure");
      if (!el) return;
      const box = el.getBoundingClientRect();
      el.style.setProperty("--mx", `${((last.clientX - box.left) / box.width * 100).toFixed(2)}%`);
      el.style.setProperty("--my", `${((last.clientY - box.top) / box.height * 100).toFixed(2)}%`);
    });
  }, { passive: true });
}

function setupMotion() {
  const reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce) return;
  document.documentElement.classList.add("tl-mo");
  // The hidden start states only exist under tl-mo, so if anything here
  // throws we must take the class back off rather than leave the page blank.
  try {
    setupRevealObserver();
    revealScan();
    document.querySelectorAll("[data-fx='words']").forEach(splitWords);
    document.querySelectorAll("[data-fx='decode']").forEach(decode);
    setupScrollRail();
    setupSpotlight();
  } catch (err) {
    document.documentElement.classList.remove("tl-mo");
    console.warn("Trinity motion disabled.", err);
  }
}

/* ── boot ───────────────────────────────────────────────── */
function bootShared() {
  setupImageFallback();
  setupNav();
  wireSharedLinks();
  setupLightbox();
  setupEnquiryForm();
  setupMotion();
}

document.addEventListener("DOMContentLoaded", bootShared);
