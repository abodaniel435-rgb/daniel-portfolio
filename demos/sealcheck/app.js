/* SealCheck — medicine authenticity screening engine.
   Everything runs locally: no network calls, no uploads. Content marked
   illustrative is teaching data, not a register and not an allegation. */
(function () {
  'use strict';

  /* ------------------------------------------------------------------ data */

  var CHECKS = [
    { id: 'seal', group: 'Packaging', w: 12, fatal: false,
      text: 'Blister foil is continuous, aligned and printed on both faces — no punched-through cells, no second layer of foil.' },
    { id: 'glue', group: 'Packaging', w: 12, fatal: true,
      text: 'Carton glue seams are original: machine glue lines, no lifted flap, no clear tape, no re-glued crush marks.' },
    { id: 'print', group: 'Packaging', w: 8, fatal: false,
      text: 'Printing is sharp and consistent — no smeared, doubled, faded or mismatched-font text or artwork.' },
    { id: 'batchprint', group: 'Packaging', w: 8, fatal: true,
      text: 'Batch and expiry are applied by the manufacturer (inkjet/laser/embossed in the artwork), not a hand ink-stamp printed over other text.' },

    { id: 'regnum', group: 'Documentation', w: 10, fatal: true,
      text: 'A NAFDAC registration number is printed and matches this product and strength — not another product from the same range.' },
    { id: 'maker', group: 'Documentation', w: 5, fatal: false,
      text: 'Manufacturer (or importer) name and a real address, plus the marketing authorisation holder, are printed and legible.' },
    { id: 'storage', group: 'Documentation', w: 5, fatal: false,
      text: 'Storage instruction is present and matches what you are about to do with it (below 25 °C / 2–8 °C / protect from light).' },
    { id: 'invoice', group: 'Documentation', w: 4, fatal: false,
      text: 'You have an invoice or receipt naming the product and batch, from a licensed outlet.' },

    { id: 'deboss', group: 'Product', w: 8, fatal: false,
      text: 'Tablet score line, shape and deboss mark are identical across the strip — no chipped, oversized or two-toned tablets.' },
    { id: 'colour', group: 'Product', w: 6, fatal: false,
      text: 'Colour, odour and (for a known product) taste match what this brand normally is — no mottling, no oil staining, no solvent smell.' },
    { id: 'suspension', group: 'Product', w: 6, fatal: false,
      text: 'Syrup/suspension looks normal: uniform, no hard cake at the base, no particulates floating, cap seal and tamper band intact.' },
    { id: 'injection', group: 'Product', w: 6, fatal: true,
      text: 'Injection solution is clear and free of particles, the ampoule/vial is uncracked, and the closure has no leakage or dried residue.' },

    { id: 'vvm', group: 'Cold chain', w: 6, fatal: false,
      text: 'VVM disc is at stage 0–2, or the pack carries an intact cold-chain indicator and you know how long it was out.' },
    { id: 'thaw', group: 'Cold chain', w: 4, fatal: false,
      text: 'No sign of thaw/refreeze: no excess condensate inside the carton, no swollen or displaced stopper, no reconstituted-looking powder.' }
  ];

  var GROUP_NOTE = {
    Packaging: 'Open it, look at the seams, look at the print. Re-packed cartons and punched foil are the two cheapest fakes to catch.',
    Documentation: 'Paperwork you can demand before you pay. A number that is missing, wrong or belongs to another product is a stop.',
    Product: 'The medicine itself. This is where an under-dosed or wrong-API pack can still pass — so passing here never proves authenticity.',
    'Cold chain': 'Heat damage is invisible in the liquid and permanent in the antigen. The dial and the clock are the only honest records.'
  };

  var SAMPLE = { seal: 'yes', glue: 'no', print: 'unsure', batchprint: 'yes', regnum: 'yes', maker: 'unsure', storage: 'yes', invoice: 'no', deboss: 'yes', colour: 'unsure', suspension: 'yes', injection: 'unsure', vvm: 'yes', thaw: 'yes' };

  var REG_FORMATS = [
    { re: /^([AF])-(\d{5})(CR|X)$/, kind: 'variant', valid: true },
    { re: /^([AF])-(\d{5,6})$/, kind: 'standard', valid: true },
    { re: /^(\d{1,2})\/(\d{4})$/, kind: 'legacy', valid: true },
    { re: /^\d{5,6}$/, kind: 'legacy-plain', valid: true },
    { re: /^([AF])-(\d{4})$/, kind: 'short', valid: false },
    { re: /^([AF])-(\d{7,})$/, kind: 'long', valid: false },
    { re: /^([AF])\d{5,6}$/, kind: 'nodash', valid: false },
    { re: /^([A-Z])-(\d{5,6})(CR|X)?$/, kind: 'letter', valid: false }
  ];

  var PREFIX_MEANING = {
    A: 'A — imported finished product, registered through a Nigerian agent.',
    F: 'F — finished product manufactured in Nigeria.',
    CR: 'CR suffix — contract / repack-related registration seen against bulk handling. It does not belong on a sealed retail carton.',
    X: 'X suffix — bulk material or active pharmaceutical ingredient registration. Drums and Intermediate, not patient packs.',
    legacy: 'Older 5–6 digit numeric form (often written as nn/nnnn). Still printed on legacy packs; the listing may be dormant or withdrawn.',
    none: 'No recognised prefix.'
  };

  var GS1 = [
    { lo: 0, hi: 13, c: 'United States & Canada (GS1 US)', sure: true },
    { lo: 300, hi: 379, c: 'United States & Canada (GS1 US)', sure: true },
    { lo: 400, hi: 449, c: 'Germany', sure: true },
    { lo: 450, hi: 459, c: 'Japan', sure: true },
    { lo: 490, hi: 499, c: 'Japan', sure: true },
    { lo: 500, hi: 509, c: 'United Kingdom', sure: true },
    { lo: 560, hi: 569, c: 'Portugal', sure: true },
    { lo: 570, hi: 579, c: 'Norway', sure: true },
    { lo: 590, hi: 599, c: 'Poland', sure: true },
    { lo: 600, hi: 601, c: 'South Africa', sure: true },
    { lo: 603, hi: 603, c: 'Ghana', sure: false },
    { lo: 609, hi: 609, c: 'Morocco', sure: true },
    { lo: 611, hi: 611, c: 'Tunisia', sure: true },
    { lo: 613, hi: 613, c: 'Algeria', sure: true },
    { lo: 614, hi: 614, c: 'Nigeria', sure: true },
    { lo: 615, hi: 615, c: 'Kenya', sure: false },
    { lo: 616, hi: 616, c: 'Tanzania', sure: false },
    { lo: 690, hi: 699, c: 'China', sure: true },
    { lo: 890, hi: 890, c: 'India', sure: true }
  ];

  var MADE_IN = { NG: 'Nigeria', IN: 'India', CN: 'China', ZA: 'South Africa', DE: 'Germany', GB: 'United Kingdom', KE: 'Kenya', FR: 'France', XX: null };

  var REGISTRY = [
    { name: 'Artemether–lumofantrine 20/120 mg (AL/ACT)', cls: 'Antimalarial', shape: 'A-##### (imported)', pack: '2 blister strips × 6 tablets', price: [1800, 3500],
      seal: 'Foil printed on both faces, tablet deboss on each side, batch and expiry jet-printed on the carton flap and repeated on the foil print. Expect a manufacturer hologram or colour-shift tile on many imports.' },
    { name: 'Artesunate + amodiaquine (ASAQ)', cls: 'Antimalarial', shape: 'A-##### (imported)', pack: '6 × 2 tablets, adult paediatric blisters', price: [1500, 2800],
      seal: 'Blister cavity depth uniform, seal continuous when peeled, packaging leaflet folded to a standard quarter-fold. Loose tablets in a strip are a re-pack signal.' },
    { name: 'Dihydroartemisinin + piperaquine', cls: 'Antimalarial', shape: 'A-##### (imported)', pack: '3 daily packs, 18 tablets', price: [2500, 4500],
      seal: 'Day-labelled blisters (D1/D2/D3) printed, not stickered. Missing day labels on a 3-day pack is a defect.' },
    { name: 'Artemisia annua syrup (herbal)', cls: 'Antimalarial', shape: 'A-##### / F-##### herbal listing', pack: '100–200 mL bottle', price: [1500, 3000],
      seal: 'Tamper band on the cap, sediment characteristic but not a hard cake. A “no alcohol” claim with a solvent smell is a contradiction worth reporting.' },
    { name: 'Amoxicillin 500 mg capsules', cls: 'Antibiotic', shape: 'F-##### or A-#####', pack: '100 capsule blister strip / 20-caps pack', price: [2500, 5000],
      seal: 'Cap and body colour consistent, no powder dusting inside the blister, batch laser or inkjet on the foil. A 250 mg number printed on a 500 mg carton is a common falsification tell.' },
    { name: 'Co-amoxiclav 625 mg tablets', cls: 'Antibiotic', shape: 'A-##### (imported)', pack: '12–14 tablets', price: [6500, 12000],
      seal: 'Film-coated, biconvex, deboss on one face only per the brand. Foil peel should be clean and continuous; a mottled coating means moisture ingress.' },
    { name: 'Ciprofloxacin 500 mg tablets', cls: 'Antibiotic', shape: 'F-##### (local)', pack: '10–20 tablets', price: [2000, 4000],
      seal: 'Score line straight and centred, identical colour across the strip. Expect a printed batch on the blister edge on most local brands.' },
    { name: 'Paracetamol 500 mg tablets', cls: 'Analgesic / anti-inflammatory', shape: 'F-##### (local)', pack: '100-tablet strip, 10-tablet blister', price: [800, 1800],
      seal: 'Highest-volume falsified product. Genuine packs have a sharp deboss, uniform white, and batch/expiry printed by machine on the strip. Hand-stamped dates are the classic tell.' },
    { name: 'Ibuprofen 400 mg tablets', cls: 'Analgesic / anti-inflammatory', shape: 'F-##### / A-#####', pack: '20 tablets', price: [900, 1900],
      seal: 'Film coat without chips, blister sealed per cavity, carton flap glued at the factory seam only.' },
    { name: 'Oxytetracycline 20% injection', cls: 'Antibiotic', shape: 'A-#####X (bulk) / A-#####', pack: '100 mL vial', price: [2500, 5500],
      seal: 'Aluminium cap and stopper intact, solution free of particulates and of the grey precipitate that forms after freezing. Cracks in the vial neck mean discard, whatever the paperwork says.' },
    { name: 'Insulin glargine 100 IU/mL penfill', cls: 'Diabetes', shape: 'A-##### (imported, cold chain)', pack: '3 mL penfill, carton with VVM on some presentations', price: [14000, 26000],
      seal: 'Should be clear and colourless — never cloudy unless it is the NPH presentation. Seal ring on the penfill, batch on carton and label must match each other exactly.' },
    { name: 'ORS + zinc paediatric kit', cls: 'Rehydration / supplement', shape: 'F-##### (local)', pack: '20 ORS sachets + 10 zinc dispersible tablets', price: [450, 900],
      seal: 'Sachets nitrogen-flushed and free-flowing; a cakey or salty-crusted sachet has taken moisture. Zinc tablet deboss consistent. Expired ORS is a hard stop, not a soft one.' },
    { name: 'Metformin 500 mg tablets', cls: 'Metabolic', shape: 'F-##### (local)', pack: '100 tablets', price: [1800, 3400],
      seal: 'Score line, uniform white film coat, batch/expiry machine printed. Long-chain resellers sometimes mix two batches in one carton — check the blister print across the strip.' },
    { name: 'Amlodipine 5 mg tablets', cls: 'Cardiovascular', shape: 'F-##### / A-#####', pack: '30 tablets', price: [1200, 2800],
      seal: 'Small tablets: look for size variation inside one strip and for a foil print that repeats the wrong strength.' },
    { name: 'Vitamin A 200,000 IU capsules', cls: 'Rehydration / supplement', shape: 'F-##### (local)', pack: '20–100 softgel capsules', price: [350, 900],
      seal: 'Softgels should be uniform, glossy and free of merged pairs. Stuck-together capsules mean the pack has been hot.' },
    { name: 'Albendazole 400 mg tablets', cls: 'Antibiotic', shape: 'F-##### (local)', pack: '2–6 chewable tablets', price: [250, 700],
      seal: 'Chewable tablets are pale and slightly mottled by design — but the deboss must be identical. Campaign packs distributed free are frequently re-sold loose with no carton at all.' },
    { name: 'Fluoxetine 20 mg capsules', cls: 'Psychiatric', shape: 'A-##### (imported)', pack: '28 capsules', price: [3000, 6500],
      seal: 'Two-tone cap/body colour per brand, no powder at the cap junction, batch on each blister run. Chronic buyers notice a changed fill long before the carton does.' }
  ];

  var VVM = [
    { stage: 0, inner: '#e6ede8', title: 'Stage 0 — fully usable', body: 'Inner square clearly lighter than the reference ring, gap visible all round. Use normally.', verdict: 'ok' },
    { stage: 1, inner: '#b3c3b8', title: 'Stage 1 — usable', body: 'Inner square lighter than the ring but the square edge is approaching it. Use; for an opened multidose vial the in-use clock still applies.', verdict: 'ok' },
    { stage: 2, inner: '#7b8e83', title: 'Stage 2 — use today, then discard', body: 'Edge of the inner square has reached the ring. WHO guidance for Type 8–14: vaccinate within the remainder of the session and discard at the end of it.', verdict: 'warn' },
    { stage: 3, inner: '#48564e', title: 'Stage 3 — DISCARD', body: 'Inner square the same shade as, or darker than, the ring. The vial must not be used. Mark it, quarantine it, and tell the person who issued it.', verdict: 'bad' },
    { stage: 4, inner: '#28312c', title: 'Stage 4 — DISCARD and report', body: 'Inner square much darker than the ring; the vial has been excessively hot. Discard, record it as a cold-chain failure and report the event.', verdict: 'bad' }
  ];

  var BUDGETS = [
    { key: 'vaccine', label: 'Vaccine (freeze-sensitive)', lo: 2, hi: 8, warn: 12, discard: 24, hard: true },
    { key: 'insulin', label: 'Insulin glargine / soluble', lo: 2, hi: 30, warn: 240, discard: 480, hard: false },
    { key: 'antibiotic-syrup', label: 'Antibiotic syrup (refrigerate)', lo: 2, hi: 8, warn: 72, discard: 144, hard: false },
    { key: 'oxytet', label: 'Oxytetracycline injection', lo: 15, hi: 25, warn: 60, discard: 150, hard: false },
    { key: 'ors', label: 'ORS + zinc', lo: 2, hi: 30, warn: 200, discard: 500, hard: false }
  ];

  var RECALLS = [
    { date: '2026-09-28', product: 'Artemether–lumefantrine 20/120 mg, adult pack', cat: 'Antimalarial', batch: 'SX-2411-K', reason: 'Sample batch failed dissolution assay (illustrative)', region: 'South-east', action: 'Quarantine batch; refer patients who completed the course' },
    { date: '2026-08-14', product: 'Paracetamol 500 mg tablets, 100-tablet strip', cat: 'Analgesic', batch: 'PB-2603-118', reason: 'Packaging bore a registration number belonging to another product (illustrative)', region: 'Lagos', action: 'Remove from sale and report the outlet' },
    { date: '2026-07-06', product: 'Amoxicillin 500 mg capsules', cat: 'Antibiotic', batch: 'AX-2549-02', reason: 'Suspected zero-API counterfeit in open market (illustrative)', region: 'South-south', action: 'Do not use; submit sample for testing' },
    { date: '2026-06-19', product: 'Tetanus toxoid-containing vaccine 0.5 mL', cat: 'Vaccine', batch: 'TT-6620-D', reason: 'Cold-chain excursion during distribution, VVM advanced to stage 3 (illustrative)', region: 'North-central', action: 'Discard vials at stage 3 and above; report to state EPI officer' },
    { date: '2026-05-30', product: 'ORS + zinc paediatric kit', cat: 'Rehydration', batch: 'RZ-2517-4', reason: 'Sachet moisture ingress, caking reported (illustrative)', region: 'North-west', action: 'Inspect sachets; discard caked units' },
    { date: '2026-04-11', product: 'Oxytetracycline 20% injection 100 mL', cat: 'Injection', batch: 'OT-2488-7', reason: 'Particulate matter and cracked vials at consignments level (illustrative)', region: 'South-west', action: 'Quarantine the whole consignment pending inspection' },
    { date: '2026-03-02', product: 'Ciprofloxacin 500 mg tablets', cat: 'Antibiotic', batch: 'CP-2471-19', reason: 'Under-labelled strength on one production line (illustrative)', region: 'Abuja FCT', action: 'Verify strength against the carton and leaflet before dispensing' },
    { date: '2025-12-15', product: 'Artemisia-based antimalarial syrup 150 mL', cat: 'Antimalarial', batch: 'AR-2402-S', reason: 'Declared assay content outside specification (illustrative)', region: 'South-east', action: 'Withdraw batch; advise prescribers' },
    { date: '2025-11-08', product: 'Co-amoxiclav 625 mg tablets', cat: 'Antibiotic', batch: 'CA-2365-9', reason: 'Re-packed cartons reported at three outlets (illustrative)', region: 'South-west', action: 'Check glue seams and blister foil continuity on every pack' },
    { date: '2025-10-21', product: 'Ibuprofen 400 mg tablets', cat: 'Analgesic', batch: 'IB-2341-3', reason: 'Hand-stamped expiry over-printed a machine date (illustrative)', region: 'North-central', action: 'Reject any pack whose date can be smeared with a thumb' }
  ];

  /* ----------------------------------------------------------------- stores */

  var wStore = kit.store('sealcheck:weights', null);
  var uStore = kit.store('sealcheck:unsure', 0.4);
  var bStore = kit.store('sealcheck:budgets', null);
  var hStore = kit.store('sealcheck:history', []);

  var weights = wStore.read() || CHECKS.reduce(function (acc, c) { acc[c.id] = c.w; return acc; }, {});
  var budgets = bStore.read() || BUDGETS.map(function (b) { return { key: b.key, lo: b.lo, hi: b.hi, warn: b.warn, discard: b.discard }; });
  var unsureCredit = kit.clamp(Number(uStore.read()), 0, 1);
  var expiryState = { expired: false, label: '' };
  var lastScore = null;

  function budgetFor(key) {
    var row = null;
    budgets.forEach(function (b) { if (b.key === key) row = b; });
    return row || BUDGETS[0];
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  /* -------------------------------------------------- 1. registration engine */

  function checkRegistration(raw) {
    var clean = String(raw || '').toUpperCase().replace(/\s+/g, '');
    var out = { raw: clean, valid: false, kind: '', prefix: '', digits: '', suffix: '', notes: [] };
    if (!clean) { out.notes.push('Nothing entered.'); return out; }
    out.digits = clean.replace(/[^0-9]/g, '').length + ' digits';

    for (var i = 0; i < REG_FORMATS.length; i++) {
      var m = REG_FORMATS[i].re.exec(clean);
      if (!m) continue;
      var f = REG_FORMATS[i];
      out.kind = f.kind;
      out.valid = f.valid;
      if (f.kind === 'variant') {
        out.prefix = PREFIX_MEANING[m[1]] + ' ' + PREFIX_MEANING[m[3]];
        out.suffix = m[3];
        out.notes.push('Bulk/contract suffixes on a sealed retail carton are a strong falsification signal — this shape should not be what the patient is holding.');
      } else if (f.kind === 'standard') {
        out.prefix = PREFIX_MEANING[m[1]];
        if (String(m[2]).length === 6) out.notes.push('Body of six digits: the older documented length. Still a recognised shape, but more often seen on legacy or dormant listings.');
      } else if (f.kind === 'legacy') {
        out.prefix = PREFIX_MEANING.legacy;
        out.notes.push('No letter prefix at all — ask which agent imported it and confirm the listing is still active.');
      } else if (f.kind === 'legacy-plain') {
        out.prefix = PREFIX_MEANING.legacy;
        out.notes.push('Plain number with no prefix: recognised as an older form, but it is also exactly what a copied label looks like when the copier dropped the letter.');
      } else if (f.kind === 'short') {
        out.notes.push('Only ' + m[2].length + ' digits in the body. Documented forms are 5–6 digits, so a 4-digit body is not a valid shape.');
      } else if (f.kind === 'long') {
        out.notes.push('A body of ' + m[2].length + ' digits is longer than any documented form. Usually a barcode number or a batch code being read as a registration number.');
      } else if (f.kind === 'nodash') {
        out.notes.push('Recognised digits but no separator. Genuine packs print the dash; a missing dash is usually a bad transcription — or a bad copy.');
      } else if (f.kind === 'letter') {
        out.notes.push('“' + m[1] + '-” is not a prefix this tool knows. Documented retail prefixes are A (imported) and F (locally manufactured).');
      }
      break;
    }

    if (!out.kind) {
      out.prefix = PREFIX_MEANING.none;
      out.notes.push('No documented NAFDAC-style pattern matches this string at all.');
    }
    return out;
  }

  function consistency(res, type) {
    var isA = /^A-/.test(res.raw), isF = /^F-/.test(res.raw);
    if (!res.valid) return { ok: false, text: 'The shape itself failed, so product type cannot be compared.' };
    if (type === 'bulk') {
      return res.suffix
        ? { ok: true, text: 'Consistent — a bulk/API registration is exactly what carries the CR or X suffix. It should be on a drum or a material record, never on a patient pack.' }
        : { ok: false, text: 'Bulk material normally shows the CR/X variant. A plain retail shape on a drum is worth questioning.' };
    }
    if (type === 'finished') {
      return isA
        ? { ok: true, text: 'Consistent: an A- prefix is the imported finished-product form.' }
        : { ok: true, text: 'An F- prefix on a product you believe is imported means a Nigerian factory made it. Ask which one, and confirm the register entry.' };
    }
    if (type === 'local') {
      if (res.suffix) return { ok: false, text: 'A locally manufactured retail pack should not carry a bulk suffix.' };
      return isF
        ? { ok: true, text: 'Consistent: F- is the locally manufactured finished-product form.' }
        : { ok: false, text: 'The pack claims a Nigerian factory but the number is the imported form (A-). Either the origin text or the number is wrong — that question is worth asking before paying.' };
    }
    if (type === 'herbal') {
      return { ok: true, text: 'Herbal and traditional medicines are listed under the retail shapes; the listing category, not the shape, is what to confirm.' };
    }
    return { ok: true, text: 'Product type not stated, so only the shape was checked.' };
  }

  /* ------------------------------------------------------- 2. barcode engine */

  function checkDigit(digits) {
    var body = digits.slice(0, -1);
    var sum = 0;
    for (var i = body.length - 1, w = 3; i >= 0; i--, w = (w === 3 ? 1 : 3)) {
      sum += Number(body[i]) * w;
    }
    return (10 - (sum % 10)) % 10;
  }

  function prefixLookup(code) {
    for (var i = 0; i < GS1.length; i++) {
      if (code >= GS1[i].lo && code <= GS1[i].hi) return GS1[i];
    }
    return null;
  }

  function analyseBarcode(raw, madeIn) {
    var digits = String(raw || '').replace(/[^0-9]/g, '');
    var res = { digits: digits, len: digits.length, known: false, symbology: '', prefix: '', entry: null, checkOk: null, conflict: null, notes: [] };
    if (!digits) { res.symbology = 'nothing entered'; return res; }
    if (digits.length !== 8 && digits.length !== 12 && digits.length !== 13) {
      res.symbology = digits.length + ' digits — not EAN-8, EAN-13 or UPC-A';
      res.notes.push('GS1 product codes are 8, 12 or 13 digits. Other lengths are usually a batch code, an internal shelf code, or a partial read of the bars.');
      return res;
    }
    res.known = true;
    res.symbology = digits.length === 8 ? 'EAN-8 (short code)' : (digits.length === 12 ? 'UPC-A / GTIN-12' : 'EAN-13 / GTIN-13');
    res.checkOk = (checkDigit(digits) === Number(digits[digits.length - 1]));

    if (digits.length === 8) {
      res.prefix = digits.slice(0, 2);
      res.notes.push('EAN-8 short codes do not carry enough leading digits for a reliable country read. This tool will not assert a country from 8 digits.');
      res.entry = { c: 'not determined from a short code', sure: false };
    } else {
      res.prefix = digits.slice(0, 3);
      res.entry = prefixLookup(Number(res.prefix));
      if (!res.entry) res.notes.push('Prefix ' + res.prefix + ' is outside the ranges this tool carries. That is not evidence of anything — check the GS1 list.');
    }

    var made = MADE_IN[madeIn] || null;
    if (res.entry && res.entry.sure && made) {
      var same = res.entry.c.indexOf(made) !== -1;
      res.conflict = same ? 'match' : 'conflict';
      if (!same) {
        res.notes.push('Prefix points to ' + res.entry.c + ' while the pack says “Made in ' + made + '”. Read carefully: this is common and often perfectly legal — the prefix is the licence-holder’s country. It is worth a question, not an accusation.');
      }
    } else if (res.entry && !res.entry.sure && made) {
      res.conflict = 'unknown';
      res.notes.push('This prefix range is one the tool only reports with low confidence, so no comparison is attempted against “Made in ' + made + '”.');
    }
    return res;
  }

  /* --------------------------------------------------------- 3. expiry engine */

  var MONTHS = { JAN: 1, FEB: 2, MAR: 3, APR: 4, MAY: 5, JUN: 6, JUL: 7, AUG: 8, SEP: 9, OCT: 10, NOV: 11, DEC: 12 };

  function twoDigitYear(y) { return y <= 40 ? 2000 + y : 1900 + y; }
  function daysInMonth(y, m) { return new Date(y, m, 0).getDate(); }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function monthEnd(y, m) { return new Date(y, m - 1, daysInMonth(y, m)); }

  function parseExpiry(raw) {
    var text = String(raw || '').toUpperCase().trim();
    var res = { text: text, readings: [], legend: '', formats: [], empty: text === '' };
    if (!text) return res;

    var legendMatch = /(EXP|EXPIRY|EXPIRES|USE\s?BY|BEST\s?BEFORE|BB)\b/.exec(text);
    if (legendMatch) res.legend = legendMatch[0].replace(/\s+/g, ' ');

    var mName = /\b([A-Z]{3})\b\D{0,3}(\d{4})\b/.exec(text);
    if (mName && MONTHS[mName[1]]) {
      var y = Number(mName[2]);
      res.readings.push({ label: 'Month name', as: mName[1] + ' ' + y, until: monthEnd(y, MONTHS[mName[1]]), note: 'Unambiguous — the month is spelled out, so no digit-order question arises.' });
      res.formats.push('MONTH YYYY');
      res.named = true;
    }
    var mDyM = /(\d{1,2})[.\-/ ]([A-Z]{3})[.\-/ ](\d{4})/.exec(text.replace(/\s+/g, ' '));
    if (mDyM && MONTHS[mDyM[2]]) {
      var dy = Number(mDyM[3]), mo = MONTHS[mDyM[2]], da = Math.min(Number(mDyM[1]), daysInMonth(dy, mo));
      res.readings.push({ label: 'DD MMM YYYY', as: mDyM[1] + ' ' + mDyM[2] + ' ' + dy, until: new Date(dy, mo - 1, da), note: 'Unambiguous.' });
      res.formats.push('DD MMM YYYY');
    }

    var nums = res.named ? [] : text.replace(/[A-Z]/g, ' ').split(/[^0-9]+/).filter(function (s) { return s.length; });
    if (nums.length === 2) {
      var a = nums[0], b = nums[1];
      res.formats.push('MM/YY');
      res.formats.push('MM/YYYY');
      res.formats.push('DD/MM');
      if (b.length === 4 && a.length <= 2 && Number(a) >= 1 && Number(a) <= 12) {
        res.readings.push({ label: 'MM/YYYY', as: pad(Number(a)) + '/' + b, until: monthEnd(Number(b), Number(a)), note: 'Month and four-digit year: usable to the last day of ' + n2m(Number(a)) + ' ' + b + '.' });
      }
      if (b.length === 2 && a.length <= 2 && Number(a) >= 1 && Number(a) <= 12) {
        res.readings.push({ label: 'MM/YY', as: pad(Number(a)) + '/' + b + ' → ' + pad(Number(a)) + '/' + twoDigitYear(Number(b)), until: monthEnd(twoDigitYear(Number(b)), Number(a)), note: 'Two-digit year resolved to ' + twoDigitYear(Number(b)) + ' (the tool assumes 20YY up to 40).' });
      }
      if (a.length <= 2 && b.length <= 2 && Number(b) >= 1 && Number(b) <= 12) {
        res.readings.push({ label: 'DD/MM (day/month)', as: a + '/' + pad(Number(b)) + ', year not printed', until: null, invalid: false, note: 'This reading is a day and a month with no year anywhere in the string, so it cannot be resolved on its own.' });
      } else if (Number(b) > 12) {
        res.readings.push({ label: 'DD/MM (day/month)', as: a + '/' + b, until: null, invalid: true, note: 'Impossible: ' + b + ' is not a month. That eliminates one reading and is why ' + a + '/' + b + ' resolves the way it does.' });
      }
      if (a.length === 4 && b.length <= 2 && Number(b) >= 1 && Number(b) <= 12) {
        res.readings.push({ label: 'YYYY/MM', as: a + '/' + pad(Number(b)), until: monthEnd(Number(a), Number(b)), note: 'Year first — increasingly common on imported packs.' });
      }
    }

    if (nums.length === 3) {
      res.formats.push('DD/MM/YYYY');
      res.formats.push('MM/DD/YYYY');
      res.formats.push('YYYY-MM-DD');
      var p1 = Number(nums[0]), p2 = Number(nums[1]), p3 = Number(nums[2]);
      if (nums[0].length === 4) {
        if (p2 >= 1 && p2 <= 12) {
          var dayISO = Math.min(p3, daysInMonth(p1, p2));
          res.readings.push({ label: 'YYYY-MM-DD', as: fmt(new Date(p1, p2 - 1, dayISO)), until: new Date(p1, p2 - 1, dayISO), note: 'ISO form: unambiguous, and the day is significant — the pack is out of date on this date, not at the end of the month.' });
        } else {
          res.readings.push({ label: 'YYYY-MM-DD', as: nums[0] + '-' + nums[1] + '-' + nums[2], until: null, invalid: true, note: 'Impossible as ISO: ' + p2 + ' is not a month.' });
        }
      } else if (nums[2].length === 4) {
        if (p1 >= 1 && p1 <= 31 && p2 >= 1 && p2 <= 12) {
          var dA = Math.min(p1, daysInMonth(p3, p2));
          res.readings.push({ label: 'DD/MM/YYYY', as: fmt(new Date(p3, p2 - 1, dA)), until: new Date(p3, p2 - 1, dA), note: p1 > 12 ? 'The first number exceeds 12, so day/month is the only order that works. European convention, most common on packs sold in Nigeria.' : 'European convention (day first) — the usual form on packs sold in Nigeria.' });
        }
        if (p1 >= 1 && p1 <= 12 && p2 >= 1 && p2 <= 31) {
          var dB = Math.min(p2, daysInMonth(p3, p1));
          var dateB = new Date(p3, p1 - 1, dB);
          var dup = res.readings.some(function (x) { return x.until && fmt(x.until) === fmt(dateB); });
          if (!dup) {
            res.readings.push({ label: 'MM/DD/YYYY', as: fmt(dateB), until: dateB, note: p2 > 12 ? 'American order is the only one that works here because ' + p2 + ' cannot be a month.' : 'American order is also possible: both numbers are ≤ 12, so this string is genuinely ambiguous. Act on the earlier date.' });
          }
        }
        if (p1 > 31 || p2 > 31 || p1 > 12 && p2 > 12) {
          res.readings.push({ label: 'three-part', as: nums.join('/'), until: null, invalid: true, note: 'Neither order works: ' + p1 + ' and ' + p2 + ' are both too large to be a day or a month.' });
        }
      }
    }

    if (nums.length === 1 && nums[0].length === 4) {
      res.formats.push('YYMM');
      res.formats.push('DDMM');
      var n = nums[0], yy = Number(n.slice(0, 2)), mm = Number(n.slice(2));
      var dd = Number(n.slice(0, 2)), dm = Number(n.slice(2));
      if (mm >= 1 && mm <= 12) {
        res.readings.push({ label: 'YYMM', as: pad(mm) + '/' + twoDigitYear(yy), until: monthEnd(twoDigitYear(yy), mm), note: 'Read as year then month: ' + n2m(mm) + ' ' + twoDigitYear(yy) + '. NAFDAC-era packs often print this without a separator.' });
      } else {
        res.readings.push({ label: 'YYMM', as: n, until: null, invalid: true, note: 'Cannot be YYMM: ' + mm + ' is not a month.' });
      }
      if (dm >= 1 && dm <= 12 && dd <= 31) {
        res.readings.push({ label: 'DDMM', as: pad(dd) + ' ' + n2m(dm) + ', year unstated', until: null, note: 'Read as day and month — plausible, but the year is not in the string, so this pack cannot be resolved from these four digits alone.' });
      } else {
        res.readings.push({ label: 'DDMM', as: n, until: null, invalid: true, note: 'Cannot be DDMM: ' + dm + ' is not a month.' });
      }
    }

    if (nums.length === 1 && (nums[0].length === 6 || nums[0].length === 8)) {
      res.formats.push(nums[0].length === 6 ? 'YYMMDD / DDMMYY' : 'YYYYMMDD');
      var s = nums[0];
      if (s.length === 8) {
        var Y = Number(s.slice(0, 4)), M = Number(s.slice(4, 6)), D = Number(s.slice(6, 8));
        if (M >= 1 && M <= 12) res.readings.push({ label: 'YYYYMMDD', as: fmt(new Date(Y, M - 1, D)), until: new Date(Y, M - 1, D), note: 'Eight digits starting with a four-digit year: unambiguous.' });
      } else {
        var y2 = twoDigitYear(Number(s.slice(0, 2))), m2 = Number(s.slice(2, 4)), d2 = Number(s.slice(4, 6));
        if (m2 >= 1 && m2 <= 12 && d2 >= 1 && d2 <= 31) {
          res.readings.push({ label: 'YYMMDD', as: fmt(new Date(y2, m2 - 1, Math.min(d2, daysInMonth(y2, m2)))), until: new Date(y2, m2 - 1, Math.min(d2, daysInMonth(y2, m2))), note: 'Read as year-month-day.' });
        }
        var d3 = Number(s.slice(0, 2)), m3 = Number(s.slice(2, 4)), y3 = twoDigitYear(Number(s.slice(4, 6)));
        if (m3 >= 1 && m3 <= 12 && d3 >= 1 && d3 <= 31) {
          res.readings.push({ label: 'DDMMYY', as: fmt(new Date(y3, m3 - 1, Math.min(d3, daysInMonth(y3, m3)))), until: new Date(y3, m3 - 1, Math.min(d3, daysInMonth(y3, m3))), note: 'Read as day-month-year. Where both are possible, the earlier of the two is the safe one.' });
        }
      }
    }

    if (!res.readings.length) res.readings.push({ label: 'no reading', as: 'unrecognised', until: null, invalid: true, note: 'Nothing in “' + text + '” fits a documented date convention. Re-read the pack; if there is genuinely no date, that is a red flag.' });

    var today = new Date(); today.setHours(0, 0, 0, 0);
    res.readings.forEach(function (r) {
      if (!r.until) { r.state = 'unresolved'; r.months = null; return; }
      r.months = (r.until.getFullYear() - today.getFullYear()) * 12 + (r.until.getMonth() - today.getMonth()) +
        (r.until.getDate() >= today.getDate() ? 0 : -1);
      r.months = Math.round(r.months * 10) / 10;
      r.state = r.months < 0 ? 'expired' : 'valid';
    });
    var resolved = res.readings.filter(function (r) { return r.until; });
    resolved.sort(function (a, b) { return a.until - b.until; });
    res.candidates = res.readings.length;
    res.safe = resolved.length ? resolved[0] : null;
    res.latest = resolved.length ? resolved[resolved.length - 1] : null;
    res.ambiguous = resolved.length > 1;
    res.eliminated = res.candidates > resolved.length;
    res.expired = !!(res.safe && res.safe.state === 'expired');
    return res;
  }

  function n2m(n) {
    var names = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    return names[Math.max(0, Math.min(11, n - 1))];
  }

  var HARD_STOP = { 'Injection / IV fluid': 1, 'Reconstituted antibiotic syrup': 1, 'ORS + zinc': 1, 'Vaccine': 1, 'Insulin': 1 };

  function expiryAdvice(cls, openedDays, parsed) {
    var notes = [];
    if (parsed.empty) { notes.push('No date entered. If the pack carries no legible date at all, that is a red flag on its own — do not use it, report it.'); return notes; }
    if (HARD_STOP[cls]) {
      notes.push('<b>' + escapeHtml(cls) + '</b> is a hard-stop class: once out of date it is discarded. Not “a little weaker”, not “fine for one more day”. Sterility, potency and — for injections — particulation all fail on the date, not on your judgement.');
    } else {
      notes.push('For this class the printed expiry is still the limit, but a passed date plus correct storage is a conversation with the pharmacist rather than an automatic discard.');
    }
    if (cls === 'Reconstituted antibiotic syrup') {
      notes.push(openedDays > 14
        ? 'You entered ' + openedDays + ' days since reconstitution. Refrigerated amoxicillin-type suspensions are normally discarded at 14 days (many inserts say 7). The printed expiry no longer applies — discard.'
        : 'After reconstitution the clock is 7–14 days refrigerated, whichever the insert says, even if the printed expiry is years away. You are at day ' + openedDays + '.');
    }
    if (cls === 'Insulin') {
      notes.push(openedDays > 28
        ? 'In-use insulin is normally discarded 28 days from first use (some presentations 42) whatever the printed date. You entered ' + openedDays + ' days.'
        : 'Unopened pens/vials follow the 2–8 °C printed date; in use, the 28-day clock governs. You are at day ' + openedDays + '.');
    }
    if (parsed.ambiguous) {
      notes.push('Two or more readings are possible. Act on the earliest: ' + escapeHtml(fmt(parsed.safe.until)) + '. Ask the pharmacist to confirm against the batch record before the later reading is trusted.');
    }
    if (parsed.legend && /BEST\s?BEFORE|BB/.test(parsed.legend)) {
      notes.push('The legend says “best before”, not “expiry”. On a medicine that is a labelling defect worth reporting — for food-grade products like ORS it still means the manufacturer is not standing behind the pack.');
    }
    if (!parsed.legend) notes.push('No legend printed next to the number. Unlabelled digits on a medicine carton are exactly where misreads happen — record the string verbatim when you report it.');
    return notes;
  }

  /* ------------------------------------------------------- 4. cold chain math */

  function coldMath(cls, temp, hours, prior, shaken, storage) {
    var b = budgetFor(cls);
    var dev = temp < b.lo ? b.lo - temp : (temp > b.hi ? temp - b.hi : 0);
    var dh = dev * hours;
    var total = dh + prior;
    var used = b.discard > 0 ? total / b.discard * 100 : 0;
    var res = { b: b, dev: dev, dh: dh, total: total, used: used, level: 'ok', title: '', body: '' };

    var freezeRisk = b.hard && (temp <= 0 || cls === 'vaccine' && storage === 'sun' && temp > 25);
    if (cls === 'vaccine' && (temp <= 0 || shaken === 'yes')) {
      res.level = 'discard';
      res.title = 'Freeze or shake damage — discard';
      res.body = 'Freeze-sensitive vaccines (adsorbed, oil-adjuvant, or any with a flocculating antigen) are damaged at 0 °C even for minutes, and the damage is invisible. A vial that froze or was shaken hard is quarantined and reported — the degree-hour budget is not the relevant test here.';
      return res;
    }
    if (total >= b.discard) {
      res.level = 'discard';
      res.title = 'Beyond the screening budget — do not use';
      res.body = kit.amount(total, 1) + ' degree-hours against an illustrative discard guide of ' + kit.amount(b.discard, 0) + '. Quarantine, label, and report the event. For vaccines this goes to the state EPI officer before a decision is made.';
      return res;
    }
    if (total >= b.warn) {
      res.level = 'warn';
      res.title = 'Inside the warn band — use only with a decision on record';
      res.body = kit.amount(total, 1) + ' degree-hours consumed (' + kit.pct(used, 0) + ' of the discard guide). For a vaccine this is a documented excursion that must be reported and assessed against the product-specific stability data, not decided here.';
      return res;
    }
    if (dev === 0) {
      res.title = 'Inside the ' + b.lo + '–' + b.hi + ' °C range';
      res.body = 'No degree-hours accrued by this event. Prior accumulation stands at ' + kit.amount(prior, 1) + '.';
      if (cls === 'insulin' && temp > 8) res.body += ' Note: in use at room temperature, insulin is normally allowed up to 28 days from first use (672 h). ' + kit.amount(hours, 1) + ' h of that clock has just been used.';
    } else {
      res.title = 'Minor excursion — within budget';
      res.body = kit.amount(dh, 1) + ' degree-hours from ' + kit.amount(hours, 1) + ' h at ' + temp + ' °C (' + kit.amount(dev, 1) + ' °C off the ' + b.lo + '–' + b.hi + ' °C range). Total ' + kit.amount(total, 1) + ' of a ' + kit.amount(b.discard, 0) + ' guide.';
    }
    res.level = 'ok';
    return res;
  }

  /* ------------------------------------------------------------ 5. scoring */

  function readAnswers() {
    var a = {};
    CHECKS.forEach(function (c) {
      var el = document.querySelector('input[name="' + c.id + '"]:checked');
      if (el) a[c.id] = el.value;
    });
    return a;
  }

  function score(answers) {
    var answeredW = 0, credit = 0, losses = [], flags = [], totalW = 0, groups = {};
    CHECKS.forEach(function (c) {
      var w = Number(weights[c.id]) || 0;
      totalW += w;
      groups[c.group] = groups[c.group] || { w: 0, earned: 0 };
      groups[c.group].w += w;
      var v = answers[c.id];
      if (!v) return;
      answeredW += w;
      var factor = v === 'yes' ? 1 : (v === 'unsure' ? unsureCredit : 0);
      credit += w * factor;
      groups[c.group].earned += w * factor;
      if (v !== 'yes') losses.push({ id: c.id, text: c.text, group: c.group, lost: w * (1 - factor), value: v });
      if (v === 'no' && c.fatal) flags.push('Override · ' + c.group + ': ' + c.text);
    });

    if (document.getElementById('vvm-fail').checked) flags.push('Override · cold chain: VVM is at stage 3 or 4 — the vial is discarded on the dial alone.');
    if (document.getElementById('exp-fail').checked) flags.push('Override · expiry: the safe date reading puts this pack out of date' + (expiryState.label ? ' (' + expiryState.label + ')' : '') + '.');

    var regInput = document.getElementById('p-number').value;
    var reg = checkRegistration(regInput);
    if (regInput && !reg.valid) flags.push('Override · documentation: “' + reg.raw + '” is not a recognised registration-number shape.');

    var score100 = answeredW ? credit / answeredW * 100 : null;
    losses.sort(function (a, b) { return b.lost - a.lost; });
    return {
      score: score100, answeredW: answeredW, totalW: totalW, credit: credit,
      coverage: totalW ? answeredW / totalW * 100 : 0, answered: Object.keys(answers).length,
      losses: losses, flags: flags, groups: groups, reg: reg
    };
  }

  function tier(s) {
    if (s === null) return { key: 'none', title: 'Nothing inspected yet', cls: '' };
    if (s >= 85) return { key: 'ok', title: 'Use as directed', cls: 'ok' };
    if (s >= 66) return { key: 'warn', title: 'Inspect further with the pharmacist', cls: 'warn' };
    return { key: 'bad', title: 'Do not use — report it', cls: 'bad' };
  }

  /* ---------------------------------------------------------------- paint */

  function setFigure(id, value, cls) {
    var el = document.getElementById(id);
    if (!el) return;
    var text = value === null || value === undefined ? '—' : String(value);
    if (el.textContent !== text) {
      el.textContent = text;
      if (!kit.reduce) {
        el.classList.remove('tick');
        void el.offsetWidth;
        el.classList.add('tick');
      }
    }
    if (cls) el.className = el.className.replace(/\s(pass|fail)/, '') + ' ' + cls;
  }

  function buildChecklist() {
    var host = document.getElementById('check-groups');
    var order = ['Packaging', 'Documentation', 'Product', 'Cold chain'];
    var html = '';
    order.forEach(function (g) {
      var rows = CHECKS.filter(function (c) { return c.group === g; });
      var gw = rows.reduce(function (a, c) { return a + (Number(weights[c.id]) || 0); }, 0);
      html += '<div class="check-group"><p class="check-group-title">' + g + '<span>' + gw + ' weight</span></p>' +
        '<p class="grp-note">' + GROUP_NOTE[g] + '</p>';
      rows.forEach(function (c) {
        html += '<div class="check" data-check="' + c.id + '">' +
          '<span class="q" id="q-' + c.id + '">' + escapeHtml(c.text) +
          '<span class="w">weight ' + (Number(weights[c.id]) || 0) + (c.fatal ? ' · a No answer overrides the score' : '') + '</span></span>' +
          '<span class="seg" role="radiogroup" aria-labelledby="q-' + c.id + '">' +
          ['yes', 'no', 'unsure'].map(function (v) {
            return '<label data-v="' + v + '"><input type="radio" name="' + c.id + '" value="' + v + '">' + v + '</label>';
          }).join('') + '</span></div>';
      });
      html += '</div>';
    });
    host.innerHTML = html;
  }

  function setAnswer(id, value) {
    var el = document.querySelector('input[name="' + id + '"][value="' + value + '"]');
    if (el) el.checked = true;
  }

  function paintChecks() {
    kit.$$('.check').forEach(function (row) {
      var id = row.dataset.check;
      var el = document.querySelector('input[name="' + id + '"]:checked');
      var v = el ? el.value : '';
      row.className = 'check' + (v ? ' is-' + v : '') + (v === 'no' && (CHECKS.filter(function (c) { return c.id === id; })[0] || {}).fatal ? ' is-fatal' : '');
      kit.$$('.seg label', row).forEach(function (lab) {
        lab.className = lab.dataset.v === v ? 'on-' + v : '';
      });
    });
  }

  function paint() {
    var answers = readAnswers();
    var r = score(answers);
    var panel = document.getElementById('score-panel');

    if (r.score === null) {
      setFigure('score', '—');
      document.getElementById('score-note').textContent = 'no checks answered yet';
      verdict('Nothing inspected yet', 'Work down the checklist — the verdict, the score and the reasons update as you answer.', '');
      kit.$('#reason-list').innerHTML = '<li class="tiny">Answer a few checks and the largest weighted losses appear here.</li>';
      document.getElementById('flags').hidden = true;
      kit.donut(document.getElementById('score-donut'), 0, { label: 'no data' });
      document.getElementById('group-bars').innerHTML = '';
    } else {
      var s = Math.round(r.score * 10) / 10;
      if (lastScore !== null && lastScore !== s && !kit.reduce) { panel.classList.remove('tighten'); void panel.offsetWidth; panel.classList.add('tighten'); }
      lastScore = s;
      setFigure('score', s.toFixed(1));
      document.getElementById('score-note').textContent = r.coverage < 100
        ? 'of 100 · provisional: ' + kit.pct(r.coverage, 0) + ' of the weight answered, unanswered checks excluded'
        : 'of 100 · all 14 checks answered';
      var t = tier(r.score);
      if (r.flags.length) {
        verdict('Do not use — report it', 'The numeric score is ' + s.toFixed(1) + ', but ' + r.flags.length + ' red flag' + (r.flags.length > 1 ? 's are' : ' is') +
          ' present and a red flag overrides the arithmetic. Keep the pack, keep the receipt, and follow the reporting steps below.', 'bad');
      } else {
        verdict(t.title, describeVerdict(t.key, r, s), t.cls);
      }
      renderReasons(r);
      renderFlags(r);
      var colour = t.key === 'ok' ? 'var(--ok)' : t.key === 'warn' ? 'var(--warn)' : 'var(--danger)';
      kit.donut(document.getElementById('score-donut'), r.score, { label: kit.pct(r.score, 1) + ' integrity', color: colour });
      kit.bars(document.getElementById('group-bars'), ['Packaging', 'Documentation', 'Product', 'Cold chain'].map(function (g) {
        var gp = r.groups[g] || { w: 0, earned: 0 };
        return { label: g, value: gp.w ? gp.earned / gp.w * 100 : 0, display: gp.w ? Math.round(gp.earned * 10) / 10 + ' / ' + gp.w : '—' };
      }));
    }

    setFigure('m-answered', r.answered + '/14');
    setFigure('m-coverage', kit.pct(r.coverage, 0));
    setFigure('m-shape', r.reg.raw ? (r.reg.valid ? 'valid shape · ' + r.reg.kind : 'not valid') : '—');
    worked(r);
    paintChecks();
  }

  function describeVerdict(key, r, s) {
    if (key === 'ok') return 'No overriding defect and ' + s.toFixed(1) + ' of 100. Still confirm the registration on the official register, and keep the receipt: a passing visual check is not proof of identity or content.';
    if (key === 'warn') return 'Score ' + s.toFixed(1) + ' of 100 with real defects but nothing conclusive on its own. Do not accept this pack as the only course of treatment while a pharmacist re-checks the seams, the number and the date.';
    return 'Score ' + s.toFixed(1) + ' of 100 from multiple documented defects. Do not use it, do not return it to the shelf, and report it with the batch number.';
  }

  function renderReasons(r) {
    var top = r.losses.slice(0, 3);
    var list = document.getElementById('reason-list');
    if (!top.length) {
      list.innerHTML = '<li class="tiny">Every answered check passed — nothing is pulling the score down.</li>';
      return;
    }
    list.innerHTML = top.map(function (l) {
      return '<li>' + escapeHtml(short(l.text)) + ' <b>−' + (Math.round(l.lost * 10) / 10) + '</b> · ' + l.value + ' (' + l.group + ')</li>';
    }).join('');
  }

  function short(text) { return text.length > 68 ? text.slice(0, 65) + '…' : text; }

  function renderFlags(r) {
    var box = document.getElementById('flags');
    if (!r.flags.length) { box.hidden = true; kit.$('#flag-list').innerHTML = ''; return; }
    box.hidden = false;
    kit.$('#flag-list').innerHTML = r.flags.map(function (f) { return '<li>' + escapeHtml(f) + '</li>'; }).join('');
  }

  function verdict(title, body, cls) {
    var box = document.getElementById('verdict');
    box.className = 'verdict' + (cls ? ' ' + cls : '');
    document.getElementById('verdict-title').textContent = title;
    document.getElementById('verdict-body').textContent = body;
  }

  function worked(r) {
    var steps = [];
    if (r.answered === 0) {
      kit.$('#worked').innerHTML = '<li>No checks answered. Press <b>Load worked example</b> in the inspection form to reproduce the re-packed-carton scenario.</li>';
      setFigure('worked-score', '—');
      document.getElementById('worked-flags').textContent = '0 overrides';
      return;
    }
    steps.push('Answers: ' + r.answered + ' of 14 checks · weight answered <b>' + r.answeredW + '</b> of <b>' + r.totalW + '</b> (' + kit.pct(r.coverage, 0) + ' coverage).');
    steps.push('Credit rule: Yes = weight × 1, Unsure = weight × <b>' + unsureCredit + '</b>, No = 0.');
    steps.push('Sum of weighted credit = <b>' + (Math.round(r.credit * 10) / 10) + '</b>. Score = credit ÷ answered weight × 100 = <b>' + (r.score === null ? '—' : r.score.toFixed(1)) + '</b>.');
    steps.push('Largest losses: ' + (r.losses.length ? r.losses.slice(0, 3).map(function (l) { return escapeHtml(short(l.text)) + ' (−' + (Math.round(l.lost * 10) / 10) + ')'; }).join('; ') : 'none') + '.');
    steps.push('Overrides fired: ' + (r.flags.length ? r.flags.map(escapeHtml).join(' · ') : '<b>none</b> — verdict follows the number.') + '.');
    steps.push('Number shape from the identity field: <b>' + (r.reg.raw ? escapeHtml(r.reg.raw) + ' → ' + (r.reg.valid ? 'valid ' + r.reg.kind : 'not a documented shape') : 'not entered') + '</b>.');
    kit.$('#worked').innerHTML = steps.map(function (s) { return '<li>' + s + '</li>'; }).join('');
    setFigure('worked-score', r.score === null ? '—' : r.score.toFixed(1));
    document.getElementById('worked-flags').textContent = r.flags.length + (r.flags.length === 1 ? ' override' : ' overrides');
  }

  /* ---------------------------------------------------- register + lookup UI */

  function paintRegister() {
    var raw = document.getElementById('reg-input').value;
    var type = document.getElementById('reg-type').value;
    var r = checkRegistration(raw);
    setFigure('reg-shape', raw ? (r.valid ? 'recognised shape' : 'NOT a recognised shape') : 'awaiting input');
    setFigure('reg-prefix', r.prefix || '—');
    setFigure('reg-digits', raw ? r.digits || '—' : '—');
    var con = raw ? consistency(r, type) : null;
    setFigure('reg-consistent', con ? con.text : '—');
    setFigure('reg-lookup', 'not performed here — verify on the NAFDAC register');

    var box = document.getElementById('reg-verdict');
    var title = document.getElementById('reg-verdict-title'), body = document.getElementById('reg-verdict-body');
    if (!raw) {
      box.className = 'verdict verdict-ink';
      title.textContent = 'Enter a number';
      body.textContent = 'A valid shape is not proof. Confirm on the NAFDAC register or the mobile app before you accept the pack.';
      return;
    }
    if (!r.valid) {
      box.className = 'verdict verdict-ink bad';
      title.textContent = 'Reject on the shape alone';
      body.textContent = (r.notes[0] || 'Not a documented form.') + ' Ask the seller for the registration number of the exact product and strength, then verify it on the official register. Do not pay for a pack whose number cannot be read as a number.';
      return;
    }
    var mismatch = con && con.ok === false;
    var context = (r.kind === 'variant' || r.kind === 'legacy' || r.kind === 'legacy-plain');
    if (r.kind === 'variant') {
      box.className = 'verdict verdict-ink warn';
      title.textContent = 'Valid shape, wrong context';
      body.textContent = r.prefix + ' A retail carton should not carry a bulk or contract suffix. Ask for the registration of the finished product you are holding, and verify it on the official register.';
      return;
    }
    if (context) {
      box.className = 'verdict verdict-ink warn';
      title.textContent = 'Older numeric form — confirm the listing is still live';
      body.textContent = r.prefix + ' ' + (r.notes[0] || '') + ' Legacy numbers are frequently dormant or withdrawn, so the shape passing tells you less here, not more.';
      return;
    }
    box.className = 'verdict verdict-ink ' + (mismatch ? 'warn' : 'ok');
    title.textContent = mismatch ? 'Shape valid — but the type does not line up' : 'Shape valid. That is permission to ask, not proof.';
    body.textContent = r.prefix + (mismatch ? ' ' + con.text : '') + ' Verify this registration against the product name, strength and pack you are holding, on the official register, with a pharmacist present.';
  }

  function paintLookup() {
    var q = document.getElementById('lookup-q').value.toLowerCase().trim();
    var cls = document.getElementById('lookup-class').value;
    var rows = REGISTRY.filter(function (p) {
      if (cls && p.cls !== cls) return false;
      if (!q) return true;
      return (p.name + ' ' + p.cls + ' ' + p.shape + ' ' + p.pack + ' ' + p.seal).toLowerCase().indexOf(q) !== -1;
    });
    kit.$('#lookup-body').innerHTML = rows.map(function (p) {
      var i = REGISTRY.indexOf(p);
      return '<tr><td>' + escapeHtml(p.name) + '</td><td>' + escapeHtml(p.cls) + '</td><td class="mono">' + escapeHtml(p.shape) + '</td>' +
        '<td>' + escapeHtml(p.pack) + '<span class="seal-note">' + escapeHtml(p.seal) + '</span></td>' +
        '<td class="num">' + kit.money(p.price[0]) + '–' + kit.money(p.price[1]) + '</td>' +
        '<td><button class="btn btn-ghost btn-sm" type="button" data-use="' + i + '">Use in inspection</button></td></tr>';
    }).join('');
    document.getElementById('lookup-count').textContent = rows.length + ' of ' + REGISTRY.length + ' shown';
    document.getElementById('lookup-empty').hidden = rows.length > 0;
  }

  function paintPrefixTable() {
    kit.$('#prefix-body').innerHTML = GS1.map(function (g) {
      var code = g.lo === g.hi ? String(g.lo) : g.lo + '–' + g.hi;
      if (g.lo < 100 && g.hi < 100) code = pad3(g.lo) + (g.hi === g.lo ? '' : '–' + pad3(g.hi));
      return '<tr><td class="mono">' + code + '</td><td>' + escapeHtml(g.c) + '</td><td>' +
        (g.sure ? '<span class="badge badge-ok">documented</span>' : '<span class="badge badge-warn">reported — confirm</span>') + '</td></tr>';
    }).join('');
  }

  function pad3(n) { return n < 100 ? (n < 10 ? '00' : '0') + n : String(n); }

  function paintBarcode() {
    var res = analyseBarcode(document.getElementById('bc-input').value, document.getElementById('bc-made').value);
    var box = document.getElementById('bc-verdict');
    var title = document.getElementById('bc-title'), body = document.getElementById('bc-body');
    setFigure('bc-len', res.known ? res.symbology : (res.digits.length ? res.symbology : '—'));
    setFigure('bc-prefix', res.prefix || '—');
    setFigure('bc-country', res.entry ? res.entry.c : '—');
    setFigure('bc-cd', res.checkOk === null ? '—' : (res.checkOk ? 'valid' : 'INVALID'), res.checkOk === null ? '' : (res.checkOk ? 'pass' : 'fail'));
    setFigure('bc-match', res.conflict === 'match' ? 'prefix and “Made in” agree' : res.conflict === 'conflict' ? 'prefix and “Made in” differ' : res.conflict === 'unknown' ? 'cannot compare confidently' : '—');

    if (!res.digits) {
      box.className = 'verdict'; title.textContent = 'Awaiting digits'; body.textContent = 'Type the number under the bars.'; return;
    }
    if (!res.known) {
      box.className = 'verdict warn'; title.textContent = 'That is not a GS1 product code';
      body.textContent = res.notes[0] || 'Wrong length.'; return;
    }
    if (!res.checkOk) {
      box.className = 'verdict bad';
      title.textContent = 'Check digit fails — this code is broken';
      body.textContent = 'Recomputed check digit should be ' + checkDigit(res.digits) + ' but the code ends in ' + res.digits.slice(-1) + '. A failed check digit means the string is mistyped, misread or copied badly: that is arithmetic, not opinion. ' + (res.notes[0] || '');
      return;
    }
    if (res.conflict === 'conflict') {
      box.className = 'verdict warn';
      title.textContent = 'Prefix and “Made in” disagree';
      body.textContent = res.notes[0];
      return;
    }
    if (res.conflict === 'unknown') {
      box.className = 'verdict';
      title.textContent = 'Well-formed code — prefix assignment not asserted here';
      body.textContent = 'The check digit arithmetic passes, which only says the string is a plausible code. ' + (res.notes[0] || '') +
        ' Look the prefix up on the GS1 list before repeating any country claim from this barcode.';
      return;
    }
    box.className = 'verdict ok';
    title.textContent = 'Internally consistent — which proves very little';
    body.textContent = 'The code is well-formed and the prefix does not contradict the printed country. Remember what that means: the licence was issued in ' +
      (res.entry ? res.entry.c : 'an unlisted range') + ', the check digit arithmetic is right, and a counterfeiter who copies a real barcode gets exactly this result. ' +
      (res.notes[0] || '');
  }

  /* -------------------------------------------------------------- expiry UI */

  function paintExpiry() {
    var raw = document.getElementById('exp-input').value;
    var cls = document.getElementById('exp-class').value;
    var opened = kit.clamp(kit.num(document.getElementById('exp-open').value), 0, 400);
    var p = parseExpiry(raw);
    var box = document.getElementById('exp-verdict');
    var title = document.getElementById('exp-title'), body = document.getElementById('exp-body');

    kit.$('#format-pills').innerHTML = (p.formats.length ? p.formats : ['—']).map(function (f) {
      return '<span class="pill' + (p.formats.length ? ' is-on' : '') + '">' + escapeHtml(f) + '</span>';
    }).join('');

    if (p.empty) {
      document.getElementById('exp-safe').textContent = '—';
      document.getElementById('exp-safe-note').textContent = 'enter what the pack prints';
      box.className = 'verdict'; title.textContent = 'Awaiting a date';
      body.textContent = 'Type the date exactly as printed, including the legend if there is one.';
      kit.$('#exp-rows').innerHTML = '';
      kit.$('#exp-rules').innerHTML = '';
      expiryState = { expired: false, label: '' };
      return;
    }

    kit.$('#exp-rows').innerHTML = p.readings.map(function (r) {
      var until = r.until ? fmt(r.until) : 'unresolved';
      var st = r.invalid ? 'impossible' : (r.state === 'expired' ? 'out of date' : r.state === 'valid' ? 'in date' : 'no year printed');
      return '<tr class="' + (r === p.safe && r.until ? 'is-safe' : '') + (r.state === 'expired' ? ' is-past' : '') + '">' +
        '<td>' + escapeHtml(r.label) + '</td><td>' + escapeHtml(r.as) + '</td><td class="num">' + escapeHtml(until) + '</td>' +
        '<td class="num">' + (r.months === null || r.months === undefined ? '—' : r.months.toFixed(1)) + '</td>' +
        '<td>' + escapeHtml(st) + '</td></tr>';
    }).join('');

    if (!p.safe) {
      document.getElementById('exp-safe').textContent = 'cannot resolve';
      document.getElementById('exp-safe-note').textContent = '“' + p.text + '” does not yield a resolvable date';
      box.className = 'verdict bad'; title.textContent = 'No usable date';
      body.textContent = p.readings[0].note + ' Treat a pack whose date cannot be resolved as a reporting case, not as a purchase.';
      expiryState = { expired: true, label: p.text };
      document.getElementById('exp-fail').checked = true;
      return;
    }

    document.getElementById('exp-safe').textContent = fmt(p.safe.until);
    document.getElementById('exp-safe-note').textContent = 'safe reading: ' + p.safe.label + ' · ' +
      (p.safe.months < 0 ? Math.abs(p.safe.months) + ' months past' : p.safe.months + ' months remaining') +
      (p.ambiguous ? ' · latest possible reading ' + fmt(p.latest.until) : '');

    var past = p.safe.months < 0;
    var useByBreach = (cls === 'Reconstituted antibiotic syrup' && opened > 14) || (cls === 'Insulin' && opened > 28);
    expiryState = { expired: past || useByBreach, label: fmt(p.safe.until) };
    document.getElementById('exp-fail').checked = past || useByBreach;

    if (past) {
      box.className = 'verdict bad';
      title.textContent = 'Out of date on the safe reading' + (p.ambiguous ? ' — and on at least one other' : '');
      body.textContent = 'Do not use it. ' + (p.ambiguous ? 'A later reading exists (' + fmt(p.latest.until) + ') that would put this pack in date, and that is precisely the ambiguity you are not allowed to resolve in your own favour at a medicine counter. ' : '') +
        (HARD_STOP[cls] ? 'This class is a hard stop.' : 'Ask the pharmacist to verify against the batch record before any use.');
    } else if (useByBreach) {
      box.className = 'verdict bad';
      title.textContent = 'In date, but past its in-use clock';
      body.textContent = 'Once opened or reconstituted, the printed expiry stops governing the product. Discard and record the day you opened it.';
    } else if (p.ambiguous) {
      box.className = 'verdict warn';
      title.textContent = 'Ambiguous on the pack, unambiguous on the action';
      body.textContent = 'Two readings resolve to different dates. ' + p.safe.label + ' gives the earliest, ' + fmt(p.safe.until) + '; the latest possible reading is ' + fmt(p.latest.until) + '. ' + p.safe.note + ' Act on the earliest.';
    } else if (p.eliminated) {
      box.className = 'verdict warn';
      title.textContent = 'Two readings were possible — one is impossible';
      body.textContent = 'The string admits ' + p.candidates + ' readings. ' + p.safe.note + ' The safe reading is therefore ' + fmt(p.safe.until) +
        ' (' + (p.safe.months < 0 ? Math.abs(p.safe.months) + ' months past' : p.safe.months + ' months left') + '). Record why the other reading was rejected when you report it.';
    } else {
      box.className = 'verdict ok';
      title.textContent = 'In date on every reading';
      body.textContent = p.safe.label + ': usable until ' + fmt(p.safe.until) + ' — ' + p.safe.months + ' months from today. ' + p.safe.note;
    }

    kit.$('#exp-rules').innerHTML = expiryAdvice(cls, opened, p).map(function (n) { return '<div class="rule">' + n + '</div>'; }).join('');
    paint();
  }

  /* ---------------------------------------------------------------- cold UI */

  function paintVvm() {
    var stage = Number(document.getElementById('vvm-picker').dataset.stage || 1);
    var v = VVM[stage];
    document.getElementById('vvm-inner').style.backgroundColor = v.inner;
    document.getElementById('vvm-ringlabel').textContent = String(stage);
    kit.$$('#vvm-picker button').forEach(function (b, i) { b.setAttribute('aria-pressed', String(i === stage)); });
    var box = document.getElementById('vvm-verdict');
    box.className = 'verdict ' + (v.verdict === 'bad' ? 'bad' : v.verdict === 'warn' ? 'warn' : 'ok');
    document.getElementById('vvm-title').textContent = v.title;
    document.getElementById('vvm-body').textContent = v.body;
    document.getElementById('vvm-fail').checked = stage >= 3;
  }

  function paintCold() {
    var cls = document.getElementById('cc-class').value;
    var b = budgetFor(cls);
    var temp = kit.num(document.getElementById('cc-temp').value);
    var hours = kit.clamp(kit.num(document.getElementById('cc-hours').value), 0, 720);
    var prior = kit.clamp(kit.num(document.getElementById('cc-prior').value), 0, 5000);
    var res = coldMath(cls, temp, hours, prior, document.getElementById('cc-shaken').value, document.getElementById('cc-storage').value);

    document.getElementById('cc-dev').textContent = temp + ' °C · ' + (res.dev > 0 ? 'off range by ' + kit.amount(res.dev, 1) + ' °C' : 'inside ' + b.lo + '–' + b.hi);
    document.getElementById('cc-dh').textContent = kit.amount(res.dh, 1) + (prior ? ' + ' + kit.amount(prior, 1) + ' prior' : '');
    document.getElementById('cc-use').textContent = kit.pct(res.used, 1);
    var bar = document.getElementById('cc-bar');
    bar.style.width = kit.clamp(res.used, 0, 100) + '%';
    document.getElementById('cc-bar-note').textContent = 'budget consumed · illustrative discard guide ' + kit.amount(b.discard, 0) + ' degree-hours';
    var box = document.getElementById('cc-verdict');
    box.className = 'verdict ' + (res.level === 'discard' ? 'bad' : res.level === 'warn' ? 'warn' : 'ok');
    document.getElementById('cc-title').textContent = res.title;
    document.getElementById('cc-body').textContent = res.body;
  }

  /* ------------------------------------------------------------------ rules */

  function paintWeights() {
    kit.$('#weight-body').innerHTML = CHECKS.map(function (c) {
      return '<tr data-w="' + c.id + '"><td>' + escapeHtml(short(c.text)) + '</td>' +
        '<td class="num"><input type="number" min="0" max="40" step="1" value="' + (Number(weights[c.id]) || 0) + '" aria-label="Weight for ' + escapeHtml(short(c.text)) + '"></td>' +
        '<td>' + (c.fatal ? '<span class="badge badge-bad">override</span>' : '<span class="tiny">weighted only</span>') + '</td></tr>';
    }).join('');
  }

  function paintBudgets() {
    kit.$('#budget-body').innerHTML = budgets.map(function (b, i) {
      return '<tr data-b="' + i + '"><td>' + escapeHtml((BUDGETS.filter(function (x) { return x.key === b.key; })[0] || {}).label || b.key) + '</td>' +
        '<td class="num"><input type="number" min="-20" max="60" step="0.5" value="' + b.lo + '" data-f="lo" aria-label="Lower range for ' + escapeHtml(b.key) + '"></td>' +
        '<td class="num"><input type="number" min="-20" max="60" step="0.5" value="' + b.hi + '" data-f="hi" aria-label="Upper range for ' + escapeHtml(b.key) + '"></td>' +
        '<td class="num"><input type="number" min="0" max="2000" step="1" value="' + b.warn + '" data-f="warn" aria-label="Warn degree-hours"></td>' +
        '<td class="num"><input type="number" min="0" max="2000" step="1" value="' + b.discard + '" data-f="discard" aria-label="Discard degree-hours"></td></tr>';
    }).join('');
  }

  function refreshCheckWeights() {
    kit.$$('.check').forEach(function (row) {
      var c = CHECKS.filter(function (x) { return x.id === row.dataset.check; })[0];
      var w = row.querySelector('.w');
      if (w && c) w.textContent = 'weight ' + (Number(weights[c.id]) || 0) + (c.fatal ? ' · a No answer overrides the score' : '');
    });
    ['Packaging', 'Documentation', 'Product', 'Cold chain'].forEach(function (g) {
      var rows = CHECKS.filter(function (c) { return c.group === g; });
      var gw = rows.reduce(function (a, c) { return a + (Number(weights[c.id]) || 0); }, 0);
      var host = kit.$$('.check-group-title span').filter(function (el) { return el.parentNode.textContent.indexOf(g) === 0; })[0];
      if (host) host.textContent = gw + ' weight';
    });
  }

  /* ---------------------------------------------------------------- report */

  function currentContext() {
    var answers = readAnswers();
    var r = score(answers);
    var t = tier(r.score);
    var vTitle = r.flags.length ? 'Do not use — report it (red flag override)' : t.title;
    return { r: r, verdict: vTitle, score: r.score === null ? null : Math.round(r.score * 10) / 10 };
  }

  function buildReport() {
    var c = currentContext();
    var f = function (id) { return document.getElementById(id).value.trim(); };
    var when = f('o-when') || kit.dateLabel(new Date());
    var lines = [];
    lines.push('MEDICINE QUALITY CONCERN — DRAFT REPORT');
    lines.push('generated by SealCheck (a screening aid; no conclusion about authenticity can be drawn from it)');
    lines.push('');
    lines.push('Date of inspection / purchase : ' + when);
    lines.push('Outlet described               : ' + (f('o-name') || '(not recorded)') + (f('o-place') ? ' — ' + f('o-place') : ''));
    lines.push('Price paid                   : ' + (kit.num(f('o-price')) ? kit.money(kit.num(f('o-price'))) : '(not recorded)'));
    lines.push('Product                      : ' + (f('p-name') || '(not recorded)'));
    lines.push('Number as printed            : ' + (f('p-number') || '(none)') + ' → shape check: ' +
      (c.r.reg.raw ? (c.r.reg.valid ? 'recognised (' + c.r.reg.kind + ')' : 'NOT recognised') + ' — verify on the register' : 'not entered'));
    lines.push('Batch as printed             : ' + (f('p-batch') || '(none)'));
    lines.push('Expiry as printed            : ' + (f('p-expiry') || '(none)') + (expiryState.label ? ' → safe reading ' + expiryState.label : ''));
    lines.push('');
    lines.push('Inspection score             : ' + (c.score === null ? 'not scored' : c.score.toFixed(1) + ' / 100') +
      '   coverage ' + kit.pct(c.r.coverage, 0) + ' of weight');
    lines.push('Verdict                      : ' + c.verdict);
    lines.push('');
    lines.push('Red flags overriding the score (' + c.r.flags.length + '):');
    lines.push(c.r.flags.length ? c.r.flags.map(function (x) { return '  - ' + x; }).join('\n') : '  - none');
    lines.push('');
    lines.push('Largest weighted defects:');
    lines.push(c.r.losses.length ? c.r.losses.slice(0, 5).map(function (l) {
      return '  - [' + l.group + ' / answered ' + l.value + ' / −' + (Math.round(l.lost * 10) / 10) + '] ' + l.text;
    }).join('\n') : '  - none recorded');
    if (f('o-defects')) {
      lines.push('');
      lines.push('Observer’s own words:');
      lines.push('  ' + f('o-defects'));
    }
    lines.push('');
    lines.push('Suggested recipients: the licensed pharmacist / superintendent at the point of sale; the state drug officer or state pharmacist;');
    lines.push('NAFDAC regulatory reporting channel (integrity line and the complaint form at nafdac.gov.ng), with the physical pack, carton,');
    lines.push('leaflet and receipt retained. If a dose was already taken, inform the prescriber as well.');
    lines.push('');
    lines.push('Records kept by the reporter: pack, photographs of all faces and the seal damage, receipt, and the remainder of the batch at the outlet.');

    document.getElementById('report-text').value = lines.join('\n');
    document.getElementById('report-status').textContent = 'Report built from the ' + c.r.answered + ' answered checks. Copy it, print it, or save it to the history below.';
    kit.toast('Report drafted — copy it or print it');
  }

  function copyReport() {
    var text = document.getElementById('report-text').value;
    if (!text) { buildReport(); text = document.getElementById('report-text').value; }
    function done() { kit.toast('Copied to the clipboard'); }
    function fail() {
      var ta = document.getElementById('report-text');
      ta.removeAttribute('readonly');
      ta.select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      ta.setAttribute('readonly', '');
      window.getSelection().removeAllRanges();
      kit.toast(ok ? 'Copied to the clipboard' : 'Copying is blocked — select the text and copy manually');
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, fail);
    } else fail();
  }

  /* ---------------------------------------------------------------- history */

  function history() { return hStore.read() || []; }

  function saveInspection() {
    var c = currentContext();
    if (c.score === null) { kit.toast('Answer the checks before saving an inspection'); return; }
    var f = function (id) { return document.getElementById(id).value.trim(); };
    var list = history();
    list.unshift({
      id: kit.uid('insp'),
      when: Date.now(),
      date: f('o-when') || kit.dateLabel(new Date()),
      product: f('p-name') || 'unnamed pack',
      number: f('p-number') || '—',
      batch: f('p-batch') || '—',
      expiry: f('p-expiry') || '—',
      score: c.score, verdict: c.verdict,
      flags: c.r.flags.length,
      coverage: Math.round(c.r.coverage),
      outlet: (f('o-name') + ' ' + f('o-place')).trim() || '—'
    });
    if (list.length > 200) list.length = 200;
    if (!hStore.write(list)) { kit.toast('This browser blocked saving; copy the report text instead'); return; }
    paintHistory();
    kit.toast('Inspection saved');
  }

  function paintHistory() {
    var list = history();
    var body = kit.$('#history-body');
    document.getElementById('history-count').textContent = list.length + (list.length === 1 ? ' inspection' : ' inspections') +
      (list.length ? ' · mean score ' + (list.reduce(function (a, r) { return a + r.score; }, 0) / list.length).toFixed(1) : '');
    document.getElementById('history-empty').hidden = list.length > 0;
    if (!list.length) { body.innerHTML = ''; return; }
    body.innerHTML = list.map(function (row) {
      var k = row.score >= 85 ? 'ok' : row.score >= 66 ? 'warn' : 'bad';
      return '<tr><td>' + escapeHtml(row.date) + '</td><td>' + escapeHtml(row.product) + '</td><td class="mono">' + escapeHtml(row.number) + '</td>' +
        '<td class="mono">' + escapeHtml(row.batch) + '</td><td class="mono">' + escapeHtml(row.expiry) + '</td>' +
        '<td class="num score-cell ' + k + '">' + row.score.toFixed(1) + '</td>' +
        '<td>' + escapeHtml(row.verdict) + '</td>' +
        '<td class="mono">' + row.flags + '</td><td>' + escapeHtml(row.outlet) + '</td>' +
        '<td><button class="row-del" type="button" data-del="' + row.id + '" aria-label="Remove inspection on ' + escapeHtml(row.date) + ' ×">×</button></td></tr>';
    }).join('');
  }

  function exportHistory() {
    var list = history();
    if (!list.length) { kit.toast('Nothing saved to export yet'); return; }
    kit.csv([
      ['Date', 'Product', 'Number as printed', 'Batch', 'Expiry as printed', 'Score /100', 'Coverage %', 'Verdict', 'Red flags', 'Outlet', 'Notes']
    ].concat(list.map(function (r) {
      return [r.date, r.product, r.number, r.batch, r.expiry, r.score, r.coverage, r.verdict, r.flags, r.outlet, 'Screening aid only — not a verification'];
    })), 'sealcheck-inspections-' + new Date().toISOString().slice(0, 10) + '.csv');
  }

  /* ---------------------------------------------------------------- recalls */

  function paintRecalls() {
    var cat = document.getElementById('recall-cat').value;
    var batch = document.getElementById('recall-batch').value.toUpperCase().replace(/\s+/g, '');
    var rows = RECALLS.filter(function (r) { return !cat || r.cat === cat; });
    var hits = batch ? rows.filter(function (r) { return r.batch.toUpperCase() === batch; }) : [];
    kit.$('#recall-body').innerHTML = rows.map(function (r) {
      return '<tr class="' + (hits.indexOf(r) !== -1 ? 'is-hit' : '') + '"><td>' + escapeHtml(r.date) + '</td><td>' + escapeHtml(r.product) + '</td>' +
        '<td>' + escapeHtml(r.cat) + '</td><td class="mono">' + escapeHtml(r.batch) + '</td><td>' + escapeHtml(r.reason) + '</td>' +
        '<td>' + escapeHtml(r.region) + '</td><td>' + escapeHtml(r.action) + '</td></tr>';
    }).join('');
    document.getElementById('recall-empty').hidden = rows.length > 0;
    var badge = document.getElementById('recall-hit');
    if (!batch) { badge.textContent = 'no batch entered'; badge.className = 'badge badge-warn'; }
    else if (hits.length) { badge.textContent = 'sample match: ' + hits.length + ' row(s) — this is FICTION, check the real notice'; badge.className = 'badge badge-bad'; }
    else { badge.textContent = 'no match in the sample data — proves nothing about your pack'; badge.className = 'badge badge-ink'; }
  }

  /* ------------------------------------------------------------------ boot */

  kit.boot({
    ready: function () {
      buildChecklist();
      paintWeights();
      paintBudgets();
      paintPrefixTable();
      paintLookup();
      paintRecalls();

      document.getElementById('vvm-picker').dataset.stage = '1';
      VVM.forEach(function (v) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = String(v.stage);
        b.setAttribute('aria-pressed', 'false');
        b.setAttribute('aria-label', 'VVM stage ' + v.stage);
        b.addEventListener('click', function () {
          document.getElementById('vvm-picker').dataset.stage = String(v.stage);
          paintVvm();
          paint();
        });
        b.addEventListener('keydown', function (e) {
          if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
          var next = (Number(document.getElementById('vvm-picker').dataset.stage) + (e.key === 'ArrowRight' ? 1 : 4)) % 5;
          document.getElementById('vvm-picker').dataset.stage = String(next);
          paintVvm();
          paint();
          kit.$$('#vvm-picker button')[next].focus();
        });
        document.getElementById('vvm-picker').appendChild(b);
      });
      paintVvm();

      if (document.getElementById('o-when').value === '') document.getElementById('o-when').value = new Date().toISOString().slice(0, 10);
      if (document.getElementById('exp-input').value === '') document.getElementById('exp-input').value = 'EXP 04/27';
      document.getElementById('unsure-credit').value = unsureCredit;

      // default state: the documented worked example, so the tool is live on arrival
      Object.keys(SAMPLE).forEach(function (k) { setAnswer(k, SAMPLE[k]); });

      kit.$$('#check-form input').forEach(function (el) {
        el.addEventListener('change', function () { paint(); });
        el.addEventListener('input', paint);
      });
      kit.on(document.getElementById('lookup-body'), 'click', '[data-use]', function (e, el) {
        var p = REGISTRY[Number(el.dataset.use)];
        if (!p) return;
        document.getElementById('p-name').value = p.name;
        document.getElementById('reg-input').value = '';
        paint();
        kit.toast('Loaded ' + p.name + ' into the inspection form');
        document.getElementById('inspect').scrollIntoView({ behavior: kit.reduce ? 'auto' : 'smooth', block: 'start' });
      });

      ['reg-input', 'reg-type'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', paintRegister);
        document.getElementById(id).addEventListener('change', paintRegister);
      });
      ['bc-input', 'bc-made'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', paintBarcode);
        document.getElementById(id).addEventListener('change', paintBarcode);
      });
      document.getElementById('bc-input').addEventListener('input', function (e) {
        var el = e.target;
        var clean = el.value.replace(/[^0-9 ]/g, '');
        if (clean !== el.value) el.value = clean;
      });
      ['exp-input', 'exp-class', 'exp-open'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', paintExpiry);
        document.getElementById(id).addEventListener('change', paintExpiry);
      });
      ['cc-class', 'cc-temp', 'cc-hours', 'cc-prior', 'cc-shaken', 'cc-storage'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', paintCold);
        document.getElementById(id).addEventListener('change', paintCold);
      });
      ['lookup-q', 'lookup-class'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', paintLookup);
        document.getElementById(id).addEventListener('change', paintLookup);
      });
      ['recall-cat', 'recall-batch'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', paintRecalls);
        document.getElementById(id).addEventListener('change', paintRecalls);
      });

      kit.on(document.getElementById('weight-body'), 'input', 'input', function (e, el) {
        var id = el.closest('tr').dataset.w;
        weights[id] = kit.clamp(Math.round(kit.num(el.value)), 0, 40);
        wStore.write(weights);
        refreshCheckWeights();
        paint();
      });
      document.getElementById('reset-weights').addEventListener('click', function () {
        weights = CHECKS.reduce(function (acc, c) { acc[c.id] = c.w; return acc; }, {});
        wStore.write(weights);
        document.getElementById('unsure-credit').value = 0.4;
        unsureCredit = 0.4; uStore.write(0.4);
        paintWeights(); refreshCheckWeights(); paint();
        kit.toast('Default weights restored');
      });
      document.getElementById('unsure-credit').addEventListener('input', function (e) {
        unsureCredit = kit.clamp(Number(e.target.value), 0, 1);
        uStore.write(unsureCredit);
        paint();
      });
      kit.on(document.getElementById('budget-body'), 'input', 'input', function (e, el) {
        var row = el.closest('tr');
        var i = Number(row.dataset.b);
        budgets[i][el.dataset.f] = kit.num(el.value);
        bStore.write(budgets);
        paintCold();
      });
      document.getElementById('reset-budgets').addEventListener('click', function () {
        budgets = BUDGETS.map(function (b) { return { key: b.key, lo: b.lo, hi: b.hi, warn: b.warn, discard: b.discard }; });
        bStore.write(budgets);
        paintBudgets(); paintCold();
        kit.toast('Default budgets restored');
      });

      document.getElementById('load-sample').addEventListener('click', function () {
        Object.keys(SAMPLE).forEach(function (k) { setAnswer(k, SAMPLE[k]); });
        document.getElementById('p-name').value = 'Amoxicillin 500 mg capsules (re-packed carton scenario)';
        document.getElementById('p-number').value = 'F-21348';
        document.getElementById('p-batch').value = 'B2411-07';
        document.getElementById('p-expiry').value = 'EXP 04/27';
        document.getElementById('vvm-fail').checked = false;
        document.getElementById('exp-fail').checked = false;
        paint(); paintExpiry(); paintRegister();
        kit.toast('Worked example loaded: 2 defects, 4 unsure, glue-seam override');
      });
      document.getElementById('clear-answers').addEventListener('click', function () {
        kit.$$('#check-form input[type="radio"]').forEach(function (el) { el.checked = false; });
        document.getElementById('vvm-fail').checked = false;
        document.getElementById('exp-fail').checked = false;
        lastScore = null;
        paint();
        kit.toast('Answers cleared');
      });

      document.getElementById('build-report').addEventListener('click', buildReport);
      document.getElementById('copy-report').addEventListener('click', copyReport);
      document.getElementById('save-inspection').addEventListener('click', saveInspection);
      document.getElementById('print-report').addEventListener('click', function () {
        if (!document.getElementById('report-text').value) buildReport();
        kit.print();
      });
      document.getElementById('export-history').addEventListener('click', exportHistory);
      document.getElementById('print-history').addEventListener('click', function () {
        if (!history().length) { kit.toast('Nothing saved yet'); return; }
        kit.print();
      });
      document.getElementById('clear-history').addEventListener('click', function () {
        if (!history().length) { kit.toast('The history is already empty'); return; }
        hStore.clear();
        paintHistory();
        kit.toast('Inspection history cleared');
      });
      kit.on(document.getElementById('history-body'), 'click', '[data-del]', function (e, el) {
        var list = history().filter(function (row) { return row.id !== el.dataset.del; });
        hStore.write(list);
        paintHistory();
      });

      paintCold();
      paintRegister();
      paintBarcode();
      paintExpiry();
      paint();
      paintHistory();
    }
  });
})();
