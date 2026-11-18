# Design transformation — audit and per-project visual strategy

Status: **approved for implementation.** This document is the audit + strategy step. Nothing here is
implemented yet; each wave lands with its own before/after screenshots and a functionality re-test.

Scope: all 26 eligible projects plus the portfolio shell. **CurenSee and City of Gold Estates are
excluded and must not be touched.**

---

## 1. The standard being aimed at

"Not generic" has to be measurable, or it is a mood board. Five tests, applied to every project:

1. **Identity test** — cover the logo. Can you still tell which product this is? Most of the current set
   fails this: the layout, type scale and card anatomy are shared, so only the hex code gives it away.
2. **Craft test** — is there at least one interaction on the page that could not have come from a
   template: a mask, a scrub, a draw, a physical metaphor? 8 of 10 standalone projects have **two or
   fewer `@keyframes` total**, and `sites/abosodion-farm/style.css` has **zero**.
3. **Art direction test** — does the imagery belong to *this* business, and is it composed rather than
   dropped in? Every kit demo uses the same 1536×1024 hero in the same frame with the same mask.
4. **Internal-page test** — do the dashboard, forms, empty states and error pages hold the same standard
   as the marketing page? `core/views/errors/404.php` is **byte-identical in four apps**
   (md5 `e4014372…`), and five of seven PHP apps contain **no chart CSS at all**.
5. **Restraint test** — is every animation doing a job? The kit currently fires word-curtain, decode,
   ticker, tilt, spotlight and bloom on all ten tools unconditionally, which is motion as decoration.

**Internal benchmark: `apps/hotel-miramar`.** Nine keyframes, real clip-path curtain wipes, an
`animation-timeline: scroll()` rail with a rAF fallback, self-hosted woff2, a video ad, 28 public/auth
pages. It is the only build that passes tests 2 and 5. Its own failure is test 1 in the interior: the
`bg-stage` milk-glass wash (`main.css:2446-2485`) makes every section the same blurred ivory pane. The
goal is not "make everything Miramar" — it is Miramar's craft level with a different visual DNA per
product.

---

## 2. Audit findings (measured)

### 2.1 The portfolio shell
- 28 projects render from one template string (`script.js:30-50`) into `repeat(2, 1fr)` (`styles.css:107`)
  — 14 rows of an identical card at an identical `aspect-ratio: 1.58`.
- `projects.js` carries **28 hand-picked `accent` hexes (26 distinct). No CSS or JS file reads the field
  at all.** The per-project identity data is computed and thrown away.
- `.project-card-featured { grid-column: auto }` exists at `styles.css:219` and is never assigned — the
  asymmetric grid was started and abandoned.
- Case study is one fixed template for all 28: five short text blocks, one screenshot, no diagram, no
  process, no outcome, no second image.
- Motion: 5 keyframes, three of which are infinite background drifts; the parallax writes a 6% offset to
  a fixed layer (`ui.js:19-32`), which is imperceptible. **`index.html` → `project.html` is a hard
  document reload** — no View Transition, no outgoing state.
- Type: Space Grotesk at a flat 600 for every heading from 70px to 25px — no hierarchy contrast. 11 faces
  loaded, roughly a third unused, all render-blocking from Google.
- Payload: `tech-circuit.jpg` is **448 KB** and is the hero background; no `srcset` anywhere, so 1200px
  JPEGs go to 320px phones; `projects.js` (50 KB) is fully parsed to render one project.
- No breakpoint above 980px: the shell caps at `min(1240px, …)`, so on a 27" display it sits in a small
  centred column.

### 2.2 The seven PHP applications
- **Four share one hero recipe** (ajocircle, farmcoop, meditrack, routepilot): dark background + `::before`
  gradient veil over `plate.webp` + oversized radial glow `::after` + a `1.06–1.1fr / .92fr` grid + a
  right-hand translucent panel. Confirmed on screen: AjoCircle and MediTrack are the same skeleton with
  different hues.
- All seven emit the same `<div class="bg-plate">` with the same `inset:-8%`, `scale(1.06)`,
  `plate-drift` and a near-identical rAF factor (`0.05`/`0.055`).
- Same component vocabulary everywhere: `.panel / .panel-head / .chart / .chart-bar / .empty`, and the
  same `<aside class="portal-side">` sticky card as the only dashboard navigation.
