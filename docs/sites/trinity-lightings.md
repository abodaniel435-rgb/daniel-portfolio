# Trinity Lightings — deployment guide & tech report

## 1. What you are deploying
A chandelier showroom: nine photographed pieces with category and finish filters, a quick-view for specifications, and a Lagos showroom visit page with a validated enquiry form.

- **Local source:** `sites/trinity-lightings`
- **Stack:** HTML5, CSS3, vanilla JavaScript, one small PHP image-listing endpoint
- **Database:** none. No database. `listimages.php` reads the `images/` folder and returns JSON.
- **Hosting:** any cPanel plan. No Python, Node, Composer or build step required.

## 2. Files to upload
Upload the **contents** of `sites/trinity-lightings` into the target folder (for example `public_html/trinity/`).
Keep this structure:

```
index.html       home and featured pieces
catalog.html     filterable catalogue
visit.html       showroom details and enquiry form
style.css        design system
shared.js        navigation, lightbox, reveal
products.js      product data, filters, quick view
listimages.php   returns the image manifest as JSON
assets/          hero and showroom photography
images/          01-aurora-crystal.jpg ... 09-opaline-modern.jpg
```

`index.html` must sit at the folder root so the domain or subdomain opens it directly.

## 3. Install on cPanel
1. In cPanel → **Domains**, create the subdomain (e.g. `trinity.yourdomain.com`) and point its document root at the folder you uploaded.
2. If you must use `public_html`, upload into `public_html/trinity/` and open `https://yourdomain.com/trinity/`.
3. Set file permissions: folders `755`, files `644`.
4. No database, environment variables or cron jobs are required.
5. Load the site over **https** once to confirm the certificate is active.

## 4. Optional configuration
- The site runs on any static host, but `listimages.php` requires PHP 8+. If the host has no PHP, remove the fetch and rely on `products.js`, which already contains the product data.
- To add a piece, add its image to `images/` and an entry to `products.js`.

## 5. Post-deploy checks
1. Home, catalogue and visit pages all load with no missing images.
2. Category and finish filters narrow the catalogue; search matches names.
3. The product count updates and an empty result shows an empty state.
4. Quick view opens the right piece with its size, finish and price, and closes with Escape, the backdrop or the close button.
5. The enquiry form rejects an empty name, a malformed email and an empty message.
6. `listimages.php` returns valid JSON and does not duplicate the grid.
7. At 390px the catalogue is one column with no horizontal scrolling.

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
- Enquiries are validated in the browser and handed to the retailer (mailto or the configured endpoint); there is no server-side inbox.
- Stock is not tracked.

## 7. Rollback
Keep a zip of the previous version before uploading. To roll back, delete the new files and re-upload the previous zip. Upload the previous folder zip; no database to restore.
