// ============================================================
// Trinity Lightings — products.js
// The catalogue is driven by whatever is in /images.
//
//   on a PHP host:  GET listimages.php -> ["01-aurora-crystal.jpg", ...]
//   on a static host: GET images.json  -> the same array, checked in
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
  "10-lustre-crystal.jpg": {
    name: "Lustre",
    category: "Crystal",
    badge: "Signature",
    price: "Price on request",
    desc: "Five graduated tiers of clear crystal around a brass spine, dressed with candle sleeves.",
    detail: "The largest crystal piece on the floor. It needs a fall of at least three metres to read properly — bring your measurements.",
    finish: "Polished brass · clear crystal tiers",
  },
  "11-orchid-crystal.jpg": {
    name: "Orchid",
    category: "Crystal",
    badge: "New",
    price: "Price on request",
    desc: "A wide oval cloud of clear glass rods, each cut to a different length.",
    detail: "Held on four cables so it sits flat and low. Our most requested piece for living rooms with a 2.7 metre ceiling.",
    finish: "Brushed brass canopy · staggered glass rods",
  },
  "12-sable-crystal.jpg": {
    name: "Sable",
    category: "Crystal",
    badge: "",
    price: "Price on request",
    desc: "Faceted charcoal and clear glass on a gunmetal frame — crystal without the sweetness.",
    detail: "The smoke tones keep it from glowing too warm, so it suits a dark-painted room or a studio rather than a classic dining space.",
    finish: "Gunmetal frame · smoke and clear crystal",
  },
  "13-regent-brass.jpg": {
    name: "Regent",
    category: "Brass",
    badge: "",
    price: "Price on request",
    desc: "Eight curved brass arms with frosted tulip shades around a turned central column.",
    detail: "The traditional shape, done properly — solid cast arms, no tubing. Shades are replaceable individually.",
    finish: "Polished brass · frosted glass shades",
  },
  "14-kano-brass.jpg": {
    name: "Kano",
    category: "Brass",
    badge: "Signature",
    price: "Price on request",
    desc: "A domed brass shade pierced with thousands of small stars, throwing patterned light across the walls.",
    detail: "Switched on, the room gets the drawing rather than the fitting. Works beautifully over a low table or in a corner.",
    finish: "Aged brass · perforated dome",
  },
  "15-aurelia-brass.jpg": {
    name: "Aurelia",
    category: "Brass",
    badge: "New",
    price: "Price on request",
    desc: "Five brass rings of different diameters, nested at staggered heights with small globe bulbs.",
    detail: "Reads as one sculptural mass from below and as five separate rings from the side. Hung from a rectangular canopy.",
    finish: "Brushed brass · opal globes",
  },
  "16-vertex-modern.jpg": {
    name: "Vertex",
    category: "Modern",
    badge: "",
    price: "Price on request",
    desc: "A matte black geometric frame with a continuous warm LED line along its underside.",
    detail: "Dimmable, with the driver in the canopy so nothing shows above the ceiling plate. Made to order in two sizes.",
    finish: "Matte black brass · integrated LED",
  },
  "17-cinder-modern.jpg": {
    name: "Cinder",
    category: "Modern",
    badge: "",
    price: "Price on request",
    desc: "A set of three black cylindrical pendants with soft opal diffusers at staggered heights.",
    detail: "Sold as a set of three on one linear canopy — the standard answer for a kitchen island or a long dining table.",
    finish: "Blackened brass · opal diffuser",
  },
  "18-ivory-modern.jpg": {
    name: "Ivory",
    category: "Modern",
    badge: "New",
    price: "Price on request",
    desc: "A wide, shallow drum of veined alabaster on a slim brass rod, glowing through the stone.",
    detail: "No two slabs vein the same way, so we photograph yours before it leaves the showroom. The quietest, warmest light we sell.",
    finish: "Natural alabaster · brushed brass",
  },
};

// Shown only when neither listimages.php nor images.json can be reached
// (for example when the site is opened straight from disk).
const FALLBACK_FILES = Object.keys(PRODUCT_META);

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

  // 1. On a PHP host the folder itself is the catalogue: drop a photo into
  //    /images and it appears. This stays the authoritative source there.
  try {
    const res = await fetch("listimages.php", {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });
    if (res.ok) {
      const data = await res.json();
      if (Array.isArray(data) && data.length) files = data;
    }
  } catch (err) {
    /* static host — no PHP, fall through to the manifest */
  }

  // 2. On GitHub Pages there is no PHP, so the checked-in manifest lists
  //    every photographed piece. Without it the showroom would only ever
  //    show the bundled fallback.
  if (!files.length) {
    try {
      const res = await fetch("images.json", { cache: "no-store" });
      if (res.ok) {
        const data = await res.json();
        if (Array.isArray(data) && data.length) files = data;
      }
    } catch (err) {
      console.warn("Could not read images.json — using the bundled catalogue.", err);
    }
  }

  if (!files.length) files = FALLBACK_FILES.slice();

  PRODUCTS = files.map(toProduct);
  return PRODUCTS;
}

const PRODUCTS_READY = loadProducts();