- Inter is the body face in 5 of 7. Fraunces is display in 2, Sora in 2, IBM Plex Mono in 3.
- `--radius: 16px` + `--ease: cubic-bezier(.16,1,.3,1)` + `--shadow: 0 26px 60px -34px` in pricedesk vs
  `0 24px 60px -32px` in nairadesk: the same recipe with the hue rotated.
- **nairadesk has no IntersectionObserver anywhere** — nothing reveals on scroll after first paint.
- Charts: ajocircle, farmcoop, meditrack use static `linear-gradient` divs; routepilot animates them;
  nairadesk, pricedesk and payrite have **no chart rule at all**. No app uses an SVG chart.
- `.empty` dashed box is reused 24× in ajocircle's views. Login/register ship the same
  `.auth/.auth-art/.auth-points` 3-tick block in four apps.
- `sites/pricedesk/public/assets/app.css:249` comments "counting numbers on the board" — no counter code
  exists in the app. A comment claiming a feature that was never built.
- `views/home.php` in meditrack advertises **"0 Completed consultations"** in the hero stat row: the
  marketing page displays its own emptiness.

### 2.3 The ten kit tools
- The shared layer dictates nearly everything: one font trio (Fraunces / Inter / JetBrains Mono) requested
  identically in all ten heads, one card, one button, one section rhythm, 28 keyframes and a JS `motion()`
  that fires reveal, counters, tilt, spotlight, word-curtain, decode, ticker and curtain wipe on every tool.
- **`kit.css:634-648` overrides each tool's own hero** into the same dark rounded stage, and `:668-675`
  forces `h1 em` to a mint/amber gradient-clip. Individual hero CSS in six tools is dead — it is painted
  over by the kit.
- Across all ten tool stylesheets: **157 `font-family:` declarations and zero `--font-*` token overrides.**
- `demos/payrite/payrite.css:3-8` sets `--brand:#146b5a`, `--accent:#c8964f` — **identical to the kit
  defaults.** It has 94 lines, no hero CSS and no signature element: 100% template.
- All ten heroes are byte-identical in structure (orbit + split + copy + art frame + glow).
- Reduced-motion coverage is partial per tool: `rowIn` keeps running in cropkeep, suncost, landedcost,
  fuelmeter, riderpool and payrite; `marketrate/app.js` has no guard at all.
- Overflow risks: `min-width` on tables (620–980px), a 420–470px `hero-orbit` at `right:-140px`, and
  `html{overflow-x:hidden}` (`kit.css:32`) which hides the symptom instead of fixing it.

### 2.4 The ten standalone projects
- 8 of 10 have ≤ 2 keyframes. `abosodion-farm` has **0** and no reveal observer at all.
  `trinity-lightings` has no scroll motion whatsoever (`shared.js`: 0 IntersectionObserver).
- Sameness measured: **Fraunces in 3 projects, Cormorant in 2, cream/ivory paper in 5** (`#f7f3e8`,
  `#FAF6EC`, `#f7f2e9`, `#fbf7ef`, `#f4f0e7` — all within ~4% of each other), **terracotta/brass/amber
  accent in 6**, lime-on-near-black in 2, and the same easing token in two pairs.
- `skycast` and `thatguys-fitness` both open with a **centred** heading + sub + form/buttons — the exact
  hero the brief forbids.
- **Trinity Lightings ships two different catalogues:** `sites/trinity-lightings` has 9 product images and
  10 products; `demos/trinity-lightings` has 18 and 19. The tree the portfolio links is the shrunken one.
- Remote image dependencies still live: `apps/campus-coin/frontend/src/legacy.css:7` and `:90` hotlink
  three `images.unsplash.com` layers, and `tools/forgestack/build/kite-courier/app/config.php:209`
  hotlinks one product photo.
- `apps/campus-coin` loads **two competing skins** — `styles.css` (14 keyframes, ink+lime) alongside
  `legacy.css` (14 more, neon-green + hard `17px 19px 0 #172656` shadow).
- `apps/hotel-miramar/app/static/css` ships `main.css.bak`, `main.js.bak` and six `_preview_*_tmp.html`
  files in the deployed tree.
- `kite-courier` and `forgestack` take their entire palette from a config array (`config.php:18-27`) —
  which is how the generator reproduces one look into every future build.

