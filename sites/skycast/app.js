/* ============================================================
   SKYCAST — app.js
   Data: Open-Meteo forecast + geocoding (free, no API key)
   Places: local index built from COUNTRIES / STATES in
           countries-data.js, enriched with live geocoding hits.
   ============================================================ */

"use strict";

/* ── endpoints ──────────────────────────────────────────── */
const GEOCODE_URL  = "https://geocoding-api.open-meteo.com/v1/search";
const FORECAST_URL = "https://api.open-meteo.com/v1/forecast";

const LS_UNIT   = "skycast.unit";
const LS_RECENT = "skycast.recent";
const MAX_RECENT = 5;

/* ── WMO weather codes ──────────────────────────────────── */
const WMO = {
  0:  { desc: "Clear sky",            icon: "clear" },
  1:  { desc: "Mainly clear",         icon: "clear" },
  2:  { desc: "Partly cloudy",        icon: "partly" },
  3:  { desc: "Overcast",             icon: "cloudy" },
  45: { desc: "Fog",                  icon: "fog" },
  48: { desc: "Icy fog",              icon: "fog" },
  51: { desc: "Light drizzle",        icon: "drizzle" },
  53: { desc: "Drizzle",              icon: "drizzle" },
  55: { desc: "Dense drizzle",        icon: "drizzle" },
  56: { desc: "Freezing drizzle",     icon: "sleet" },
  57: { desc: "Freezing drizzle",     icon: "sleet" },
  61: { desc: "Light rain",           icon: "rain" },
  63: { desc: "Rain",                 icon: "rain" },
  65: { desc: "Heavy rain",           icon: "rain" },
  66: { desc: "Freezing rain",        icon: "sleet" },
  67: { desc: "Freezing rain",        icon: "sleet" },
  71: { desc: "Light snow",           icon: "snow" },
  73: { desc: "Snow",                 icon: "snow" },
  75: { desc: "Heavy snow",           icon: "snow" },
  77: { desc: "Snow grains",          icon: "snow" },
  80: { desc: "Light showers",        icon: "showers" },
  81: { desc: "Showers",              icon: "showers" },
  82: { desc: "Violent showers",      icon: "showers" },
  85: { desc: "Snow showers",         icon: "snow" },
  86: { desc: "Heavy snow showers",   icon: "snow" },
  95: { desc: "Thunderstorm",         icon: "storm" },
  96: { desc: "Thunderstorm, hail",   icon: "storm" },
  99: { desc: "Severe hailstorm",     icon: "storm" },
};
const getWMO = (code) => WMO[code] || { desc: "Unsettled conditions", icon: "cloudy" };

/* ── tiny helpers ───────────────────────────────────────── */
const $ = (id) => document.getElementById(id);
const clamp = (n, a, b) => Math.min(b, Math.max(a, n));

function store(key, value) {
  try { localStorage.setItem(key, JSON.stringify(value)); } catch { /* private mode */ }
}
function read(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    return raw === null ? fallback : JSON.parse(raw);
  } catch { return fallback; }
}
function toF(c) { return (c * 9) / 5 + 32; }
function tempText(c, decimals) {
  if (c === null || c === undefined || Number.isNaN(c)) return "—";
  const v = state.unit === "F" ? toF(c) : c;
  return decimals ? v.toFixed(decimals) : String(Math.round(v));
}
function unitSym() { return state.unit === "F" ? "°F" : "°C"; }

/* ── app state ──────────────────────────────────────────── */
const state = {
  unit: "C",
  data: null,          // last forecast payload
  meta: null,          // { label, sub, lat, lon, source, precision }
  hours: [],
  days: [],
  suggestions: [],
  active: -1,
  token: 0,            // guards out-of-order weather responses
  geoToken: 0,         // guards out-of-order geocode responses
  last: null,          // last successful request, for "try again"
  recent: [],
  theme: "",
  clock: null,
  tz: "UTC",
};

/* ── local place index (countries-data.js) ──────────────── */
const PLACES = [];
(function buildPlaceIndex() {
  if (typeof COUNTRIES === "undefined") return;
  COUNTRIES.forEach((country) => {
    const regions = (typeof STATES !== "undefined" && STATES[country.code]) || [];
    regions.forEach((row) => {
      if (!row || row.length < 3) return;
      PLACES.push({
        city: String(row[0]),
        country: country.name,
        code: country.code,
        lat: Number(row[1]),
        lon: Number(row[2]),
      });
    });
  });
})();

