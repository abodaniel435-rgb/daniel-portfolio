# FuelMeter — deployment guide & tech report

## 1. What you are deploying
The real cost of moving a vehicle or a person one kilometre on a Nigerian road: fuel, servicing, tyres, oil and amortised fixed cost at a pump price you set, against the fares actually charged for the same trip.

- **Local source:** `demos/fuelmeter`
- **Stack:** HTML5, CSS3, vanilla JavaScript on the shared `/_kit` layer
- **Database:** none — every calculation runs in the browser, saved records live in `localStorage` under the `fuelmeter:` key prefix (`afrik:fuelmeter:profiles`, `:prices`, `:modes`, `:factors`, `:asof`, `:log`, `:fleet`)
- **Hosting:** any cPanel plan. No Python, Node, PHP, Composer or build step.

## 2. Files to upload
Six files in the tool folder, plus the two shared folders it loads from:

```
public_html/fuelmeter/
├── index.html  data.html  playbook.html  record.html  app.js  fuelmeter.css
├── _kit/       kit.css kit.js pages.css pages.js    ← shared, required
└── _media/     fuelmeter-hero.webp                   ← the only media file used
```

`_kit/` and `_media/` must be **siblings** of `fuelmeter/` — `public_html/_kit/` and `public_html/_media/` — because the pages request `../_kit/kit.css` and `../_media/fuelmeter-hero.webp`; the tool folder on its own renders unstyled with a missing hero. To keep the repository layout, upload the contents of `demos/` unchanged to `public_html/demos/` (`demos/fuelmeter/`, `demos/_kit/`, `demos/_media/`) and open `/demos/fuelmeter/`. Paths are relative, so neither arrangement needs a rewrite.

- `index.html` — the tool: trip planner (vehicle, state, corridor presets, traffic, A/C, load, idling, return leg), the per-kilometre cost stack, drive-versus-hail comparison with a crossover distance, monthly budget with pump-rise scenarios, odometer fuel log and a fleet tab, plus the editable reference tables.
- `data.html` — the published reference figures: thirteen state pump rows and the gap between them, km/L per class on open road, city and go-slow, the whole per-kilometre stack, service intervals and street prices, tyre sizes, six fare comparators, nine corridor distances.
- `playbook.html` — the operating guide: the four numbers that decide the rest, ten steps to a defensible km/L, seven habits that make a cost-per-kilometre a lie, eight leaks, pre-trip checklist, monthly routine, glossary.
- `record.html` — the ledger mirror of `afrik:fuelmeter:log` for this browser, with a table naming every key the tool writes, plus sort, CSV export, print and clear.

## 3. Install on cPanel
1. cPanel → **Domains**: add `fuelmeter.yourdomain.com` with document root `public_html/fuelmeter`, or use that subfolder under an existing domain.
2. Extract the zip (four HTML files, `app.js`, `fuelmeter.css`, `_kit/`, `_media/`) so the tree above is exact, then delete the archive from the web root.
3. Permissions `755` folders, `644` files; no database, cron job, environment variable or rewrite rule, because the price table and the log are browser data.
4. Load `https://…/fuelmeter/` to confirm the certificate and check the Network panel for `200` on `/_kit/kit.css`, `/_kit/pages.css`, `/_kit/kit.js` and `/_media/fuelmeter-hero.webp`.
5. `_kit/` is shared with PayRite, SunCost and SealCheck: after replacing it, hard-refresh (`Ctrl`+`F5`) each tool, since the kit stylesheet is cached once per origin. Rollback is re-extracting the previous zip; reverting `_kit/` changes all four sites.

## 4. Configuration / keeping it accurate
Editable in the page and persisted per browser: the thirteen state petrol and diesel prices (`:prices`), the nine vehicle profiles with km/L, tank, seats, service interval, tyre and oil amortisation and monthly fixed cost (`:profiles`), the six fare modes with minimum fare and rate per kilometre (`:modes`), the six behavioural factors — idling litres per hour, A/C penalty, load penalty, default monthly distance, degradation flag and go-slow multiplier (`:factors`) — the **"Prices as of"** date (`:asof`), the fill-up log and the fleet rows. Each table has its **Restore default …** button. Authored in `app.js`: those shipped tables, the nine corridor distances with their town-to-state mapping, the diesel idling discount, the verdict thresholds and the pump-rise ladder (+0/10/15/20/30 per cent).

