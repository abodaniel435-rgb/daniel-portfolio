# SealCheck — deployment guide & tech report

## 1. What you are deploying
Counter-side screening for falsified and heat-damaged medicines in Nigeria: fourteen weighted visual checks plus registration-number, barcode, expiry and cold-chain parsers, producing a score, red-flag overrides and a report to hand to a pharmacist or regulator.

- **Local source:** `demos/sealcheck`
- **Stack:** HTML5, CSS3, vanilla JavaScript on the shared `/_kit` layer
- **Database:** none — every calculation runs in the browser, saved records live in `localStorage` under the `sealcheck:` key prefix (`afrik:sealcheck:weights`, `:unsure`, `:budgets`, `:history`)
- **Hosting:** any cPanel plan. No Python, Node, PHP, Composer or build step.

## 2. Files to upload
Six files in the tool folder, plus the two shared folders it loads from:

```
public_html/sealcheck/
├── index.html  data.html  playbook.html  record.html  app.js  sealcheck.css
├── _kit/       kit.css kit.js pages.css pages.js    ← shared, required
└── _media/     sealcheck-hero.webp                   ← the only media file used
```

`_kit/` and `_media/` must be **siblings** of `sealcheck/`, one level above it (`public_html/_kit/`, `public_html/_media/`), because the pages request `../_kit/kit.css` and `../_media/sealcheck-hero.webp`. The tool folder alone renders unstyled with no hero. To keep the repository layout, upload the contents of `demos/` unchanged to `public_html/demos/` (`demos/sealcheck/`, `demos/_kit/`, `demos/_media/`) and open `/demos/sealcheck/`; all paths are relative, so nothing needs rewriting.

- `index.html` — the tool: the 14-check scoring checklist in four groups (weights total 100), score donut and group bars, registration and barcode parsers, expiry reader, degree-hour cold-chain calculator, VVM stages, illustrative product register, recall sample table, report builder and inspection log.
- `data.html` — the published reference figures: number shapes and what each is printed on, which seals copy well, storage bands per dosage form, in-use clocks, recall-prone categories, GS1 prefix ranges, sources.
- `playbook.html` — the operating guide: the seven looks in catch order, eight ways a fake passes a lazy buyer, the counter checklist, reporting steps, glossary.
- `record.html` — the ledger mirror of `afrik:sealcheck:history` for this browser: sortable table, CSV export, print, clear.

## 3. Install on cPanel
1. cPanel → **Domains**: create `sealcheck.yourdomain.com` with document root `public_html/sealcheck`, or use that subfolder of an existing domain.
2. Extract the zip (four HTML files, `app.js`, `sealcheck.css`, `_kit/`, `_media/`) so the tree above is exact, then delete the archive from the document root.
3. Permissions `755` on folders, `644` on files; no database, cron, environment variable or handler is required, since parsing and scoring are client-side.
4. Load `https://…/sealcheck/` to confirm the certificate and check the Network panel for `200` on `/_kit/kit.css`, `/_kit/pages.css` and `/_media/sealcheck-hero.webp`.
5. `_kit/` is shared with PayRite, SunCost and FuelMeter: after replacing it, hard-refresh (`Ctrl`+`F5`) all four, because the stylesheet is cached once per origin. Rollback is re-extracting the previous zip; `_kit/` reverts four sites at once.

## 4. Configuration / keeping it accurate
Editable in the page, remembered per browser: the per-check weights (`afrik:sealcheck:weights`, with **Restore default weights**), the credit given to an "unsure" answer (`:unsure`, default 0.4), the five cold-chain budgets in degrees and hours (`:budgets`, restorable) and the inspection log (`:history`, capped at 200 rows). Authored in `app.js` and needing a code edit plus re-upload: the fourteen checks with their groups and fatal flags, the eight registration-number patterns and prefix meanings, the GS1 prefix table, the seventeen illustrative register rows with their price bands, the ten sample recall notices, the five hard-stop classes and the VVM stage texts.

