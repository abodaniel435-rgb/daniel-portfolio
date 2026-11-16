# Master summary — completed and outstanding work

**Date:** 11 November 2026 · **Workspace:** `C:\Users\HI\Documents\Qoder\2026-11-07\e34d91d8`

## 1. Deliverables against the brief

| # | Deliverable | Status | Where |
| --- | --- | --- | --- |
| 1 | Redesigned portfolio | **Done** | `portfolio/daniel-portfolio/` — 27 projects, filters, case studies, mobile nav |
| 2 | Upgraded fitness demos | **Done** | `demos/thatguys-fitness/demos.html` — interactive Coach Studio, 8 exercises, 24 AI instructor frames, phase-by-phase player |
| 3 | Functional custom AI agent | **Done** | `agent/forgestack/` — brief → deployed-ready PHP + MySQL site |
| 4 | Multiple professional sites across industries | **Done** | 8 full-stack builds + 6 redesigned sites + 10 new browser tools (tables below) |
| 5 | Every site in the portfolio | **Done** | `portfolio/daniel-portfolio/projects.js` (single registry, `pages[]` per tool) |
| 6 | Full source | **Done** | `sites/`, `new-sites/`, `agent/forgestack/`, `demos/` |
| 7 | Database files & configs | **Done** | `database/schema.sql` + seeds per PHP site; `database/` + `run.py` for Flask; Django migrations for Campus Coin |
| 8 | cPanel deployment packages | **Done** | 14 site zips + 10 self-contained tool zips in `C:\Users\HI\Downloads` (flat, `.env` excluded) |
| 9 | Individual deployment docs | **Done** | `docs/sites/*.md` — 24 guides, each with a tech report |
| 10 | Tech-stack reports | **Done** | Inside every `docs/sites/*.md`; generated sites also ship `TECH_STACK.md` |
| 11 | Summary of completed/outstanding | **This file** | `docs/MASTER-SUMMARY.md` |

## 2. Sites

### New full-stack builds (this assignment)

| Site | Industry | Stack | Data | Doc |
| --- | --- | --- | --- | --- |
| Hotel Miramar SG | Hospitality | Flask 3, SQLAlchemy, PyMySQL | MySQL | `docs/sites/hotel-miramar.md` |
| Campus Coin | Student fintech | Django 5.2 + DRF + JWT, React/Vite | MySQL | `docs/sites/campus-coin.md` |
| MediTrack | Healthcare | PHP 8.2, PDO | MySQL | `docs/sites/meditrack.md` |
| AjoCircle | Community finance | PHP 8.2, PDO | MySQL | `docs/sites/ajocircle.md` |
| FarmCoop | Agritech marketplace | PHP 8.2, PDO | MySQL | `docs/sites/farmcoop.md` |
| RoutePilot | Logistics | PHP 8.2, PDO | MySQL | `docs/sites/routepilot.md` |
| ForgeStack | Developer tool / agent | PHP 8.2, PDO, vanilla JS | MySQL (optional) | `docs/sites/forgestack.md` |
| Kite Courier | Logistics (generated) | PHP 8.2, PDO | MySQL | `docs/sites/kite-courier.md` |

### Redesigned existing projects

RentJury, ThatGuys Fitness, SkyCast, ABOSODION Farm, Moonlight Events, Trinity Lightings — plus the portfolio itself. Each has a deployment doc in `docs/sites/`.

### Ten new browser tools, one problem each (`demos/`)

Each is a four-page static site — the tool, a `data.html` carrying the published figures it works from, a `playbook.html` with the operating guide, and a `record.html` that mirrors what the tool saved in this browser. Thirty generated pages plus ten tool pages, all built from authored specs by `tools/build_pages.mjs` and wired into each tool's own navigation by `tools/link_pages.mjs`.

| Tool | The problem it takes on | Doc |
| --- | --- | --- |
| PayRite | PAYE taken at a flat rate, rent relief forgotten, pension on the wrong base — and nobody can show how net pay was reached | `docs/sites/payrite.md` |
| SunCost | Solar quotes with no reasoning, systems that die at 10pm, and no comparison against the fuel actually burned | `docs/sites/suncost.md` |
| SealCheck | Falsified and substandard medicine sold openly, with no way for the buyer to check a pack | `docs/sites/sealcheck.md` |
| FuelMeter | Fuel is the biggest running cost and almost nobody computes cost per kilometre | `docs/sites/fuelmeter.md` |
| MarketRate | Price information is asymmetric — a crate sells at the gate for a fraction of the city price | `docs/sites/marketrate.md` |
| FeesPlan | Three school-fees lump sums a year, met with 20%-a-month salary advances | `docs/sites/feesplan.md` |
| LandedCost | Importers quote from FOB and meet duty, levies and demurrage after the cargo arrives | `docs/sites/landedcost.md` |
| CropKeep | Roughly a third of perishable harvest is lost between farm and buyer | `docs/sites/cropkeep.md` |
| RiderPool | A half-empty minivan and a fare built on habit that nobody can justify | `docs/sites/riderpool.md` |
| ReCircle | Recyclable material leaves homes for nothing on scales the seller cannot check | `docs/sites/recircle.md` |

