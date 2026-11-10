# ABOSODION Farm — deployment guide & tech report

## 1. What you are deploying
A farm-direct storefront for broilers, layers and graded eggs, with category filters, search and a WhatsApp ordering flow that pre-fills the product and quantity.

- **Local source:** `sites/abosodion-farm`
- **Stack:** HTML5, CSS3, vanilla JavaScript, WhatsApp deep links
- **Database:** none. The catalogue is static; orders arrive in WhatsApp. No customer data is stored on the server.
- **Hosting:** any cPanel plan. No Python, Node, Composer or build step required.

## 2. Files to upload
Upload the **contents** of `sites/abosodion-farm` into the target folder (for example `public_html/farm/`).
Keep this structure:

```
index.html     home, story and trust signals
products.html  catalogue with filters and search
contact.html   validated enquiry form
style.css      design system
```

`index.html` must sit at the folder root so the domain or subdomain opens it directly.

## 3. Install on cPanel
1. In cPanel → **Domains**, create the subdomain (e.g. `farm.yourdomain.com`) and point its document root at the folder you uploaded.
2. If you must use `public_html`, upload into `public_html/farm/` and open `https://yourdomain.com/farm/`.
3. Set file permissions: folders `755`, files `644`.
4. No database, environment variables or cron jobs are required.
5. Load the site over **https** once to confirm the certificate is active.

## 4. Optional configuration
- Update prices and stock in the product markup in `products.html`.
- The WhatsApp number appears in the order links (`https://wa.me/234...`) and on `contact.html`; change both if the number changes.
- Keep the pre-filled message URL-encoded so line breaks and naira signs survive.

## 5. Post-deploy checks
1. All three pages load with images and the correct naira prices.
2. Category filters and search narrow the catalogue and the count updates.
3. An empty filter result shows a helpful empty state.
4. An **Order** button opens WhatsApp with the product name, quantity and price already in the message.
5. `contact.html` rejects an empty name, a malformed phone number and an empty message.
6. A valid enquiry shows a confirmation state.
7. At 390px the nav and cards stack without horizontal scrolling.

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
- Orders are completed in WhatsApp; the site does not take payment.
- Stock levels are not automated — update the catalogue when a batch sells out.

## 7. Rollback
Keep a zip of the previous version before uploading. To roll back, delete the new files and re-upload the previous zip. Static content only; keep the previous `products.html` if prices change.