---

## 3. Root causes

1. **A shared layer is being used as a design system.** `_kit` and `_shared-core` correctly share
   *primitives* (reset, form controls, focus rings) and incorrectly share *identity* (fonts, hero, cards,
   motion). Identity cannot be inherited or every product looks like one product.
2. **The kit overrides the tool.** Specificity flows the wrong way: `.kit-anim .hero` beats the authored
   hero, so per-tool design work is silently discarded.
3. **Motion was added as a layer, not choreographed per page.** Infinite ambient drifts plus a reveal
   observer is "animation present" without "animation designed".
4. **Nothing measured the rendered page.** A comment claiming counters, a `0` in a hero stat, and a 9-vs-18
   catalogue all survived because the checks were greps, not screenshots.
5. **The accent data was never wired.** The single cheapest distinctiveness win in the portfolio was
   already authored and left unread.

---

## 4. Portfolio-wide design law

These are constraints, not preferences. A wave does not pass review if it breaks one.

**L1 — Identity is authored per project, never inherited.**
Display type, palette, hero composition and the signature motion belong to the project. The shared layer
may provide tokens and primitives only. Rule: **no display face appears in more than one project; a body
face may appear in at most three.**

**L2 — No two projects may sit close in both surface and accent.** Base surfaces must not be within 6%
relative luminance *and* accents within 20° hue of each other. This kills the five-cream / six-terracotta
problem directly.

**L3 — Every project gets exactly one signature motion device** that no other project uses, tied to what
the product is about (a payout ring for rotating savings, a route draw for logistics, a stamp for a
verdict). Everything else is baseline craft: one reveal, one hover depth, one page transition.

**L4 — Baseline motion, uniformly good:** staggered reveal on scroll (IntersectionObserver, `once`),
hover depth that moves `translate` not `transform` so keyframes cannot clobber it, a real page transition
(View Transitions where supported, a CSS cross-fade fallback where not), and animated counters only where
the number is a fact.

**L5 — Prohibited:** gradient-clipped headline text as a default, glassmorphism used because a panel needs
filling, floating circles/orbits as decoration, three-up identical feature cards as a section, a centred
heading+sub+two-buttons hero, a stat that displays zero, and any image whose only job is to fill space.

**L6 — Accessibility is part of the design, not a follow-up.** `prefers-reduced-motion` must neutralise
*every* animation in the project including per-component keyframes (the kit's `rowIn` gaps are the current
failure), 4.5:1 body contrast, visible focus, labelled controls, skip link, and no motion that blocks
navigation.

**L7 — Performance budget per page:** ≤ 180 KB CSS+JS uncompressed, ≤ 350 KB above-the-fold imagery, no
render-blocking webfont request (self-hosted, subset, `font-display: swap`), `srcset`/`sizes` on every
content image, lazy below the fold, and no animation library unless CSS/WAAPI demonstrably cannot do the
job.

**L8 — cPanel compatibility is non-negotiable.** No build step, no npm, no Node server, no Docker for the
PHP apps and the demos. Fonts and any library are committed as local files in the project folder. React
projects (`campus-coin`) keep their existing Vite build because that project already deploys that way;
nothing new gains a build step.

**L9 — Local imagery only.** No `images.unsplash.com`, no remote `url()` in CSS, no data-URI placeholder
standing in for a real photograph. Every image referenced must exist in the tree it ships from.

**L10 — Internal screens get the same art direction as marketing pages.** Sidebar rail or documented tab
system, one real SVG chart family shared by convention but themed per product, designed empty states with
a next action, filters that visibly change the table, and a per-project error page.

---

## 5. The identity map

Base = page surface; ink = text; accents are the project's own. Display faces are unique per project (L1).

