/* ============================================================
   RENTJURY — app.js
   Shared logic used across all three pages.
============================================================ */

/* ---------- Theme (light / dark) ---------- */
(function initTheme() {
  const saved = localStorage.getItem("kw-theme");
  if (saved === "dark") document.documentElement.setAttribute("data-theme", "dark");
})();

function toggleTheme() {
  const isDark = document.documentElement.getAttribute("data-theme") === "dark";
  if (isDark) {
    document.documentElement.removeAttribute("data-theme");
    localStorage.setItem("kw-theme", "light");
  } else {
    document.documentElement.setAttribute("data-theme", "dark");
    localStorage.setItem("kw-theme", "dark");
  }
  updateThemeIcon();
}

function updateThemeIcon() {
  const btn = document.querySelectorAll("[data-theme-toggle]");
  const isDark = document.documentElement.getAttribute("data-theme") === "dark";
  btn.forEach(b => { b.textContent = isDark ? "☀ Light" : "● Dark"; });
}

/* ---------- Formatting ---------- */
function nairaFmt(n) {
  return "₦" + Math.round(n).toLocaleString("en-NG");
}

/* ---------- Verdict engine ---------- */
// Returns { verdict, label, pct, band }
function computeVerdict(area, type, amount) {
  const band = AREA_DATA[area] && AREA_DATA[area][type];
  if (!band || !amount) return null;
  const pct = Math.round(((amount - band.avg) / band.avg) * 100);

  let verdict, label;
  if (amount <= band.max * 0.98 && pct <= 8) {
    verdict = "fair";
    label = "FAIR PRICE";
  } else if (pct > 8 && pct <= 25) {
    verdict = "watch";
    label = "SLIGHTLY HIGH";
  } else if (pct > 25) {
    verdict = "over";
    label = "OVERPRICED";
  } else {
    verdict = "fair";
    label = "GOOD DEAL";
  }
  return { verdict, label, pct, band };
}

function stampMarkup(result) {
  if (!result) return "";
  const sign = result.pct > 0 ? "+" : "";
  return `
    <div class="stamp stamp--${result.verdict}" role="status">
      <span class="stamp__ring"></span>
      <span class="stamp__label">${result.label}</span>
      <span class="stamp__pct">${sign}${result.pct}% vs area avg</span>
    </div>
  `;
}

/* ---------- Fee breakdown checker ---------- */
function checkFees(rent, agencyFee, legalFee, cautionFee) {
  const flags = [];
  if (agencyFee > FEE_STANDARDS.agencyFeeMaxPct) {
    flags.push(`Agency fee is ${agencyFee}% of rent — standard is up to ${FEE_STANDARDS.agencyFeeMaxPct}%.`);
  }
  if (legalFee > FEE_STANDARDS.legalFeeMaxPct) {
    flags.push(`Legal/agreement fee is ${legalFee}% of rent — standard is up to ${FEE_STANDARDS.legalFeeMaxPct}%.`);
  }
  if (cautionFee > FEE_STANDARDS.cautionFeeHighPct) {
    flags.push(`Caution deposit is ${cautionFee}% of rent — this is unusually high and should be refundable.`);
  } else if (cautionFee > FEE_STANDARDS.cautionFeeWatchPct) {
    flags.push(`Caution deposit is ${cautionFee}% of rent — on the high side, confirm it's fully refundable in writing.`);
  }
  const totalPct = 100 + Number(agencyFee) + Number(legalFee) + Number(cautionFee);
  const totalAmount = rent * (totalPct / 100);
  return { flags, totalPct, totalAmount };
}

/* ---------- Community reports storage ----------
   Reports are local-first: they are saved to localStorage so the list
   updates instantly and survives a reload. js/firebase.js is optional —
   getFirebase() only imports it when a real config has been pasted in,
   so the site never breaks (or logs errors) without a Firebase project. */

const RJ_REPORTS_KEY = "rj-reports-v1";

