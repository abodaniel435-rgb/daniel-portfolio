// ============================================================
// Trinity Lightings — products.js
// The catalogue is driven by listimages.php, which returns a
// plain JSON array of filenames found in /images.
//
// Contract (unchanged):
//   GET listimages.php -> ["01-aurora-crystal.jpg", ...]
//
// Descriptions below are keyed by filename so the showroom copy
// stays human while the photo list stays automatic. Any photo
// dropped into /images without an entry still appears, with a
// tidy generated name and a generic description.
// ============================================================

"use strict";

let PRODUCTS = [];

const PRODUCT_META = {
  "01-aurora-crystal.jpg": {
    name: "Aurora",
    category: "Crystal",
    badge: "Signature",
    price: "Price on request",
    desc: "Nine graduated tiers of hand-cut crystal on a slim brass frame — the piece most people stop and photograph.",
    detail: "Warm candle-style bulbs sit inside the prism ring so the light fractures instead of glaring. Best in a double-height stairwell or above a formal dining table.",
    finish: "Polished brass frame · clear crystal",
  },
  "02-cascade-crystal.jpg": {
    name: "Cascade",
    category: "Crystal",
    badge: "New",
    price: "Price on request",
    desc: "Hundreds of hand-cut glass rods falling to different lengths, so the silhouette shifts as you walk past it.",
    detail: "A modern reading of the crystal chandelier. The staggered drop keeps it from feeling heavy, even in a low-ceilinged room.",
    finish: "Brushed brass canopy · clear glass rods",
  },
  "03-celeste-crystal.jpg": {
    name: "Celeste",
    category: "Crystal",
    badge: "",
    price: "Price on request",
    desc: "A quiet cluster of faceted spheres and teardrops on fine brass stems — delicate enough for a bedroom or a narrow hallway.",
    detail: "Each sphere is individually hung, so the cluster can be arranged to suit the ceiling height of your space.",
    finish: "Antique brass stems · faceted crystal",
  },
  "04-solaris-brass.jpg": {
    name: "Solaris",
    category: "Brass",
    badge: "Signature",
    price: "Price on request",
    desc: "Three concentric brass rings suspended on slim cables, lit from a warm perimeter of LEDs.",
    detail: "The most architectural piece we carry. Reads as a single line of light from across a room and as three distinct rings from underneath.",
    finish: "Brushed brass · warm-white LED",
  },
  "05-meridian-brass.jpg": {
    name: "Meridian",
    category: "Brass",
    badge: "",
    price: "Price on request",
    desc: "An aged-brass globe with an opal diffuser — the softest light in the collection, and the easiest to live with.",
    detail: "Sold singly or as a run of three at staggered heights over a kitchen island or a long dining table.",
    finish: "Aged brass · opal glass",
  },
  "06-atelier-brass.jpg": {
    name: "Atelier",
    category: "Brass",
    badge: "",
    price: "Price on request",
    desc: "Mid-century sputnik arms in antique brass, tipped with exposed filament bulbs.",
    detail: "A statement in a lounge or entryway. We recommend warm 2200K bulbs to keep the brass reading gold rather than yellow.",
    finish: "Antique brass · exposed filament",
  },
  "07-halo-modern.jpg": {
    name: "Halo",
    category: "Modern",
    badge: "",
    price: "Price on request",
    desc: "One long, thin bar of blackened brass with a continuous diffused glow. Almost invisible until it is switched on.",
    detail: "Made to order in three lengths to sit precisely over an island, a boardroom table or a corridor.",
    finish: "Blackened brass · diffused acrylic",
  },
  "08-monolith-modern.jpg": {
    name: "Monolith",
    category: "Modern",
    badge: "New",
    price: "Price on request",
    desc: "Overlapping discs of translucent alabaster on a brass spine, glowing like a stack of warm stones.",
    detail: "Each disc is unique in veining. A sculptural choice for a stairwell, a gallery wall or an entrance hall.",
    finish: "Brass spine · natural alabaster",
  },
  "09-opaline-modern.jpg": {
    name: "Opaline",
    category: "Modern",
    badge: "",
    price: "Price on request",
    desc: "Three opal glass spheres on fine brass stems, floating at different heights.",
    detail: "A softer, rounder alternative to the Meridian pendant — lovely clustered over a bedside or a reading corner.",
    finish: "Brushed brass · opal glass",
  },
};

// Shown only when listimages.php cannot be reached (for example when
// the site is previewed without PHP). Keeps the showroom furnished.
const FALLBACK_FILES = [
  "01-aurora-crystal.jpg",
  "02-cascade-crystal.jpg",
  "03-celeste-crystal.jpg",
  "04-solaris-brass.jpg",
  "05-meridian-brass.jpg",
  "06-atelier-brass.jpg",
  "07-halo-modern.jpg",
  "08-monolith-modern.jpg",
  "09-opaline-modern.jpg",
];

function prettifyFilename(filename) {
  return String(filename)
    .replace(/\.[a-z0-9]+$/i, "")
    .replace(/^\d+[-_]/, "")
    .replace(/[-_]+/g, " ")
    .replace(/\b\w/g, (m) => m.toUpperCase());
}

function toProduct(filename, index) {
  const meta = PRODUCT_META[filename] || {};
  return {
    filename,
    name: meta.name || prettifyFilename(filename) || `Piece No. ${index + 1}`,
    category: meta.category || "Chandelier",
    price: meta.price || "Price on request",
    badge: meta.badge || "",
    desc: meta.desc || "Part of our curated lighting collection. Message us for dimensions, finishes and pricing.",
    detail: meta.detail || "Available to view in the Lagos showroom. Ask us about installation and delivery.",
    finish: meta.finish || "Finish on request",
    img: `images/${filename}`,
  };
}

async function loadProducts() {
  let files = [];
  try {
    const res = await fetch("listimages.php", {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });
    if (!res.ok) throw new Error(`listimages.php responded ${res.status}`);
    const data = await res.json();
    if (Array.isArray(data)) files = data;
  } catch (err) {
    console.warn("Could not load the product image list — using the bundled catalogue.", err);
  }

  if (!files.length) files = FALLBACK_FILES.slice();

  PRODUCTS = files.map(toProduct);
  return PRODUCTS;
}

const PRODUCTS_READY = loadProducts();