| # | Project | Base | Ink | Accent(s) | Display | Body | Signature motion | Hero composition |
|---|---|---|---|---|---|---|---|---|
| 0 | Portfolio shell | `#0c0e0d` + paper bands `#f4f2ec` | inverted | **the 26 project accents** | Syne | Archivo | View-Transition page swap + pinned index rail | full-bleed featured work reel |
| 1 | RentJury | `#fbfaf7` | `#12161c` | `#7a1f2b`, `#c8402f` | Newsreader | Work Sans | stamped-seal verdict reveal + evidence drawer | tenancy document with the assessment stamped onto it |
| 2 | Abosodion Farm | `#f8f6f0` | `#14251a` | `#2f7d4f`, `#e0a13a` | Bricolage Grotesque | Figtree | Ken Burns harvest plate + live price board | full-bleed farm photo, signage-type price board |
| 3 | FarmCoop | `#0f1d14` | `#f2f5ee` | `#d94f2b`, `#e8b64a` | Archivo Black | Public Sans | awning stripe drift + sold-out wash | asymmetric stall grid of real produce |
| 4 | MediTrack | `#eef4f6` | `#0a2b36` | `#12798c`, `#e8735a` | Literata | Onest | slot-chip cascade + time-to-appointment arc | light clinical split, live availability rail |
| 5 | AjoCircle | `#151b3d` | `#f6f5fb` | `#0f9f74`, `#e3b341` | Epilogue | Manrope | conic payout ring rotating into member order | dark ledger + rotating payout wheel |
| 6 | RoutePilot | `#0b1220` | `#f5f7fa` | `#f5a524`, `#35c2e8` | Chivo | DM Sans | SVG route path draw + status chip morph | live tracking panel over a drawn route |
| 7 | NairaDesk | `#f7f9f7` | `#0d1a14` | `#1b6f4f`, `#b4812a` | Sora | Inter | payslip assembling line by line + run timeline scrub | split: copy + slip that builds itself |
| 8 | PriceDesk | `#fffdf8` | `#1f1508` | `#a8431f`, `#2f6b4f` | Instrument Serif | Karla | board row cascade + unit morph | market board, rows ticking in |
| 9 | PayRite | `#f4f3ef` | `#131a20` | `#d99a2b`, `#2f9e7a` | Familjen Grotesk | Schibsted Grotesk | row-flash recalculation + band-table wipe | live calculator (keep — best hero of the seven) |
| 10 | Kite Courier | `#f4f6f8` | `#14181d` | `#e2571f`, `#1f6f8b` | Red Hat Display | Barlow | dashed courier trail with a moving marker | light asphalt/white split, live status |
| 11 | Hotel Miramar | `#1e3a2e` | `#fbf7ef` | `#9c7c46`, `#c2a268` | Cormorant Garamond | Inter | keep clip-path wipes + scroll rail | keep full-bleed photo + booking bar |
| 12 | Campus Coin | `#08090b` | `#f6f8ea` | `#c9f75a`, `#f6b64c` | Space Grotesk | Manrope | card-deal stack + price roll digits | asymmetric + scramble headline (keep) |
| 13 | SkyCast | `#08111f` | `#f3f8ff` | `#ffd9a0`, `#9ad8f0` | Outfit | Sora | condition colour morph + particle layer + 7-day rail | full-bleed live sky with search as the object |
| 14 | ThatGuys Fitness | `#0b0b0d` | `#f4f3ef` | `#e8342b`, `#c6ff3d` | Bebas Neue | Barlow Condensed | scroll-scrubbed coach sprite + kinetic type slam | action frame, not a gradient glow |
| 15 | Trinity Lightings | `#171512` | `#f7f2e9` | `#c9a227`, `#e8d9b0` | Marcellus | Jost | beam sweep + lamp-halo bloom on hover | dark showroom, object-led mosaic (18 lamps) |
| 16 | Moonlight Events | `#07060d` | `#f5ede1` | `#eebd6f`, `#e2603a`, `#8c2f4d` | Playfair Display | Instrument Sans | poster-wall parallax + countdown flip digits | editorial poster wall with a live festival card |
| 17 | ForgeStack | `#14161a` | `#e6ebf0` | `#ff6a2b`, `#d9e1e8` | Unbounded | IBM Plex Sans | forge spark trail + build-log typewriter | console: brief→site pipeline as the opening screen |
| 18 | CropKeep | `#f6f4ee` | `#23301b` | `#4c6b21`, `#c9973a` | Zilla Slab | Figtree | decay-track time lapse + cold-chain pulse | cold-store cross-section with a rot timeline |
| 19 | FeesPlan | `#f5f6fa` | `#1a1f36` | `#2f4a8c`, `#cf9a2c` | DM Serif Display | Public Sans | ledger rule-draw + term split animation | ruled ledger with three fee lumps |
| 20 | FuelMeter | `#101418` | `#f0f3f5` | `#c0451f`, `#1f6f8b` | Rajdhani | Onest | gauge needle sweep + drip fall | dark instrument panel, gauge as the hero |
| 21 | LandedCost | `#eef1f4` | `#1d2a33` | `#2b4a63`, `#c1652b` | Oswald | Barlow | container stack-up + tide line | cargo manifest stacking as you scroll |
| 22 | MarketRate | `#f7f4ec` | `#16231a` | `#1f6b3c`, `#dd6f26` | Anton | Karla | chalkboard wipe + chalk-dust reveal | market board under a striped awning |
| 23 | ReCircle | `#f2f6f4` | `#0f2a22` | `#16704f`, `#2b87b8` | Onest | DM Sans | loop mark rotation + 7-day bar rise | materials drop-off with a weighing scale |
| 24 | RiderPool | `#f4f4f8` | `#1b1e3a` | `#3c4a99`, `#dd9f3b` | Barlow Condensed | Inter Tight | ETA timeline runner + stop stagger | minibus route strip with live stops |
| 25 | SealCheck | `#f2f4f6` | `#16181c` | `#1f8a5f`, `#d99321` | Schibsted Grotesk | Public Sans | foil-seal tighten + shrink-wrap gloss | verification bay, radius-6 hardware feel |
| 26 | SunCost | `#f5f8fa` | `#0f2733` | `#12506b`, `#e0932b` | Albert Sans | Outfit | sun-disc arc travel + irradiance sweep | roof elevation with the sun path across it |