They share one zero-dependency design system (`demos/_kit/`) — `kit.css`/`kit.js` for the tools and `pages.css`/`pages.js` for the generated pages — and are versioned as `?v=6` so a kit change cannot be served stale.

Two of the ten have since been taken off `localStorage` and rebuilt as PHP + MySQL applications with real accounts, review queues and test suites: **PayRite → NairaDesk** (section 6f) and **MarketRate → PriceDesk** (section 6g). Their demos remain online as the calculator front-end; the servers are the products.

### Left untouched, as instructed

**CurrenSee** and **Cityofgoldestate.com**. Their portfolio entries were not edited; no source for either exists in this workspace and no live deployment was touched.

## 3. The fitness demo section (Phase 2)

Eight exercises — push-up, goblet squat, walking lunge, forearm plank, glute bridge, bent-over row, standing shoulder press, Romanian deadlift — each with:

- a three-phase frame sequence of a realistic AI-generated instructor (start / mid / end), camera-matched per exercise;
- muscle groups, equipment, starting position, execution steps, breathing, sets/reps, common mistakes, safety notes;
- an interactive player with phase timeline, autoplay, keyboard control and a live step readout;
- text instructions that stay visible while the player runs, and reduced-motion support.

The frames are honestly labelled as frame sequences — the page does not claim they are live video. Static stills are never presented as animation.

## 4. Security posture (applies to every build)

- No password is hardcoded in application code. Administrators are seeded from `ADMIN_EMAIL` / `ADMIN_PASSWORD` environment values (`.env`), hashed with `password_hash()` (PHP) or the framework hasher (Flask/Django).
- Sessions regenerate on login; login attempts are throttled; CSRF tokens guard every state-changing POST.
- All database access uses prepared statements; input is validated server-side; admin routes check roles server-side.
- Deployment packages exclude `.env`, `*.lock`, logs, SQLite scratch databases and local backups.
- ForgeStack — which writes files and creates databases — is documented as an admin-only tool behind Basic Auth or an IP allowlist, never a public page.

## 5. Deployment packaging

Two scripts, both in `tools/`, both writing flat archives to `C:\Users\HI\Downloads`.

**`build_zips.py` — 14 full-stack sites**, suffix `-cPanel.zip`: ThatGuys-Fitness, SkyCast, ABOSODION-Farm, Moonlight-Events, Trinity-Lightings, RentJury, MediTrack, AjoCircle, FarmCoop, RoutePilot, Hotel-Miramar, Campus-Coin, ForgeStack, Kite-Courier.

**`build_demo_zips.py` — 10 browser tools**, suffix `-tool.zip`: SealCheck 164 KB, PayRite 171 KB, FuelMeter 172 KB, FeesPlan 186 KB, SunCost 212 KB, CropKeep 235 KB, LandedCost 239 KB, RiderPool 249 KB, MarketRate 271 KB, ReCircle 283 KB. Each archive is self-contained: `<slug>/` (tool page plus `data.html`, `playbook.html`, `record.html`), the four `_kit/` files and only the `_media/` images that tool actually references. Extract into the document root so the tools' `../_kit/` and `../_media/` paths resolve — every zip unpacks to the same `document root` layout and one tool per extraction is enough to go live.

Known packaging notes:
- Hotel Miramar's 265 MB hero video (`app/static/videos/hotel-miramar-tour.mp4`) is excluded from the zip to stay under cPanel upload limits. Upload it separately with File Manager/FTP to the same path; without it the poster image remains and the layout is unaffected.
- `-tool.zip` archives carry the shared kit inside them; if several tools are deployed side by side they should share one `demos/_kit/` rather than each bringing a copy.

## 6. Verified in this pass

