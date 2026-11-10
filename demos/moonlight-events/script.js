/* ============================================================
   MOONLIGHT — festival guide
   Renders data.json (with an inline fallback so file:// works),
   filtering, search, detail view, saved list and countdown.
   ============================================================ */

(function () {
  "use strict";

  /* Inline copy of data.json — used when fetch is blocked (file://). */
  var FESTIVAL_FALLBACK = [
    {
      "name": "Pongal",
      "country": "India",
      "religion": "Hindu",
      "month": "January",
      "description": "A four-day Tamil harvest festival honoring the sun, fresh crops, and gratitude for abundance.",
      "history": "Pongal is one of South India's oldest harvest celebrations. Families cook newly harvested rice with milk, decorate homes with kolam patterns, and thank nature, cattle, and the sun for a successful farming season."
    },
    {
      "name": "Sinulog",
      "country": "Philippines",
      "religion": "Christian",
      "month": "January",
      "description": "A major Cebu festival filled with street dancing, drumbeats, and colorful devotion to the Santo Nino.",
      "history": "Sinulog honors the Child Jesus and blends Catholic devotion with local cultural traditions. Processions, dance competitions, and rhythmic parades make it one of the Philippines' most recognized January festivals."
    },
    {
      "name": "Carnival",
      "country": "Brazil",
      "religion": "Cultural",
      "month": "February",
      "description": "World-famous celebrations with samba dancing, costumes, music trucks, and massive street parades.",
      "history": "Brazilian Carnival grew from Portuguese pre-Lenten customs and transformed through African, Indigenous, and local Brazilian influence. Rio's samba school parades became an international symbol of the festival.",
      "image": "images/carnival.svg"
    },
    {
      "name": "Sapporo Snow Festival",
      "country": "Japan",
      "religion": "Cultural",
      "month": "February",
      "description": "A winter festival known for giant snow sculptures, ice art, evening lights, and snowy city displays.",
      "history": "The Sapporo Snow Festival began when local students built snow statues in Odori Park. It grew into one of Japan's best-known winter festivals, drawing visitors for large-scale snow and ice creations."
    },
    {
      "name": "Holi",
      "country": "India",
      "religion": "Hindu",
      "month": "March",
      "description": "The festival of colors celebrating spring, joy, and the triumph of good over evil.",
      "history": "Holi is linked to the stories of Krishna, Radha, and the legend of Prahlad and Holika. Communities celebrate by throwing colored powders, singing, dancing, and sharing festive food.",
      "image": "images/holi.svg"
    },
    {
      "name": "Las Fallas",
      "country": "Spain",
      "religion": "Cultural",
      "month": "March",
      "description": "Valencia's fiery March festival featuring towering satirical sculptures, fireworks, music, and street celebrations.",
      "history": "Las Fallas evolved from old carpenters' spring bonfires and became a major city tradition. Neighborhood groups build giant artistic figures called fallas, then burn many of them during the climactic night of La Crema."
    },
    {
      "name": "Sakura Festival",
      "country": "Japan",
      "religion": "Cultural",
      "month": "April",
      "description": "A spring blossom celebration centered on hanami picnics, soft pink cherry trees, and seasonal gatherings.",
      "history": "Hanami has been practiced in Japan for more than a thousand years. What began among the imperial court spread widely and remains a cherished way to welcome spring under blooming cherry trees.",
      "image": "images/sakura.svg"
    },
    {
      "name": "Eid al-Fitr",
      "country": "Global",
      "religion": "Islamic",
      "month": "April",
      "description": "A joyful celebration marking the end of Ramadan with prayers, charity, family visits, and shared meals.",
      "history": "Eid al-Fitr was established in the early Islamic community and is observed worldwide. Families gather for prayer, festive clothing, generous giving, and meals that mark the close of the fasting month.",
      "image": "images/eid-al-fitr.svg"
    },
    {
      "name": "Easter",
      "country": "Global",
      "religion": "Christian",
      "month": "April",
      "description": "A major Christian festival celebrating the resurrection of Jesus with worship services, music, shared meals, and joyful spring gatherings.",
      "history": "Easter is the central feast of the Christian calendar, observed across many cultures with church services, processions, and family traditions. In 2026, Easter Sunday falls on April 5.",
      "image": "images/easter.svg"
    },
    {
      "name": "Aoi Matsuri",
      "country": "Japan",
      "religion": "Cultural",
      "month": "May",
      "description": "A historic Kyoto procession of court costumes, ox-drawn carts, and hollyhock decorations.",
      "history": "Aoi Matsuri is one of Kyoto's oldest festivals, with roots in imperial rituals held to pray for peace and a good harvest. Its elegant parade recreates the atmosphere of Japan's classical court era."
    },
    {
      "name": "Cinco de Mayo",
      "country": "Mexico",
      "religion": "Cultural",
      "month": "May",
      "description": "A commemorative festival marking the Mexican victory at Puebla with music, food, and civic pride.",
      "history": "Cinco de Mayo remembers the Battle of Puebla in 1862, when Mexican forces defeated a larger French army. It remains especially associated with Puebla and with cultural celebrations of Mexican identity."
    },
    {
      "name": "Festa Junina",
      "country": "Brazil",
      "religion": "Cultural",
      "month": "June",
      "description": "A cheerful June festival of bonfires, folk dancing, plaid costumes, and traditional corn dishes.",
      "history": "Festa Junina developed from European midsummer traditions and became deeply tied to Brazilian rural life. Communities celebrate with quadrilha dances, paper flags, bonfires, and harvest-themed foods.",
      "image": "images/festa-junina.svg"
    },
    {
      "name": "Midsummer",
      "country": "Sweden",
      "religion": "Cultural",
      "month": "June",
      "description": "A bright summer celebration with flower crowns, dancing around a maypole, and long evenings outdoors.",
      "history": "Swedish Midsummer grew from ancient seasonal customs celebrating light, fertility, and the height of summer. It remains one of Sweden's most beloved traditions, centered on gatherings in nature."
    },
    {
      "name": "Gion Matsuri",
      "country": "Japan",
      "religion": "Cultural",
      "month": "July",
      "description": "Kyoto's famous summer festival known for towering floats, lantern-lit streets, and grand processions.",
      "history": "Gion Matsuri began in 869 as a ritual response to epidemic disease. Over centuries it expanded into one of Japan's largest traditional festivals, centered around the Yasaka Shrine and elaborate float parades.",
      "image": "images/gion-matsuri.svg"
    },
    {
      "name": "Durbar Festival",
      "country": "Nigeria",
      "religion": "Cultural",
      "month": "July",
      "description": "A regal northern Nigerian festival of horse parades, drumming, robes, and ceremonial pageantry.",
      "history": "Durbar developed from Hausa royal cavalry displays and remains a striking showcase of horsemanship, leadership, and regional heritage. Riders in vivid attire perform before crowds and traditional rulers.",
      "image": "images/durbar.svg"
    },
    {
      "name": "Guelaguetza",
      "country": "Mexico",
      "religion": "Cultural",
      "month": "July",
      "description": "Oaxaca's major cultural festival featuring Indigenous dance, music, regional dress, and community sharing.",
      "history": "Guelaguetza comes from Zapotec traditions of reciprocity and offering. It grew into a major gathering where communities across Oaxaca present dances, songs, and gifts that celebrate local identity.",
      "image": "images/guelaguetza.svg"
    },
    {
      "name": "Obon",
      "country": "Japan",
      "religion": "Cultural",
      "month": "August",
      "description": "A summer festival of lanterns, ancestral remembrance, bon odori dancing, and homecoming gatherings.",
      "history": "Obon is rooted in Buddhist remembrance traditions and is widely observed across Japan. Families return home, visit graves, light lanterns, and join community dances honoring ancestors."
    },
    {
      "name": "New Yam Festival",
      "country": "Nigeria",
      "religion": "Cultural",
      "month": "August",
      "description": "A harvest thanksgiving celebrating the arrival of new yams with feasting, dance, and masquerades.",
      "history": "The New Yam Festival is celebrated across many communities in southern Nigeria, especially among the Igbo. It marks gratitude for the harvest and the ceremonial beginning of eating newly gathered yams.",
      "image": "images/new-yam.svg"
    },
    {
      "name": "Oktoberfest",
      "country": "Germany",
      "religion": "Cultural",
      "month": "September",
      "description": "Munich's world-famous folk festival with tents, brass bands, rides, and Bavarian food traditions.",
      "history": "Oktoberfest began in 1810 with celebrations for the marriage of Crown Prince Ludwig and Princess Therese. It grew into a globally recognized festival that traditionally opens in September."
    },
    {
      "name": "La Merce",
      "country": "Spain",
      "religion": "Cultural",
      "month": "September",
      "description": "Barcelona's city festival filled with giants, human towers, music, fireworks, and street performance.",
      "history": "La Merce honors Barcelona's patron saint and has become one of the city's signature festivals. It combines civic celebration with traditional Catalan culture, modern arts, and spectacular closing fireworks."
    },
    {
      "name": "Navratri",
      "country": "India",
      "religion": "Hindu",
      "month": "October",
      "description": "A nine-night festival of devotion, dance, music, and celebration of the divine feminine.",
      "history": "Navratri is observed in different forms across India and centers on worship of the goddess in her many forms. Garba and dandiya dance events make it especially vibrant in western India."
    },
    {
      "name": "MassKara Festival",
      "country": "Philippines",
      "religion": "Cultural",
      "month": "October",
      "description": "A smiling street festival with masks, dance performances, bright costumes, and energetic parades.",
      "history": "MassKara emerged in Bacolod during a difficult economic period as a celebration of resilience and community spirit. Its iconic smiling masks and lively processions remain central to the festival."
    },
    {
      "name": "Diwali",
      "country": "India",
      "religion": "Hindu",
      "month": "November",
      "description": "A festival of lights celebrating hope, renewal, and the triumph of light over darkness.",
      "history": "Diwali has been celebrated for centuries and is associated with stories such as Rama's return to Ayodhya. Homes glow with lamps, families exchange sweets, and many worship Lakshmi for prosperity.",
      "image": "images/diwali.svg"
    },
    {
      "name": "Day of the Dead",
      "country": "Mexico",
      "religion": "Cultural",
      "month": "November",
      "description": "A remembrance festival with marigolds, candles, altars, parades, and family gatherings.",
      "history": "Day of the Dead blends Indigenous remembrance traditions with Catholic observances. Families build ofrendas, visit cemeteries, and celebrate enduring ties with loved ones who have died.",
      "image": "images/day-of-the-dead.svg"
    },
    {
      "name": "Christmas",
      "country": "Global",
      "religion": "Christian",
      "month": "December",
      "description": "A December celebration of the birth of Jesus Christ with gifts, music, lights, and family gatherings.",
      "history": "Christmas has been observed since the early centuries of Christianity and developed many local traditions over time. Seasonal lights, decorated trees, nativity scenes, and shared meals became central customs in many places.",
      "image": "images/christmas.svg"
    },
    {
      "name": "St. Lucia's Day",
      "country": "Sweden",
      "religion": "Christian",
      "month": "December",
      "description": "A candlelit winter festival of songs, white robes, saffron buns, and light in the darkest season.",
      "history": "St. Lucia's Day became a cherished Swedish winter tradition combining saintly remembrance with seasonal customs of light, music, and hospitality. Processions often feature crowns of candles and choral singing."
    },
    {
      "name": "Christmas Markets",
      "country": "Germany",
      "religion": "Cultural",
      "month": "December",
      "description": "Festive town squares filled with lights, wooden stalls, ornaments, warm drinks, and holiday music.",
      "history": "German Christmas markets date back to the late Middle Ages and became a beloved Advent tradition. They bring together crafts, food, lights, and winter atmosphere in city centers across the country."
    }
  ];

  var COUNTRY_REGION = {
    India: "Asia",
    Philippines: "Asia",
    Brazil: "Americas",
    Japan: "Asia",
    Spain: "Europe",
    Mexico: "Americas",
    Sweden: "Europe",
    Nigeria: "Africa",
    Germany: "Europe",
    Global: "Global"
  };

  /* Editorial notes that sit alongside the data: dates, host city, food, tips. */
  var GUIDE = {
    "Pongal": { date: "2026-01-14", dateLabel: "14–17 January 2026", city: "Tamil Nadu, India", food: "Venpongal (rice and moong dal with ghee), sakkarai pongal and sticks of sugarcane.", tips: "Kolam patterns appear on doorsteps before sunrise — walk the side streets of Madurai or Chennai early, and book village homestays weeks ahead." },
    "Sinulog": { date: "2026-01-18", dateLabel: "18 January 2026", city: "Cebu City, Philippines", food: "Lechon, bibingka and ripe mangoes from the stalls around Fuente Osmeña.", tips: "The Grand Parade runs all day: book a hotel on the route months ahead, bring earplugs for the drums and water for the heat." },
    "Carnival": { date: "2026-02-17", dateLabel: "13–17 February 2026", city: "Rio de Janeiro, Brazil", food: "Feijoada, grilled churrasco and caipirinhas between blocos.", tips: "Sambadrome tickets go on sale months in advance; for the free street parties, pick one bloco and arrive early rather than chasing five." },
    "Sapporo Snow Festival": { date: "2026-02-04", dateLabel: "4–11 February 2026", city: "Sapporo, Hokkaido, Japan", food: "Miso ramen, seafood donburi and hot sake against the cold.", tips: "Odori Park is best after dark when the sculptures are lit; rent proper snow boots — the pavements turn to ice." },
    "Holi": { date: "2026-03-04", dateLabel: "4 March 2026", city: "Mathura and Vrindavan, India", food: "Gujiya, thandai and fresh pakoras shared between colour runs.", tips: "Wear clothes you can lose, oil your hair and seal your phone in a bag; the Braj region celebrations run for days around the main day." },
    "Las Fallas": { date: "2026-03-15", dateLabel: "15–19 March 2026", city: "Valencia, Spain", food: "Bunyols, paella cooked over wood and horchata.", tips: "The mascletà fires at 2pm daily in Plaça de l'Ajuntament — earplugs matter. Stay for La Crema on the final night." },
    "Sakura Festival": { date: "2026-04-01", dateLabel: "Late March – early April 2026", city: "Tokyo and Kyoto, Japan", food: "Hanami dango, sakura-mochi and convenience-store bento under the trees.", tips: "Follow the bloom forecast rather than fixed dates; claim a picnic spot before 9am and never shake the branches." },
    "Eid al-Fitr": { date: "2026-03-20", dateLabel: "Around 20 March 2026", city: "Worldwide", food: "Biryani, sheer khurma and trays of ma'amoul and dates.", tips: "The date follows the moon and is confirmed the night before; greet people with “Eid Mubarak” and expect many shops to close." },
    "Easter": { date: "2026-04-05", dateLabel: "5 April 2026", city: "Worldwide", food: "Roast lamb, hot cross buns and simnel cake.", tips: "Check local mass times — the Easter Vigil on Saturday night is often the most atmospheric service; book lunch tables well ahead." },
    "Aoi Matsuri": { date: "2026-05-15", dateLabel: "15 May 2026", city: "Kyoto, Japan", food: "Yatsuhashi, matcha and kaiseki lunches near Shimogamo Shrine.", tips: "The procession leaves the Imperial Palace around 10.30am; stake out a curb spot on Karasuma Street early." },
    "Cinco de Mayo": { date: "2026-05-05", dateLabel: "5 May 2026", city: "Puebla, Mexico", food: "Mole poblano, chalupas and cemitas.", tips: "Puebla's parade and re-enactment are the heart of it; arrive the night before and expect the zócalo to be packed." },
    "Festa Junina": { date: "2026-06-24", dateLabel: "24 June 2026", city: "Caruaru and Campina Grande, Brazil", food: "Pamonha, canjica, grilled corn and quentão.", tips: "Quadrilha dancing starts after dark; bring cash for the stalls and expect the biggest crowds at weekends." },
    "Midsummer": { date: "2026-06-20", dateLabel: "19–20 June 2026", city: "Dalarna and Stockholm, Sweden", food: "Pickled herring, new potatoes, strawberries and snaps.", tips: "The maypole is raised on Midsummer Eve; countryside celebrations are family-led, so book rural stays early and bring a flower crown." },
    "Gion Matsuri": { date: "2026-07-17", dateLabel: "17 July 2026", city: "Kyoto, Japan", food: "Yakitori, takoyaki and kakigori from the yatai stalls.", tips: "The Yamaboko Junko float parade starts at 9am; the nights before, the streets close for the Byobu Matsuri — go then to see the floats up close." },
    "Durbar Festival": { date: "2026-07-25", dateLabel: "Late July 2026 (set by the Emirate)", city: "Kano, Nigeria", food: "Kilishi, suya and masa by the palace gates.", tips: "Riders gather before the Emir's palace; ask before photographing people and dress modestly for the procession grounds." },
    "Guelaguetza": { date: "2026-07-20", dateLabel: "20–27 July 2026", city: "Oaxaca City, Mexico", food: "Tlayudas, mole and tejate.", tips: "The Monday performances at Cerro del Fortín sell out — buy from the state ticket office, not resellers — and arrive early for the calenda." },
    "Obon": { date: "2026-08-13", dateLabel: "13–16 August 2026", city: "Kyoto and nationwide, Japan", food: "Ohagi, shaved ice and festival-stall yakisoba.", tips: "Bon odori dances are free and open to visitors; lantern ceremonies differ by region, so check your city's schedule." },
    "New Yam Festival": { date: "2026-08-22", dateLabel: "August 2026 (dates vary by community)", city: "Southeast Nigeria", food: "Roasted new yam with palm oil, yam porridge and fresh fish.", tips: "Each community sets its own date; go as someone's guest, expect masquerades, and never harvest before the ceremony." },
    "Oktoberfest": { date: "2026-09-19", dateLabel: "19 September – 4 October 2026", city: "Munich, Germany", food: "Pretzels, roast chicken, weisswurst and beer by the litre.", tips: "Reserve tent tables online months ahead; weekday mornings are calmer, and cash still helps for tips." },
    "La Merce": { date: "2026-09-24", dateLabel: "Late September 2026", city: "Barcelona, Spain", food: "Pa amb tomàquet, cava and seasonal calçots.", tips: "Castells (human towers) go up in Plaça Sant Jaume; the closing fireworks and correfoc are loud — keep children back from the sparks." },
    "Navratri": { date: "2026-10-11", dateLabel: "11–19 October 2026", city: "Ahmedabad and Gujarat, India", food: "Fafda-jalebi, sundal and khandvi.", tips: "Dress in the colour of the day, learn the basic garba two-step, and note that most venues charge entry for non-residents." },
    "MassKara Festival": { date: "2026-10-17", dateLabel: "October 2026 (third week)", city: "Bacolod, Philippines", food: "Chicken inasal, batchoy and piaya.", tips: "The street-dance competition is the centrepiece; smiling masks are sold everywhere, so buy yours on arrival rather than online." },
    "Diwali": { date: "2026-11-08", dateLabel: "8 November 2026", city: "Jaipur and Varanasi, India", food: "Mithai, kaju katli and trays of samosas shared with neighbours.", tips: "Lamps are lit after sunset; the ghats are spectacular but crowded, so pick a rooftop for the fireworks and book trains early." },
    "Day of the Dead": { date: "2026-11-02", dateLabel: "1–2 November 2026", city: "Oaxaca and Mexico City, Mexico", food: "Pan de muerto, mole and warm atole.", tips: "Cemeteries are active late into the night — go respectfully and quietly; public ofrendas in the plazas are free to visit." },
    "Christmas": { date: "2026-12-25", dateLabel: "25 December 2026", city: "Worldwide", food: "Roast dinners, stollen and panettone.", tips: "Book travel and restaurants months ahead; many museums close on the day, but church services are usually open to all." },
    "St. Lucia's Day": { date: "2026-12-13", dateLabel: "13 December 2026", city: "Stockholm and nationwide, Sweden", food: "Saffron buns (lussekatter), gingerbread and warm glögg.", tips: "Lucia processions in churches often start mid-afternoon — arrive 30 minutes early for a seat, and bring a warm coat for the wait." },
    "Christmas Markets": { date: "2026-11-27", dateLabel: "Late November – 24 December 2026", city: "Nuremberg and Dresden, Germany", food: "Bratwurst, lebkuchen and glühwein in collectable mugs.", tips: "Bring cash, expect a small deposit on mugs, and visit on a weekday evening to dodge the weekend crush." }
  };

  var MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  var MONTH_SHORT = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  var STORE_KEY = "moonlight.saved.v1";
  var REDUCED_MOTION = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var state = { search: "", region: "all", month: "all", tradition: "all" };
  var festivals = [];
  var saved = readSaved();
  var cardRecords = [];
  var featured = null;
  var featuredTarget = null;
  var lastTrigger = null;
  var storageWarned = false;

  var els = {
    grid: document.getElementById("festivalGrid"),
    empty: document.getElementById("emptyState"),
    results: document.getElementById("results"),
    search: document.getElementById("search"),
    region: document.getElementById("regionFilter"),
    month: document.getElementById("monthFilter"),
    clear: document.getElementById("clearFilters"),
    emptyReset: document.getElementById("emptyReset"),
    chips: Array.prototype.slice.call(document.querySelectorAll("[data-tradition]")),
    featureImage: document.getElementById("featureImage"),
    featureTitle: document.getElementById("featureTitle"),
    featureDate: document.getElementById("featureDate"),
    featureDesc: document.getElementById("featureDesc"),
    cdDays: document.getElementById("cdDays"),
    cdHours: document.getElementById("cdHours"),
    cdMinutes: document.getElementById("cdMinutes"),
    cdSeconds: document.getElementById("cdSeconds"),
    countdownLabel: document.getElementById("countdownLabel"),
    featureOpen: document.getElementById("featureOpen"),
    featureSave: document.getElementById("featureSave"),
    savedBtn: document.getElementById("savedBtn"),
    savedCount: document.getElementById("savedCount"),
    savedDialog: document.getElementById("savedDialog"),
    savedList: document.getElementById("savedList"),
    savedEmpty: document.getElementById("savedEmpty"),
    savedClose: document.getElementById("savedClose"),
    detailDialog: document.getElementById("detailDialog"),
    detailClose: document.getElementById("detailClose"),
    detailImage: document.getElementById("detailImage"),
    detailKicker: document.getElementById("detailKicker"),
    detailTitle: document.getElementById("detailTitle"),
    detailDescription: document.getElementById("detailDescription"),
    detailDate: document.getElementById("detailDate"),
    detailLocation: document.getElementById("detailLocation"),
    detailTradition: document.getElementById("detailTradition"),
    detailRegion: document.getElementById("detailRegion"),
    detailHistory: document.getElementById("detailHistory"),
    detailFood: document.getElementById("detailFood"),
    detailTips: document.getElementById("detailTips"),
    detailSave: document.getElementById("detailSave"),
    toast: document.getElementById("toast"),
    feedbackForm: document.getElementById("feedbackForm"),
    feedbackStatus: document.getElementById("feedbackStatus")
  };

  /* ---------------- helpers ---------------- */

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function escapeXml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&apos;");
  }

  function slug(value) {
    return String(value).toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
  }

  function hash(value) {
    var h = 0;
    for (var i = 0; i < value.length; i += 1) {
      h = (h * 31 + value.charCodeAt(i)) % 1000003;
    }
    return h;
  }

  function normalise(value) {
    return String(value || "").toLowerCase().replace(/\s+/g, " ").trim();
  }

  function pad(n) {
    return n < 10 ? "0" + n : String(n);
  }

  function parseDate(iso) {
    var parts = String(iso).split("-");
    return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12, 0, 0);
  }

  function nextOccurrence(iso) {
    var base = parseDate(iso);
    var now = new Date();
    var candidate = new Date(base.getFullYear(), base.getMonth(), base.getDate(), 12, 0, 0);
    while (candidate.getTime() <= now.getTime()) {
      candidate = new Date(candidate.getFullYear() + 1, candidate.getMonth(), candidate.getDate(), 12, 0, 0);
    }
    return candidate;
  }

  function longDate(date) {
    return date.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" });
  }

  function shortDate(festival) {
    var d = parseDate(festival.guide.date);
    return d.getDate() + " " + MONTH_SHORT[d.getMonth()];
  }

  function formatNumber(value) {
    return String(value).padStart(2, "0");
  }

  /* ---------------- saved list ---------------- */

  function readSaved() {
    try {
      var raw = window.localStorage.getItem(STORE_KEY);
      var parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed.filter(function (id) { return typeof id === "string"; }) : [];
    } catch (error) {
      return [];
    }
  }

  function persistSaved() {
    try {
      window.localStorage.setItem(STORE_KEY, JSON.stringify(saved));
      return true;
    } catch (error) {
      if (!storageWarned) {
        storageWarned = true;
        toast("This browser blocked local storage — your list will reset on reload.");
      }
      return false;
    }
  }

  function byId(id) {
    for (var i = 0; i < festivals.length; i += 1) {
      if (festivals[i].id === id) return festivals[i];
    }
    return null;
  }

  function isSaved(id) {
    return saved.indexOf(id) !== -1;
  }

  function toast(message) {
    els.toast.textContent = message;
    els.toast.classList.add("is-visible");
    window.clearTimeout(toast.timer);
    toast.timer = window.setTimeout(function () {
      els.toast.classList.remove("is-visible");
    }, 2600);
  }

  function toggleSave(id) {
    var festival = byId(id);
    if (!festival) return;
    var index = saved.indexOf(id);
    if (index === -1) {
      saved.push(id);
      toast("Saved " + festival.name + " to your list.");
    } else {
      saved.splice(index, 1);
      toast("Removed " + festival.name + " from your list.");
    }
    persistSaved();
    syncSaveButtons();
    renderSavedList();
  }

  function syncSaveButtons() {
    document.querySelectorAll("[data-save-btn]").forEach(function (button) {
      var festival = byId(button.getAttribute("data-save-btn"));
      if (!festival) return;
      var on = isSaved(festival.id);
      button.setAttribute("aria-pressed", String(on));
      button.setAttribute("aria-label", (on ? "Remove " : "Save ") + festival.name + (on ? " from my list" : " to my list"));

      var label = button.querySelector("[data-save-label]");
      if (label) label.textContent = on ? "Saved" : "Save";
    });

    var detailId = els.detailSave.getAttribute("data-save-btn") || "";
    var detailOn = isSaved(detailId);
    els.detailSave.setAttribute("aria-pressed", String(detailOn));
    els.detailSave.textContent = detailOn ? "Saved to my list" : "Save to my list";

    els.savedCount.textContent = String(saved.length);
    els.savedBtn.setAttribute("aria-label", "Open my saved festivals, " + saved.length + " saved");
  }

  function renderSavedList() {
    var items = saved.map(byId).filter(Boolean);

    els.savedEmpty.hidden = items.length > 0;
    els.savedList.hidden = items.length === 0;

    els.savedList.innerHTML = items.map(function (festival) {
      var image = imageFor(festival);
      return '<div class="saved-item">' +
        '<img src="' + escapeHtml(image.src) + '" alt="" width="54" height="54" loading="lazy">' +
        '<div>' +
          '<span class="saved-name">' + escapeHtml(festival.name) + '</span>' +
          '<span class="saved-meta">' + escapeHtml(festival.country) + ' · ' + escapeHtml(festival.month) + '</span>' +
        '</div>' +
        '<div class="saved-actions">' +
          '<button class="icon-btn" type="button" data-open-saved="' + escapeHtml(festival.id) + '" aria-label="Open ' + escapeHtml(festival.name) + '">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>' +
          '</button>' +
          '<button class="icon-btn" type="button" data-remove-saved="' + escapeHtml(festival.id) + '" aria-label="Remove ' + escapeHtml(festival.name) + ' from my list">' +
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>' +
          '</button>' +
        '</div>' +
      '</div>';
    }).join("");
  }

  function openSaved() {
    renderSavedList();
    if (!els.savedDialog.open) els.savedDialog.showModal();
    document.body.classList.add("dialog-open");
  }

  /* ---------------- imagery ---------------- */

  var MONTH_TONES = {
    January: ["#0b1c33", "#1d4f74", "#cfe9f5"],
    February: ["#22102a", "#7a2f4a", "#f3c7cf"],
    March: ["#2a0f1f", "#a63a2f", "#ffd0a8"],
    April: ["#20122e", "#5b3f8c", "#e7c9ff"],
    May: ["#0d2418", "#2f6b3f", "#d8f0b8"],
    June: ["#241203", "#b45a12", "#ffd9a0"],
    July: ["#2a0d16", "#9c2f2a", "#ffb98a"],
    August: ["#241708", "#8a5a12", "#ffe1a3"],
    September: ["#1f0f0c", "#8c4a34", "#f6c8a0"],
    October: ["#1c0f2a", "#6a2a63", "#ffc27a"],
    November: ["#150c22", "#5a2a4a", "#e8c07a"],
    December: ["#081c26", "#1f5566", "#cdeef2"]
  };

  function wrapTitle(value) {
    var words = String(value).toUpperCase().split(/\s+/);
    var lines = [];
    var line = "";
    words.forEach(function (word) {
      if (!line.length) {
        line = word;
      } else if ((line + " " + word).length <= 15) {
        line += " " + word;
      } else {
        lines.push(line);
        line = word;
      }
    });
    if (line) lines.push(line);
    return lines.slice(0, 3);
  }

  function posterFor(festival) {
    var tone = MONTH_TONES[festival.month] || MONTH_TONES.November;
    var seed = hash(festival.name + festival.country);
    var lines = wrapTitle(festival.name);
    var titleStart = 600 - (lines.length - 1) * 82;
    var title = lines.map(function (line, i) {
      return '<text x="80" y="' + (titleStart + i * 82) + '" font-family="Georgia, \'Times New Roman\', serif" font-size="78" fill="' + tone[2] + '">' + escapeXml(line) + "</text>";
    }).join("");

    var stars = "";
    for (var i = 1; i <= 7; i += 1) {
      var sx = 90 + ((seed * i * 37) % 1020);
      var sy = 120 + ((seed * i * 53) % 300);
      stars += '<circle cx="' + sx + '" cy="' + sy + '" r="' + (2 + (i % 3)) + '" fill="' + tone[2] + '" opacity="0.55"/>';
    }

    var cx = 880 + (seed % 90);
    var cy = 210 + (seed % 80);

    var svg =
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800">' +
        '<defs>' +
          '<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">' +
            '<stop offset="0" stop-color="' + tone[0] + '"/>' +
            '<stop offset="1" stop-color="' + tone[1] + '"/>' +
          '</linearGradient>' +
          '<radialGradient id="glow" cx="0.76" cy="0.24" r="0.62">' +
            '<stop offset="0" stop-color="' + tone[2] + '" stop-opacity="0.5"/>' +
            '<stop offset="1" stop-color="' + tone[2] + '" stop-opacity="0"/>' +
          '</radialGradient>' +
        '</defs>' +
        '<rect width="1200" height="800" fill="url(#bg)"/>' +
        '<rect width="1200" height="800" fill="url(#glow)"/>' +
        stars +
        '<circle cx="' + cx + '" cy="' + cy + '" r="' + (134 + (seed % 24)) + '" fill="none" stroke="' + tone[2] + '" stroke-opacity="0.35" stroke-width="2"/>' +
        '<circle cx="' + cx + '" cy="' + cy + '" r="92" fill="' + tone[2] + '" opacity="0.92"/>' +
        '<circle cx="' + (cx + 34) + '" cy="' + (cy - 26) + '" r="86" fill="' + tone[0] + '" opacity="0.26"/>' +
        '<path d="M0 690 Q 300 620 600 672 T 1200 640 L1200 800 L0 800 Z" fill="#07060d" opacity="0.55"/>' +
        '<text x="1120" y="752" text-anchor="end" font-family="Helvetica, Arial, sans-serif" font-size="20" letter-spacing="7" fill="' + tone[2] + '" fill-opacity="0.78">MOONLIGHT / 2026</text>' +
        '<text x="82" y="' + (titleStart - 66) + '" font-family="Helvetica, Arial, sans-serif" font-size="21" letter-spacing="5" fill="' + tone[2] + '" fill-opacity="0.8">' + escapeXml(festival.country.toUpperCase()) + '</text>' +
        title +
        '<text x="82" y="700" font-family="Helvetica, Arial, sans-serif" font-size="19" letter-spacing="3" fill="' + tone[2] + '" fill-opacity="0.7">' + escapeXml(festival.month.toUpperCase()) + ' · ' + escapeXml(festival.religion.toUpperCase()) + '</text>' +
      '</svg>';

    return "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svg);
  }

  function imageFor(festival) {
    if (festival.image) {
      return { src: festival.image, alt: festival.name + " festival artwork" };
    }
    return { src: posterFor(festival), alt: "Poster artwork for " + festival.name + " in " + festival.country };
  }

  /* ---------------- data ---------------- */

  function defaultGuide(festival) {
    var monthIndex = MONTHS.indexOf(festival.month);
    return {
      date: "2026-" + pad(monthIndex + 1) + "-15",
      dateLabel: festival.month + " 2026",
      city: festival.country,
      food: "Local festival cooking — ask your host for the dish of the day.",
      tips: "Dates can shift with the lunar calendar; check local listings close to the day."
    };
  }

  function hydrate(data) {
    return data.map(function (festival, index) {
      var guide = GUIDE[festival.name] || defaultGuide(festival);
      return {
        id: slug(festival.name),
        index: index,
        name: festival.name,
        country: festival.country,
        religion: festival.religion,
        month: festival.month,
        description: festival.description,
        history: festival.history,
        image: festival.image || null,
        guide: guide,
        region: COUNTRY_REGION[festival.country] || "Global",
        searchText: [
          festival.name, festival.country, festival.month, festival.religion,
          festival.description, festival.history, guide.city, guide.food, guide.tips
        ].join(" ").toLowerCase()
      };
    });
  }

  function loadData() {
    if (window.location.protocol === "file:") {
      return Promise.resolve(FESTIVAL_FALLBACK.slice());
    }
    return fetch("data.json", { cache: "no-store" })
      .then(function (response) {
        if (!response.ok) throw new Error("HTTP " + response.status);
        return response.json();
      })
      .then(function (data) {
        if (!Array.isArray(data) || !data.length) throw new Error("Empty data");
        return data;
      })
      .catch(function () {
        return FESTIVAL_FALLBACK.slice();
      });
  }

  /* ---------------- rendering ---------------- */

  function buildCard(festival) {
    var image = imageFor(festival);
    var article = document.createElement("article");
    article.className = "festival-card";
    article.setAttribute("data-reveal", "");
    article.style.setProperty("--i", String(festival.index % 6));
    article.setAttribute("data-region", festival.region);
    article.setAttribute("data-month", festival.month);
    article.setAttribute("data-tradition", festival.religion);

    article.innerHTML =
      '<button class="save-btn" type="button" data-save-btn="' + escapeHtml(festival.id) + '" aria-pressed="false" aria-label="Save ' + escapeHtml(festival.name) + ' to my list">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/></svg>' +
      '</button>' +
      '<button class="card-open" type="button" aria-haspopup="dialog" data-open="' + escapeHtml(festival.id) + '" aria-label="Open details for ' + escapeHtml(festival.name) + '">' +
        '<span class="card-media">' +
          '<img src="' + escapeHtml(image.src) + '" width="1200" height="800" loading="lazy" decoding="async" alt="' + escapeHtml(image.alt) + '">' +
          '<span class="card-index">' + pad(festival.index + 1) + ' / ' + festivals.length + '</span>' +
        '</span>' +
        '<span class="card-body">' +
          '<span class="card-kicker">' + escapeHtml(festival.country) +
            '<span class="sep" aria-hidden="true">·</span>' + escapeHtml(festival.region) +
          '</span>' +
          '<span class="card-title">' + escapeHtml(festival.name) + '</span>' +
          '<span class="card-desc">' + escapeHtml(festival.description) + '</span>' +
          '<span class="card-foot">' +
            '<span class="card-date">' + escapeHtml(shortDate(festival)) + '</span>' +
            '<span class="card-more">View briefing →</span>' +
          '</span>' +
        '</span>' +
      '</button>';

    return article;
  }

  function renderGrid() {
    els.grid.innerHTML = "";
    cardRecords = [];
    festivals.forEach(function (festival) {
      var card = buildCard(festival);
      els.grid.appendChild(card);
      cardRecords.push({ el: card, festival: festival });
      observeReveal(card);
    });
    syncSaveButtons();
  }

  function populateFilters() {
    var regions = [];
    var months = [];
    festivals.forEach(function (festival) {
      if (regions.indexOf(festival.region) === -1) regions.push(festival.region);
      if (months.indexOf(festival.month) === -1) months.push(festival.month);
    });
    regions.sort();
    months.sort(function (a, b) { return MONTHS.indexOf(a) - MONTHS.indexOf(b); });

    regions.forEach(function (region) {
      var option = document.createElement("option");
      option.value = region;
      option.textContent = region;
      els.region.appendChild(option);
    });

    months.forEach(function (month) {
      var option = document.createElement("option");
      option.value = month;
      option.textContent = month;
      els.month.appendChild(option);
    });
  }

  function applyFilters() {
    var query = normalise(state.search);
    var visible = 0;

    cardRecords.forEach(function (record) {
      var festival = record.festival;
      var matches =
        (state.region === "all" || festival.region === state.region) &&
        (state.month === "all" || festival.month === state.month) &&
        (state.tradition === "all" || festival.religion === state.tradition) &&
        (!query || festival.searchText.indexOf(query) !== -1);

      record.el.hidden = !matches;
      if (matches) {
        visible += 1;
        if (!record.el.classList.contains("in")) observeReveal(record.el);
      }
    });

    els.empty.classList.toggle("is-visible", visible === 0);
    els.results.textContent = visible === 0
      ? "No festivals match — try a wider search"
      : "Showing " + visible + " of " + festivals.length + " festivals" +
        (state.region === "all" ? "" : " · " + state.region) +
        (state.month === "all" ? "" : " · " + state.month) +
        (state.tradition === "all" ? "" : " · " + state.tradition);
  }

  function resetFilters() {
    state.search = "";
    state.region = "all";
    state.month = "all";
    state.tradition = "all";
    els.search.value = "";
    els.region.value = "all";
    els.month.value = "all";
    els.chips.forEach(function (chip) {
      chip.setAttribute("aria-pressed", String(chip.getAttribute("data-tradition") === "all"));
    });
    applyFilters();
    els.search.focus();
  }

  /* ---------------- detail view ---------------- */

  function openFestival(id, trigger) {
    var festival = byId(id);
    if (!festival) return;

    lastTrigger = trigger || null;
    var image = imageFor(festival);
    var guide = festival.guide;

    els.detailImage.src = image.src;
    els.detailImage.alt = image.alt;
    els.detailKicker.textContent = festival.country + " · " + festival.region + " · " + festival.month;
    els.detailTitle.textContent = festival.name;
    els.detailDescription.textContent = festival.description;
    els.detailDate.textContent = guide.dateLabel;
    els.detailLocation.textContent = guide.city;
    els.detailTradition.textContent = festival.religion;
    els.detailRegion.textContent = festival.region;
    els.detailHistory.textContent = festival.history || "A tradition passed down through generations.";
    els.detailFood.textContent = guide.food;
    els.detailTips.textContent = guide.tips;

    els.detailSave.setAttribute("data-save-btn", festival.id);
    syncSaveButtons();

    if (els.savedDialog.open) els.savedDialog.close();
    if (!els.detailDialog.open) els.detailDialog.showModal();
    document.body.classList.add("dialog-open");
  }

  function closeDetail() {
    if (els.detailDialog.open) els.detailDialog.close();
  }

  /* ---------------- hero + countdown ---------------- */

  function getNextFestival() {
    var best = null;
    var bestDate = null;
    festivals.forEach(function (festival) {
      var occurrence = nextOccurrence(festival.guide.date);
      if (!bestDate || occurrence.getTime() < bestDate.getTime()) {
        best = festival;
        bestDate = occurrence;
      }
    });
    return { festival: best, date: bestDate };
  }

  function paintFeatured(festival, date) {
    featured = festival;
    featuredTarget = date;
    var image = imageFor(festival);

    els.featureImage.src = image.src;
    els.featureImage.alt = image.alt;
    els.featureTitle.textContent = festival.name;
    els.featureDate.textContent = longDate(date) + " · " + festival.country;
    els.featureDesc.textContent = festival.description;
    els.countdownLabel.textContent = "Counting down to " + festival.name + ", " + longDate(date);

    els.featureOpen.setAttribute("data-open", festival.id);
    els.featureSave.setAttribute("data-save-btn", festival.id);
    syncSaveButtons();
    tickCountdown();
  }

  function tickCountdown() {
    if (!featuredTarget) return;
    var diff = featuredTarget.getTime() - Date.now();

    if (diff <= 0) {
      var next = getNextFestival();
      if (next.festival) paintFeatured(next.festival, next.date);
      return;
    }

    var total = Math.floor(diff / 1000);
    var days = Math.floor(total / 86400);
    var hours = Math.floor((total % 86400) / 3600);
    var minutes = Math.floor((total % 3600) / 60);
    var seconds = total % 60;

    els.cdDays.textContent = formatNumber(days);
    els.cdHours.textContent = formatNumber(hours);
    els.cdMinutes.textContent = formatNumber(minutes);
    els.cdSeconds.textContent = formatNumber(seconds);
  }

  /* ---------------- reveal ---------------- */

  var observer = null;
  if (!REDUCED_MOTION && "IntersectionObserver" in window) {
    observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("in");
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: "0px 0px -6% 0px", threshold: 0.06 });
  }

  function observeReveal(node) {
    if (!observer) {
      node.classList.add("in");
      return;
    }
    if (node.classList.contains("in")) return;
    observer.observe(node);
  }

  /* ---------------- header ---------------- */

  function initHeader() {
    var header = document.getElementById("siteHeader");
    var nav = document.getElementById("siteNav");
    var menuBtn = document.getElementById("menuBtn");

    function setOpen(open) {
      nav.classList.toggle("open", open);
      menuBtn.setAttribute("aria-expanded", String(open));
      menuBtn.setAttribute("aria-label", open ? "Close menu" : "Open menu");
      header.classList.toggle("menu-open", open);
    }

    menuBtn.addEventListener("click", function () {
      setOpen(menuBtn.getAttribute("aria-expanded") !== "true");
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && nav.classList.contains("open")) {
        setOpen(false);
        menuBtn.focus();
      }
    });

    nav.addEventListener("click", function (event) {
      if (event.target.closest("a")) setOpen(false);
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 900) setOpen(false);
    });

    var ticking = false;
    function onScroll() {
      header.classList.toggle("scrolled", window.scrollY > 10);
      ticking = false;
    }
    window.addEventListener("scroll", function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(onScroll);
      }
    }, { passive: true });
    onScroll();
  }

  /* ---------------- events ---------------- */

  function initEvents() {
    els.search.addEventListener("input", function () {
      state.search = els.search.value;
      applyFilters();
    });

    els.region.addEventListener("change", function () {
      state.region = els.region.value;
      applyFilters();
    });

    els.month.addEventListener("change", function () {
      state.month = els.month.value;
      applyFilters();
    });

    els.chips.forEach(function (chip) {
      chip.addEventListener("click", function () {
        state.tradition = chip.getAttribute("data-tradition");
        els.chips.forEach(function (other) {
          other.setAttribute("aria-pressed", String(other === chip));
        });
        applyFilters();
      });
    });

    els.clear.addEventListener("click", resetFilters);
    els.emptyReset.addEventListener("click", resetFilters);

    els.grid.addEventListener("click", function (event) {
      var saveButton = event.target.closest("[data-save-btn]");
      if (saveButton) {
        toggleSave(saveButton.getAttribute("data-save-btn"));
        return;
      }
      var openButton = event.target.closest("[data-open]");
      if (openButton) {
        openFestival(openButton.getAttribute("data-open"), openButton);
      }
    });

    els.featureOpen.addEventListener("click", function () {
      if (featured) openFestival(featured.id, els.featureOpen);
    });

    els.featureSave.addEventListener("click", function () {
      if (featured) toggleSave(featured.id);
    });

    els.savedBtn.addEventListener("click", openSaved);
    document.getElementById("heroSavedBtn").addEventListener("click", openSaved);
    document.getElementById("footerSavedBtn").addEventListener("click", openSaved);

    els.savedClose.addEventListener("click", function () { els.savedDialog.close(); });
    document.querySelectorAll("[data-close-saved]").forEach(function (btn) {
      btn.addEventListener("click", function () { els.savedDialog.close(); });
    });

    els.savedList.addEventListener("click", function (event) {
      var remove = event.target.closest("[data-remove-saved]");
      if (remove) {
        toggleSave(remove.getAttribute("data-remove-saved"));
        return;
      }
      var open = event.target.closest("[data-open-saved]");
      if (open) {
        var id = open.getAttribute("data-open-saved");
        els.savedDialog.close();
        openFestival(id, els.savedBtn);
      }
    });

    els.detailClose.addEventListener("click", closeDetail);
    document.querySelectorAll("[data-close-detail]").forEach(function (btn) {
      btn.addEventListener("click", closeDetail);
    });

    els.detailSave.addEventListener("click", function () {
      var id = els.detailSave.getAttribute("data-save-btn");
      if (id) toggleSave(id);
    });

    els.detailDialog.addEventListener("click", function (event) {
      if (event.target === els.detailDialog) els.detailDialog.close();
    });

    els.savedDialog.addEventListener("click", function (event) {
      if (event.target === els.savedDialog) els.savedDialog.close();
    });

    els.detailDialog.addEventListener("close", function () {
      document.body.classList.remove("dialog-open");
      if (lastTrigger && document.contains(lastTrigger) && lastTrigger.offsetParent !== null) {
        lastTrigger.focus();
      }
    });

    els.savedDialog.addEventListener("close", function () {
      document.body.classList.remove("dialog-open");
    });

    els.feedbackForm.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!els.feedbackForm.checkValidity()) {
        els.feedbackForm.reportValidity();
        return;
      }
      var name = document.getElementById("fbName").value.trim();
      var email = document.getElementById("fbEmail").value.trim();
      var message = document.getElementById("fbMessage").value.trim();
      var subject = "Moonlight guide feedback from " + name;
      var body = message + "\n\n— " + name + " (" + email + ")";

      window.location.href = "mailto:abodaniel435@gmail.com" +
        "?subject=" + encodeURIComponent(subject) +
        "&body=" + encodeURIComponent(body);
      els.feedbackStatus.textContent = "Opening your mail app with this message…";
    });
  }

  /* ---------------- boot ---------------- */

  function init() {
    initHeader();
    initEvents();

    document.querySelectorAll(".section-head").forEach(function (head) {
      head.setAttribute("data-reveal", "");
    });

    document.querySelectorAll("[data-reveal]").forEach(observeReveal);

    loadData().then(function (data) {
      festivals = hydrate(data);
      saved = saved.filter(function (id) { return Boolean(byId(id)); });
      persistSaved();

      populateFilters();
      renderGrid();
      renderSavedList();
      applyFilters();

      var next = getNextFestival();
      if (next.festival) {
        paintFeatured(next.festival, next.date);
        window.setInterval(tickCountdown, 1000);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
