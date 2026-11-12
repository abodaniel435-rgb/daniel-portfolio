# CropKeep — deployment guide & tech report

## 1. What you are deploying
One problem: a Nigerian harvest is lost after the farm gate, not before it — heat, sacks, an open truck bed and a market you arrive at too late. CropKeep puts a naira figure on that loss for a named crop, tests the handling fixes and the storage technologies against your own budget, and ranks the ones that actually pay back.

- **Local source:** `demos/cropkeep`
- **Stack:** four static HTML pages, `cropkeep.css`, one `app.js`, plus the shared `demos/_kit` design system
- **Database:** none — every calculation runs in the browser; saved records live in `localStorage` under the `cropkeep:` key prefix (`kit.store` namespaces them as `afrik:cropkeep:…`)
- **Hosting:** any cPanel shared plan. No Python, Node, Composer, database or build step.

## 2. Files to upload
Every page pulls the kit and the hero image through **parent-relative paths** (`../_kit/kit.css`, `../_media/cropkeep-hero.webp`), so `_kit/` and `_media/` have to be uploaded as **siblings** of the tool folder, not inside it:

```
public_html/cropkeep/
├── index.html  data.html  playbook.html  record.html  app.js  cropkeep.css
├── _kit/       kit.css  kit.js  pages.css  pages.js     ← shared, required
└── _media/     cropkeep-hero.webp                       ← the only image this tool loads
```

- `index.html` — the tool: the loss calculator (crop, weight, value, days to market, ambient temperature, handling credits, cycles, budget, grid supply), the temperature ladder, the ranked storage-option matrix with payback maths, the harvest-window planner and the batch ledger.
- `data.html` — the published reference figures: unimproved loss and shelf life per crop at Nigerian ambient heat, the constants you cannot tick your way past, what each storage technology costs and prevents, safe temperature bands and packaging penalties.
- `playbook.html` — the step-by-step operating guide: nine steps in order, eight traps and the money each one takes, the before-10am checklist, a day-by-day plan for perishables, the signs a lot will not arrive intact, straight answers and a glossary.
- `record.html` — the ledger mirror of what the tool saved in this browser (`afrik:cropkeep:batches`): crop, weight, value, storage chosen, projected loss, actual loss entered afterwards, and the gap between the two.

Keeping the repository layout instead? Upload `demos/cropkeep/`, `demos/_kit/` and `demos/_media/` unchanged under `public_html/demos/` and open `/demos/cropkeep/`.

## 3. Install on cPanel
1. cPanel → **Domains**: create `cropkeep.yourdomain.com` with document root `public_html/cropkeep`, or place the folder in `public_html` and open `https://yourdomain.com/cropkeep/`.
2. Upload the tree above in binary mode via File Manager or SFTP. Keep the leading underscores and the lower case in `_kit` and `_media`.
3. Permissions: folders `755`, files `644`. No handlers, no PHP, no rewrite rules needed.
4. No database, no cron jobs, no environment variables and no sensor or weather API to provision — temperature is a typed field.
5. Confirm the certificate by loading the site over **https**; the downloads and clipboard paths expect a secure context.
6. **Hard-refresh** (`Ctrl+Shift+R`) after any kit edit. All four pages request `../_kit/kit.css?v=6`, `kit.js?v=6`, `pages.css?v=6` and `pages.js?v=6`, and the tool's own `app.js` and stylesheet carry the same suffix. The number lives in `KIT_VERSION` in `tools/build_pages.mjs` and in the four `index.html` links — bump both together when the kit changes, or a returning visitor gets the old copy on one page and the new one on the rest.