/* ── weather icons (inline SVG, animated with CSS) ──────── */
function iconSVG(kind, isDay = 1) {
  const open = '<svg class="wicon" viewBox="0 0 100 100" aria-hidden="true" focusable="false">';
  const close = "</svg>";
  const stars = `
    <g fill="#eaf2ff" class="w-pulse">
      <circle cx="20" cy="24" r="1.9"/><circle cx="78" cy="18" r="1.3"/>
      <circle cx="30" cy="66" r="1.2"/><circle cx="84" cy="58" r="1.7"/>
      <circle cx="66" cy="34" r="1"/>
    </g>`;

  const sun = (cx, cy, r, rays) => `
    <g class="w-rays" stroke="#ffcf8f" stroke-width="3.2" stroke-linecap="round" opacity=".85">
      <line x1="${cx}" y1="${cy - r - 13}" x2="${cx}" y2="${cy - r - 5}"/>
      <line x1="${cx}" y1="${cy + r + 5}" x2="${cx}" y2="${cy + r + 13}"/>
      <line x1="${cx - r - 13}" y1="${cy}" x2="${cx - r - 5}" y2="${cy}"/>
      <line x1="${cx + r + 5}" y1="${cy}" x2="${cx + r + 13}" y2="${cy}"/>
      <line x1="${cx - r - 9}" y1="${cy - r - 9}" x2="${cx - r - 4}" y2="${cy - r - 4}"/>
      <line x1="${cx + r + 4}" y1="${cy + r + 4}" x2="${cx + r + 9}" y2="${cy + r + 9}"/>
      <line x1="${cx + r + 9}" y1="${cy - r - 9}" x2="${cx + r + 4}" y2="${cy - r - 4}"/>
      <line x1="${cx - r - 9}" y1="${cy + r + 9}" x2="${cx - r - 4}" y2="${cy + r + 4}"/>
    </g>
    <circle cx="${cx}" cy="${cy}" r="${r}" fill="#ffd18c"/>
    <circle cx="${cx - r * 0.3}" cy="${cy - r * 0.32}" r="${r * 0.42}" fill="#fff1cf" opacity=".55"/>`;

  const cloud = (y = 0, scale = 1, opacity = 1) => `
    <g class="w-body" opacity="${opacity}" transform="translate(0 ${y}) scale(${scale}) translate(${(1 - scale) * 50} ${(1 - scale) * 50})">
      <ellipse cx="40" cy="63" rx="23" ry="15" fill="#f2f8ff"/>
      <ellipse cx="61" cy="60" rx="16.5" ry="13" fill="#f7fbff"/>
      <circle cx="48" cy="49" r="14.5" fill="#fbfdff"/>
      <rect x="20" y="58" width="56" height="19" rx="9.5" fill="#eef6ff"/>
    </g>`;

  const moon = `
    <g class="w-moon">
      <path d="M64 20A28 28 0 1 0 64 76 22 22 0 0 1 64 20Z" fill="#eaf2ff"/>
      <path d="M64 20A28 28 0 1 0 64 76 22 22 0 0 1 64 20Z" fill="none" stroke="#ffffff" stroke-opacity=".35" stroke-width="1.2"/>
    </g>`;

  const drops = (color = "#a9d8f0") => `
    <g stroke="${color}" stroke-width="3.4" stroke-linecap="round">
      <line class="w-drop" x1="36" y1="78" x2="33.5" y2="85"/>
      <line class="w-drop" x1="50" y1="79" x2="47.5" y2="86"/>
      <line class="w-drop" x1="64" y1="78" x2="61.5" y2="85"/>
    </g>`;

  const flakes = () => `
    <g fill="#eaf5ff">
      <circle class="w-flake" cx="36" cy="80" r="2.6"/>
      <circle class="w-flake" cx="50" cy="82" r="2.2"/>
      <circle class="w-flake" cx="64" cy="80" r="2.6"/>
      <circle class="w-flake" cx="43" cy="86" r="1.8"/>
    </g>`;

  switch (kind) {
    case "clear":
      return open + (isDay ? sun(50, 48, 20) : moon + stars) + close;

    case "partly":
      return open + (isDay
        ? sun(33, 33, 13) + cloud(8, 0.92)
        : moon + stars + cloud(8, 0.92)) + close;

    case "cloudy":
      return open + `
        <g class="w-pulse" opacity=".45">${cloud(-11, 0.72, 1)}</g>
        ${cloud(6, 1)}
      ` + close;

    case "drizzle":
      return open + cloud(-8, 0.96) + drops("#9ed4f2") + close;

    case "rain":
    case "showers":
      return open + cloud(-9, 1) + drops("#9ed4f2") + close;

    case "sleet":
      return open + cloud(-10, 1) + drops("#bfe6f7") + flakes() + close;

    case "snow":
      return open + cloud(-8, 1) + flakes() + close;

    case "storm":
      return open + cloud(-10, 1) + `
        <path class="w-bolt" d="M54 70 43 88h8.5l-4.5 12 15-19h-8.5l4.5-11Z" fill="#ffd98a"/>` + close;

    case "fog":
      return open + `
        <g opacity=".8">${cloud(-16, 0.85, 0.9)}</g>
        <g stroke="#dfeef7" stroke-width="4.5" stroke-linecap="round">
          <line class="w-fogline" x1="24" y1="72" x2="76" y2="72"/>
          <line class="w-fogline" x1="31" y1="81" x2="69" y2="81"/>
          <line class="w-fogline" x1="26" y1="90" x2="62" y2="90"/>
        </g>` + close;

    default:
      return open + cloud(0, 1) + close;
  }
}

/* ── theme from condition + temperature ─────────────────── */
function themeFor(code, isDay, tempC) {
  const kind = getWMO(code).icon;
  let base = "cloud";
  if (kind === "clear") base = "clear";
  else if (kind === "partly" || kind === "cloudy") base = "cloud";
  else if (kind === "fog") base = "fog";
  else if (kind === "snow") base = "snow";
  else if (kind === "storm") base = "storm";
  else base = "rain";

  const night = !isDay;
  if (base === "clear") return night ? "clear-night" : "clear-day";
  if (base === "cloud") return night ? "cloud-night" : "cloud-day";
  if (base === "rain") return night ? "rain-night" : "rain";
  if (base === "snow") return night ? "snow-night" : "snow";
  return base;
}
function toneFor(tempC) {
  if (typeof tempC !== "number") return "mild";
  if (tempC >= 25) return "warm";
  if (tempC < 9) return "cold";
  return "mild";
}
function applyTheme(theme, tone) {
  document.body.dataset.theme = theme;
  document.body.dataset.tone = tone;
  if (theme !== state.theme) {
    state.theme = theme;
    buildPrecip(theme);
  }
}

