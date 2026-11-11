# SunCost — deployment guide & tech report

## 1. What you are deploying
Solar-plus-battery sizing that answers one question in naira: does this system beat the generator, how large must it be, and what does the quote have to contain.

- **Local source:** `demos/suncost`
- **Stack:** HTML5, CSS3, vanilla JavaScript on the shared `/_kit` layer
- **Database:** none — every calculation runs in the browser, saved records live in `localStorage` under the `suncost:` key prefix (`afrik:suncost:load`, `:assumptions`, `:psh`, `:quotes`)
- **Hosting:** any cPanel plan. No Python, Node, PHP, Composer or build step.

## 2. Files to upload
Six files in the tool folder, plus the two shared folders it loads from:

```
public_html/suncost/
├── index.html  data.html  playbook.html  record.html  app.js  suncost.css
├── _kit/       kit.css kit.js pages.css pages.js    ← shared, required
└── _media/     suncost-hero.webp                     ← the only media file used
```

`_kit/` and `_media/` must be **siblings** of `suncost/` — `public_html/_kit/` and `public_html/_media/` — because the pages request `../_kit/kit.css` and `../_media/suncost-hero.webp`; the tool folder alone renders unstyled with no hero. To keep the repository layout instead, upload the contents of `demos/` unchanged to `public_html/demos/` (`demos/suncost/`, `demos/_kit/`, `demos/_media/`) and open `/demos/suncost/`. Paths are relative, so neither arrangement needs a rewrite.

- `index.html` — the tool: appliance load table with night and motor flags, city and sun-hour inputs, array/inverter/battery sizing, solar-versus-generator curves, financing modes, quote ledger and the full constants editor.
- `data.html` — the published reference figures: peak sun hours per city, appliance watt and hour tables, lithium versus tubular, what a "3.5 kVA system" contains, verification lines and sources.
- `playbook.html` — the operating guide: nine steps from load list to signed quote, seven failure patterns, pre-payment checklist, maintenance year, glossary.
- `record.html` — the ledger mirror of `afrik:suncost:quotes` for this browser: sortable, CSV export, print, clear.

## 3. Install on cPanel
1. cPanel → **Domains**: point `suncost.yourdomain.com` at document root `public_html/suncost`, or use that subfolder of an existing domain.
2. Extract the zip (four HTML files, `app.js`, `suncost.css`, `_kit/`, `_media/`) so the tree above is exact, then delete the archive from the web root.
3. Permissions `755` on folders, `644` on files. No database, cron job, environment variable or rewrite rule is needed.
4. Load `https://…/suncost/` to confirm the certificate, and verify `200` responses for `/_kit/kit.css`, `/_kit/pages.css` and `/_media/suncost-hero.webp`.
5. `_kit/` is shared with the other three tools: after any kit change, hard-refresh (`Ctrl`+`F5`) each of them, since browsers cache that stylesheet once per origin. Rollback is re-extracting the previous zip — revert `_kit/` deliberately, because it rolls back four sites at once.

## 4. Configuration / keeping it accurate
Editable in the page, per browser: the appliance load list (`afrik:suncost:load`), all three constant groups — loss factor, system efficiency, oversize, PV ratio, inrush, depth of discharge, string limit, panel rating, panel/inverter/battery/hybrid/mounting prices, fuel prices, replacement intervals, upkeep, deposit and interest (`:assumptions`) — the ten city peak-sun-hour values (`:psh`) and the saved quotes (`:quotes`). **Restore starter profile** and **Restore all defaults** reset the load list and the constants. Authored in `app.js`: the city list with its shipped sun hours, the eleven appliance presets, the standard inverter ratings 1–12 kVA and the system-class thresholds; those need a code edit and re-upload.

Prices and sun hours are 2026 reference figures — annual irradiation averages and delivered street prices, not a weather feed or a supplier quote. `data.html` labels itself "Reference · the numbers behind the sizing", the spec sheet footer marks figures indicative, and there is no as-of date control on this tool, so the review date belongs in your deploy note. Visitor edits never travel: the published numbers are exactly what `app.js` ships.

## 5. Post-deploy checks
1. `/suncost/` renders styled and the hero image loads, confirming `_kit/` and `_media/` were placed as siblings.
2. The starter load list reports roughly 8.6 kWh a day and returns a panel count, an inverter kVA and a battery kWh figure with no console errors.
3. Changing the city from Lagos (5.1 h) to Onuora/Jalingo (6.1 h) raises the sun-hours field and reduces the array size and panel count.
4. Unticking every night-critical box drops the bank to 0 kWh and shows "Nothing is marked night-critical"; ticking the fridge back restores it.
5. Switching chemistry to tubular moves depth of discharge from 90% to 80%, so bank kWh, price and the crossover month all change; choosing diesel repopulates the fuel price from the constants.
6. Extending the horizon from 5 to 7 years redraws both cumulative curves, the totals and the fuel-share donut; **Save this system** writes a ledger row that also appears on `record.html` and prints as a spec sheet.
7. Editing a constant (panel price) changes capex immediately and survives a reload; **Restore all defaults** reverts it.
8. The four page links (tool, Data, Playbook, Record) resolve, and at 390px the load table and curves stack inside their scroll wrappers, the nav collapses to the hamburger and the page does not scroll sideways.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static documents; `app.js` paints sizing, curves and ledger rows into fixed elements |
| Styling | Shared `kit.css` tokens plus `suncost.css`; `pages.css` styles the editorial pages |
| Behaviour | Single IIFE on the `kit.*` helpers; one full recompute per input event, no framework |
| Charts | Two `kit.sparkline` cumulative-cost curves, a `kit.bars` capex split and a `kit.donut` fuel-share ring — inline SVG/CSS only |
| Assets | One 1536×1024 WebP with width and height set; Google Fonts (Fraunces, Inter, JetBrains Mono) with `display=swap` |
| Accessibility | Skip link, `aria-live` result panel, labelled numeric inputs, `details` accordions for constants, `prefers-reduced-motion` honoured |
| Persistence | `localStorage` for load, constants, sun-hour overrides and quotes; blocked writes degrade to a toast plus CSV export |
| Security | No endpoints, no secrets, no uploads; city, label and appliance names are escaped before insertion |
| Performance | Six files plus the shared kit, one image, one font request; the sizing pass is closed-form arithmetic |

### Known limits
- No server and no sharing: a quote saved on a phone is invisible on a laptop, and cleared site data removes the load list, constants, sun-hour edits and ledger.
- Sun hours are annual averages with no monthly irradiation, shading, dust or wet-season term; crossover is searched month by month and reports no crossing beyond 180 months.
- Battery, panel, inverter and fuel prices are 2026 reference figures needing manual refresh; roof works, changeover gear, DISCO interaction and warranty terms are outside the cost model, so this is a sizing aid rather than a tender or a guarantee.
- Links to `../payrite/`, `../sealcheck/` and `../../` resolve only when the wider portfolio is deployed at the same depth.