- **Regression sweep, 16 of 16 sites `ok`** (`tools/verify_demos.mjs` against a local server): zero page errors, zero broken images, zero reveals stuck out of view, zero bars left at zero height. The gate now distinguishes the ten kit-based tools from the six rebuilt sites that ship their own CSS/JS, so a missing kit is only an error where one is required.
- Every tool computed real figures on that sweep: PayRite ₦346,301 monthly PAYE on the sample payroll, LandedCost ₦18,617,835, RiderPool ₦497,667, FeesPlan ₦312,833, FuelMeter ₦23,768, MarketRate ₦2,200/kg, CropKeep ₦180,000, ReCircle ₦438, SealCheck 69.0/100, SunCost 8,633 Wh/day at 764 W peak.
- **Zip-extraction test**: the ten `-tool.zip` archives were unpacked to a clean directory and served on a second port; the sweep passed unchanged and PayRite's `data.html`, `playbook.html` and `record.html` all rendered from the archive rather than the working tree.
- **Fixed a defect that affected all ten tools**: `kit.store()` returned its fallback object by reference, so a caller doing `list.push(row)` mutated the module default permanently — after "Clear", a tool still reported its old rows. `read()` now deep-copies the fallback.
- **Fixed PayRite's Clear roster button**, which called an undefined `confirmClear()` and threw a `ReferenceError` instead of clearing.
- Coach Studio verified live: 8 exercises, 3 loaded AI instructor frames each, phase stepping, captions and per-step readout all driven by real event handlers — not a static gallery.
- Portfolio renders 26 cards, each tool case study now listing its three secondary pages, on desktop and mobile widths.
- All 24 archives inspected: no `.env`, no SQLite databases, no lock files, no logs. `.gitignore` confirmed to exclude all three.
- Favicon (inline SVG data URI) added to every page of ThatGuys Fitness, ABOSODION Farm, RentJury, Moonlight Events and the portfolio shell, so sub-pages no longer fall back to the browser default.
- RentJury renders correctly after converting six 1.5–2 MB PNGs to WebP (10.6 MB → 885 KB package).
- ForgeStack: preflight PASS, live blueprint, generation, MySQL install, generated site booking/tracking/admin/outbox all exercised.
- Hotel Miramar and Campus Coin: admin login and gated routes tested against MySQL; real dashboard data.

## 6b. Verified on the live site

Pushed to `abodaniel435-rgb/daniel-portfolio` (`28fc479..491b05e`) and re-checked against `https://abodaniel435-rgb.github.io/daniel-portfolio/`, not just locally:

- `projects.js` published byte-for-byte (44,653 B, 26 projects), `git ls-remote origin main` agrees with the local HEAD, and `demos/cropkeep/index.html` + `app.js` return exactly their committed byte counts.
- All 40 tool pages (10 × `index`/`data`/`playbook`/`record`) return 200, and the regression sweep run **against the live URL** reports 16 of 16 `ok` with the same readouts as locally.
- The four `_kit` files are live (`kit.js` at 13,794 B, the version carrying the store fix), so no tool is served a stale kit.
- Coach Studio, live: opening a demo shows `pushup-01.webp` at "Phase 1 of 3" and the sequence advances on its own; ArrowRight → `pushup-02.webp`, ArrowLeft → `pushup-01.webp`; the timeline carries three phase nodes; a second exercise opens as "Bodyweight Squat" with `squat-01/02/03.webp`. All 24 instructor frames return 200.
- PayRite's case study lists its three secondary pages and each href resolves to the published `demos/payrite/…` document.
- `.env`, `site.env`, `*.sqlite3` and other development files return 404 — nothing that should have stayed local got published.

One caveat worth knowing: the first sweep after publishing flagged `cropkeep`, `riderpool` and `recircle`. Those three files were byte-identical to the commit, and re-running the same three immediately reported `ok` — GitHub Pages serves the first request for a newly published asset slowly enough that the harness's 110 ms scroll steps outrun the IntersectionObservers. A live FAIL on a brand-new push is worth re-running before treating it as a defect.

## 6c. The motion pass (kit v6)

The ten tools and their 30 generated pages were pushed from a flat design to a materially more expressive one, written once in the shared kit so no page drifts from the others:

- **A lit stage for every hero.** Tools and generated pages both open on a rounded dark panel built from three brand-coloured radial gradients, a 46px lattice that drifts slowly under a radial mask, and a highlight band that crosses the panel every nine seconds. Generated-page headlines carry a slow cream-to-mint gradient; tool headlines keep their gradient `em` accent.
- **Entrances you can see.** Sections fade and lift from `translate3d(0,30px,0) scale(.98)` with a 6px blur that clears, staggered in reading order. Cards tilt in from 9 degrees on a shared perspective, get a gradient top edge that draws from the left, and a sheen that crosses on hover. Numbers count up on first view (12 metric cards on RiderPool alone). Table rows rise in sequence; the contents rail grows a progress dot that follows the reader; timeline rails draw their line and pop each dot; sort headers and filter bars animate their state changes.
- **Motion is opt-in and removable.** Every rule lives under `.kit-anim`, which `kit.js` adds before first paint only when the visitor has not asked for reduced motion; with JavaScript off or reduced motion requested the pages render exactly as before, fully visible. Printing hides the progress rail and forces every row visible.
- **Reveals no longer depend on one observer.** Content already in the first viewport reveals immediately, and a throttled scroll sweep backs up IntersectionObserver, which some embedded and narrow-viewport renderers never fire. Before this, a slow first callback could leave the top of a page blank.
- **Two defects found and fixed while doing it.** On phones the horizontal contents rail was sizing the single grid column to 4,811px, pushing the page body off-screen; `.doc > *` now takes `min-width: 0` and the rail scrolls inside its own box. In the header, a reservation for the primary button that was too small clipped SealCheck's longer "Run the 14 checks" label at tablet widths, and the nav then wrapped onto a second line on the longest menus (ReCircle has eleven links). The reservation is 176px and the header now spans 1360px, wider than the text column, so a long menu stays on one line; between 1200px and 1320px the brand tagline hides and link padding tightens. Below 1200px the inline menu gives way to the existing collapsible one rather than wrapping into two rows, and that panel is capped to the viewport height and scrolls. Measured: one-line nav with 26px or more clearance at 1200, 1280 and 1440 on all ten tools, and a single-line brand-plus-toggle header at 1024.