/* background precipitation */
function buildPrecip(theme) {
  const layer = $("precipLayer");
  if (!layer) return;
  layer.innerHTML = "";
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

  const isSnow = theme.startsWith("snow");
  const isStorm = theme.startsWith("storm");
  const isRain = theme.startsWith("rain") || isStorm;
  if (!isRain && !isSnow) return;

  const count = isSnow ? 34 : isStorm ? 80 : 56;
  const frag = document.createDocumentFragment();
  for (let i = 0; i < count; i++) {
    const el = document.createElement("span");
    if (isSnow) {
      el.className = "snowflake";
      const size = (Math.random() * 3.5 + 2.2).toFixed(1);
      el.style.cssText = `left:${(Math.random() * 100).toFixed(2)}%;width:${size}px;height:${size}px;opacity:${(Math.random() * 0.5 + 0.35).toFixed(2)};animation-duration:${(Math.random() * 8 + 9).toFixed(1)}s;animation-delay:${(-Math.random() * 12).toFixed(1)}s`;
    } else {
      el.className = "raindrop";
      el.style.cssText = `left:${(Math.random() * 100).toFixed(2)}%;height:${(Math.random() * 55 + 45).toFixed(0)}px;opacity:${(Math.random() * 0.35 + 0.3).toFixed(2)};animation-duration:${(Math.random() * 0.8 + 0.65).toFixed(2)}s;animation-delay:${(-Math.random() * 6).toFixed(2)}s`;
    }
    frag.appendChild(el);
  }
  layer.appendChild(frag);
}

/* ── suggestions ────────────────────────────────────────── */
const cityInput = $("cityInput");
const suggestionsEl = $("suggestions");

function badgeFor(result) {
  const fc = result.feature_code || "";
  if (fc.startsWith("PPLC")) return "Capital";
  if (fc.startsWith("PPLA")) return "City";
  if (fc.startsWith("PPL")) return "Town";
  if (fc.startsWith("ADM1")) return "Region";
  if (fc.startsWith("ADM2")) return "District";
  return "Place";
}

function localMatches(query, limit = 6) {
  const q = query.toLowerCase();
  const exact = [], prefix = [], contains = [], countryHit = [];
  for (let i = 0; i < PLACES.length; i++) {
    const p = PLACES[i];
    const city = p.city.toLowerCase();
    if (city === q) exact.push(p);
    else if (city.startsWith(q)) prefix.push(p);
    else if (city.includes(q)) contains.push(p);
    else if (p.country.toLowerCase().startsWith(q)) countryHit.push(p);
    if (exact.length + prefix.length > 80) break;
  }
  const pool = [...exact, ...prefix, ...contains];
  // country matches are useful but keep them behind real city hits
  const trimmed = pool.length ? pool : countryHit;
  return trimmed.slice(0, limit).map((p) => ({
    city: p.city,
    country: p.country,
    region: "",
    lat: p.lat,
    lon: p.lon,
    badge: "City",
    source: "local",
  }));
}

