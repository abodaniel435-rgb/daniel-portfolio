# MarketRate — deployment guide & tech report

## 1. What you are deploying
One problem: Nigerian produce and staples are quoted per crate, mudu, bag or basket, so two traders can both be honest and still not know which offer is cheaper. MarketRate converts every quote to naira per kilogram, then prices the haul, the season and the negotiation on top of that comparison.

- **Local source:** `demos/marketrate`
- **Stack:** four static HTML pages, `marketrate.css`, one `app.js`, plus the shared `demos/_kit` design system
- **Database:** none — every calculation runs in the browser; saved records live in `localStorage` under the `marketrate:` key prefix (`kit.store` namespaces them as `afrik:marketrate:…`)
- **Hosting:** any cPanel shared plan. No Python, Node, Composer, database or build step.

## 2. Files to upload
The pages reference the kit and the hero image with **parent-relative paths** (`../_kit/kit.css`, `../_media/marketrate-hero.webp`). Uploading the tool folder on its own produces an unstyled page. `_kit/` and `_media/` must sit beside it:

```
public_html/marketrate/
├── index.html  data.html  playbook.html  record.html  app.js  marketrate.css
├── _kit/       kit.css  kit.js  pages.css  pages.js     ← shared, required
└── _media/     marketrate-hero.webp                     ← the only image this tool loads
```

- `index.html` — the tool: editable price board (22 commodities × farm/wholesale/retail), unit-cost deal comparison, haul break-even calculator, season index with a store-to-sell-forward model, negotiation sheet, fair-price check, trade log, editable assumptions.
- `data.html` — the published reference figures behind the board: unit weights in kilograms, tier price tables, market desks with their multipliers, spoilage classes, road distances and route costs, plus what to verify locally.
- `playbook.html` — the operating guide: how to read the board in eight steps, the nine things that make a cheap crate expensive, the hold-or-sell decision, warning signs, a ten-line checklist and a glossary.
- `record.html` — the ledger mirror of what the tool saved in this browser (`afrik:marketrate:log`): sortable, removable, with CSV export and a print sheet.

If you would rather keep the repository layout, upload `demos/marketrate/`, `demos/_kit/` and `demos/_media/` unchanged under `public_html/demos/` and open the tool at `/demos/marketrate/`; the relative paths resolve as-is.

## 3. Install on cPanel
1. cPanel → **Domains**: add `marketrate.yourdomain.com` (or a subfolder). If you use the subdomain, set its document root to `public_html/marketrate`.
2. Upload the tree above with cPanel File Manager or SFTP in binary mode. Directory names are case-sensitive on Linux: `_kit` and `_media` must keep the underscore and the lower case.
3. Permissions: folders `755`, files `644`. No PHP, handler or rewrite rule is involved.
4. No database, no users, no cron jobs, no environment variables. Nothing to configure on the server.
5. Make sure `.htaccess` (if the host injects one) does not rewrite `.css`, `.js` or `.webp`.
6. Open `https://marketrate.yourdomain.com/` and confirm the certificate is active; the clipboard and download features expect a secure context.
7. **Hard-refresh** (`Ctrl+Shift+R`) after any kit edit. All four pages request `../_kit/kit.css?v=6`, `kit.js?v=6`, `pages.css?v=6` and `pages.js?v=6`, and the tool's own `app.js` and stylesheet carry the same suffix. The number lives in `KIT_VERSION` in `tools/build_pages.mjs` and in the four `index.html` links — bump both together when the kit changes, or a returning visitor gets the old copy on one page and the new one on the rest.

## 4. Configuration / keeping it accurate
- Editable in the page, persisted per browser: every board cell, the unit-to-kilogram table, spoilage loss per class, market multipliers, the transport rate (₦244/km default), the seasonal amplitude (±28% default), and the "Prices as of" date input. `Save my prices`, `Restore reference data` and `Export CSV` sit above the table, with matching restore buttons on the three assumption tables in section 09.
- Authored in `app.js`: `REF_BOARD` (22 commodities), `REF_UNITS` (14 units — crate 22 kg, mudu 0.35 kg, bag 50 kg), `REF_SPOIL` (7 classes), `REF_MARKETS` (27 desks), and the `DIST` city-pair distance table. Edit and re-upload to change the defaults everyone gets on a clean browser.
- These are 2026 planning figures, deliberately rounded. There is no market feed, no scraper and no API call anywhere in the code; the as-of date in the header is what the user last set, not a freshness guarantee.

## 5. Post-deploy checks
1. Request `/marketrate/` and check the network log: `/_kit/kit.css`, `/_kit/kit.js`, `marketrate.css`, `app.js` and `/_media/marketrate-hero.webp` all return `200`. A `404` on `_kit` means the folders were not uploaded as siblings.
2. The board renders all 22 rows with a naira-per-kilogram column and a farm-to-retail spread for each.
3. In Units and deals, entering one crate of tomato and a basket of mudus of the same crop ranks the cheaper deal per kilogram and moves the bars.
4. In the haul calculator, a long trip with the reference rate and a soft-crop spoilage figure flips the verdict from "haul it" to "sell at the gate".
5. Changing the sell month in Season moves the index; buying in a glut month and holding shows the holding and finance cost against the target price.
6. A board edit survives a reload after `Save my prices`, and `Restore reference data` puts the shipped figures back.
7. Log a trade, then open `record.html` from the nav: the row is there, sortable, and `Export CSV` downloads it.
8. At 390px the header collapses to the menu button, the board scrolls inside its own wrapper and the page itself does not scroll sideways.
9. The four nav links (`index.html`, `data.html`, `playbook.html`, `record.html`) all resolve from every page, and the footer's `../cropkeep/`-style cross-links only matter if you deploy the other tools as siblings.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; `app.js` rewrites table rows and readouts from in-page state on every input event |
| Styling | `../_kit/kit.css` (tokens, layout primitives) + `marketrate.css` (tool components); no framework, no preprocessor |
| Behaviour | Vanilla ES5-compatible JavaScript against the global `kit`; `pages.js` adds TOC, sorting and the record table on the three secondary pages |
| Charts | `kit.bars` CSS bar rows and `kit.sparkline` inline SVG; one seasonal sparkline, no chart library |
| Assets | One WebP hero with explicit `width`/`height`, inline SVG data-URI favicon, Google Fonts (`Fraunces`, `Inter`, `JetBrains Mono`) with `display=swap` |
| Accessibility | Skip link, landmarks, `aria-live` result panels, labelled inputs and selects, `prefers-reduced-motion` honoured, `:focus-visible` ring from the kit |
| Persistence | `localStorage` via `kit.store`: `afrik:marketrate:board`, `:units`, `:spoil`, `:markets`, `:settings`, `:deals`, `:log`; CSV export and print are the archive |
| Security | No secrets, no auth, no outbound calls; user-entered names pass through an escaping helper before hitting the DOM |
| Performance | One request per asset, no build step, ~80 KB `app.js` + 20 KB tool CSS + 24 KB kit CSS; page is usable offline once loaded |

### Known limits
- No server: nothing is shared, synced or centralised — a cooperative gets one board per device, and because `localStorage` is per browser and per origin, clearing site data removes the board edits and the trade log.
- Reference prices, unit weights, distances and market multipliers are 2026 indicative figures and must be updated by hand.
- Crate and mudu weights vary by market and by load, so the override field exists; the seasonal index is a shaped estimate with a user-set amplitude, not a forecast.
- Nothing here is a price quotation, a market feed, or financial or trading advice.
