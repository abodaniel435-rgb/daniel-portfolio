# LandedCost — deployment guide & tech report

## 1. What you are deploying
One problem: three supplier quotes for the same cargo (EXW, FOB, CIF) produce three different profits, because freight, insurance, the levy stack and the terminal clock get added twice, missed entirely or left out of the selling price. LandedCost runs the whole chain to a landed cost per unit, a break-even shelf price and the naira rate at which the margin disappears.

- **Local source:** `demos/landedcost`
- **Stack:** four static HTML pages, `landedcost.css`, one `app.js`, plus the shared `demos/_kit` design system
- **Database:** none — every calculation runs in the browser; saved records live in `localStorage` under the `landedcost:` key prefix (`kit.store` namespaces them as `afrik:landedcost:…`)
- **Hosting:** any cPanel shared plan. No Python, Node, Composer, database or build step.

## 2. Files to upload
The pages reference the kit and the hero image with **parent-relative paths** (`../_kit/kit.css`, `../_media/landedcost-hero.webp`), so `_kit/` and `_media/` must be uploaded as **siblings** of the tool folder:

```
public_html/landedcost/
├── index.html  data.html  playbook.html  record.html  app.js  landedcost.css
├── _kit/       kit.css  kit.js  pages.css  pages.js        ← shared, required
└── _media/     landedcost-hero.webp                        ← the only image this tool loads
```

- `index.html` — the tool: shipment and Incoterm, freight/CBM/container menus, duty and levy stack, demurrage and detention brackets, the landed-cost trail, the local tail (delivery, porters, storage, breakage, credit days), FX sensitivity, saved-quote comparison and the editable rate tables.
- `data.html` — the published reference figures: what each charge is calculated on, which goods sit in which tariff band, which charges are fixed and which are percentages, the free-day/bracket clock, and what each Incoterm hands you.
- `playbook.html` — the operating guide: ten steps in order, seven traps and what each costs, the pre-order document checklist, a month-by-month view of what is priced when, straight answers and a glossary.
- `record.html` — the ledger mirror of what the tool saved in this browser (`afrik:landedcost:shipments`): assessable value, charges, landed cost, break-even price, margin and rate, sortable, with CSV export and print.

If you keep the repository layout, upload `demos/landedcost/`, `demos/_kit/` and `demos/_media/` unchanged under `public_html/demos/` and open `/demos/landedcost/`.

## 3. Install on cPanel
1. cPanel → **Domains**: add `landedcost.yourdomain.com` with document root `public_html/landedcost`, or use `public_html/landedcost/` and open it as a subfolder.
2. Upload the tree above. The `../_kit/…` requests only resolve if `_kit` and `_media` sit **beside** `landedcost`, never inside it. Preserve the underscores and lower case — Linux is case-sensitive.
3. Permissions: folders `755`, files `644`. Nothing executes server-side.
4. No database, no cron job, no API keys. There is no exchange-rate endpoint to configure: the naira rate is a typed field.
5. Load over **https** and confirm the certificate before sharing the URL with anyone.
6. **Hard-refresh** (`Ctrl+Shift+R`) after any kit edit. All four pages request `../_kit/kit.css?v=5`, `kit.js?v=5`, `pages.css?v=5` and `pages.js?v=5`, and the tool's own `app.js` and stylesheet carry the same suffix. The number lives in `KIT_VERSION` in `tools/build_pages.mjs` and in the four `index.html` links — bump both together when the kit changes, or a returning visitor gets the old copy on one page and the new one on the rest.

## 4. Configuration / keeping it accurate
- Editable in the page and persisted (`afrik:landedcost:rules`): the levy table, the tariff bands and every fixed charge, with a `Restore default rates` button. Also per quotation: currency, naira rate, price mode (per unit or total), carton count and dimensions, freight mode, insurance basis, origin inland haulage and THC for EXW, excise/surcharge/stamp/VAT-reclaim switches, dwell and detention days, finance rate, truck and distance, margin and credit days.
- Authored in `app.js`: `LEVIES` — import duty surcharge 20% of duty, ECOWAS/ETLS 0.5% of CIF, CISS 1% of CIF, stamp 0.5% of CIF, VAT 7.5% on the duty-inclusive value, NIMASA/Shipping Council 3% of freight; `CATS` — duty bands from 0% (raw materials) through 5/10/20%, with excise on cosmetics, beverages and vehicles; `CHARGES` — LCL ₦38,000/CBM, 20ft ₦1,450,000, 40ft HC ₦2,300,000, air ₦10,500–14,500/kg, volumetric divisor 167, Form M ₦12,500, SONCAP ₦75,000/line, agent ₦150,000, THC ₦260,000/₦420,000, and demurrage brackets ₦18,000 → ₦62,000 per box per day; `TRUCKS`; and `CURS` defaults (USD 1,520, CNY 212, EUR 1,655, GBP 1,965, AED 414).
- These are 2026 reference figures, not a live tariff or FX feed. The sheet label groups the ad valorem lines as "NT242 · ECOWAS · CISS · ETLS · stamp"; the actual band and assessment basis must be confirmed against the HS line with your clearing agent, and the naira rate must be typed on the day you fund the Form M.