function dedupe(list) {
  const seen = new Set();
  return list.filter((item) => {
    const key = `${item.city.toLowerCase()}|${(item.country || "").toLowerCase()}`;
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
}

function renderSuggestions(items, note) {
  state.suggestions = items;
  state.active = -1;
  suggestionsEl.innerHTML = "";

  if (!items.length) {
    const li = document.createElement("li");
    li.className = "sug-empty";
    li.textContent = note || "No matches — try a different spelling.";
    suggestionsEl.appendChild(li);
    openSuggestions(true);
    return;
  }

  items.forEach((item, i) => {
    const li = document.createElement("li");
    li.className = "suggestion";
    li.id = `sug-${i}`;
    li.setAttribute("role", "option");
    li.setAttribute("aria-selected", "false");
    li.innerHTML = `
      <span class="sug-main">
        <span class="sug-city"></span>
        <span class="sug-region"></span>
      </span>
      <span class="sug-meta"></span>`;
    li.querySelector(".sug-city").textContent = item.city;
    li.querySelector(".sug-region").textContent = [item.region, item.country].filter(Boolean).join(", ");
    li.querySelector(".sug-meta").textContent = item.badge;
    li.addEventListener("pointerdown", (e) => {
      e.preventDefault();
      choose(item);
    });
    suggestionsEl.appendChild(li);
  });
  openSuggestions(true);
}

function openSuggestions(open) {
  suggestionsEl.hidden = !open;
  cityInput.setAttribute("aria-expanded", open ? "true" : "false");
  if (!open) {
    cityInput.removeAttribute("aria-activedescendant");
    state.active = -1;
    suggestionsEl.querySelectorAll(".suggestion.is-active").forEach((el) => {
      el.classList.remove("is-active");
      el.setAttribute("aria-selected", "false");
    });
  }
}

function highlight(index) {
  const items = suggestionsEl.querySelectorAll(".suggestion");
  if (!items.length) return;
  state.active = clamp(index, 0, items.length - 1);
  items.forEach((el, i) => {
    const on = i === state.active;
    el.classList.toggle("is-active", on);
    el.setAttribute("aria-selected", on ? "true" : "false");
  });
  const activeEl = items[state.active];
  cityInput.setAttribute("aria-activedescendant", activeEl.id);
  activeEl.scrollIntoView({ block: "nearest" });
}

function choose(item) {
  // invalidate any in-flight geocode lookup so a late response can't
  // reopen the suggestion list over the forecast the user just picked
  state.geoToken++;
  openSuggestions(false);
  cityInput.blur();
  cityInput.value = [item.city, item.country].filter(Boolean).join(", ");
  setHint(`Showing live conditions for ${item.city}.`, false);
  fetchWeather(item.lat, item.lon, {
    label: item.city,
    sub: [item.region, item.country].filter(Boolean).join(", ") || "Selected place",
    lat: item.lat,
    lon: item.lon,
    precision: item.badge,
    source: item.source,
  });
}

async function searchSuggestions(query) {
  const q = query.trim();
  const token = ++state.geoToken;
  const locals = localMatches(q, 6);

  if (q.length < 2) {
    state.suggestions = locals;
    renderSuggestions(locals, "Keep typing — or press Search.");
    return;
  }

  renderSuggestions(locals, "Searching places…");

  try {
    const url = `${GEOCODE_URL}?name=${encodeURIComponent(q)}&count=12&language=en&format=json`;
    const res = await fetch(url);
    if (!res.ok) throw new Error(`geocoding ${res.status}`);
    const data = await res.json();
    if (token !== state.geoToken) return; // a newer search started

    const live = (data.results || []).map((r) => ({
      city: r.name,
      country: r.country || "",
      region: r.admin1 && r.admin1 !== r.name ? r.admin1 : "",
      lat: r.latitude,
      lon: r.longitude,
      badge: badgeFor(r),
      source: "live",
    }));

    const merged = dedupe([...locals, ...live]).slice(0, 8);
    renderSuggestions(merged, null);
  } catch {
    if (token !== state.geoToken) return;
    renderSuggestions(locals, "Live suggestions unavailable — local list only.");
  }
}

/* ── hint ───────────────────────────────────────────────── */
const hintEl = $("searchHint");
function setHint(text, isError) {
  hintEl.textContent = text;
  hintEl.classList.toggle("is-error", Boolean(isError));
}

/* ── stage switching ────────────────────────────────────── */
function showStage(which) {
  $("idleCard").hidden = which !== "idle";
  $("skeleton").hidden = which !== "loading";
  $("errorCard").hidden = which !== "error";
  $("dashboard").hidden = which !== "dashboard";
}

function setSearchBusy(on) {
  $("searchBtn").disabled = on;
  $("searchBtn").textContent = on ? "Reading…" : "Search";
}

/* ── fetch forecast ─────────────────────────────────────── */
async function fetchWeather(lat, lon, meta) {
  const token = ++state.token;
  state.last = { lat, lon, meta };

  showStage("loading");
  setSearchBusy(true);
  setHint("Reading the sky…", false);

  const url = `${FORECAST_URL}?latitude=${lat}&longitude=${lon}` +
    "&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day," +
      "precipitation,weather_code,cloud_cover,pressure_msl,surface_pressure," +
      "wind_speed_10m,wind_direction_10m,wind_gusts_10m" +
    "&hourly=temperature_2m,weather_code,precipitation_probability,is_day," +
      "uv_index,visibility,dew_point_2m" +
    "&daily=weather_code,temperature_2m_max,temperature_2m_min,sunrise,sunset," +
      "uv_index_max,precipitation_probability_max" +
    "&timezone=auto&forecast_days=7";

  try {
    const res = await fetch(url, { signal: AbortSignal.timeout(18000) });
    if (!res.ok) throw new Error(`The weather service returned ${res.status}.`);
    const data = await res.json();
    if (!data || !data.current || !data.daily) {
      throw new Error("No forecast was returned for that location.");
    }
    if (token !== state.token) return;

    state.data = data;
    state.meta = meta;
    state.tz = data.timezone || "UTC";

    render(data, meta);
    showStage("dashboard");
    // the arc needs a laid-out path before we can measure its length
    requestAnimationFrame(() => paintDaylight(state.sunrise, state.sunset, data.current.time));
    setHint(`Live from Open-Meteo · updated ${new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}`, false);
    saveRecent(meta);
  } catch (err) {
    if (token !== state.token) return;
    const message = err && err.name === "TimeoutError"
      ? "The weather service took too long to respond."
      : (err && err.message) || "The forecast could not be loaded.";
    showError(message);
  } finally {
    if (token === state.token) setSearchBusy(false);
  }
}

/* ── free-text / submitted search ───────────────────────── */
async function runSearch(raw) {
  const text = String(raw || "").trim();
  if (!text) {
    setHint("Type a city name first — for example “Lisbon”.", true);
    cityInput.focus();
    return;
  }
  const term = text.split(",")[0].trim();
  openSuggestions(false);

  const locals = localMatches(term, 1);
  if (locals.length && locals[0].city.toLowerCase() === term.toLowerCase()) {
    choose(locals[0]);
    return;
  }

  showStage("loading");
  setSearchBusy(true);
  setHint(`Looking for “${term}”…`, false);

  try {
    const url = `${GEOCODE_URL}?name=${encodeURIComponent(term)}&count=10&language=en&format=json`;
    const res = await fetch(url, { signal: AbortSignal.timeout(12000) });
    if (!res.ok) throw new Error(`The place lookup returned ${res.status}.`);
    const data = await res.json();
    const results = data.results || [];

    if (!results.length) {
      showStage("error");
      showError(`We couldn't find “${term}”. Check the spelling, or try a nearby larger city.`);
      return;
    }

    const best = results.find((r) => (r.feature_code || "").startsWith("PPLC")) || results[0];
    await fetchWeather(best.latitude, best.longitude, {
      label: best.name,
      sub: [best.admin1, best.country].filter(Boolean).join(", "),
      lat: best.latitude,
      lon: best.longitude,
      precision: badgeFor(best),
      source: "live",
    });
  } catch (err) {
    showStage("error");
    const message = err && err.name === "TimeoutError"
      ? "The place lookup timed out. Please try again."
      : "We couldn't reach the location service. Check your connection and retry.";
    showError(message);
  } finally {
    setSearchBusy(false);
  }
}

function showError(message) {
  $("errorMsg").textContent = message;
  showStage("error");
  setHint(message, true);
  setSearchBusy(false);
}

/* ── render ─────────────────────────────────────────────── */
function render(data, meta) {
  const cur = data.current;
  const wmo = getWMO(cur.weather_code);
  const isDay = Number(cur.is_day) === 1;

  applyTheme(themeFor(cur.weather_code, isDay, cur.temperature_2m), toneFor(cur.temperature_2m));

  $("placeName").textContent = meta.label;
  $("placeMeta").textContent = meta.sub || "Current conditions";
  $("nowIcon").innerHTML = iconSVG(wmo.icon, isDay ? 1 : 0);
  $("condition").textContent = wmo.desc;
  $("tempUnit").textContent = unitSym();

  // current-hour extras come from the hourly block (visibility, UV, dew point)
  const hourIndex = findHourIndex(data, cur.time);
  state.hours = buildHours(data, hourIndex);
  state.days = buildDays(data.daily);

  const nowHour = state.hours[0] || {};
  const uv = typeof nowHour.uv === "number" ? nowHour.uv : null;
  const dew = typeof nowHour.dew === "number" ? nowHour.dew : null;
  const vis = typeof nowHour.visibility === "number" ? nowHour.visibility : null;

  state.current = {
    tempC: cur.temperature_2m,
    feelsC: cur.apparent_temperature,
    humidity: cur.relative_humidity_2m,
    wind: cur.wind_speed_10m,
    gust: cur.wind_gusts_10m,
    dir: cur.wind_direction_10m,
    pressure: cur.pressure_msl ?? cur.surface_pressure,
    uv,
    dew,
    vis,
    precipitation: cur.precipitation,
  };

  paintTemps();
  paintMetrics();
  startClock(cur.time);
  state.sunrise = data.daily.sunrise?.[0];
  state.sunset = data.daily.sunset?.[0];
  paintDaylight(state.sunrise, state.sunset, cur.time);
  paintHourly();
  paintDaily();

  const note = [];
  if (meta.precision) note.push(meta.precision);
  if (typeof meta.lat === "number") note.push(`${meta.lat.toFixed(4)}, ${meta.lon.toFixed(4)}`);
  note.push(state.tz.replace("_", " "));
  $("precisionNote").textContent = note.join(" · ");
  $("rangeNote").textContent = data.daily.time?.[0]
    ? `${fmtDay(data.daily.time[0], true)} onwards`
    : "";
}

function paintTemps() {
  const c = state.current;
  if (!c) return;
  $("tempValue").textContent = tempText(c.tempC, 1);
  $("tempUnit").textContent = unitSym();
  $("feelsLike").textContent = `Feels like ${tempText(c.feelsC, 1)}${unitSym()}`;
}

function paintMetrics() {
  const c = state.current;
  if (!c) return;
  $("mHumidity").textContent = Number.isFinite(c.humidity) ? `${c.humidity}%` : "N/A";
  $("mWind").textContent = Number.isFinite(c.wind) ? `${Math.round(c.wind)} km/h` : "N/A";
  $("mWindDir").textContent = Number.isFinite(c.dir)
    ? `${compass(c.dir)}${Number.isFinite(c.gust) ? ` · gusts ${Math.round(c.gust)}` : ""}`
    : "—";
  $("mPressure").textContent = Number.isFinite(c.pressure) ? `${Math.round(c.pressure)} hPa` : "N/A";
  $("mVisibility").textContent = Number.isFinite(c.vis)
    ? (c.vis >= 1000 ? `${(c.vis / 1000).toFixed(1)} km` : `${Math.round(c.vis)} m`)
    : "N/A";
  $("mDew").textContent = Number.isFinite(c.dew) ? `${tempText(c.dew, 1)}${unitSym()}` : "N/A";
  $("mUv").textContent = Number.isFinite(c.uv) ? c.uv.toFixed(1) : "N/A";
  $("mUvNote").textContent = Number.isFinite(c.uv) ? uvLabel(c.uv) : "Not reported";
}

function compass(deg) {
  const dirs = ["N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW"];
  return dirs[Math.round((((deg % 360) + 360) % 360) / 22.5) % 16];
}
function uvLabel(uv) {
  if (uv < 3) return "Low";
  if (uv < 6) return "Moderate";
  if (uv < 8) return "High";
  if (uv < 11) return "Very high";
  return "Extreme";
}

/* timezone clock (ported from the original) */
function startClock(localNow) {
  if (state.clock) clearInterval(state.clock);
  const tick = () => {
    try {
      const parts = new Intl.DateTimeFormat("en-GB", {
        timeZone: state.tz,
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      }).format(new Date());
      const date = new Intl.DateTimeFormat("en-GB", {
        timeZone: state.tz,
        weekday: "short",
        day: "numeric",
        month: "short",
      }).format(new Date());
      $("clockTime").textContent = parts;
      $("clockDate").textContent = date;
    } catch {
      // invalid timezone — fall back to the response's local timestamp
      $("clockTime").textContent = (localNow || "").slice(11, 16) || "--:--";
      $("clockDate").textContent = (localNow || "").slice(0, 10);
    }
  };
  tick();
  state.clock = setInterval(tick, 30000);
}

/* daylight arc */
function paintDaylight(sunrise, sunset, nowIso) {
  const arc = $("daylightProgress");
  const dot = $("daylightDot");
  const label = $("daylightLeft");
  if (!sunrise || !sunset) {
    $("sunrise").textContent = "—";
    $("sunset").textContent = "—";
    label.textContent = "";
    return;
  }
  const riseMin = minutesOfDay(sunrise);
  const setMin = minutesOfDay(sunset);
  const nowMin = minutesOfDay(nowIso);

  $("sunrise").textContent = clockText(riseMin);
  $("sunset").textContent = clockText(setMin);

  const span = Math.max(1, setMin - riseMin);
  const t = clamp((nowMin - riseMin) / span, 0, 1);

  let len = 400;
  try { len = arc.getTotalLength() || 400; } catch { /* not laid out yet */ }
  arc.style.strokeDasharray = `${(len * t).toFixed(1)} ${len.toFixed(1)}`;

  // quadratic bezier: P0(8,72) P1(150,-8) P2(292,72)
  const x = (1 - t) * (1 - t) * 8 + 2 * (1 - t) * t * 150 + t * t * 292;
  const y = (1 - t) * (1 - t) * 72 + 2 * (1 - t) * t * -8 + t * t * 72;
  dot.setAttribute("cx", x.toFixed(1));
  dot.setAttribute("cy", y.toFixed(1));

  if (nowMin < riseMin) label.textContent = "Before sunrise";
  else if (nowMin > setMin) label.textContent = "Sun has set";
  else {
    const left = setMin - nowMin;
    const h = Math.floor(left / 60);
    const m = Math.round(left % 60);
    label.textContent = h > 0 ? `${h}h ${m}m of light left` : `${m}m of light left`;
  }
}

function minutesOfDay(iso) {
  if (!iso) return 0;
  const hh = Number(iso.slice(11, 13));
  const mm = Number(iso.slice(14, 16));
  return (Number.isFinite(hh) ? hh : 0) * 60 + (Number.isFinite(mm) ? mm : 0);
}
function clockText(mins) {
  const h = Math.floor(mins / 60) % 24;
  const m = mins % 60;
  return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
}

/* hourly + daily data prep */
function findHourIndex(data, nowIso) {
  const times = data.hourly?.time || [];
  if (!times.length) return 0;
  const key = String(nowIso || times[0]).slice(0, 13);
  const found = times.findIndex((t) => t.slice(0, 13) >= key);
  return found === -1 ? Math.max(0, times.length - 24) : found;
}

function buildHours(data, startIndex) {
  const h = data.hourly;
  if (!h || !h.time) return [];
  const out = [];
  for (let i = startIndex; i < Math.min(h.time.length, startIndex + 24); i++) {
    out.push({
      iso: h.time[i],
      tempC: h.temperature_2m?.[i],
      code: h.weather_code?.[i],
      isDay: Number(h.is_day?.[i]) === 1,
      pop: h.precipitation_probability?.[i],
      uv: h.uv_index?.[i],
      dew: h.dew_point_2m?.[i],
      visibility: h.visibility?.[i],
    });
  }
  return out;
}

function buildDays(daily) {
  if (!daily || !daily.time) return [];
  return daily.time.map((iso, i) => ({
    iso,
    code: daily.weather_code?.[i],
    hi: daily.temperature_2m_max?.[i],
    lo: daily.temperature_2m_min?.[i],
    pop: daily.precipitation_probability_max?.[i],
    uvMax: daily.uv_index_max?.[i],
  }));
}

function paintHourly() {
  const strip = $("hourlyStrip");
  strip.innerHTML = "";
  if (!state.hours.length) {
    strip.innerHTML = '<p class="sug-empty">Hourly detail unavailable.</p>';
    return;
  }
  const frag = document.createDocumentFragment();
  state.hours.forEach((h, i) => {
    const wmo = getWMO(h.code);
    const cell = document.createElement("div");
    cell.className = "hour" + (i === 0 ? " is-now" : "");
    const pop = Number.isFinite(h.pop) ? h.pop : 0;
    cell.innerHTML = `
      <span class="hour-time">${i === 0 ? "Now" : fmtHour(h.iso)}</span>
      <span class="hour-icon" aria-hidden="true">${iconSVG(wmo.icon, h.isDay ? 1 : 0)}</span>
      <span class="hour-temp">${tempText(h.tempC)}°</span>
      <span class="hour-pop${pop < 10 ? " is-dry" : ""}" title="Chance of precipitation">
        <svg class="ico" aria-hidden="true"><use href="#i-drop"/></svg>${pop}%
      </span>`;
    cell.setAttribute("aria-label", `${i === 0 ? "Now" : fmtHour(h.iso)}: ${wmo.desc}, ${tempText(h.tempC)} degrees, ${pop}% chance of precipitation`);
    frag.appendChild(cell);
  });
  strip.appendChild(frag);
}

function paintDaily() {
  const list = $("dailyList");
  list.innerHTML = "";
  if (!state.days.length) {
    list.innerHTML = '<li class="sug-empty">Daily outlook unavailable.</li>';
    return;
  }
  const his = state.days.map((d) => d.hi).filter(Number.isFinite);
  const los = state.days.map((d) => d.lo).filter(Number.isFinite);
  const weekMax = his.length ? Math.max(...his) : 1;
  const weekMin = los.length ? Math.min(...los) : 0;
  const span = Math.max(1, weekMax - weekMin);

  const frag = document.createDocumentFragment();
  state.days.forEach((d, i) => {
    const wmo = getWMO(d.code);
    const left = clamp(((d.lo - weekMin) / span) * 100, 0, 100);
    const width = clamp(((d.hi - d.lo) / span) * 100, 6, 100 - left);
    const pop = Number.isFinite(d.pop) ? d.pop : 0;
    const li = document.createElement("li");
    li.className = "day-row" + (i === 0 ? " is-today" : "");
    li.innerHTML = `
      <span class="day-name">${fmtDay(d.iso, i === 0)}</span>
      <span class="day-icon" aria-hidden="true">${iconSVG(wmo.icon, 1)}</span>
      <span class="day-bar-cell">
        <span class="day-pop${pop < 10 ? " is-dry" : ""}" title="Chance of precipitation">
          <svg class="ico" aria-hidden="true"><use href="#i-drop"/></svg>${pop}%
        </span>
        <span class="day-bar" role="presentation"><span style="left:${left.toFixed(1)}%;width:${width.toFixed(1)}%"></span></span>
      </span>
      <span class="day-temps"><span class="hi">${tempText(d.hi)}°</span><span class="lo">${tempText(d.lo)}°</span></span>`;
    li.setAttribute("aria-label", `${fmtDay(d.iso, i === 0)}: ${wmo.desc}, high ${tempText(d.hi)}, low ${tempText(d.lo)}, ${pop}% chance of precipitation`);
    frag.appendChild(li);
  });
  list.appendChild(frag);
}

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
function fmtDay(iso, isToday) {
  if (!iso) return "—";
  if (isToday) return "Today";
  const d = new Date(`${iso}T12:00:00`);
  return Number.isNaN(d.getTime()) ? iso.slice(5) : WEEKDAYS[d.getDay()];
}
function fmtHour(iso) {
  const h = Number(iso.slice(11, 13));
  if (!Number.isFinite(h)) return "";
  const suffix = h < 12 ? "AM" : "PM";
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return `${hour12} ${suffix}`;
}

/* ── unit switching (persisted) ─────────────────────────── */
function setUnit(unit, persist = true) {
  state.unit = unit === "F" ? "F" : "C";
  document.querySelectorAll("[data-unit-set]").forEach((btn) => {
    const on = btn.dataset.unitSet === state.unit;
    btn.classList.toggle("is-on", on);
    btn.setAttribute("aria-pressed", on ? "true" : "false");
  });
  if (persist) store(LS_UNIT, state.unit);
  if (state.current) {
    paintTemps();
    paintMetrics();
    paintHourly();
    paintDaily();
  }
}

/* ── recent cities ──────────────────────────────────────── */
function saveRecent(meta) {
  const entry = {
    label: meta.label,
    sub: meta.sub || "",
    lat: Number(meta.lat),
    lon: Number(meta.lon),
    precision: meta.precision || "",
  };
  state.recent = [entry, ...state.recent.filter((r) => r.label !== entry.label)].slice(0, MAX_RECENT);
  store(LS_RECENT, state.recent);
  paintRecent();
}

function paintRecent() {
  const row = $("recentRow");
  const chips = $("recentChips");
  chips.innerHTML = "";
  if (!state.recent.length) {
    row.hidden = true;
    return;
  }
  state.recent.forEach((r) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "recent-chip";
    btn.textContent = r.label;
    btn.title = `Show weather for ${r.label}`;
    btn.addEventListener("click", () => {
      cityInput.value = r.label;
      fetchWeather(r.lat, r.lon, {
        label: r.label,
        sub: r.sub,
        lat: r.lat,
        lon: r.lon,
        precision: r.precision,
        source: "recent",
      });
    });
    chips.appendChild(btn);
  });
  row.hidden = false;
}

/* ── geolocation ────────────────────────────────────────── */
function useMyLocation() {
  if (!("geolocation" in navigator)) {
    showError("This browser can't share a location. Search for a city instead.");
    return;
  }
  const btn = $("locateBtn");
  btn.setAttribute("aria-busy", "true");
  setHint("Asking your browser for a location…", false);

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      btn.removeAttribute("aria-busy");
      const lat = pos.coords.latitude;
      const lon = pos.coords.longitude;
      cityInput.value = "";
      fetchWeather(lat, lon, {
        label: "My location",
        sub: "Approximate position from your browser",
        lat,
        lon,
        precision: "GPS",
        source: "gps",
      });
    },
    (err) => {
      btn.removeAttribute("aria-busy");
      let msg = "Location permission was denied. Search for a city instead.";
      if (err && err.code === 2) msg = "Your position is unavailable right now. Search a city instead.";
      if (err && err.code === 3) msg = "Locating took too long. Search a city instead.";
      showError(msg);
    },
    { enableHighAccuracy: false, timeout: 12000, maximumAge: 300000 }
  );
}

