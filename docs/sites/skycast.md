# SkyCast — deployment guide & tech report

## 1. What you are deploying
A weather lookup powered by the public Open-Meteo API, with city search, geolocation, an hourly strip, a seven-day outlook, unit switching and recent cities.

- **Local source:** `sites/skycast`
- **Stack:** HTML5, CSS3, vanilla JavaScript, Open-Meteo API
- **Database:** none. Recent cities and the unit preference are kept in `localStorage`. No API key is required.
- **Hosting:** any cPanel plan. No Python, Node, Composer or build step required.

## 2. Files to upload
Upload the **contents** of `sites/skycast` into the target folder (for example `public_html/weather/`).
Keep this structure:

```
index.html          markup and layout
style.css           design system and responsive rules
app.js              data fetching, search, units, geolocation
countries-data.js   bundled country and city dataset
```

`index.html` must sit at the folder root so the domain or subdomain opens it directly.

## 3. Install on cPanel
1. In cPanel → **Domains**, create the subdomain (e.g. `weather.yourdomain.com`) and point its document root at the folder you uploaded.
2. If you must use `public_html`, upload into `public_html/weather/` and open `https://yourdomain.com/weather/`.
3. Set file permissions: folders `755`, files `644`.
4. No database, environment variables or cron jobs are required.
5. Load the site over **https** once to confirm the certificate is active.

## 4. Optional configuration
- Open-Meteo needs no key. If the host blocks outbound HTTP, the browser still calls the API directly — only the visitor's network matters.
- To change the default city, edit the initial `query` value in `app.js`.

## 5. Post-deploy checks
1. The page loads live conditions for the default city (not placeholder values).
2. Typing two letters shows autocomplete suggestions; selecting one updates the dashboard.
3. The °C/°F toggle converts every temperature, including the hourly strip and the seven-day range.
4. The unit choice survives a reload.
5. "Use my location" either locates the visitor or shows a clear permission message.
6. A nonsense search shows an error state rather than a blank panel.
7. Recent cities persist and reload their forecast.

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
- Forecast accuracy and availability depend on Open-Meteo.
- Geolocation requires HTTPS and visitor permission.

## 7. Rollback
Keep a zip of the previous version before uploading. To roll back, delete the new files and re-upload the previous zip. No stored data beyond browser `localStorage`.