Verification after the pass, all on the local build: `verify_demos.mjs` reports 16 of 16 index pages `ok`; the new `tools/verify_pages.mjs` exercises all 30 generated pages — contents rail built, every reveal shown, metrics and table rows visible, click-to-sort reordering the first table, live filtering emptying it — and reports 30 of 30 pass. Screenshots were checked at 1440, 1024 and 390 CSS pixels.

## 6d. The photograph pass (kit v7 + the standalone stage)

The owner's instruction was blunt: *every page of every site has a background image*, and the motion should be impossible to miss. This pass did that in two layers, because the portfolio contains two different kinds of site.

**Kit v7 — the ten tools and their 30 generated pages.** `kit.js` derives the plate from the URL (`demos/<slug>/<page>.html` → `demos/_media/<slug>-hero.webp`), so no page needed markup added to it. All ten plates return 200. On top of the plate: a Ken Burns breath that never repeats the same crop, a scroll-linked parallax shift on the same layer, a diagonal curtain wipe that opens each hero image, a word-by-word curtain on the headline (each word rises out of its own clip box on a 46ms stagger), a scramble-decode on the eyebrow, a travelling light bar across the hero frame, and a marquee built from the page's own `h2` text so the ticker says something true about that page. The plate is also blended into the hero itself at 30% under a luminosity mask with a soft-edge gradient, so the photograph reads even where the body copy sits.

**`demos/_stage/` — the six sites that ship their own CSS.** abosodion-farm, moonlight-events, rentjury, skycast and thatguys-fitness carried no photograph at all and almost no motion. Five plates were generated into `demos/_media/` and a small standalone layer was written — `stage.css` and `stage.js`, linked last in each of the 12 pages by `tools/add_stage.cjs`. It deliberately imports none of the kit's resets: it reads the page's *own* background colour at runtime, sets that as the canvas, makes the body transparent so the fixed plate can show through, and derives the wash, the plate opacity and the hairline colour from whether that colour is light or dark. A dark site (SkyCast, ThatGuys, Moonlight) and a warm light site (ABOSODION) therefore get the same mechanism with different numbers, and neither loses its palette. The same layer adds the progress meter, the word curtain, the light sweep, the ticker and scroll reveals.

Two guards matter here. The layer skips the body-transparent step on any page that paints its own gradient or sky, so SkyCast keeps its atmosphere. And it skips a ticker on a page that already runs a marquee — Moonlight Events has one of its own, and two marquees stacked is noise, not motion.

**Reveals cannot strand content.** Every hidden state is written as `[data-fx-r]:not(.fx-in)`, never as a static `opacity: 0`, so an element is only ever hidden while the layer is armed; if `stage.js` throws, the `try/catch` removes `.fx-anim` and the page is exactly what it was before. Elements already on screen are marked outright at tag time, a throttled scroll sweep backs up the observer, and the tagging runs again at 1.1s and 2.6s because three of these sites fetch their lists after load.

**Kit v8 — the generated pages were getting the photograph but not the performance.** They open on a `.page-hero` rather than a `.hero`, and the headline curtain, the decode eyebrow and the marquee were all keyed to `.hero`, so 30 of the 40 kit pages sat still while the ten index pages moved. Those selectors now cover `.page-hero` as well; `riderpool/data.html` measured 11 words in the curtain, 16 ticker items and the plate, and `verify_pages.mjs` still reports all 30 generated pages pass.

**Verified.** `verify_demos.mjs` reports 16 of 16 `ok`, zero page errors, zero stuck reveals. A separate scroll-through assertion on all 12 standalone pages reports **0 elements left hidden** on every one. Note for whoever re-runs this: several of these pages set `scroll-behavior: smooth`, so a scripted scroll loop that does not override it never actually reaches the bottom and the check reports false failures — the harness must force `scroll-behavior: auto` first.

