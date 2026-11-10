# Moonlight Events — deployment guide & tech report

## 1. What you are deploying
A global festival field guide: 27 festivals across 10 countries with filters for country, month and tradition, a saved list, generated poster artwork and a live countdown.

- **Local source:** `sites/moonlight-events`
- **Stack:** HTML5, CSS3, vanilla JavaScript, JSON data, SVG posters
- **Database:** none. The festival calendar lives in `data.json`; the saved list uses `localStorage`.
- **Hosting:** any cPanel plan. No Python, Node, Composer or build step required.

## 2. Files to upload
Upload the **contents** of `sites/moonlight-events` into the target folder (for example `public_html/moonlight/`).
Keep this structure:

```
index.html   page structure and sections
style.css    editorial design system
script.js    data load, filters, saved list, countdown, reveal
data.json    the festival calendar
images/      festival artwork (SVG/JPG)
```

`index.html` must sit at the folder root so the domain or subdomain opens it directly.

## 3. Install on cPanel
1. In cPanel → **Domains**, create the subdomain (e.g. `moonlight.yourdomain.com`) and point its document root at the folder you uploaded.
2. If you must use `public_html`, upload into `public_html/moonlight/` and open `https://yourdomain.com/moonlight/`.
3. Set file permissions: folders `755`, files `644`.
4. No database, environment variables or cron jobs are required.
5. Load the site over **https** once to confirm the certificate is active.

## 4. Optional configuration
- Add a festival by appending an object to `data.json` with `id`, `name`, `country`, `month`, `religion`, `date`, `summary` and optional `image`.
- If `image` is omitted, a poster is generated from the festival's name, country and month.
- Dates should be ISO (`YYYY-MM-DD`) so the countdown picks the next event correctly.

## 5. Post-deploy checks
1. The hero shows the next festival with a live countdown and its poster or photograph.
2. The calendar renders every record in `data.json`.
3. Country, month and tradition filters combine correctly; search matches names.
4. Saving a festival updates the header count and survives a reload.
5. "See my list" opens the saved view; removing an item updates it immediately.
6. Bad or missing data leaves the page usable rather than blank.
7. At 390px the hero stacks and the filter bar scrolls without breaking the layout.

## 6. Tech report
| Area | Decision |
| --- | --- |
| Rendering | Static HTML, server-rendered only where noted |
| Styling | One hand-written stylesheet with CSS custom properties; no framework |
| Behaviour | Vanilla JavaScript, progressive enhancement, no runtime dependency |
| Assets | Compressed images with explicit dimensions; fonts loaded with `display=swap` |
| Accessibility | Semantic landmarks, visible focus states, `prefers-reduced-motion` respected |
| Security | No secrets in source; external links use `rel="noopener noreferrer"` |
| Performance | No build step, no blocking third-party scripts beyond the font request |

### Known limits
- The countdown depends on the visitor's clock.
- Festival dates are illustrative for the 2026 edition; verify before publishing.

## 7. Rollback
Keep a zip of the previous version before uploading. To roll back, delete the new files and re-upload the previous zip. Content lives in `data.json` — keep a copy before editing.
