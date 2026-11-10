/* ============================================================
   RENTJURY — data.js
   Property price bands, state/area coverage, fee standards,
   red-flag list, seed reports.
   All figures are illustrative reference bands, not live data.
============================================================ */

const PROPERTY_TYPES = [
  "Boys' Quarters (BQ)",
  "Self-Contain",
  "Studio Apartment",
  "Mini Flat",
  "1 Bedroom Flat",
  "2 Bedroom Flat",
  "3 Bedroom Flat",
  "4 Bedroom Flat",
  "Terrace Duplex",
  "Detached Duplex"
];

// Base annual rent band (₦) for a baseline-tier area, per property type
const BASE_BAND = {
  "Boys' Quarters (BQ)": { min: 150000,  avg: 220000,  max: 300000 },
  "Self-Contain":        { min: 250000,  avg: 350000,  max: 450000 },
  "Studio Apartment":    { min: 280000,  avg: 380000,  max: 500000 },
  "Mini Flat":           { min: 350000,  avg: 500000,  max: 650000 },
  "1 Bedroom Flat":      { min: 500000,  avg: 700000,  max: 900000 },
  "2 Bedroom Flat":      { min: 800000,  avg: 1100000, max: 1400000 },
  "3 Bedroom Flat":      { min: 1200000, avg: 1600000, max: 2000000 },
  "4 Bedroom Flat":      { min: 1600000, avg: 2100000, max: 2600000 },
  "Terrace Duplex":      { min: 2200000, avg: 3000000, max: 3800000 },
  "Detached Duplex":     { min: 3000000, avg: 4200000, max: 5500000 }
};

/* ------------------------------------------------------------
   COVERAGE: every state gets at least one reference area (its
   capital or main commercial hub). Lagos gets neighborhood-level
   detail since it's the deepest-covered market. FCT is included
   alongside the 36 states since it's a major rental market too.
   Multipliers are relative to a baseline tier of 1.00 and are
   illustrative estimates, not live market data.
------------------------------------------------------------- */
const STATE_GROUPS = [
  { state: "FCT (Abuja)", areas: { "Abuja (Central)": 2.20 } },
  { state: "Abia",        areas: { "Umuahia": 0.60 } },
  { state: "Adamawa",     areas: { "Yola": 0.50 } },
  { state: "Akwa Ibom",   areas: { "Uyo": 1.05 } },
  { state: "Anambra",     areas: { "Awka": 0.90 } },
  { state: "Bauchi",      areas: { "Bauchi": 0.50 } },
  { state: "Bayelsa",     areas: { "Yenagoa": 0.95 } },
  { state: "Benue",       areas: { "Makurdi": 0.55 } },
  { state: "Borno",       areas: { "Maiduguri": 0.45 } },
  { state: "Cross River", areas: { "Calabar": 0.85 } },
  { state: "Delta",       areas: { "Asaba": 1.15 } },
  { state: "Ebonyi",      areas: { "Abakaliki": 0.50 } },
  { state: "Edo",         areas: { "Benin City": 0.80 } },
  { state: "Ekiti",       areas: { "Ado-Ekiti": 0.55 } },
  { state: "Enugu",       areas: { "Enugu": 0.90 } },
  { state: "Gombe",       areas: { "Gombe": 0.50 } },
  { state: "Imo",         areas: { "Owerri": 0.80 } },
  { state: "Jigawa",      areas: { "Dutse": 0.40 } },
  { state: "Kaduna",      areas: { "Kaduna": 0.70 } },
  { state: "Kano",        areas: { "Kano": 0.80 } },
  { state: "Katsina",     areas: { "Katsina": 0.50 } },
  { state: "Kebbi",       areas: { "Birnin Kebbi": 0.45 } },
  { state: "Kogi",        areas: { "Lokoja": 0.55 } },
  { state: "Kwara",       areas: { "Ilorin": 0.65 } },
  { state: "Lagos", areas: {
      "Egbeda":            1.00,
      "Festac Town":       1.10,
      "Ogba":              1.05,
      "Gbagada":           1.30,
      "Surulere":          1.35,
      "Yaba":              1.40,
      "Ajah":              1.20,
      "Ikeja GRA":         1.70,
      "Magodo":            1.80,
      "Lekki Phase 1":     2.30,
      "Victoria Island":   3.50,
      "Ikoyi":             4.20
  }},
  { state: "Nasarawa",    areas: { "Lafia": 0.50 } },
  { state: "Niger",       areas: { "Minna": 0.55 } },
  { state: "Ogun",        areas: { "Abeokuta": 1.05 } },
  { state: "Ondo",        areas: { "Akure": 0.65 } },
  { state: "Osun",        areas: { "Osogbo": 0.60 } },
  { state: "Oyo",         areas: { "Ibadan": 0.80 } },
  { state: "Plateau",     areas: { "Jos": 0.65 } },
  { state: "Rivers",      areas: { "Port Harcourt": 1.60 } },
  { state: "Sokoto",      areas: { "Sokoto": 0.50 } },
  { state: "Taraba",      areas: { "Jalingo": 0.45 } },
  { state: "Yobe",        areas: { "Damaturu": 0.40 } },
  { state: "Zamfara",     areas: { "Gusau": 0.45 } }
];

function round10k(n) {
  return Math.round(n / 10000) * 10000;
}