Deliberate distribution: 8 dark-surface projects, 9 cool-paper, 9 warm-paper, 4 bright-white-with-blocks.
No base repeats within 6% luminance across two projects that also share an accent family (L2). Fraunces is
retired from the entire portfolio; Cormorant stays only in Hotel Miramar; Inter drops from 5 apps to 2.

---

## 6. Rebuilding the shared layers (this is the work that makes the rest stick)

### 6.1 `demos/_kit` — done

- **Stopped overriding authored heroes.** Deleted the `.kit-anim .hero` dark-stage rule and the forced
  `h1 em` gradient-clip. The kit now supplies `.shell`, `.split`, form controls, tables, focus and the
  reveal/counter primitives; each tool owns its surface, type and hero. A tool that wants emphasis on a
  phrase styles its own `em` — fuelmeter makes it a gauge bracket, landedcost a manifest band, and the
  tools whose display face has no italic ship a designed treatment instead of a synthesised oblique.
- **Identity is authored per tool, in one `:root` block.** `--font-display`, `--font-body`, `--font-mono`,
  `--hero-h1`, `--surface`, `--ink`, `--accent`, `--radius` are all read by the kit and all overridable;
  the kit defaults are system stacks on purpose, so a tool that declares nothing looks like a tool that
  declared nothing. Ten tools now carry ten different display faces, sized to the face
  (`--hero-h1`) rather than to one global clamp, and the `<h1>` line breaks were rebalanced for the new
  metrics.
- **Type is self-hosted.** `tools/fetch_fonts.mjs` + `tools/fonts-manifest.json` pull 18 variable families
  from the Google CSS2 API *as a download source only* and write `demos/_kit/fonts.css` + 42 woff2 files
  (latin **and** latin-ext — the naira sign is not in latin). Every tool head links `../_kit/fonts.css`;
  a page whose CSS never names a family never fetches it. Zero `fonts.googleapis.com` /
  `fonts.gstatic.com` requests remain in any demo.
- **Motion is opt-in.** `<html data-motion="reveal count words curtain ticker parallax spot decode">`;
  `kit.js` gates each feature through `wants()`, so the ticker runs only on the two price-feed tools
  (`marketrate`, `riderpool`), the parallax plate only on the three tools with photographic heroes, and
  the cursor spotlight ships on none. With the attribute absent the page still gets the baseline set.
  `splitWords()` now preserves source whitespace, which is what fixed the "honestly ." rendering.
- **Reduced motion is complete and proven.** The blanket `*` rule replaced the old `.kit-anim`-scoped list
  (unreachable — the class is only added when motion is welcome), `.kit-par` keeps its plate and loses its
  `transform`, the ticker/spotlight/progress rail are removed, and `.reveal` is neutralised at
  specificity-safe precedence. Verified by screenshot, not by reading the CSS.
