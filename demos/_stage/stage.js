/* ============================================================
   demos/_stage/stage.js
   Gives the standalone demos the same photographic plate and
   motion language the shared kit gives the ten tool sites,
   without importing any of the kit's resets.

   Nothing is hard-coded per site: the plate is read from the
   URL (demos/<slug>/<page>.html -> demos/_media/<slug>-hero.webp)
   and the wash is read from whatever background the page already
   has, so a dark demo and a light demo both stay legible.

   If anything here throws, .fx-anim is removed and the page falls
   back to exactly what it was before this file existed.
   ============================================================ */

(function () {
  "use strict";

  var doc = document;
  var root = doc.documentElement;
  var win = window;
  var reduce = win.matchMedia && win.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function $(s, ctx) { return (ctx || doc).querySelector(s); }
  function $$(s, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(s)); }

  var parts = win.location.pathname.split("/");
  var at = parts.indexOf("demos");
  var slug = at > -1 ? parts[at + 1] : "";
  if (!slug) return;

  var dir = win.location.pathname.slice(0, win.location.pathname.lastIndexOf("/") + 1);
  var PLATE = (dir + "../_media/" + slug + "-hero.webp").replace(/[^/]+\/\.\.\//g, "");

  /* --- the plate ---------------------------------------------------- */
  function stage() {
    var cs = win.getComputedStyle(doc.body);
    var base = cs.backgroundColor || "rgb(12, 14, 18)";
    var m = base.match(/(\d+(\.\d+)?)/g) || [12, 14, 18];
    var rgb = [Number(m[0]), Number(m[1]), Number(m[2])];
    if (base.indexOf("rgba") === 0 && Number(m[3]) < 0.5) rgb = [12, 14, 18];
    var lum = (0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2]) / 255;

    root.style.setProperty("--fx-base", base);
    // The wash is the page's own colour at two depths, so the plate reads
    // as atmosphere under the content rather than as a photo behind it.
    root.style.setProperty("--fx-wash", "rgba(" + rgb.join(",") + "," + (lum > 0.5 ? ".74" : ".8") + ")");
    root.style.setProperty("--fx-wash2", "rgba(" + rgb.join(",") + "," + (lum > 0.5 ? ".92" : ".95") + ")");
    root.style.setProperty("--fx-o", lum > 0.5 ? ".3" : ".5");
    root.style.setProperty("--fx-line", lum > 0.5 ? "rgba(0,0,0,.16)" : "rgba(255,255,255,.18)");

    // Only clear the body when it carries a flat colour. A page that paints
    // its own gradient or sky keeps it, and simply gains the hero treatment.
    var ownImage = cs.backgroundImage && cs.backgroundImage !== "none";
    if (!ownImage) root.classList.add("fx-clear");

    root.style.setProperty("--fx-bg", "url('" + PLATE + "')");

    var wrap = doc.createElement("div");
    wrap.className = "fx-stage";
    wrap.setAttribute("aria-hidden", "true");
    wrap.innerHTML = '<div class="fx-par"><i></i></div>';
    doc.body.insertBefore(wrap, doc.body.firstChild);
    return $(".fx-par", wrap);
  }

  /* --- the hero ----------------------------------------------------- */
  function heroEl() {
    var h1 = $("h1");
    if (!h1) return null;
    var node = h1.parentNode;
    while (node && node !== doc.body) {
      var cls = (node.className || "").toString();
      if (/hero|atmosphere|studio|cover|banner/i.test(cls)) return node;
      if (node.tagName === "SECTION" || node.tagName === "HEADER") return node;
      node = node.parentNode;
    }
    return $("section, main > div, main") || null;
  }

  function hero(her) {
    her.classList.add("fx-hero");
    var plate = doc.createElement("span");
    plate.className = "fx-heroplate";
    plate.setAttribute("aria-hidden", "true");
    her.insertBefore(plate, her.firstChild);

    var sweep = doc.createElement("span");
    sweep.className = "fx-sweep";
    sweep.setAttribute("aria-hidden", "true");
    her.insertBefore(sweep, her.firstChild);
  }

  /* --- word curtain -------------------------------------------------- */
  function splitWords(h1) {
    var pieces = [];
    var seen = [];

    function walk(node) {
      for (var i = 0; i < node.childNodes.length; i++) {
        var child = node.childNodes[i];
        if (child.nodeType === 3) {
          var words = child.nodeValue.split(/\s+/);
          for (var w = 0; w < words.length; w++) {
            if (words[w]) pieces.push({ word: words[w], el: null });
          }
        } else if (child.nodeType === 1) {
          if (child.nodeName === "BR") pieces.push({ br: true });
          else if (seen.indexOf(child) === -1) { seen.push(child); pieces.push({ el: child }); }
        }
      }
    }

    walk(h1);
    if (!pieces.length) return;

    h1.textContent = "";
    h1.classList.add("fx-split");
    for (var i = 0; i < pieces.length; i++) {
      if (i) h1.appendChild(doc.createTextNode(" "));
      if (pieces[i].br) { h1.appendChild(doc.createElement("br")); continue; }
      if (pieces[i].el) { h1.appendChild(pieces[i].el); continue; }
      var span = doc.createElement("span");
      span.className = "fx-w";
      span.style.setProperty("--n", i);
      span.textContent = pieces[i].word;
      h1.appendChild(span);
    }
  }

  /* --- ticker -------------------------------------------------------- */
  function ticker(her) {
    // A page that already runs a marquee of its own does not need a second.
    if ($(".marquee, .ticker, [class*=\"marquee\"], [class*=\"ticker\"]")) return;

    var words = $$("h2").map(function (h) {
      return h.textContent.replace(/\s+/g, " ").trim();
    }).filter(function (t) { return t.length > 3 && t.length < 62; });
    if (words.length < 2) return;

    var bar = doc.createElement("div");
    bar.className = "fx-ticker";
    bar.setAttribute("aria-hidden", "true");
    bar.innerHTML = '<span class="fx-ticker-label">On this page</span>';

    var track = doc.createElement("div");
    track.className = "fx-ticker-track";
    var html = words.map(function (t) { return "<span>" + t + "</span>"; }).join("");
    track.innerHTML = html + html;
    bar.appendChild(track);
    her.parentNode.insertBefore(bar, her.nextSibling);
  }

  /* --- progress + parallax ------------------------------------------- */
  function meter(par) {
    var bar = doc.createElement("div");
    bar.className = "fx-bar";
    bar.setAttribute("aria-hidden", "true");
    doc.body.appendChild(bar);

    var queued = false;
    function paint() {
      queued = false;
      var h = doc.documentElement.scrollHeight - win.innerHeight;
      bar.style.setProperty("--p", h > 0 ? Math.min(1, win.scrollY / h).toFixed(4) : "0");
      if (par) {
        par.style.setProperty("--sy", (-Math.min(win.scrollY, 1200) * 0.055).toFixed(1) + "px");
      }
    }
    function queue() { if (!queued) { queued = true; win.requestAnimationFrame(paint); } }

    win.addEventListener("scroll", queue, { passive: true });
    win.addEventListener("resize", queue);
    paint();
  }

  /* --- reveals ------------------------------------------------------- */
  var NEVER = /nav|header|footer|logo|menu|cookie|modal|dialog|form|input|button|script|style/i;
  // Pages that already animate their own panels keep doing it; stacking a
  // second reveal on top of a fade-in is how content ends up invisible.
  var ALREADY = /fade-in|reveal|anim-|is-anim|stagger/i;

  function inside(chrome) {
    var node = chrome.parentNode;
    while (node && node !== doc.body) {
      var tag = node.tagName;
      if (tag === "NAV" || tag === "HEADER" || tag === "FOOTER" || tag === "FORM" || tag === "DIALOG") return true;
      node = node.parentNode;
    }
    return false;
  }

  function revealTargets() {
    var sel = ".card, .tile, .panel, .stat, .metric, .product, .piece, .room, .item," +
      ".feature, .stat-item, .banner, .contact-card," +
      " .grid > *, .steps > *, .list > *, [class$=\"-grid\"] > *, [class$=\"-panel\"] > *," +
      " section > .wrap > p, section > .wrap > h2";
    var out = [];
    $$(sel).forEach(function (el) {
      if (el.hasAttribute("data-fx-r")) return;
      var mine = (el.className || "") + " " + (el.parentNode.className || "");
      if (NEVER.test(mine) || ALREADY.test(mine)) return;
      if (inside(el)) return;
      if (el.offsetHeight === 0 && el.offsetWidth === 0) return;
      if (el.children.length > 14) return;
      if (el.querySelector("h1")) return;
      out.push(el);
    });
    return out.slice(0, 120);
  }

  var io = null;
  var watch = [];

  function mark(el) {
    if (el.classList.contains("fx-in")) return;
    el.classList.add("fx-in");
  }

  function tag() {
    var nodes = revealTargets();
    nodes.forEach(function (el, i) {
      el.style.setProperty("--i", i % 9);
      el.setAttribute("data-fx-r", i % 7 === 3 ? "wipe" : "rise");
    });

    $$("section h2").slice(0, 30).forEach(function (h) {
      if (h.nextElementSibling && h.nextElementSibling.classList.contains("fx-rule")) return;
      var line = doc.createElement("span");
      line.className = "fx-rule";
      line.setAttribute("aria-hidden", "true");
      h.parentNode.insertBefore(line, h.nextSibling);
    });

    var fresh = nodes.concat($$(".fx-rule").filter(function (el) { return !el._fxSeen; }));
    fresh.forEach(function (el) { el._fxSeen = true; });
    watch = watch.concat(fresh);

    if ("IntersectionObserver" in win) {
      if (!io) {
        io = new IntersectionObserver(function (entries) {
          entries.forEach(function (e) {
            if (e.isIntersecting) { mark(e.target); io.unobserve(e.target); }
          });
        }, { rootMargin: "0px 0px -8% 0px", threshold: 0.04 });
      }
      fresh.forEach(function (el) { io.observe(el); });
    }
    sweep();
  }

  // A reveal that scrolls past while the observer is catching up must not
  // strand the content, so anything already on screen is marked outright.
  var queued = false;
  function sweep() {
    queued = false;
    for (var i = 0; i < watch.length; i++) {
      if (watch[i].classList.contains("fx-in")) continue;
      if (watch[i].getBoundingClientRect().top < win.innerHeight - 24) mark(watch[i]);
    }
  }
  function queue() { if (!queued) { queued = true; win.requestAnimationFrame(sweep); } }

  function reveal() {
    tag();
    win.addEventListener("scroll", queue, { passive: true });
    win.addEventListener("resize", queue);
    win.addEventListener("orientationchange", queue);

    // Several demos fetch their list after load, so tag again once the
    // data has arrived. Already-tagged elements are skipped.
    win.setTimeout(tag, 1100);
    win.setTimeout(tag, 2600);
  }

  /* --- boot ---------------------------------------------------------- */
  try {
    var par = stage();

    if (!reduce) {
      root.classList.add("fx-anim");
      var her = heroEl();
      if (her) {
        hero(her);
        var h1 = $("h1", her);
        if (h1) splitWords(h1);
        ticker(her);
      }
      meter(par);
      reveal();
    }
  } catch (err) {
    root.classList.remove("fx-anim");
    if (win.console && win.console.warn) win.console.warn("stage motion disabled", err);
  }
})();