function readLocalReports() {
  try {
    const raw = localStorage.getItem(RJ_REPORTS_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (err) {
    return [];
  }
}

function writeLocalReports(list) {
  try {
    localStorage.setItem(RJ_REPORTS_KEY, JSON.stringify(list.slice(0, 400)));
  } catch (err) { /* storage full or blocked — keep going */ }
}

/* Seeds the local list once so the community view is never empty. */
function seedLocalReports(seed) {
  if (readLocalReports().length) return;
  writeLocalReports(seed.map(r => ({ ...r })));
}

/* Saves one report locally and returns the stored entry. */
function saveReportLocal(report) {
  const entry = {
    id: "local-" + Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
    area: report.area,
    type: report.type,
    amount: Number(report.amount) || 0,
    agencyFee: Number(report.agencyFee) || 0,
    legalFee: Number(report.legalFee) || 0,
    cautionFee: Number(report.cautionFee) || 0,
    note: (report.note || "").slice(0, 160),
    date: report.date || new Date().toISOString().slice(0, 10),
    source: "local"
  };
  let list = readLocalReports();
  // Keep the starter reports as a baseline the first time someone adds one.
  if (!list.length && typeof SEED_REPORTS !== "undefined") {
    list = SEED_REPORTS.map(r => ({ ...r }));
  }
  writeLocalReports([entry, ...list.filter(r => r.id !== entry.id)]);
  return entry;
}

/* Merges Firestore reports with local ones, de-duping on area/type/rent/date. */
function mergeReports(remote, local) {
  const seen = new Set();
  const out = [];
  [...(remote || []), ...(local || [])].forEach(r => {
    if (!r || !r.area || !r.type) return;
    const key = [r.area, r.type, Math.round(Number(r.amount) / 1000), r.date].join("|");
    if (seen.has(key)) return;
    seen.add(key);
    out.push(r);
  });
  return out;
}

/* Optional Firebase. Returns a module or null — never throws. */
let rjFirebasePromise = null;
function getFirebase() {
  if (!rjFirebasePromise) {
    rjFirebasePromise = fetch("js/firebase.js", { cache: "no-store" })
      .then(res => (res.ok ? res.text() : ""))
      .then(src => {
        // Placeholder config (YOUR_API_KEY, YOUR_PROJECT_ID …) → stay local-only.
        if (!src || /YOUR_[A-Z_]+/.test(src)) return null;
        return import("./js/firebase.js");
      })
      .catch(() => null);
  }
  return rjFirebasePromise;
}

/* ---------- Community report helpers ---------- */
function verdictKey(result) {
  return result ? result.verdict : "fair";
}

function verdictName(verdict) {
  return verdict === "over" ? "Overpriced" : verdict === "watch" ? "Slightly high" : "Fair price";
}

function verdictBadgeMarkup(verdict) {
  return `<span class="badge badge--${verdict}"><span class="badge__dot"></span>${verdictName(verdict)}</span>`;
}

function reportDateLabel(iso) {
  const d = new Date(String(iso || "") + "T00:00:00");
  if (isNaN(d.getTime())) return "Undated";
  return d.toLocaleDateString("en-NG", { day: "2-digit", month: "short", year: "numeric" });
}

/* Keeps user-entered report text from breaking the markup. */
function escapeHtml(value) {
  return String(value == null ? "" : value).replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[c]));
}

function reportDateValue(iso) {
  const d = new Date(String(iso || "") + "T00:00:00");
  return isNaN(d.getTime()) ? 0 : d.getTime();
}

/* Plain-language verdict guidance used by both checkers. */
function verdictGuidance(result, area, type, amount) {
  const band = result.band;
  const gap = Math.round(amount - band.avg);
  const gapAbs = nairaFmt(Math.abs(gap));

  if (result.verdict === "over") {
    return {
      summary: `At ${nairaFmt(amount)}, this is ${result.pct}% above the ${area} median for a ${type.toLowerCase()} — ${gapAbs} more a year than renters there typically pay.`,
      steps: [
        `Ask the agent to justify the gap in writing — renovation, borehole, inverter, security, service charge.`,
        `Counter at the area median of ${nairaFmt(band.avg)}. It is a normal opening position, not an insult.`,
        `Walk the street: two or three other listings in ${area} will tell you if this is the landlord or the agent.`
      ]
    };
  }
  if (result.verdict === "watch") {
    return {
      summary: `At ${nairaFmt(amount)}, this is ${result.pct}% above the ${area} median — inside haggling range, but outside the band most renters here pay.`,
      steps: [
        `Use ${nairaFmt(band.avg)} as your counter-offer anchor and ask what moves the price up.`,
        `Confirm whether service charge, water and security are inside the rent or billed on top.`,
        `Check the agency fee separately — a high rent plus a full 10% fee is where money quietly leaks.`
      ]
    };
  }
  return {
    summary: `At ${nairaFmt(amount)}, this sits inside the ${area} band for a ${type.toLowerCase()} (${nairaFmt(band.min)}–${nairaFmt(band.max)}). The rent itself is not the problem.`,
    steps: [
      `Get the fee breakdown in writing before any payment, with agency, legal and caution listed separately.`,
      `Inspect the property and verify the landlord's identity before paying a kobo.`,
      `Ask what the rent becomes at renewal — bands move, and the first year is often the cheap one.`
    ]
  };
}

