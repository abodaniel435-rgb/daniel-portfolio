# FeesPlan — deployment guide & tech report

## 1. What you are deploying
One problem: Nigerian school fees arrive as a lump on a fixed date, and the levy lines that ride along with them are never budgeted for. FeesPlan turns a household's real per-term bill into the monthly sinking-fund figure needed to meet it, compares the ways of paying, and produces a dated plan and a letter.

- **Local source:** `demos/feesplan`
- **Stack:** four static HTML pages, `feesplan.css`, one `app.js`, plus the shared `demos/_kit` design system
- **Database:** none — every calculation runs in the browser; saved records live in `localStorage` under the `feesplan:` key prefix (`kit.store` namespaces them as `afrik:feesplan:…`)
- **Hosting:** any cPanel shared plan. No Python, Node, Composer, database or build step.

## 2. Files to upload
The pages reference the kit and the hero image with **parent-relative paths** (`../_kit/kit.css`, `../_media/feesplan-hero.webp`). Uploading the tool folder alone gives an unstyled page:

```
public_html/feesplan/
├── index.html  data.html  playbook.html  record.html  app.js  feesplan.css
├── _kit/       kit.css  kit.js  pages.css  pages.js     ← shared, required
└── _media/     feesplan-hero.webp                       ← the only image this tool loads
```

- `index.html` — the tool: per-child planner with the levy lines, a six-year cost horizon, the sinking-fund maths, a five-option comparison (saving, lump from income, salary advance, school instalments, ajo circle), a payment-plan builder, three letter templates, a circle ledger, and the editable fee bands.
- `data.html` — the published reference figures: the numbers that decide the plan, the month-by-month calendar of when money falls due, every line that appears on a fee bill and whether it recurs, and what each band costs per term and on resumption day.
- `playbook.html` — the step-by-step operating guide: ten steps in order, eight traps and what each costs, the paperwork to demand first, how to read a bill, the year's rhythm, straight answers and a glossary.
- `record.html` — the ledger mirror of what the tool saved in this browser (`afrik:feesplan:plans`): each dated plan, its total, how many payments are ticked off, what is still owed and the next date that matters.

To keep the repository layout instead, upload `demos/feesplan/`, `demos/_kit/` and `demos/_media/` unchanged under `public_html/demos/` and open `/demos/feesplan/`.

## 3. Install on cPanel
1. cPanel → **Domains**: add `feesplan.yourdomain.com` with document root `public_html/feesplan`, or use a subfolder of `public_html` and open `https://yourdomain.com/feesplan/`.
2. Upload the tree above. `_kit` and `_media` must be **siblings** of `feesplan`, not inside it, or every `../` request resolves one level too high. Keep the underscore and the lower case.
3. Permissions: folders `755`, files `644`. There is no server-side code to execute.
4. No database, no cron job, no mail configuration; the letters are copied or printed, never sent.
5. Load the site over **https** and confirm the certificate: the copy-to-clipboard button in the letter section relies on a secure context and falls back to a manual-copy toast without one.
6. **Hard-refresh** (`Ctrl+Shift+R`) after any kit edit. All four pages request `../_kit/kit.css?v=6`, `kit.js?v=6`, `pages.css?v=6` and `pages.js?v=6`, and the tool's own `app.js` and stylesheet carry the same suffix. The number lives in `KIT_VERSION` in `tools/build_pages.mjs` and in the four `index.html` links — bump both together when the kit changes, or a returning visitor gets the old copy on one page and the new one on the rest.

