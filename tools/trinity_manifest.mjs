// Rebuild demos/trinity-lightings/images.json from whatever is in images/.
//   node tools/trinity_manifest.mjs
//
// On a PHP host listimages.php does this at request time and the folder is
// always the catalogue. GitHub Pages has no PHP, so the same listing has to be
// checked in — this is the command that keeps the two in agreement.
import { readdirSync, statSync, writeFileSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const HERE = dirname(fileURLToPath(import.meta.url));
const DIR = join(HERE, "..", "demos", "trinity-lightings", "images");
const OUT = join(DIR, "..", "images.json");
const ALLOWED = new Set(["jpg", "jpeg", "png", "webp"]);

const files = readdirSync(DIR)
  .filter((name) => ALLOWED.has(name.split(".").pop().toLowerCase()))
  // A zero-byte file is a cloud placeholder, not a photograph. Listing it
  // would put a broken card in the showroom.
  .filter((name) => statSync(join(DIR, name)).size > 1024)
  .sort((a, b) => a.localeCompare(b, "en", { numeric: true }));

if (!files.length) {
  console.error("no images found in " + DIR + " — refusing to write an empty manifest");
  process.exit(1);
}

writeFileSync(OUT, JSON.stringify(files, null, 2) + "\n");
console.log(`wrote ${files.length} piece(s) to images.json`);
