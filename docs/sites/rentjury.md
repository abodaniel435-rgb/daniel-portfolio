# RentJury — deployment guide & tech report

## 1. What you are deploying
A rent and agent-fee reference for Nigeria: a verdict on any quoted rent, rent bands per state, an agent-fee compliance check, and community reports.

- **Local source:** `sites/rentjury`
- **Stack:** HTML5, CSS3, vanilla JavaScript, optional Firebase (guarded)
- **Database:** none. The rent-band dataset is local; community submissions use `localStorage`, with Firebase writes only when a config exists.
- **Hosting:** any cPanel plan. No Python, Node, Composer or build step required.

## 2. Files to upload
Upload the **contents** of `sites/rentjury` into the target folder (for example `public_html/rentjury/`).
Keep this structure:

```
index.html        landing page and quick check
check-price.html  full price checker with live verdict
community.html    community reports and submission form
css/style.css     design system
js/data.js        rent bands and fee standards
js/app.js         verdict engine, filters, forms, persistence
js/firebase.js    optional, guarded Firebase bridge
assets/img/       hero and listing photography
```

`index.html` must sit at the folder root so the domain or subdomain opens it directly.

## 3. Install on cPanel
1. In cPanel → **Domains**, create the subdomain (e.g. `rentjury.yourdomain.com`) and point its document root at the folder you uploaded.
2. If you must use `public_html`, upload into `public_html/rentjury/` and open `https://yourdomain.com/rentjury/`.
3. Set file permissions: folders `755`, files `644`.
4. No database, environment variables or cron jobs are required.
5. Load the site over **https** once to confirm the certificate is active.

## 4. Optional configuration
- The site is fully functional without Firebase. To enable shared reports, add a Firebase web config to `js/firebase.js`; the code must keep working when the config is absent.
- Update rent bands in `js/data.js` as market data changes. Keep the min/median/max ordering.

## 5. Post-deploy checks
1. The quick check on the home page returns a verdict for a valid rent.
2. `check-price.html` updates the verdict live as the state, rent and agent fee change.
3. The range bar places the marker at the quoted price and shows the band values.
4. An invalid or empty rent shows a field error rather than a verdict.
5. Community reports can be filtered by state, sorted and searched.
6. A new report validates, saves and appears in the list after a reload.
7. At 390px the checker stacks with no horizontal scrolling.

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
- Rent bands are reference values, not live market feeds.
- Without Firebase, community reports are per-device.

## 7. Rollback
Keep a zip of the previous version before uploading. To roll back, delete the new files and re-upload the previous zip. No server data; keep a copy of `js/data.js` before changing bands.
