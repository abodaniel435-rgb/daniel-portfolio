# ReCircle — deployment guide & tech report

## 1. What you are deploying
One problem: households, schools and scrap dealers hand material away without ever seeing a price in front of them, so a mixed sack, an unweighed crate and a collector's trip cost quietly eat the money. ReCircle counts pieces, converts them to kilograms and to naira, and shows what sorting, a viable pickup and a checked weigh-in are worth.

- **Local source:** `demos/recircle`
- **Stack:** four static HTML pages, `recircle.css`, one `app.js`, plus the shared `demos/_kit` design system
- **Database:** none — every calculation runs in the browser; saved records live in `localStorage` under the `recircle:` key prefix (`kit.store` namespaces them as `afrik:recircle:…`)
- **Hosting:** any cPanel shared plan. No Python, Node, Composer, database or build step.

## 2. Files to upload
The pages request the kit and the hero image through **parent-relative paths** (`../_kit/kit.css`, `../_media/recircle-hero.webp`). Uploading `recircle` alone yields an unstyled page; `_kit/` and `_media/` must be **siblings**:

```
public_html/recircle/
├── index.html  data.html  playbook.html  record.html  app.js  recircle.css
├── _kit/       kit.css  kit.js  pages.css  pages.js      ← shared, required
└── _media/     recircle-hero.webp                        ← the only image this tool loads
```

- `index.html` — the tool: the piece counter (16 items with per-piece weights and kilogram overrides), sorted-versus-mixed value with the contamination deduction, the pickup-trip viability check, the weigh-in sanity check, the earnings ledger and statement, group share-outs and the editable price tables.
- `data.html` — the published reference figures: what a kilogram of each material is worth sorted and dry against what a mixed load gets, the per-piece weights, how the contamination deduction is applied, the minimum a pickup has to return, and the three honest limits on the reference.
- `playbook.html` — the step-by-step operating guide: nine steps in order, seven traps and what each costs, the signs you are being shorted rather than unlucky, the ten-second checklist and a glossary.
- `record.html` — the ledger mirror of what the tool saved in this browser (`afrik:recircle:ledger`): every load and weigh-in, the buyer, the kilograms, the naira, and the gap between what your count implied and what you were paid for.

To deploy without restructuring, upload `demos/recircle/`, `demos/_kit/` and `demos/_media/` unchanged under `public_html/demos/` and open `/demos/recircle/`.

## 3. Install on cPanel
1. cPanel → **Domains**: add `recircle.yourdomain.com` with document root `public_html/recircle`, or use `public_html/recircle/` and open it as a subfolder.
2. Upload the tree above. The `../_kit/…` and `../_media/…` paths only resolve when those two folders sit **beside** `recircle`; keep the underscores and lower case exactly.
3. Permissions: folders `755`, files `644`. No server-side runtime is involved.
4. No database, no cron, no API keys — there is no buyer feed to connect; prices are typed.
5. Verify **https** loads with a valid certificate before circulating the URL; the clipboard "make a record" button needs a secure context and falls back to a manual-copy message without one.
6. **Hard-refresh** (`Ctrl+Shift+R`) after any kit edit. All four pages request `../_kit/kit.css?v=3`, `kit.js?v=3`, `pages.css?v=3` and `pages.js?v=3`, and the tool's own `app.js` and stylesheet carry the same suffix. The number lives in `KIT_VERSION` in `tools/build_pages.mjs` and in the four `index.html` links — bump both together when the kit changes, or a returning visitor gets the old copy on one page and the new one on the rest.

## 4. Configuration / keeping it accurate
- Editable in the page (persisted per browser): every material price, its contamination deduction and minimum quantity, the per-piece weights, the mixed rate you are actually offered (₦45/kg default), loads per month, the pickup distance and ₦/km, the collection calendar, the weigh-in tolerance (12% default), and the group members with their float, kilograms and earnings. Section 09 carries `Restore default prices`, `Restore sample calendar` and the weight table.
- Authored in `app.js`: `DEFAULT_PRICES` — PET clear ₦120/kg, PET coloured ₦80, HDPE ₦200, mixed plastic ₦45, sachet ₦15, carton ₦35, newsprint ₦60, mixed paper ₦45, aluminium ₦300, scrap iron ₦45, copper ₦4,200, brass ₦2,600, cable ₦180, glass ₦30, used cooking oil ₦350, plus per-piece lines for phones (₦700), laptop boards (₦3,500) and tyres (₦250); `DEFAULT_ITEMS` — sachet 3 g, 500 ml PET 10 g, 1.5 L PET 30 g, 20 L jerrycan 400 g, drink can 14 g, tin can 60 g, glass bottle 320 g, car tyre 7 kg and the rest; `VEHICLES` — hand cart ₦40/km, tricycle ₦120/km, 3-ton truck ₦320/km; `DEFAULT_FACTORS` and `RANGES` for the diversion figures.
- The price table is headed "Indicative buyer prices as of" with a date input you set yourself. These are 2026 reference figures, not a live scrap feed, and no code in this folder calls an API — when buyers move their rates, edit the table or `app.js` and re-date it.