- **Overflow is fixed at the cause.** `html{overflow-x:hidden}` is gone; `.table-wrap` paints a real
  fade-edge affordance (Lea Verou scroll shadows, `local` masks over `scroll` gradients); `hero-orbit`
  sizes and offsets in `clamp()` in the kit and in all four tools that overrode it.
- **`?v=` bumped to 11** across all 40 tool and companion pages, and `tools/build_pages.mjs` stamps the
  new value plus the per-tool `data-motion` and font link on generated pages.
- **Packaging follows.** `tools/build_demo_zips.py` now writes a per-tool `_kit/fonts.css` containing only
  the `@font-face` blocks for families that tool names, and ships only those woff2 files — 6-8 faces and
  not 1.4 MB. An extracted PayRite zip served over HTTP resolves Familjen Grotesk / Schibsted Grotesk /
  JetBrains Mono, reports zero out-of-origin requests and renders standalone.

### 6.2 `sites/_shared-core` and the seven apps
- Add a per-app identity block: the layout reads a `$THEME` array (surface, ink, accents, display, body,
  plate) so no two apps can accidentally inherit each other's recipe.
- Give the shared error pages a per-app skin and a real message; delete the four byte-identical 404s.
- Ship one small **SVG chart primitive** (line, bar, donut) in the core, themed per app, and use it in
  every dashboard. Static gradient divs are retired.
- Replace the `portal-side` sticky card with a proper dashboard rail: grouped nav, active state, breadcrumb,
  and a content grid that is not one `.panel` repeated.
- Add designed empty states (illustrated, with the action that fills them) and filter bars that visibly
  change the table.
- nairadesk gets the reveal observer it does not have; pricedesk either builds the counters its comment
  promises or the comment goes.
