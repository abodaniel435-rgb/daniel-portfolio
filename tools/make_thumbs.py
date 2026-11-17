#!/usr/bin/env python
"""Generate the small image candidates the portfolio grid's srcset points at.

Paths resolve from this file, so run it from anywhere. The output directory is
committed, which is what keeps the site free of a build step.
"""
import os
import sys
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, "assets", "projects")
OUT = os.path.join(SRC, "thumbs")
WIDTHS = (320, 640)
# Not cards: the hero backdrop and the social-share plate.
NOT_CARDS = {"tech-circuit", "portfolio-plate"}
QUALITY = {320: 74, 640: 72}


def encode(img, width, quality, path):
    height = max(1, round(img.height * width / img.width))
    img.resize((width, height), Image.LANCZOS).save(
        path, "JPEG", quality=quality, optimize=True, progressive=True)
    return os.path.getsize(path), height


def main():
    os.makedirs(OUT, exist_ok=True)
    rows = []
    for name in sorted(os.listdir(SRC)):
        stem, ext = os.path.splitext(name)
        if ext.lower() != ".jpg" or stem in NOT_CARDS:
            continue
        with Image.open(os.path.join(SRC, name)) as img:
            native = img.width
            if native <= WIDTHS[1]:
                print("skip (already small) %s" % name)
                continue
            geo = {native: (img.width, img.height)}
            sizes = {native: os.path.getsize(os.path.join(SRC, name))}
            for width in WIDTHS:
                size, height = encode(img.convert("RGB"), width, QUALITY[width],
                                      os.path.join(OUT, "%s-%d.jpg" % (stem, width)))
                geo[width] = (width, height)
                sizes[width] = size
        rows.append((stem, native, geo, sizes))

    for stem, native, geo, sizes in rows:
        detail = "  ".join("%dw:%dx%d %.0fK" % (w, *geo[w], sizes[w] / 1024)
                           for w in sorted(sizes, reverse=True))
        print("%-22s %s" % (stem, detail))
    full = sum(s[native] for _, native, _, s in rows)
    picked = sum(s[640] for _, _, _, s in rows)
    print("\n%d cards, %.2f MB at the full candidate against %.2f MB at 640w" %
          (len(rows), full / 1048576, picked / 1048576))
    odd = [(stem, native, geo[native]) for stem, native, geo, _ in rows if native != 1200]
    if odd:
        print("non-1200 sources, mirror these in script.js NATIVE_SIZES:")
        for stem, native, (width, height) in odd:
            print("  'assets/projects/%s.jpg': [%d, %d]" % (stem, width, height))
    return 0


if __name__ == "__main__":
    sys.exit(main())