## 4. Configuration / keeping it accurate
- Editable in the page, saved per browser: each child's per-term fees and all eight levy lines, the school-type preset applied to a child, the term bill and due date, what is already saved and what can be put aside monthly, fund rate, fee-inflation rate and horizon, advance rate and repayment months, instalment surcharge, circle size, contribution frequency and default rate, plus the whole fee-band table in section 09 with its `Restore default bands` button.
- Authored in `app.js`: `DEFAULT_BANDS` — public ₦12,000, low-cost private ₦35,000, standard private ₦120,000, premium private ₦450,000 (3 terms) and public ₦150,000 / private ₦650,000 university (2 terms) — with their levy stacks; `EXTRAS` (which lines recur per term and which per year); the class list from Creche to University 400L; and the three worked personas.
- These are 2025/2026-session indicative figures, not quotations. Fees move when the school writes to you; the letter form ships pre-filled with "Second Term, 2025/2026 session", and the tool has no calendar feed. Update `DEFAULT_BANDS` and re-upload so new visitors get sane starting numbers.

## 5. Post-deploy checks
1. Check the network log on `/feesplan/`: `/_kit/kit.css`, `/_kit/kit.js`, `feesplan.css`, `app.js` and `/_media/feesplan-hero.webp` all return `200`.
2. Add a second child: the household per-term and per-year totals both rise, and the year total divides correctly across the band's number of terms.
3. In the sinking-fund section, moving the due date changes the monthly sinking-fund target — five weeks out costs far more per month than eight months out — and the "months from today" note tracks the date.
4. With the fund rate at 0% the required deposit is exactly the shortfall divided by the months left; a positive rate lowers it.
5. In the comparison, raising the advance rate reorders the five options and flips the "Feasible?" column against what can be saved monthly.
6. Build a payment plan, tick a payment as paid, save it, reload: the plan appears in the saved list and again on `record.html`, and `Export CSV` downloads it.
7. Type a school name and a total in section 07 and confirm all three letter tabs (request, reminder, withheld-examinations steps) update, then check Copy and Print.
8. Add a member to the circle in section 08: the pot, the cycles needed before the due date and the arrears list all change.
9. At 390px the child cards, options table and letter sheet stack without sideways scrolling, and the four nav links (`index.html`, `data.html`, `playbook.html`, `record.html`) resolve on every page.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Four static HTML documents; `app.js` paints child cards, schedules, option rows and the circle ledger from in-page state |
| Styling | `../_kit/kit.css` + `feesplan.css`; custom-property tokens, no framework, no preprocessor |
| Behaviour | Vanilla JavaScript on the shared `kit` global; `pages.js` provides the TOC, table sorting and the saved-plans table |
| Charts | `kit.bars` for the horizon, option and circle panels; no chart library, no canvas |
| Assets | One WebP hero with explicit `width`/`height`, inline SVG data-URI favicon, Google Fonts with `display=swap` |
| Accessibility | Skip link, landmarks, `aria-live` verdict boxes, labelled inputs, `role="tablist"` letters, `prefers-reduced-motion` honoured |
| Persistence | `localStorage` via `kit.store`: `afrik:feesplan:bands`, `:kids`, `:plans`, `:circle`; CSV export is the only way out of the browser |
| Security | No secrets, no authentication, no network calls; free-text (child names, schools) is escaped before insertion |
| Performance | ~80 KB `app.js` + 15 KB tool CSS + 24 KB kit CSS, no build step, readable offline once loaded |

### Known limits
- Footer cross-links to the other tools (`../suncost/`, `../recircle/`, …) and to the portfolio root (`../../`) resolve only when the whole `demos/` tree is deployed at the same depth; a single-tool ZIP leaves them dead, so keep the sibling folders or accept those two broken links.
- No server: nothing syncs between a parent's phone and a family computer, and no bursar sees the plan. Because `localStorage` is per browser and per origin, clearing site data deletes the bands, the children, the saved plans and the circle ledger.
- Fee bands, levy lines and inflation assumptions are 2025/2026 reference values that need manual updating each session.
- The circle model estimates default risk from a rate you supply; it cannot know whether your circle is honest.
- Advance and instalment maths assume the flat rates you type; a real offer may price differently.
- Nothing here is financial, legal or enrolment advice, and no letter is a binding agreement with a school.