## 5. Post-deploy checks
1. On `/landedcost/`, the network log shows `/_kit/kit.css`, `/_kit/kit.js`, `landedcost.css`, `app.js` and `/_media/landedcost-hero.webp` all at `200`; a `404` on `_kit` means the sibling folders were not uploaded.
2. A CIF valuation and the levy stack reproduce the landed cost per unit shown on the sheet: duty = CIF × band, surcharge = 20% of duty, ECOWAS/ETLS, CISS and stamp on CIF, VAT 7.5% on CIF + duty + levies, plus the fixed port and agent lines, divided by the unit count.
3. Selecting CIF does **not** add your own freight and insurance to the assessable value, while FOB does — watch the CIF line in the readout change when you switch the Incoterm strip.
4. An air shipment whose CBM × 167 exceeds the gross weight charges on the volumetric figure.
5. Raising dwell days past the rent-free period steps the calculation up the brackets and moves the "cost per extra day" figure and the verdict.
6. In FX sensitivity, the break-even rate and the +20% scenario both move when you change the selling price or the naira rate.
7. Save a quotation, then change the currency and save a second one: both appear in the comparison table and on `record.html`, and `Export CSV` downloads them.
8. Print the sheet (`Print quotation`) and confirm the line-by-line breakdown renders on paper.
9. At 390px the forms stack, the wide tables scroll inside their own wrappers with no page-level sideways scroll, and the four nav links (`index.html`, `data.html`, `playbook.html`, `record.html`) resolve on every page.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; `app.js` recomputes from all three forms on every `input`/`change` and repaints the readouts |
| Styling | `../_kit/kit.css` + `landedcost.css`; token-driven, no framework, no preprocessor, dedicated `@media print` sheet |
| Behaviour | Vanilla JavaScript on the shared `kit` global; `pages.js` for the TOC, sortable tables and the saved-shipment record |
| Charts | `kit.bars` cost-group bars, `kit.donut` share-of-landed-cost, `kit.sparkline` for the demurrage clock and the margin-against-rate curve |
| Assets | One WebP hero with explicit `width`/`height`, inline SVG data-URI favicon, Google Fonts with `display=swap` |
| Accessibility | Skip link, landmarks, `aria-live` verdict panels, labelled inputs, `role="img"` chart labels, `prefers-reduced-motion` honoured |
| Persistence | `afrik:landedcost:rules` (edited rate tables) and `afrik:landedcost:shipments` (saved quotations); CSV export and print are the only exits |
| Security | No secrets, no auth, no network calls; quotation names and goods labels escaped before insertion |
| Performance | ~68 KB `app.js` + 16 KB tool CSS + 24 KB kit CSS, no build step, works offline once loaded |

### Known limits
- Footer cross-links to the other tools (`../suncost/`, `../recircle/`, …) and to the portfolio root (`../../`) resolve only when the whole `demos/` tree is deployed at the same depth; a single-tool ZIP leaves them dead, so keep the sibling folders or accept those two broken links.
- No server and no integrations: no banks, no shipping lines, no customs portal, no live FX or tariff feed. The naira rate is typed by you, so every answer is only as current as the number you entered.
- Bands are ranges collapsed to one indicative figure; the HS line decides the real rate, and this tool does not classify goods.
- `localStorage` is per browser and per origin — a cleared cache or a second device loses the saved quotations and any edited rates.
- Port, terminal and line charges are billed by the operator and can differ from these defaults, sometimes materially.
- Nothing here is customs, tax, legal or financial advice, and no figure is a quotation you can settle against.