/* Rent-to-income read (annual rent vs annual gross income). */
function rentToIncome(annualRent, annualIncome) {
  if (!annualIncome || annualIncome <= 0) return null;
  const pct = Math.round((annualRent / annualIncome) * 100);
  let tone, label, note;
  if (pct <= 30) {
    tone = "fair";
    label = "Comfortable";
    note = `Rent takes ${pct}% of your gross annual income — inside the 30% most advisers suggest.`;
  } else if (pct <= 40) {
    tone = "watch";
    label = "Stretched";
    note = `Rent takes ${pct}% of your gross annual income. Workable, but food, transport and savings will feel it — especially if you pay yearly in one lump.`;
  } else {
    tone = "over";
    label = "High risk";
    note = `Rent takes ${pct}% of your gross annual income. A single missed month or a rent review would put real pressure on you.`;
  }
  return { pct, tone, label, note };
}

/* ---------- Toast helper ---------- */
function showToast(message) {
  let container = document.getElementById("kw-toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "kw-toast-container";
    container.className = "kw-toast-container";
    document.body.appendChild(container);
  }
  const toast = document.createElement("div");
  toast.className = "kw-toast";
  toast.textContent = message;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add("show"));
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 3200);
}

/* ---------- Scroll-reveal ---------- */
function prefersReducedMotion() {
  return window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
}

function initReveal() {
  const els = document.querySelectorAll("[data-reveal]");
  if (!("IntersectionObserver" in window) || els.length === 0 || prefersReducedMotion()) {
    els.forEach(el => el.classList.add("is-visible"));
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  els.forEach(el => io.observe(el));
}

/* ---------- Counters ---------- */
function initCounters() {
  const els = document.querySelectorAll("[data-counter]");
  if (els.length === 0) return;
  if (!("IntersectionObserver" in window) || prefersReducedMotion()) {
    els.forEach(el => {
      el.textContent = Number(el.getAttribute("data-counter")).toLocaleString("en-NG");
    });
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = Number(el.getAttribute("data-counter"));
      const duration = 900;
      const start = performance.now();
      function tick(now) {
        const p = Math.min((now - start) / duration, 1);
        el.textContent = Math.round(target * (0.2 + 0.8 * p)).toLocaleString("en-NG");
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = target.toLocaleString("en-NG");
      }
      requestAnimationFrame(tick);
      io.unobserve(el);
    });
  }, { threshold: 0.4 });
  els.forEach(el => io.observe(el));
}

/* ---------- Populate area/type selects ---------- */
function populateSelect(el, options, placeholder) {
  if (!el) return;
  el.innerHTML = `<option value="" disabled selected>${placeholder}</option>` +
    options.map(o => `<option value="${o}">${o}</option>`).join("");
}

// Builds a <select> grouped by state (optgroup) so all 36 states + FCT
// are browsable without one giant flat list.
function populateAreaSelect(el, placeholder, includeAllOption) {
  if (!el) return;
  let html = includeAllOption
    ? `<option value="">All areas</option>`
    : `<option value="" disabled selected>${placeholder}</option>`;
  html += STATE_GROUPS.map(group => {
    const opts = Object.keys(group.areas)
      .map(area => `<option value="${area}">${area}</option>`)
      .join("");
    return `<optgroup label="${group.state}">${opts}</optgroup>`;
  }).join("");
  el.innerHTML = html;
}

/* ---------- Mobile nav (sticky header) ---------- */
function initMobileNav() {
  const toggle = document.getElementById("navToggle");
  const links = document.getElementById("navLinks");
  if (!toggle || !links) return;

  function setOpen(open) {
    links.classList.toggle("is-open", open);
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
  }

  toggle.addEventListener("click", () => {
    setOpen(!links.classList.contains("is-open"));
  });

  links.addEventListener("click", (e) => {
    if (e.target.closest("a")) setOpen(false);
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && links.classList.contains("is-open")) {
      setOpen(false);
      toggle.focus();
    }
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 900) setOpen(false);
  });
}

/* ---------- Shared init ---------- */
document.addEventListener("DOMContentLoaded", () => {
  updateThemeIcon();
  initReveal();
  initCounters();
  initMobileNav();
  document.querySelectorAll("[data-theme-toggle]").forEach(b => b.addEventListener("click", toggleTheme));

  const year = document.getElementById("kw-year");
  if (year) year.textContent = new Date().getFullYear();
});