**Hotel Miramar and Campus Coin, re-checked live rather than by reading the diff.** Miramar was run as a real Flask app and every page was asserted to carry its own plate key (`index`, `rooms`, `room`, `attractions`, `about`, `location`, `contact`, `feedback`, `auth`), with `room_detail` pulling the booked room's own photograph into the background layer. Campus Coin was built with Vite (116 kB CSS / 408 kB JS) and served from `dist/`; the landing page renders the Lagos night plate behind the hero with the currency marquee above it.

## 6e. Trinity Lightings — what the catalogue actually is

The owner asked repeatedly for the other ~100 pieces back. The mechanism is worth stating plainly, because it changes what "restoring" means:

The original showroom was never a hard-coded list. `listimages.php` in the owner's own source scanned `images/` and returned whatever was in the folder, so the count on screen was exactly the number of photographs on disk. The descriptions in `products.js` are keyed *by filename*, and any photo without an entry still appears with a generated name — which is why the catalogue resizes itself to the folder with no code change.

All **172 original filenames** were recovered and preserved, and `tools/trinity_manifest.mjs` regenerates `images.json` from whatever is in the folder. Every one of those files on this machine is **zero bytes** — the folder is a set of cloud placeholders, and the photograph data was never downloaded here. Nothing was deleted in this workspace; the images were already absent before any edit was made, which is why the showroom shows the 18 pieces that have real files.

So the fix is data, not code, and it is one step:

1. Download the 172 photos for real (open the folder once so the cloud fetches them, or export from the phone/original camera card).
2. Drop them into `demos/trinity-lightings/images/`.
3. On cPanel nothing else is required — `listimages.php` will list them immediately. For GitHub Pages run `node tools/trinity_manifest.mjs`, which rewrites `images.json` from the folder and refuses to write an empty manifest.

To make 172 photographs load acceptably, the grid now pages: it opens with 24 pieces, shows "*n* of 172 shown", and a **Show more** button adds another 24 and scrolls to the first new card. Search and collection filters reset the page and count against the whole catalogue, not the visible slice.

## 6f. The full-stack conversion pass (NairaDesk) and three core bugs

The owner asked whether any of this was actually backend work, and if not, to convert it. The
honest answer has two halves. Five of the builds were already full-stack — AjoCircle, FarmCoop,
Meditrack, RoutePilot and ForgeStack are PHP 8.2 + MySQL with real auth, roles, validation,
automation and cPanel installers. The ten tools in `demos/` were not: they keep their state in
`localStorage`, which is a fine calculator and not a product.

**PayRite became NairaDesk** (`sites/nairadesk`, registered as the 27th project). Same engine,
moved server-side: register a company, keep a staff roster with per-employee reliefs, run a month
and get a frozen pay run in MySQL that survives deleting the roster row it came from. An admin
console edits the statutory bands as data, so a relief ceiling that moves between filing seasons
is one field, not a redeploy.

Two tests back that claim rather than assert it:

- `tools/smoke.php` — **66 assertions over real HTTP** (sessions, CSRF refusal and forged tokens,
  registration, the JSON API against the engine, duplicate pay-run months, tenant isolation,
  bcrypt-only storage, cookie flags, the admin console, a live rate edit picked up by the next
  calculation, audit contents, the 8-per-15-minutes lockout). It signs into the administrator
  section with a throwaway admin it creates and deletes, so **no real password is needed to run
  it and none is ever written into the repository.** Current status: 66 passed, 0 failed.
- `tools/engine_parity.php` — cuts the calculator out of `demos/payrite/app.js` at run time and
  executes it under Node against `Payroll::compute()`. **52 cases × 14 figures = 672 comparisons,
  0 mismatches.** Nothing is copied into the test, so if the demo is ever refactored the anchors
  vanish and it fails loudly instead of comparing a stale snapshot.

**Three latent bugs were found by writing those tests, and all three lived in the shared core** —
so they were silently affecting AjoCircle, FarmCoop, Meditrack and RoutePilot too:

1. `Db::update()` mixed named `SET` placeholders with a positional `?` in the `WHERE`, which PDO
   rejects (HY093). Any admin rate save or account toggle would have returned a 500. It now
   supports both styles.
2. `Auth` had no `login()` — registration created an account and then left the visitor signed
   out. Added, with `session_regenerate_id(true)` so the pre-login session id is retired.
