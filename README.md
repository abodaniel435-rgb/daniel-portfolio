# Daniel — Full-stack developer

A static, responsive portfolio for GitHub Pages. It showcases selected project previews and uses local screenshot assets so the portfolio remains fast and self-contained.

Live: https://abodaniel435-rgb.github.io/daniel-portfolio/

## Run locally

Open `index.html` in a browser, or serve this folder with any static file server.

## Publish with GitHub Pages

Push this repository to GitHub, then enable Pages from **Settings → Pages**. Choose **Deploy from a branch**, `main`, and the repository root.

## Structure

```
index.html        hero, work grid, about, contact
project.html      case-study template (?p=<slug>)
projects.js       the project registry — add or edit a project here
script.js         grid rendering, filters, nav, reveal animations
styles.css        design system
assets/projects/  project thumbnails (1200 × 750)
sites/            source for every redesigned and newly built site
  _shared-core/   the shared PHP core the PHP builds run on
apps/             Hotel Miramar (Flask) and Campus Coin (Django + React) — deployed via render.yaml
tools/forgestack/ the site-factory agent, its templates and its generated builds
docs/             deployment guides and tech reports, one per project
```

`sites/`, `apps/` and `tools/` are published source, not hosted demos — GitHub Pages serves them as static files, so PHP and Python do not execute here. Each project card links to its folder; deployment packages and step-by-step cPanel guides are described in `docs/`.

## Included project previews (16)

- City of Gold Estates — paid client build, live at cityofgoldestate.com
- RentJury
- CurrenSee
- ABOSODION Farm
- FarmCoop
- ThatGuys Fitness — interactive Coach Studio demos
- Moonlight Events
- Trinity Lightings
- SkyCast
- Hotel Miramar SG — live at https://hotel-miramar-sg-9xuf.onrender.com/
- Campus Coin — live at https://campus-coin-nm0j.onrender.com/
- MediTrack
- AjoCircle
- RoutePilot
- ForgeStack — the site-factory agent (brief → deployable PHP + MySQL site)
- Kite Courier — generated end-to-end by ForgeStack

The two Python apps under `apps/` deploy through `render.yaml`. Deployment guides and tech reports for every build live in the assignment workspace under `docs/sites/`.

## Visual asset

The hero circuit-board background is bundled locally from [Unsplash](https://images.unsplash.com/photo-1518770660439-4636190af475?w=1920&q=84&fit=crop).