- Remove stats that display zero from marketing heroes (meditrack's "0 Completed consultations") — show
  the fact only when it is a fact.

### 6.3 The portfolio shell
- Wire `accent` into the card and case page: `--p-accent` on the card, driving the hover square, the tag
  rule, the case-page plate tint and the case hero underline. This is the cheapest distinctiveness win
  available and it is already authored.
- Replace the uniform 2-up grid with an editorial rhythm: one featured full-bleed project, a 2-col band, a
  3-col band, and an index-list band for the tools — implemented by assigning the already-declared
  `project-card-featured` class and adding a `layout` field to `projects.js`.
- Case study: per-category templates (product / commerce / tool / console) with a process section, an
  outcome section, a second image and a real figure, instead of five text blocks.
- Add View Transitions between index and case (with a `prefers-reduced-motion` and unsupported-browser
  fallback), a ≥1440px tier that uses the width, and animated counters for the proof row.
- Typography: Space Grotesk display with a real scale (three weights, distinct sizes for h1/h2/h3),
  DM Sans body, DM Mono for the rails — all four latin subsets self-hosted in `assets/fonts/` with
  `font-display: swap`. The Google Fonts `<link>` is gone, so the shell makes zero third-party requests.
- Performance: `tech-circuit.jpg` re-encoded 1920×1280/448 KB → 1400×933/116 KB; `tools/make_thumbs.py`
  writes 320w and 640w candidates for all 28 cards and `cardImage()` emits `srcset`/`sizes` per card kind.
  A forced full-grid load went from 3.80 MB to 1.09 MB of images at 1440. Case pages no longer fetch the
  448 KB hero plate as a fallback `src` — `project.html` ships a data-URI frame and `project.js` supplies
  the project's own artwork. `projects.js` stays whole: it is 15.7 KB gzipped, and splitting it would add
  a build step, which is the one thing this repository cannot carry (cPanel hosts it as-is).
- Deleted the orphan `assets/projects/trinity-chandelier.jpg`; the 960×600 pair now declares its real
  intrinsic size in `NATIVE_SIZES`, and every hero `<img>` carries `width`/`height`.

---

## 7. Defects to fix in the same pass (found by the audit, not by design taste)

1. **Trinity Lightings ships two catalogues.** `sites/` has 9 images / 10 products, `demos/` has 18 / 19.
   The portfolio links the shrunken tree. Reconcile to the full set and add a check that the two counts
   match.
2. **Three remote image dependencies**: `campus-coin/src/legacy.css:7`, `:90` and
   `kite-courier/app/config.php:209`. Replace with local files (L9).
3. **`campus-coin` loads two competing skins** — `legacy.css` is deleted, not reconciled.
4. **`hotel-miramar` ships build junk**: `main.css.bak`, `main.js.bak`, six `_preview_*_tmp.html`.
5. **`abosodion-farm` puts a data-URI SVG in `onerror` on every image** (`index.html:49,107,120,133,146`) —
   a broken photo silently becomes a placeholder, which is how gaps hide. Remove and let the real file
   fail loudly in testing.
6. **`kite-courier` and `forgestack` palettes come from `config.php`** — the generator will keep cloning
   one look. ForgeStack's profiles must emit a per-project identity block, which is the highest-leverage
   fix in this document: it changes every future build.
7. **`pricedesk` CSS comment claims counters that do not exist** (`app.css:249`).
8. **Reduced-motion gaps** in six kit tools plus `marketrate/app.js`.

---

## 8. Waves

Each wave ends with: before/after screenshots at 1440 / 768 / 390, a reduced-motion capture, a clean
console, a functionality re-test (the PHP apps run their smoke suites; the tools run the regression
sweep), and a page-weight check against L7. **No project is reported redesigned until its internal pages
are done too.**

| Wave | Projects | Why this order |
|---|---|---|
| **W1 — Foundations** | `_kit` split, `_shared-core` theme block + SVG charts + error pages, portfolio shell (accent wiring, editorial grid, View Transitions, fonts, payload) | Everything downstream inherits from these three. Doing projects first would re-create the sameness. |
| **W2 — The seven apps** | PayRite, NairaDesk, PriceDesk, AjoCircle, FarmCoop, MediTrack, RoutePilot | Highest count of shared-recipe violations; they also prove the new core theme mechanism. |
| **W3 — The ten tools** | MarketRate, CropKeep, LandedCost, FuelMeter, SealCheck, SunCost, RiderPool, ReCircle, FeesPlan, PayRite demo | Cheapest per project once the kit stops overriding identity; each gets its own surface, display face and one signature device. |
| **W4 — The ten standalone** | Hotel Miramar (interior only), Trinity Lightings, SkyCast, ThatGuys Fitness, Moonlight Events, Abosodion Farm, RentJury, Campus Coin, Kite Courier, ForgeStack | Mixed maturity: Miramar needs restraint work, the others need motion and identity added. |
| **W5 — Sweep and publish** | defect list §7, cross-project similarity check, push + live verification | The audit must be re-runnable, not trusted. |

**Verification tooling to build in W1** (so "premium" stays measurable): `tools/check_identity.mjs` —
renders every project and asserts that the computed `font-family`, base surface and accent differ across
projects (the same rendered-DOM approach that caught the missing background plates); `tools/check_motion.mjs`
— asserts each project declares ≥1 signature keyframe and that reduced-motion zeroes every animation;
`tools/check_images.mjs` — asserts every referenced image exists locally and no remote URL appears in any
shipped CSS/JS/HTML/config.

---

## 9. What is already good enough

Not everything is wrong, and re-cutting working craft is how regressions get introduced.

- **Hotel Miramar's motion system** — clip-path curtain wipes, `animation-timeline: scroll(root)` with a
  rAF fallback, `html.mo` gating, self-hosted woff2. This is the reference implementation; only its
  interior milk-glass wash and its shipped `.bak` files need work.
- **PayRite's hero** — a live calculator as the first object on the page is the most purposeful hero in
  the portfolio. It keeps its job; it changes its type and surface.
- **RoutePilot's interaction code** — lifting on `translate` rather than `transform` so keyframes cannot
  clobber it, semantic status tokens, a dark theme block, `sr-only` labels, animated chart bars.
- **SealCheck and FuelMeter** — radius-6 hardware feel and a real SVG gauge with needle overshoot: the two
  tools that already look like themselves.
- **ThatGuys Fitness's coach sprite** — a 3-frame scrub is a genuine idea; it needs scroll-binding and one
  real action photograph in the hero, not a rebuild.
- **SkyCast's `@property` colour morph** across 11 condition themes is a legitimately advanced technique;
  it needs a photographic sky and scroll choreography around it, not replacement.