## 5. Post-deploy checks
1. On `/recircle/` the network log shows `/_kit/kit.css`, `/_kit/kit.js`, `recircle.css`, `app.js` and `/_media/recircle-hero.webp` returning `200`; a `404` on `_kit` means the sibling folders were missed.
2. Sorting the same count into PET and HDPE beats the mixed-material figure: the uplift line is positive and the sorted/mixed bars separate.
3. Counting 100 × 1.5 L PET gives about 3 kg, and entering an "actual kg" override on that line replaces the counted weight everywhere downstream.
4. The contamination deduction lowers the wet value — aluminium at 3% barely moves, water sachets at 14% take a visible cut — and materials below their minimum quantity are flagged.
5. In the pickup section, the minimum kilograms needed equals trip cost ÷ the offered rate; sharing the load across more households flips the verdict from "not worth the trip" to viable.
6. The weigh-in check with a buyer's 6.9 kg against your 7.4 kg and a 0.4 kg crate settles inside or outside the tolerance, and `Log this weigh-in in the ledger` writes it to the record.
7. Save a load and a ledger entry, choose a statement month, then confirm the totals, `Export ledger CSV`, and the same rows on `record.html`.
8. In group accounts, switching the share-out between per-kilogram, equal and keep-a-slice changes the member table and the audit line still reconciles to the distributable total.
9. At 390px the counter rows, quick-add pills and ledger tables stack or scroll in place with no page-level sideways scroll, and the four nav links (`index.html`, `data.html`, `playbook.html`, `record.html`) resolve from every page.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; `app.js` recomputes the load, trip, scale check, ledger and group split from page state on each input |
| Styling | `../_kit/kit.css` + `recircle.css`; custom-property tokens, no framework, no preprocessor, `@media print` block for the record sheet |
| Behaviour | Vanilla JavaScript against the shared `kit` global; `pages.js` adds the TOC, sortable tables and the saved-ledger record view |
| Charts | `kit.bars` for the sorted/mixed split and ledger trend, `kit.donut` for the diversion share, `kit.sparkline` for the weekly line |
| Assets | One WebP hero with explicit `width`/`height`, inline SVG data-URI favicon, Google Fonts with `display=swap` |
| Accessibility | Skip link, landmarks, `aria-live` verdict panels, labelled inputs with `inputmode`, screen-reader captions on tables, `prefers-reduced-motion` honoured |
| Persistence | `afrik:recircle:prices`, `:itemweights`, `:cal`, `:load`, `:pickup`, `:ledger`, `:records`, `:group`, `:factors`, `:opts`; CSV export and print are the archive |
| Security | No secrets, no authentication, no network calls; names, notes and buyer text escaped before insertion |
| Performance | ~84 KB `app.js` + 18 KB tool CSS + 24 KB kit CSS, no build step, works offline once loaded |

### Known limits
- Footer cross-links to the other tools (`../suncost/`, `../recircle/`, …) and to the portfolio root (`../../`) resolve only when the whole `demos/` tree is deployed at the same depth; a single-tool ZIP leaves them dead, so keep the sibling folders or accept those two broken links.
- No server: nothing is booked, collected or paid, and no figure reaches a buyer, a school or a co-operative office — and because `localStorage` is per browser and per origin, clearing site data or changing phones erases prices, the ledger and the group accounts.
- Scrap prices move weekly and vary by city and by buyer; these are 2026 reference values that you must re-enter and re-date by hand.
- Per-piece weights are averages; wet or contaminated material will not match a calculated kilogram.
- The diversion and CO₂ figures are cited as ranges and are deliberately kept behind a toggle — they are context, not a verifiable credit.
- Nothing here is a valuation, a contract, a waste-management compliance statement or financial advice.