The shipped prices are 2026 reference values with an as-of date you set, not a live feed: the page states that static hosting cannot know what the pump charged this morning. The trip panel prints "as of *date*" beside the price and falls back to two days before the visit when no date is stored, so set the date on deploy and update it whenever the pump moves. Visitor edits stay in the visitor's browser — published numbers are the defaults in `app.js`.

## 5. Post-deploy checks
1. `/fuelmeter/` renders styled and the hero loads, confirming `_kit/` and `_media/` were placed as siblings.
2. First load opens on Lagos → Ibadan, 130 km, a Toyota Corolla priced at Oyo, and returns a trip total, per-kilometre and per-seat figures plus a tank gauge reading "of a 50 L tank left on arrival"; clearing the distance shows "Pick a route".
3. Switching the corridor to Abuja → Kaduna re-prices the trip at Kaduna and sets 170 km; changing **Prices as of** updates the inline "as of …" label.
4. The go-slow multiplier with A/C on pushes effective km/L below the claimed figure and 500 kg of load takes another 12.5 per cent; a distance beyond one tank raises "This trip is more than one tank", and two hours of idling on a short trip raises "Idling is eating the trip".
5. The five cost bars — fuel at the pump, servicing, tyres, oil and filters, insurance and registration — add up to the trip total, and the vehicle table's fuel, wear and fixed columns sum to its total per-kilometre column.
6. The head-to-head table re-ranks when the passenger count changes and states how much lower the trip would look if driving were priced on fuel alone.
7. Logging two consecutive entries for the same vehicle with **Full tank this time** ticked and a rising odometer produces a learned km/L beside the claimed figure; unticking it on the second row removes the sample again, and the flag line warns when the learned mean falls more than the set percentage below baseline. The month filter, **Export log CSV** and **Print monthly sheet** act on the rows in view.
8. The four page links (tool, Data, Playbook, Record) resolve, and at 390px the planner, comparison table and log stack inside their scroll wrappers with no sideways page movement.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static documents; `app.js` paints results, tables and the worked example into fixed elements |
| Styling | Shared `kit.css` tokens plus `fuelmeter.css` for the gauge, cost bars and editable tables; `pages.css` for inner pages |
| Behaviour | One IIFE over the `kit.*` helpers: delegated input on editable tables, tabbed log/fleet panels, recompute on every keystroke |
| Charts | CSS bar tracks for the cost stack and mode comparison, an SVG needle gauge for tank use, `kit.sparkline` for learned km/L |
| Assets | One 1536×1024 WebP with explicit dimensions; Google Fonts (Fraunces, Inter, JetBrains Mono) with `display=swap` |
| Accessibility | Skip link, labelled inputs and `aria-label`ed table cells, `aria-live` result regions, `role="tab"` log/fleet switch, reduced motion respected |
| Persistence | `localStorage` for prices, profiles, fares, factors, as-of date, log and fleet; a blocked write falls back to a toast and CSV export |
| Security | No endpoints, no secrets, no uploads; every entered name and note is escaped before it reaches the DOM |
| Performance | Six files plus the shared kit, one image; the model is arithmetic over a dozen small arrays |

### Known limits
- No server, account or sync: the log, the edited price rows and the as-of date are per browser and are gone when site data is cleared; CSV export and the print sheet are the only handoffs.
- Pump prices, fares, service intervals and tyre prices are hand-maintained 2026 reference figures that drift unless someone updates them, and corridor distances are planning distances, not measured truth.
- Learned km/L needs two consecutive full-tank rows for the same vehicle — partial fills, a falling odometer or a single entry yield no sample — and the figure is a three-sample mean, not a diagnosis.
- Tolls, levies, driver wages, insurance, parking, depot fuel and depreciation beyond the amortised fixed line are excluded; the output is a planning aid, not a quotation, a warranty, an accounting record or a fare tariff.
- Links to `../payrite/`, `../suncost/`, `../riderpool/`, `../rentjury/` and `../../` resolve only when the wider portfolio is deployed at the same depth.
