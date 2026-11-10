# ThatGuys Fitness — deployment guide & tech report

## 1. What you are deploying
A five-day bodyweight training site with an interactive Coach Studio: eight exercises, three photographed phases each, captions, playback controls and a spoken voice guide.

- **Local source:** `sites/thatguys-fitness`
- **Stack:** HTML5, CSS3, vanilla JavaScript, WebP imagery
- **Database:** none. No accounts, orders or stored user data. Saved preferences use `localStorage` only.
- **Hosting:** any cPanel plan. No Python, Node, Composer or build step required.

## 2. Files to upload
Upload the **contents** of `sites/thatguys-fitness` into the target folder (for example `public_html/fitness/`).
Keep this structure:

```
index.html        home
workouts.html     five-day plan
demos.html        Coach Studio library
guide.html        training guide
styles.css        site + studio design system
script.js         plan interactions
assets/coaches/   24 WebP coach frames (8 exercises x 3 phases)
```

`index.html` must sit at the folder root so the domain or subdomain opens it directly.

## 3. Install on cPanel
1. In cPanel → **Domains**, create the subdomain (e.g. `fitness.yourdomain.com`) and point its document root at the folder you uploaded.
2. If you must use `public_html`, upload into `public_html/fitness/` and open `https://yourdomain.com/fitness/`.
3. Set file permissions: folders `755`, files `644`.
4. No database, environment variables or cron jobs are required.
5. Load the site over **https** once to confirm the certificate is active.

## 4. Optional configuration
- To add an exercise, add a record to the `EXERCISES` array in `assets/js/studio.js` and drop three frames named `{id}-01.webp`, `{id}-02.webp`, `{id}-03.webp` into `assets/coaches/`.
- Frames are 900x900 WebP at quality 82; keep that size to preserve the crossfade.
- The voice guide uses the browser's SpeechSynthesis API — no key or service required.

## 5. Post-deploy checks
1. Home page loads and the hero image appears.
2. **Open Coach Studio** opens `demos.html` and renders eight cards.
3. Filter chips (All / Push / Pull / Legs / Hinge / Core) change the grid.
4. Clicking a card opens the player; the three phases advance and captions change.
5. Space toggles playback, arrow keys step, Escape closes.
6. The deep link (`demos.html#squat`) opens that exercise directly.
7. On a phone (390px) the nav wraps without horizontal scrolling.

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
- The demonstrations are real photographic phases played in sequence, not video. This is stated in the interface rather than disguised.
- The spoken guide depends on the voices installed on the visitor's device.

## 7. Rollback
Keep a zip of the previous version before uploading. To roll back, delete the new files and re-upload the previous zip. Coach frames are content, not user data, so a rollback is safe.