// Flatten STATE_GROUPS into a simple area -> multiplier map
const AREA_MULTIPLIER = {};
// Flatten STATE_GROUPS into area -> state, for lookups/labels
const AREA_STATE = {};
STATE_GROUPS.forEach(group => {
  Object.keys(group.areas).forEach(area => {
    AREA_MULTIPLIER[area] = group.areas[area];
    AREA_STATE[area] = group.state;
  });
});

const AREA_DATA = {};
Object.keys(AREA_MULTIPLIER).forEach(area => {
  AREA_DATA[area] = {};
  const mult = AREA_MULTIPLIER[area];
  PROPERTY_TYPES.forEach(type => {
    const base = BASE_BAND[type];
    AREA_DATA[area][type] = {
      min: round10k(base.min * mult),
      avg: round10k(base.avg * mult),
      max: round10k(base.max * mult)
    };
  });
});

const AREAS = Object.keys(AREA_DATA);
const STATE_COUNT = STATE_GROUPS.length; // includes FCT alongside the 36 states

// Standard, widely-accepted fee ceilings in the Nigerian rental market
const FEE_STANDARDS = {
  agencyFeeMaxPct: 10,   // agency fee, % of annual rent
  legalFeeMaxPct: 10,    // legal / agreement fee, % of annual rent
  cautionFeeWatchPct: 50, // caution deposit above this % of rent = watch
  cautionFeeHighPct: 100  // above this % = flag as high
};

const RED_FLAGS = [
  "Agent refuses to let you inspect the property before paying",
  "Asked to pay into a personal account instead of an official/company account",
  "No signed tenancy agreement offered before or at payment",
  "Agency fee quoted above 10% of the annual rent",
  "Pressure to pay immediately or 'lose the apartment today'",
  "Landlord or agent's identity can't be verified anywhere",
  "Known issues with the property (bills, disputes) not disclosed upfront",
  "No receipt issued after payment is made"
];

const TESTIMONIALS = [
  {
    quote: "I typed in the price the agent gave me and the stamp came back OVERPRICED, 40% over the Yaba average. Walked away and found something fair two streets down.",
    name: "Tolu",
    context: "First-time renter, Yaba, Lagos"
  },
  {
    quote: "Relocated for work and had zero reference for what things should cost outside my home state. This gave me a number to hold agents to.",
    name: "Chidinma",
    context: "Relocated to Abuja"
  },
  {
    quote: "The fee breakdown checker caught it, my 'legal fee' was almost double the standard. I renegotiated before signing anything.",
    name: "Emeka",
    context: "Shop owner, Port Harcourt"
  }
];

// Seed community reports (used only if the database is empty)
const SEED_REPORTS = [
  { id: "s1", area: "Yaba", type: "1 Bedroom Flat", amount: 950000, agencyFee: 8, legalFee: 8, cautionFee: 40, note: "Off Herbert Macaulay, newly renovated.", date: "2026-06-02" },
  { id: "s2", area: "Lekki Phase 1", type: "2 Bedroom Flat", amount: 3800000, agencyFee: 15, legalFee: 10, cautionFee: 100, note: "Agent insisted on same-day payment.", date: "2026-06-10" },
  { id: "s3", area: "Egbeda", type: "Self-Contain", amount: 320000, agencyFee: 10, legalFee: 10, cautionFee: 20, note: "Fair deal, receipt given promptly.", date: "2026-06-14" },
  { id: "s4", area: "Ikeja GRA", type: "3 Bedroom Flat", amount: 3900000, agencyFee: 10, legalFee: 10, cautionFee: 50, note: "Landlord verified with C of O copy.", date: "2026-06-20" },
  { id: "s5", area: "Surulere", type: "Mini Flat", amount: 720000, agencyFee: 20, legalFee: 15, cautionFee: 100, note: "Fees stacked way above standard, avoid this agent.", date: "2026-06-27" },
  { id: "s6", area: "Ajah", type: "2 Bedroom Flat", amount: 1250000, agencyFee: 10, legalFee: 10, cautionFee: 30, note: "Reasonable, close to the express.", date: "2026-07-03" },
  { id: "s7", area: "Victoria Island", type: "1 Bedroom Flat", amount: 3200000, agencyFee: 12, legalFee: 10, cautionFee: 100, note: "Serviced, fee slightly above standard but disclosed upfront.", date: "2026-07-09" },
  { id: "s8", area: "Ogba", type: "1 Bedroom Flat", amount: 730000, agencyFee: 10, legalFee: 10, cautionFee: 25, note: "No issues, straightforward process.", date: "2026-07-14" },
  { id: "s9", area: "Abuja (Central)", type: "2 Bedroom Flat", amount: 3300000, agencyFee: 10, legalFee: 10, cautionFee: 50, note: "Wuse district, negotiated down from initial ask.", date: "2026-07-18" },
  { id: "s10", area: "Port Harcourt", type: "3 Bedroom Flat", amount: 2600000, agencyFee: 15, legalFee: 10, cautionFee: 100, note: "GRA area, fees stacked above standard.", date: "2026-07-20" },
  { id: "s11", area: "Ibadan", type: "Self-Contain", amount: 220000, agencyFee: 10, legalFee: 10, cautionFee: 20, note: "Bodija area, straightforward process.", date: "2026-07-21" },
  { id: "s12", area: "Enugu", type: "1 Bedroom Flat", amount: 620000, agencyFee: 10, legalFee: 10, cautionFee: 30, note: "Independence Layout, fair and documented.", date: "2026-07-22" }
];