/* ── wiring ─────────────────────────────────────────────── */
function wireEvents() {
  $("searchForm").addEventListener("submit", (e) => {
    e.preventDefault();
    runSearch(cityInput.value);
  });

  $("retryBtn").addEventListener("click", () => {
    if (state.last) fetchWeather(state.last.lat, state.last.lon, state.last.meta);
    else { showStage(state.data ? "dashboard" : "idle"); cityInput.focus(); }
  });

  $("locateBtn").addEventListener("click", useMyLocation);

  document.querySelectorAll("[data-unit-set]").forEach((btn) => {
    btn.addEventListener("click", () => setUnit(btn.dataset.unitSet));
  });

  /* mobile menu */
  const menuBtn = $("menuBtn");
  const menu = $("mobileMenu");
  const closeMenu = (focus) => {
    if (menu.hidden) return;
    menu.hidden = true;
    menuBtn.setAttribute("aria-expanded", "false");
    menuBtn.setAttribute("aria-label", "Open menu");
    if (focus) menuBtn.focus();
  };
  const openMenu = () => {
    menu.hidden = false;
    menuBtn.setAttribute("aria-expanded", "true");
    menuBtn.setAttribute("aria-label", "Close menu");
  };
  menuBtn.addEventListener("click", () => (menu.hidden ? openMenu() : closeMenu(true)));
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeMenu(true);
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest("#mobileMenu") && !e.target.closest("#menuBtn")) closeMenu(false);
  });
  $("mmLocate").addEventListener("click", () => { closeMenu(false); useMyLocation(); });
  $("mmFocus").addEventListener("click", () => { closeMenu(false); cityInput.focus(); });
  $("mmClear").addEventListener("click", () => {
    closeMenu(false);
    state.recent = [];
    store(LS_RECENT, []);
    paintRecent();
    setHint("Recent places cleared.", false);
  });

  cityInput.addEventListener("input", () => {
    const value = cityInput.value;
    $("clearInput").hidden = !value;
    clearTimeout(state.debounce);
    if (!value.trim()) {
      openSuggestions(false);
      setHint("Start typing a city, or use your current location.", false);
      return;
    }
    state.debounce = setTimeout(() => searchSuggestions(value), 260);
  });

  cityInput.addEventListener("focus", () => {
    if (cityInput.value.trim()) searchSuggestions(cityInput.value);
  });

  cityInput.addEventListener("keydown", (e) => {
    const isOpen = !suggestionsEl.hidden;
    if (e.key === "ArrowDown") {
      if (!isOpen) { searchSuggestions(cityInput.value || ""); return; }
      e.preventDefault();
      highlight(state.active + 1);
    } else if (e.key === "ArrowUp") {
      if (!isOpen) return;
      e.preventDefault();
      highlight(state.active <= 0 ? suggestionsEl.querySelectorAll(".suggestion").length - 1 : state.active - 1);
    } else if (e.key === "Enter") {
      if (isOpen && state.active >= 0 && state.suggestions[state.active]) {
        e.preventDefault();
        choose(state.suggestions[state.active]);
      }
    } else if (e.key === "Escape") {
      if (isOpen) { e.preventDefault(); openSuggestions(false); }
    } else if (e.key === "Tab") {
      openSuggestions(false);
    }
  });

  cityInput.addEventListener("blur", () => {
    setTimeout(() => openSuggestions(false), 120);
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".search-shell")) openSuggestions(false);
  });

  $("clearInput").addEventListener("click", () => {
    cityInput.value = "";
    $("clearInput").hidden = true;
    openSuggestions(false);
    cityInput.focus();
    setHint("Start typing a city, or use your current location.", false);
  });

  $("clearRecent").addEventListener("click", () => {
    state.recent = [];
    store(LS_RECENT, []);
    paintRecent();
  });
}

