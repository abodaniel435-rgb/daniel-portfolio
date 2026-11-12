# RiderPool — deployment guide & tech report

## 1. What you are deploying
One problem: a seven-seater leaves a motor park with four seats empty and a fare that came from habit. Nobody in the car can say what the trip actually costs, what one seat has to carry, whether an eleven-kilometre detour to drop a passenger inside an estate pays for itself, or which of four ways of splitting the money is fair when riders travel 40 km and 245 km. RiderPool does that arithmetic and prints the result. It matches nobody, holds no money and asks for no account.

- **Local source:** `demos/riderpool`
- **Stack:** four static HTML pages, `riderpool.css`, one `app.js` (1,636 lines), plus the shared `demos/_kit` design system
- **Database:** none — every calculation runs in the browser; saved records live in `localStorage` under the `riderpool:` key prefix (`kit.store` namespaces them as `afrik:riderpool:…`)
- **Hosting:** any cPanel shared plan. No Python, Node, Composer, database, API key or build step.

## 2. Files to upload
The pages reference the kit and the hero image with **parent-relative paths** (`../_kit/kit.css`, `../_media/riderpool-hero.webp`), so `_kit/` and `_media/` must be uploaded as **siblings** of the tool folder:

```
public_html/riderpool/
├── index.html  data.html  playbook.html  record.html  app.js  riderpool.css
├── _kit/       kit.css  kit.js  pages.css  pages.js     ← shared, required
└── _media/     riderpool-hero.webp                      ← the only image this tool loads
```

- `index.html` — the tool: trip builder and fare maths (`#build`), fair pricing per seat (`#pricing`), the four split modes compared (`#split`), detour-priced stop sequence with the ETA chain (`#sequence`), printable trip card and manifest (`#card`), the saved trip ledger (`#ledger`), fifteen sample runs (`#board`) and five editable assumption tables (`#assumptions`).
- `data.html` — the published figures the engine works from: nine sortable tables. Fourteen corridors with distance and time, eight vehicle classes plus their per-kilometre and per-seat-km cost, the five split methods against what a correct split collects, the sample board priced by each method, ten city litre prices, the seat adjustments against the asking fare, the four parcel rates with their combinations and reliefs, the sample Benin park → CMS run stop by stop, and where to verify each figure. The corridor and class tables carry live filters.
- `playbook.html` — the operating guide: ten steps from the question to the signature, eight ways the quoted number stops describing the trip and what each costs the short-distance rider, ten lines to agree in writing, the signs not to board and what each one costs you, the day-of-trip sequence timed against the tool's own ETA chain, twelve straight answers and a seventeen-term glossary.
- `record.html` — the ledger mirror of what the tool saved in this browser (`afrik:riderpool:ledger`): trip code, date, route, class, distance, seats, asking fare, cost, amount still to cover and per-seat fare, sortable, with CSV export and print.

If you keep the repository layout, upload `demos/riderpool/`, `demos/_kit/` and `demos/_media/` unchanged under `public_html/demos/` and open `/demos/riderpool/`.

## 3. Install on cPanel
1. cPanel → **Domains**: add `riderpool.yourdomain.com` with document root `public_html/riderpool`, or use `public_html/riderpool/` and open it as a subfolder.
2. Upload the tree above. The `../_kit/…` requests only resolve if `_kit` and `_media` sit **beside** `riderpool`, never inside it. Keep the underscores and lower case — Linux is case-sensitive.
3. Permissions: folders `755`, files `644`. Nothing executes server-side.
4. No database, no cron job, no SMTP setup. The manifest and trip card are printed or copied by the visitor; the tool sends nothing anywhere.
5. Load over **https** and confirm the certificate before sharing the URL with anyone.
6. **Hard-refresh** (`Ctrl+Shift+R`) after any kit edit. All four pages request `../_kit/kit.css?v=5`, `kit.js?v=5`, `pages.css?v=5` and `pages.js?v=5`, and the tool's own `app.js` and stylesheet carry the same suffix. The number lives in `KIT_VERSION` in `tools/build_pages.mjs` and in the four `index.html` links — bump both together when the kit changes, or a returning visitor gets the old copy on one page and the new one on the rest.