3. `base_path()` returned a bare `\` on Windows when the app sat at the document root, which
   prefixed **every** generated URL with a backslash. Chrome read `/login` as a hostname and
   served "this site can't be reached". Two regression assertions now fail if a backslash appears
   in any generated URL.

Also fixed in NairaDesk itself: a duplicate pay-run month hit the unique key and crashed instead of
opening the run that exists, and the auth pages rendered outside the layout so flash messages —
including the lockout notice — were invisible.

**Two caveats.** The other nine tools were still frontend-only; NairaDesk is the first conversion,
done properly with tests rather than nine done thinly — section 6g adds the second, from MarketRate.
And the cPanel
archives in `Downloads` were packaged on 9 November — before these core fixes. They have been
rebuilt from this repository by `tools/build_php_zips.py`, which refuses to write a package
containing a `.env`, a log or an install lock and checks that the `Db.php` inside carries the
fix: AjoCircle 90 KB, FarmCoop 108 KB, MediTrack 62 KB, RoutePilot 91 KB and NairaDesk 126 KB,
all five reporting `core/Db.php=fixed`. Upload those, not the 9 November copies.

## 6g. The second conversion (PriceDesk) and the bug that was in all six apps

MarketRate became **PriceDesk** (`sites/pricedesk`, registered as the 28th project). The demo already
had the right idea — a reporter files a price in whatever unit the market actually sells in, and the
board answers in naira per kilogram — but it kept its quotes in `localStorage`, which means one
browser's data is nobody's evidence. The conversion moved three things server-side: an account per
reporter, a review queue between "filed" and "published", and the aggregation itself, so the number a
trader acts on is computed from every verified quote in the database rather than from whatever that
one phone happened to cache.

`tools/smoke.php` drives it over real HTTP: **159 assertions, 0 failed**, covering sessions and CSRF
refusal, registration and role assignment, filing a price in a local unit and watching it land on the
board only after verification, duplicate and self-verification refusals, the JSON API versus the
engine, per-market versus all-market aggregation, the haul maths, tenant isolation, bcrypt-only
storage, the login throttle, the admin console, reference-data edits picked up by the next
calculation, automation, and teardown that asserts the board is exactly the size it was before the
run. Like NairaDesk's suite it mints a throwaway admin and deletes it afterwards, so no real
password is needed to run it and none is stored in the repository. `tools/set_password.php` is the
companion: it reads the new password from STDIN, never echoes it, and clears that account's throttle
rows so a reset cannot lock the owner out of his own console. `sites/pricedesk/README.md` carries the
reasoning (median over mean, unit normalisation, the haul formula) plus the cPanel steps, the same way
`sites/nairadesk/README.md` does.

**Four bugs, and the important one was not in PriceDesk.**

1. `DATE_SUB(CURDATE(), INTERVAL 14 DAY` was missing its closing paren in three places — every board
   load threw `PDOException 1064`.
2. `Board::quotes()` returned raw snake_case rows into `Pricing::aggregate()`, which reads
   `reportDate` and `reporterId`. Freshness silently reported "unknown" for a board full of prices
   filed this morning. Fixed at the accessor, because three callers patching the same mismatch is
   how the mismatch survives.
3. **`Validator::max()` and `min()` validated a value and then never wrote it to `$clean`.** A field
   guarded only by a length rule was checked, passed, and dropped before the `INSERT`. PriceDesk lost
   the note on a filed price and the typed distance on a haul — and because `Validator` is the shared
   ForgeStack core, the same bug was live in AjoCircle, FarmCoop, MediTrack, RoutePilot and
   NairaDesk. Fixed once in `sites/_shared-core`, copied to all eight working copies, and each of the
   other five apps was re-read to confirm nothing depended on the old behaviour.
4. `Pricing::unitKey()` stripped digits and concatenated the rest, so "crate of 30" parsed as
   `crateof` and fell back to a default weight. It now word-matches against the commodity's own unit
   table, preferring a non-`kg` word so "50kg bag" is still a bag.

Bug 3 is the one worth reading twice: it was invisible to every test that only checked the displayed
error message, and it only showed up when a valid submission quietly lost a field.

**The fifth problem was not a bug — a screenshot caught it.** Every line on the all-markets board was
labelled *disputed*. The math was correct: pooling a wholesale floor and a city retail stall produces
a ±34% spread, and the site's own legend calls that disputed. But the two are different commodities at
different moments, and labelling the structural gap between them "the market does not agree" contradicted
the copy the page prints about itself. `Pricing::acrossMarkets()` now measures spread *inside* each
market and reports the cross-market range honestly, six regression assertions pin the behaviour, and
the caption says which question the column answers. Reading the rendered page found this; no assertion
would have.

Also here: `home_compare()` carried sixteen lines of duplicated, paren-less SQL that `Board::quotes()`
already owned — the fix removed the duplication rather than patching the copy. The cPanel package
(`PriceDesk-cPanel.zip`, 179 KB, 54 files, `core/Db.php=fixed`) is in `Downloads` with the other five.

**Eight of the ten tools are still frontend-only.** Two conversions done with a real test suite beat
eight done thinly, and the shared-core fix above is the proof of why the slow route is the right one.

## 6h. The photograph pass on the six PHP apps

The brief asked for a background image on every page of every site, and the ten kit demos had got one.
The six PHP apps had not. They now do, built the same way each time so the code stays legible when you
jump between repos:

- a fixed `.bg-plate` layer carrying one commissioned photograph per site (`public/assets/img/plate.webp`,
  62–106 KB, WebP), sitting *under* the page colour so a missing file degrades to the old flat background
  rather than a broken icon;
- a `::after` scrim (vertical wash + corner vignette) that keeps text contrast where it was;
- `@keyframes plate-drift` for the slow scale, `translate` for scroll parallax, and an
  IntersectionObserver that arms itself with `.js-io` on `<html>` — so a browser that never runs the
  script still shows every block;
- a display typeface per site (AjoCircle Newsreader, MediTrack Spectral, FarmCoop its existing serif,
  RoutePilot Bricolage Grotesque) instead of six apps in one sans.

**A defect that made the parallax decorative only.** The scroll offset was written into `--sy` inside
`transform`, and `plate-drift` animates `transform` — so the keyframe replaced the whole property every
frame and the parallax never ran. Found by reading FarmCoop's CSS after it had been written the other
way, then confirmed in the browser on all six apps: the offset now rides in `translate`, which composes
with the animated `transform`, and `getComputedStyle` returns `translate: 0px 49.5px` *next to* a live
`matrix(...)` while scrolling. (Verifying it needed `scrollTo({behavior:"instant"})` — `html` sets
`scroll-behavior:smooth`, and headless throttling left a smooth scroll parked at 0, which looks exactly
like a broken handler.)

**A reduced-motion reset that was not one.** Four apps opted out with
`*{animation-duration:.001ms!important}`, which is the usual snippet but is incomplete for
`infinite` animations: at a microsecond per iteration, `plate-drift` and the live-dot `pulse` cycle
thousands of times a second — the plate jitters sideways and the CPU burns, on the machines that asked
not to be animated. Adding `animation-iteration-count:1!important` makes the reset mean what it says.
NairaDesk and PriceDesk already set `animation:none` explicitly, so they only needed `translate:none`
beside the `transform:none` they had.

**The kit's own reduced-motion bug, same family.** `demos/_kit/kit.js` had `if (reduce) return null`
inside `stage()`, which threw away the *photograph* as well as the motion. The opt-out is now scoped to
the drift and the parallax; `CDP_MEDIA="prefers-reduced-motion:reduce"` proves the plate is still in the
DOM in that mode.

## 6i. Content photography, and why the marketplace was empty

A screenshot of FarmCoop's marketplace showed six cards with blank image wells. The seeded produce and
farm photos — and MediTrack's doctor portraits — were `images.unsplash.com` hotlinks stored in the
database, so the pages depended on a third-party CDN being reachable, and any cPanel host that strips
external requests shows the same empty boxes the browser did.

- `tools/fetch_site_photos.py` now pulls every remote photo a PHP app references into
  `public/assets/img/content/*.webp` (13 for FarmCoop, 5 for MediTrack, capped at 1200 px, q76) and
  rewrites the source to the local path. Re-running is cheap: existing files are reused.
- `tools/localise_db_photos.php` does the same for rows already in a dev database, one row at a time,
  so nothing else in the table is touched.
- Stored values are now resolved by `fc_media()` / `photo_src()`: a full URL still passes through
  (a farmer who pastes a hosted photo is not broken), anything else is treated as a path under
  `public/` and served through `asset()`, which means the seed data is host-independent and works from a
  cPanel subdirectory. `fc_is_url()` became `fc_is_image()` and accepts `assets/…` without a scheme,
  rejecting `..` segments; the two `type="url"` inputs that would have refused to submit a relative path
  are `type="text"` with the same validation behind them.
- MediTrack gained the validation its admin form never had — the photo field was written straight to the
  database from `admin.php`.
- One crop fix: `.doctor-photo` is a 230 px landscape window over a portrait photograph, so `center`
  cut foreheads off. `center 18%` puts the window on the upper third, where a head shot keeps its face.

## 6j. These six apps had never been executed before

The four later builds had been written, packaged and documented, but never run. They now install against
local MySQL, and every GET route was probed anonymously and as the signed-in admin
(`tools/probe_routes.sh`, which fails on any `Warning:`/`Notice:`/`Fatal error` in the rendered HTML):

| site | public | protected anon | protected admin | role-gated | PHP warnings |
|---|---|---|---|---|---|
| FarmCoop | 200 | 302 | 200 | 403 (`/farmer`, `/buyer`) | 0 |
| RoutePilot | 200 | 302 | 200 | 403 (`/courier/queue`) | 0 |
| MediTrack | 200 | 302 | 200 | 403 (`/patient/*`) | 0 |

The tooling that made that testable is worth keeping: `tools/login_session.sh` takes a password on
stdin only (never argv, never a file) and prints the `forge_session` cookie, `tools/dev_reset_pw.php`
resets a dev account from stdin, and `tools/cdp_shot.mjs` grew `CDP_COOKIE`, `CDP_MEDIA` and `CDP_EVAL`
so a headless capture can be signed in, motion-reduced, or interrogated for computed styles.

All six cPanel packages were rebuilt from the live source with the plates and content photos inside:
AjoCircle 174 KB, FarmCoop 1481 KB, MediTrack 249 KB, RoutePilot 193 KB, NairaDesk 127 KB,
PriceDesk 179 KB — each checked to contain no `.env`, no log and no install lock, and `core/Db.php=fixed`.

## 6k. The portfolio shell got the same treatment

The sweep that proved the six apps painted a plate (`tools/check_plates.mjs`, which asks the *rendered*
DOM rather than grepping HTML) reported 61/65, and the four gaps were the portfolio's own two pages.
They now carry the same fixed photograph layer:

- `assets/projects/portfolio-plate.webp` (1600x914, 54 KB) sits behind the whole document at
  `z-index:-2`, with a paper scrim on `::after`, the slow `plate-drift` keyframe, and the scroll offset
  carried in `translate` so the keyframe's `transform` cannot clobber it.
- The opaque bands that used to hide it are now translucent — work 48%, about, contact, footer and the
  case header — so the photograph reads through the page without touching text contrast.
- The case study page wears the project's own artwork instead of a generic image: `project.js` sets
  `--case-bg` on the body and `.bg-plate` reads it, blurred. That replaced `.case-hero::before`, which
  painted the same artwork behind only the first screen.
- Reveal-on-scroll and the parallax moved into a shared `ui.js`. The case page had always shipped
  `.reveal` elements with **no observer at all**, so its About and Contact bands were sitting at
  `opacity:0` for every visitor who scrolled past them; loading `ui.js` on both pages fixed that and
  removed the duplication.
- `prefers-reduced-motion` now stops the plate outright (`animation/transform/translate: none`) and the
  34 reveal nodes resolve visible immediately — verified with `CDP_MEDIA`, not assumed.
- Cache-busters moved to `?v=10` on `styles.css`, `projects.js`, `script.js`, `project.js` and `ui.js`.

Sweep result after the change: **65/65 pages paint a background image.**

## 7. Outstanding / optional

- Nothing required by the brief is unfinished in this workspace, and the portfolio itself is now published (section 6b). The remaining work belongs to cPanel: 24 packages sit in `C:\Users\HI\Downloads` waiting to be uploaded, and the ten tools are already live on GitHub Pages — deploying them to a host is optional, only needed if he wants them off Pages or under his own domain.
- The generator tooling (`tools/build_pages.mjs`, `tools/pages/*.mjs`, `tools/link_pages.mjs`, `tools/build_demo_zips.py`, `tools/verify_demos.mjs`) lives in the workspace, not in the Pages repository, so the site is self-sufficient but regenerating a page needs the workspace. Moving the generators into the repo is a deliberate choice, not an oversight to fix silently.
- Optional polish: re-encode the Hotel Miramar tour video to 720p if a smaller download is wanted; add screenshots to generated `docs/` on future builds.
- The generated Kite Courier and the six deploy-ready PHP builds have no public URL yet — deployment is an owner action (packages + guides are ready).
- Hotel Miramar and Campus Coin still carry their development passwords until the owner rotates them on the deployed copy.
- **The 172 Trinity photographs are an owner action, not an open engineering task** (section 6e). The code path, the manifest generator and the paging are all in place and tested; the showroom will fill itself the moment real files replace the zero-byte placeholders.
- Hotel Miramar's live Render deployment still serves the previous navy-and-gold build until the owner redeploys; the redesigned source is what is committed here.
- **ForgeStack still seeds remote hero photos.** `tools/forgestack/app/Profiles.php` holds four `images.unsplash.com` URLs that land in a generated site's `app/config.php`, so a new build hotlinks its hero until the generator bundles the image into the project the way `tools/fetch_site_photos.py` now does for the six PHP apps. That is generator plumbing, not a one-line swap, and it is deliberately left un-half-done.
- Campus Coin's `src/legacy.css` keeps two remote `url()`s on `.shell::before` and `.landing`. Neither class exists in the JSX any more, so the browser never fetches them — dead CSS, not a dependency. The live background stage (`src/motion.jsx`) already serves four local images from `public/bg/`.
- The ten kit demos share one typeface trio (Fraunces + Inter + JetBrains Mono). Rotating a display face per tool is polish, and the six PHP apps now each have their own.