All of it is curated teaching data, not a live register: the recall rows are invented fiction with invented batch numbers, and the identity field deliberately stops at "not performed here — verify on the NAFDAC register". The tool shows no as-of date, so date your review when you deploy; the sample notices carry 2025–2026 dates purely illustratively, and price bands are planning ranges. Visitor edits never travel — the shipped arrays are what other people see.

## 5. Post-deploy checks
1. `/sealcheck/` is styled and the hero loads, proving `_kit/` and `_media/` were uploaded as siblings.
2. With nothing answered the panel reads "Nothing inspected yet", the score is a dash and the flags box is hidden.
3. **Load worked example** answers all fourteen checks, lands the score in the "inspect further" band and still returns "Do not use — report it": a red flag overrides the arithmetic, because the glue-seam check is fatal.
4. `A-12345` is accepted as a recognised imported shape, while `A-1234`, `A12345` and `B-12345` are rejected and each rejection also appears as an override.
5. A 13-digit code beginning 614 reads as Nigeria and a bad final digit is flagged; `600`–`601` against "Made in Nigeria" reports a conflict, and an 8-digit code refuses to assert a country.
6. `MAR 2027` resolves to month end, `2027-03-05` to the exact day, and an ambiguous `05/06/2027` lists both readings and instructs you to act on the earlier; cold chain at 30 °C for 10 hours on a vaccine returns discard, and a freeze or shake answer discards whatever the degree-hour budget says.
7. Raising a check weight changes score, coverage and the worked list and persists after reload; **Save to inspection history** prepends a row that also appears on `record.html`. **Export CSV** on the tool page downloads `sealcheck-inspections-YYYY-MM-DD.csv`, the one on `record.html` downloads `sealcheck-record.csv`.
8. The four page links (tool, Data, Playbook, Record) resolve, and at 390px each check row stacks its yes/no/unsure control, the tables scroll inside their wrappers and the page does not move sideways.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static documents; the checklist, register tables and verdict are built by `app.js` from arrays |
| Styling | Shared `kit.css` tokens plus a substantial `sealcheck.css` for the check grid, VVM disc and flags; `pages.css` for inner pages |
| Behaviour | One IIFE over the `kit.*` helpers: radio segments, delegated input, pure parsing and scoring functions |
| Charts | `kit.donut` for the score, `kit.bars` for group credit, CSS track for the degree-hour budget; no chart library |
| Assets | One 1536×1024 WebP with explicit dimensions; Google Fonts (Fraunces, Inter, JetBrains Mono) with `display=swap` |
| Accessibility | Skip link, `role="radiogroup"` segments with per-check labels, six `aria-live` regions, flags and verdicts carried as text rather than colour alone, reduced motion honoured |
| Persistence | `localStorage` for weights, unsure credit, budgets and a 200-row history; a blocked save falls back to the copyable report text |
| Security | No network calls whatsoever; product, batch, outlet and note strings are escaped before insertion, and recall content is labelled fiction |
| Performance | Six files and one image; scoring is a single pass over fourteen checks per answer |

### Known limits
- A passing visual check proves nothing about content: an under-dosed tablet that prints well is invisible here, and the page says so.
- No NAFDAC register, laboratory or GS1 lookup is performed — the number check validates shape only, and the recall table is invented fiction rather than a notice feed.
- Weights, budgets and the log are per browser, capped at 200 inspections, lost with cleared site data and never synced between devices; CSV export is the only handoff.
- Degree-hour budgets are screening guides, not product-specific stability data, and reporting duties must be confirmed with NAFDAC or the state authority: this is a screening and reporting aid, not a verification service, a laboratory or medical or legal advice.
- Links to `../payrite/`, `../suncost/`, `../marketrate/` and `../../` resolve only when the rest of the portfolio sits at the same depth.