## 4. Configuration / keeping it accurate
- Editable in the page and persisted under thirteen `afrik:riderpool:*` keys: `corridors`, `classes`, `prices`, `parcels` and `adj` (the five assumption tables in `#assumptions`), plus `trip` (the current build), `stops`, `seats`, `safety`, `checks`, `travel`, `ledger` and `asof`.
- Authored in `app.js`: `DEFAULT_CORRIDORS` (14 routes, Benin City → Lagos at 245 km is the opening one), `DEFAULT_CLASSES` (8: Sienna and Hiace at 6 seats / 9 km per litre / ₦49 per km wear / ₦5,000 tolls / ₦3,000 levy, plus Saloon, SUV, Hilux, Kiss, 30-seater, Keke and Okada), `DEFAULT_PRICES` (10 cities), `DEFAULT_PARCELS` (₦800 / ₦2,000 / ₦3,500 / ₦9,000), `DEFAULT_ADJ` (front seat +8%, last −7%, child −50%, student −15%, night +10%, luggage ₦2,000, detour ₦18 per km over 11 km), `DEFAULT_STOPS` (9, Benin park to CMS), `DEFAULT_SEATS` (6 riders) and `SAMPLE_TRIPS` (15, labelled as fiction on the page).
- These are 2026 reference figures, not a live fuel, toll or union price feed, and the as-of date is stored and displayed. On the shipped defaults the engine reproduces the figures printed on the pages exactly: a ₦89,922 trip, ₦14,987 per seat, ₦35,922 short at the ₦9,000 the park quotes, ten seats to break even, ₦232.33 per km, ₦61.17 per seat-km, a ₦990 detour against ₦2,556 of fare, and 1,064 rider-kilometres at ₦84.51.
- If diesel, tolls or the park levy move, change the assumption tables — do not edit `app.js` and re-deploy for a price that changes monthly.

## 5. Post-deploy checks
1. On `/riderpool/`, the network log shows `/_kit/kit.css`, `/_kit/kit.js`, `riderpool.css`, `app.js` and `/_media/riderpool-hero.webp` all at `200`; a `404` on `_kit` means the sibling folders were not uploaded.
2. The default Benin City → Lagos Sienna trip reports a total cost of ₦89,922 and a cost per seat of ₦14,987, and the verdict names the ₦35,922 shortfall against the ₦9,000 asking fare.
3. Switching the vehicle class changes distance-scaled fuel, wear, tolls and levy together, and the per-seat figure moves with them.
4. `#split` ranks the four methods and the even split shows the 40 km rider paying the same as the 245 km rider — that gap is the argument the page exists to settle.
5. Adding a detour stop moves the ETA chain for every rider behind it and prices the diversion; a stop whose fare is below its cost is flagged.
6. Save a trip, then open `record.html`: the row carries the trip code, and toggling a seat to paid in the ledger reduces the amount still owed.
7. Print the trip card and the manifest and confirm both render on paper with the trip code and the check-in window.
8. Load a sample run from `#board` and confirm it is labelled as fiction, not as a real match.
9. At 390px the builder stacks, the wide tables scroll inside their own wrappers with no page-level sideways scroll, and the four nav links (`index.html`, `data.html`, `playbook.html`, `record.html`) resolve on every page.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; `app.js` recomputes the whole trip from the builder and the assumption tables on every `input`/`change` and repaints each readout |
| Styling | `../_kit/kit.css` + `riderpool.css`; token-driven, no framework, no preprocessor, dedicated `@media print` sheet for the card and manifest |
| Behaviour | Vanilla JavaScript on the shared `kit` global; `pages.js` for the TOC, sortable tables, filters and the saved-trip record |
| Charts | `kit.bars` for the cost ladder and split comparison, `kit.sparkline` for the travel log, a CSS timeline for the stop sequence with a moving runner |
| Assets | One WebP hero (150 KB, explicit `width`/`height`), inline SVG data-URI favicon, Google Fonts with `display=swap` |
| Accessibility | Skip link, landmarks, `aria-current` on the active nav item, labelled inputs, `prefers-reduced-motion` honoured — counters, ticks and the timeline runner all stand still when it is set |
| Persistence | Thirteen `afrik:riderpool:*` keys; the ledger snapshot stores the route, cost, split method and a per-seat array so a trip can be re-opened and reconciled |
| Security | No secrets, no auth, no network calls; rider names and phone numbers are escaped before insertion and never leave the browser |
| Performance | 89 KB `app.js`, 24 KB tool CSS, 24 KB kit CSS; no build step, works offline once loaded |

### Known limits
- Footer cross-links to the other tools (`../suncost/`, `../recircle/`, …) and to the portfolio root (`../../`) resolve only when the whole `demos/` tree is deployed at the same depth; a single-tool ZIP leaves them dead, so keep the sibling folders or accept those two broken links.
- It is not a ride-hailing service. No matching, no driver or rider accounts, no location tracking, no payment, no escrow — the tool says so on the page and in `#limits`, and nothing in the code contradicts it.
- No server and no integrations: no fuel price feed, no toll authority data, no union fare table. Every rate is typed by whoever edits the assumption tables.
- `localStorage` is per browser and per origin. A cleared cache, a private window or a second phone loses the saved trips; CSV export and the printed manifest are the only exits.
- The fifteen sample trips are invented illustrations of the maths, not records of real journeys.
- A fair split is an arithmetic answer, not a legal one. Fare rules on a corridor belong to the union and the state, and this tool does not know them.