## 4. Configuration / keeping it accurate
- Editable in the page: the crop table (baseline loss, loss floor, shelf-life days at the reference temperature, base temperature), the handling-credit point values, the achieved-loss figures per storage technology, all indicative prices, and the sizing constants — the `Q10` interval (10 °C default), harvest-to-market season length (4 months), bag size (50 kg), capacity slack (10%) and the refrigerated-van range (₦18,000–45,000 a trip). `Restore all defaults` in section 07 puts the shipped figures back.
- Authored in `app.js`: `CROPS` (14 crops across the `perish` and `dry` classes — tomato 40% baseline, 2.5 days, greens 45% at 1.6 days, maize 18% over 180 days), `CREDITS` (six handling practices with point values), `OPTIONS` (nine technologies from hermetic PICS bags to a CoolBot room, each with capacity, seasons of life, power requirement and achieved loss), `DEFAULT_PRICES` (bag ₦3,500, improved store ₦380,000, solar dryer ₦145,000, charcoal cooler ₦190,000, CoolBot ₦950,000, evaporative chamber ₦120,000, processing line ₦260,000, shared cold store ₦6,000/week) and `DEFAULTS`.
- These are mid-points of published Nigerian post-harvest ranges, edited and rounded — not measurements, and not a live source. The tool says so in the details block labelled "Indicative prices and costs — 2026 market" and in section 07; update `app.js` and re-upload when you have better local numbers.

## 5. Post-deploy checks
1. Load `/cropkeep/` and check the network tab: `/_kit/kit.css`, `/_kit/kit.js`, `cropkeep.css`, `app.js` and `/_media/cropkeep-hero.webp` all `200`.
2. Raising the ambient temperature shortens the survive-the-trip verdict: tomato at 28 °C with a two-day window reads "yes, with days to spare", and 34 °C flips it to "no, it is X days late".
3. The temperature ladder re-renders as you change the same field — shelf life roughly halves for every `Q10` degrees above the crop's base temperature.
4. Ticking "Crates, not sacks" lowers the adjusted loss and raises the avoided naira; the adjusted figure never drops below that crop's floor.
5. In the option matrix, changing cycles, budget or grid availability re-ranks the technologies, and grid-powered options are penalised or excluded when you say there is no supply.
6. Set the weekly volumes in the harvest-window planner and confirm the capacity line and the panic-week callout move with them.
7. Press `Add this batch to the ledger`, then enter the actual loss on the row: the difference column fills, the totals update, the batch appears on `record.html`, and `Export CSV` downloads it.
8. Edit a baseline figure in section 07 and confirm the calculator follows it immediately; `Restore all defaults` reverses it.
9. At 390px the calculator stacks, the matrix and batch tables scroll inside their own wrappers with no sideways page scroll, and the four nav links (`index.html`, `data.html`, `playbook.html`, `record.html`) resolve.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; `app.js` recomputes the whole answer set from the form on each input and repaints the readouts |
| Styling | `../_kit/kit.css` + `cropkeep.css`; CSS custom properties, no framework, no preprocessor, print rules included |
| Behaviour | Vanilla JavaScript over the shared `kit` global; `pages.js` supplies the TOC, sortable tables and the batch record view |
| Charts | CSS/SVG bar rows from `kit.bars` for the ladder, planner and rankings; no chart library |
| Assets | One WebP hero with explicit `width`/`height`, inline SVG data-URI favicon, Google Fonts (`Fraunces`, `Inter`, `JetBrains Mono`) with `display=swap` |
| Accessibility | Skip link, landmarks, `aria-live` verdict box, labelled numeric inputs with `inputmode`, table captions, `prefers-reduced-motion` honoured |
| Persistence | `afrik:cropkeep:crops`, `:prices`, `:assumptions`, `:credits`, `:batches`; CSV export and print are the archive |
| Security | No secrets, no authentication, no outbound requests; batch labels and free text escaped before rendering |
| Performance | ~60 KB `app.js` + 16 KB tool CSS + 24 KB kit CSS, no build step, usable offline once loaded |

### Known limits
- Footer cross-links to the other tools (`../suncost/`, `../recircle/`, …) and to the portfolio root (`../../`) resolve only when the whole `demos/` tree is deployed at the same depth; a single-tool ZIP leaves them dead, so keep the sibling folders or accept those two broken links.
- No server: nothing is shared with an aggregator, a cooperative office or another phone — and because `localStorage` is per browser and per origin, a cleared cache or a new device loses the assumptions and the batch history.
- Loss rates and shelf lives are published-range mid-points, not laboratory or farm measurements, and they vary by variety, season and handling.
- Aflatoxin and moisture are out of scope: the tool says so, and that needs a lab test.
- Storage prices are 2026 indicative figures and must be re-quoted locally before anybody spends money.
- The environmental and CO₂ lines are context, not a verified credit; nothing here is agronomic, financial or food-safety advice.
