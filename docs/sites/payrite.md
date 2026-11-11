# PayRite — deployment guide & tech report

## 1. What you are deploying
A monthly Nigerian payroll computation: gross-to-net PAYE with reliefs, pension and NHF, the employer-side cost, and a roster you can export for an accountant.

- **Local source:** `demos/payrite`
- **Stack:** HTML5, CSS3, vanilla JavaScript on the shared `/_kit` layer
- **Database:** none — every calculation runs in the browser, saved records live in `localStorage` under the `payrite:` key prefix (`kit.store` namespaces them as `afrik:payrite:bands` and `afrik:payrite:roster`)
- **Hosting:** any cPanel plan. No Python, Node, PHP, Composer or build step.

## 2. Files to upload
Six files in the tool folder, plus the two shared folders it loads from:

```
public_html/payrite/
├── index.html  data.html  playbook.html  record.html  app.js  payrite.css
├── _kit/       kit.css kit.js pages.css pages.js    ← shared, required
└── _media/     payrite-hero.webp                     ← the only media file used
```

`_kit/` and `_media/` must be **siblings** of `payrite/` — that is, `public_html/_kit/` and `public_html/_media/` — because the pages request `../_kit/kit.css` and `../_media/payrite-hero.webp`. Deploying the tool folder alone yields an unstyled page with a broken hero. Keeping the repository layout instead? Upload the contents of `demos/` unchanged to `public_html/demos/` so `demos/payrite/`, `demos/_kit/` and `demos/_media/` sit side by side, and open `/demos/payrite/`. Paths are relative, so neither arrangement needs a rewrite.

- `index.html` — the tool: salary components, live net/PAYE/pension/NHF readout, verdict box, relief-comparison table, editable PAYE bands, split bars and donut, roster and a nine-step worked example.
- `data.html` — the published reference figures (consolidated relief, rent cap, bands, remittance table) with search and click-to-sort.
- `playbook.html` — the operating guide: eight payroll steps, six ranked mistakes, checklist, remittance calendar, glossary.
- `record.html` — the ledger mirror of `afrik:payrite:roster` for this browser: sortable table, CSV export, print, clear.

## 3. Install on cPanel
1. cPanel → **Domains**: create `payrite.yourdomain.com` with document root `public_html/payrite`, or use that subfolder under an existing domain.
2. Extract the zip (four HTML files, `app.js`, `payrite.css`, `_kit/`, `_media/`) so the tree above is exact, then delete the archive from the document root.
3. Permissions `755` on folders, `644` on files. No database, cron job, environment variable or `.htaccess` rule is required.
4. Load `https://…/payrite/` to confirm the certificate, and check the Network panel: `/_kit/kit.css`, `/_kit/pages.css` and `/_media/payrite-hero.webp` must return `200`, not `404`.
5. `kit.css` and `pages.css` are shared with SunCost, SealCheck and FuelMeter, so hard-refresh (`Ctrl`+`F5`) those three after any kit change. Rollback is re-extracting the previous zip; revert `_kit/` only when you mean to change all four sites.

## 4. Configuration / keeping it accurate
Editable in the page and remembered per browser: the six PAYE band widths and rates (`afrik:payrite:bands`, with **Restore default bands**) and the roster rows. Authored in `app.js` and needing an edit plus re-upload: the shipped `DEFAULT_BANDS`, the consolidated relief formula (₦200,000 + 20% of annual gross), rent relief at 20% of rent capped at ₦6,250,000, pension at 8% employee / 10% employer, and NHF at 2.5% each side.

These are 2026 reference figures, not a live FIRS or state revenue feed, and the tool makes no network request. `data.html` labels itself "Reference · 2026 filing year"; the calculator prints no as-of date, so stamp your review date into the footer note at deploy time. A visitor who edits the bands changes only their own browser — the published defaults remain as authored.

## 5. Post-deploy checks
1. `/payrite/` is styled and the hero loads, confirming `_kit/` and `_media/` sit beside the tool folder.
2. The shipped package (₦280,000 basic + ₦84,000 + ₦56,000 + ₦20,000) paints a net figure, a take-home donut and no console errors.
3. A ₦400,000 basic package produces a net figure and a nine-step worked example whose arithmetic agrees with it: gross − PAYE − pension − NHF equals the printed net.
4. Unticking **Claim rent relief** moves PAYE, the verdict and the relief-comparison table; unticking pension flips the verdict to "No pension deducted".
5. Changing a band rate to 10% changes every dependent figure and survives a reload; **Restore default bands** puts it back to 7%.
6. **Add to roster** appends a row and updates the totals; the same row shows on `record.html`. **Export CSV** on the tool page downloads `payrite-roster-YYYY-MM-DD.csv`, the one on `record.html` downloads `payrite-record.csv`.
7. Zeroing the amounts shows "enter a package to begin" — no stray `₦0` presented as a result.
8. The four page links (tool, Data, Playbook, Record) all resolve, and at 390px the calculator and results stack, the roster scrolls inside its table wrapper, the hamburger opens the nav and the page does not scroll sideways.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; results are written into fixed elements by `app.js` |
| Styling | Shared `kit.css` tokens plus one hand-written `payrite.css`; `pages.css` styles the inner pages |
| Behaviour | One IIFE over the `kit.*` helpers, recomputing on every keystroke; no framework, no runtime dependency |
| Charts | `kit.donut` take-home ring and `kit.bars` monthly split in inline SVG/CSS; no chart library |
| Assets | One 1536×1024 WebP with explicit dimensions; Google Fonts (Fraunces, Inter, JetBrains Mono) with `display=swap` |
| Accessibility | Skip link, labelled inputs and switches, `aria-live` result panel, visible focus, `prefers-reduced-motion` respected |
| Persistence | `localStorage` only (`afrik:payrite:bands`, `afrik:payrite:roster`); a blocked write falls back to a toast plus CSV |
| Security | No secrets, no endpoints, no uploads; roster names are escaped before entering the DOM |
| Performance | Six files and one image; the only network call is the font request |

### Known limits
- No server, no accounts and no sync: the roster and band edits are per browser, die with cleared site data, and CSV export is the only way off the device.
- Bands, reliefs and caps are 2026 reference figures that must be re-checked against current PITA/FIRS guidance by hand; nothing updates itself.
- No VAT, state levies, benefit-in-kind, withholding or filing; figures are indicative and are not a tax opinion.
- Footer cross-links to `../suncost/`, `../sealcheck/` and `../../` resolve only if the rest of the portfolio is deployed at the same depth; deploying PayRite alone leaves them dead.