/* ── deep links (?place=&lat=&lon= or ?q=) ──────────────── */
function handleDeepLink() {
  const params = new URLSearchParams(window.location.search);
  const lat = Number(params.get("lat"));
  const lon = Number(params.get("lon"));
  const place = params.get("place");
  const q = params.get("q");

  if (place && Number.isFinite(lat) && Number.isFinite(lon) && params.get("lat")) {
    cityInput.value = place;
    fetchWeather(lat, lon, {
      label: place,
      sub: params.get("sub") || "Shared location",
      lat,
      lon,
      precision: "Shared",
      source: "link",
    });
    return true;
  } else if (q) {
    cityInput.value = q;
    runSearch(q);
    return true;
  }
  return false;
}

/* first visit: open on a live sky rather than an empty stage */
function loadDefaultPlace() {
  const fallback = { city: "Lagos", country: "Nigeria", lat: 6.5244, lon: 3.3792 };
  const local = localMatches("Lagos", 1)[0];
  const place = local || fallback;
  cityInput.value = place.city;
  fetchWeather(place.lat, place.lon, {
    label: place.city,
    sub: place.country,
    lat: place.lat,
    lon: place.lon,
    precision: "Default",
    source: "default",
  });
}

/* returning visit: re-open the last place so the dashboard is never empty */
function loadRecentPlace() {
  const r = state.recent[0];
  if (!r || !Number.isFinite(Number(r.lat)) || !Number.isFinite(Number(r.lon))) {
    loadDefaultPlace();
    return;
  }
  cityInput.value = r.label;
  fetchWeather(Number(r.lat), Number(r.lon), {
    label: r.label,
    sub: r.sub || "",
    lat: Number(r.lat),
    lon: Number(r.lon),
    precision: r.precision || "Recent",
    source: "recent",
  });
}

/* ── init ───────────────────────────────────────────────── */
function init() {
  state.unit = read(LS_UNIT, "C") === "F" ? "F" : "C";
  state.recent = Array.isArray(read(LS_RECENT, [])) ? read(LS_RECENT, []) : [];

  setUnit(state.unit, false);
  paintRecent();
  wireEvents();

  // start on a night or day sky depending on the visitor's clock
  const hour = new Date().getHours();
  const dayish = hour > 6 && hour < 19;
  applyTheme(dayish ? "clear-day" : "clear-night", "mild");

  if (!handleDeepLink()) {
    if (state.recent.length) loadRecentPlace();
    else loadDefaultPlace();
  }
}

document.addEventListener("DOMContentLoaded", init);
