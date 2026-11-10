/* ============================================================
   THATGUYS FITNESS — script.js
  Tab switching, accordion, fade-ins, and short looping
  exercise video demos.
   ============================================================ */

const DAYS = ['monday','tuesday','wednesday','thursday','friday'];

function switchDay(dayId) {
  const panel = document.getElementById(dayId);
  if (!panel) {
    window.location.href = 'workouts.html#' + dayId;
    return;
  }
  DAYS.forEach(d => {
    const day = document.getElementById(d);
    if (day) day.classList.remove('active');
    const tab = document.getElementById('tab-' + d);
    if (tab) tab.classList.remove('active');
    const fl = document.getElementById('float-' + d);
    if (fl) fl.classList.remove('float-active');
  });
  panel.classList.add('active');
  const tab = document.getElementById('tab-' + dayId);
  if (tab) tab.classList.add('active');
  const fl = document.getElementById('float-' + dayId);
  if (fl) fl.classList.add('float-active');
  const schedule = document.getElementById('schedule');
  if (schedule) schedule.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function toggleEx(header) {
  const item = header.closest('.exercise-item');
  const wasOpen = item.classList.contains('open');
  const siblings = item.parentElement.querySelectorAll('.exercise-item');
  siblings.forEach(s => s.classList.remove('open'));
  if (!wasOpen) item.classList.add('open');
}

function openHashDay() {
  const day = window.location.hash.replace('#', '').toLowerCase();
  if (DAYS.includes(day) && document.getElementById(day)) switchDay(day);
}

const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); }
  });
}, { threshold: 0.1 });

/* ============================================================
   PHOTO-BASED 3-STEP DEMO ENGINE
   ------------------------------------------------------------
   Drop your own photos into:  images/exercises/
   Named:  <exercise-slug>-1.jpg  (step 1 / setup)
           <exercise-slug>-2.jpg  (step 2 / working phase)
           <exercise-slug>-3.jpg  (step 3 / return)

   Slugs are the exercise name, lowercased, spaces -> dashes,
   punctuation stripped. e.g. "Diamond Push-Up" -> diamond-push-up
   Run slugManifest() in the browser console any time to print
   the full expected file list for every exercise.

   Each <img> tries .jpg, then .jpeg, then .png, then .webp in
   that order. If none exist yet, a "photo needed" placeholder
   shows instead — so the site never breaks while you're still
   shooting photos. Nothing else needs to change in the HTML.
   ============================================================ */

const IMG_DIR = 'images/exercises/';
const IMG_EXT_FALLBACKS = ['jpg', 'jpeg', 'png', 'webp'];

/* ---------- VERIFIED FREE STOCK PHOTOS (Pexels, free-for-commercial-use,
   no attribution required) ----------
   These are used ONLY when you haven't uploaded your own photo yet for
   that exercise+step (local file always wins if present). Each set below
   is 3 photos from the SAME photographer's same shoot, so the 3 steps
   look like one cohesive session rather than 3 random strangers.

   This only covers the generic gym movements below — I could not find
   real stock matches for the household-object variations (towel curl,
   backpack curl, table row, reverse snow angel, etc.) since stock
   photographers shoot in gyms with real equipment. Those exercises will
   keep showing the "photo needed" placeholder until you add your own —
   see STOCK-PHOTO-SOURCES.txt for browse links, or shoot them yourself.

   To add more later: add a new "Exact Exercise Name": [url1, url2, url3]
   line below. All 3 URLs are optional — fewer than 3 is fine.
   ============================================================ */
const STOCK_PHOTOS = {
  "Diamond Push-Up": [
    "assets/photos/b1e4ddbe4d12.webp",
    "assets/photos/a596bdc1e07b.webp",
    "assets/photos/ab287cec29a8.webp"
  ],
  "Standard Push-Up": [
    "assets/photos/b1e4ddbe4d12.webp",
    "assets/photos/a596bdc1e07b.webp",
    "assets/photos/ab287cec29a8.webp"
  ],
  "Wide Push-Up": [
    "assets/photos/b1e4ddbe4d12.webp",
    "assets/photos/a596bdc1e07b.webp",
    "assets/photos/ab287cec29a8.webp"
  ],
  "Decline Push-Up": [
    "assets/photos/b1e4ddbe4d12.webp",
    "assets/photos/a596bdc1e07b.webp",
    "assets/photos/ab287cec29a8.webp"
  ],
  "Tricep Push-Up (Elbows In)": [
    "assets/photos/b1e4ddbe4d12.webp",
    "assets/photos/a596bdc1e07b.webp",
    "assets/photos/ab287cec29a8.webp"
  ],
  "Diamond Push-Up — To Failure": [
    "assets/photos/b1e4ddbe4d12.webp",
    "assets/photos/a596bdc1e07b.webp",
    "assets/photos/ab287cec29a8.webp"
  ],
  "Jump Squat — Finisher": [
    "assets/photos/6fed841c7d35.webp",
    "assets/photos/f81ece0fb594.webp",
    "assets/photos/9bc6eda55705.webp"
  ],

  /* --- REAL, SPECIFIC MATCHES (single photo, reused across the 3 steps) --- */
  "Chin-Up (Doorframe or Bar)": [
    "assets/photos/df1fda2bc541.webp"
  ],
  "Towel Curl (Door Curl)": [
    "assets/photos/d9504d8d95ae.webp"
  ],
  "Backpack Curl": [
    "assets/photos/d9504d8d95ae.webp"
  ],
  "Towel Curl — Slow Negative": [
    "assets/photos/d9504d8d95ae.webp"
  ],
  "Backpack Curl — Heavy": [
    "assets/photos/d9504d8d95ae.webp"
  ],

  /* --- APPROXIMATE / GENERIC (per your request) ---
     No real stock photo exists of these specific movements. These are
     generic strength/gym photos used as a placeholder "vibe" only —
     they do NOT show the actual exercise position. Swap these out for
     real photos of yourself performing the movement as soon as you can;
     until then this is closer to a themed placeholder than a demo. */
  "Superman Hold": [
    "assets/photos/124c28899f8c.webp"
  ],
  "Superman — Pause Reps": [
    "assets/photos/124c28899f8c.webp"
  ],
  "Reverse Snow Angel": [
    "assets/photos/4866a7a2a1a6.webp"
  ],
  "Reverse Snow Angel — Drop Set": [
    "assets/photos/4866a7a2a1a6.webp"
  ],
  "Table Row (Inverted Row)": [
    "assets/photos/df1fda2bc541.webp"
  ],
  "Table Row — Feet Elevated": [
    "assets/photos/df1fda2bc541.webp"
  ],
  "Lateral Raise (Backpack or Bottles)": [
    "assets/photos/48be45a461f7.webp"
  ],
  "Front Raise (Water Bottles)": [
    "assets/photos/48be45a461f7.webp"
  ],
  "Chair Dips": [
    "assets/photos/25e0974ed91d.webp"
  ],
  "Chair Dips — Slow Tempo": [
    "assets/photos/25e0974ed91d.webp"
  ],
  "Bulgarian Split Squat": [
    "assets/photos/a5905c53fa40.webp"
  ],
  "Single-Leg Romanian Deadlift": [
    "assets/photos/a5905c53fa40.webp"
  ],
  "Standing Calf Raise": [
    "assets/photos/f3a0c6a073d2.webp"
  ]
};

function slugify(name){
  return name
    .toLowerCase()
    .replace(/[()]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

/* ---------- EXERCISE STEP COPY ---------- */
const STEP_DATA = {
  "Superman Hold": { steps:[
    {t:'Set Up', c:'Lie face down, arms stretched overhead.', motion:'start'},
    {t:'Lift & Squeeze', c:'Raise chest, arms and legs together. Hold 2 sec.', motion:'up'},
    {t:'Lower Slow', c:'Return with control, no momentum.', motion:'down'}]},
  "Reverse Snow Angel": { steps:[
    {t:'Chest Raised', c:'Arms at sides, chest lifted off the floor.', motion:'start'},
    {t:'Sweep Wide', c:'Arc arms out and overhead like a snow angel.', motion:'up'},
    {t:'Return', c:'Bring arms back down, keep chest up.', motion:'down'}]},
  "Table Row (Inverted Row)": { steps:[
    {t:'Hang Extended', c:'Grip the table edge, body straight, arms long.', motion:'start'},
    {t:'Pull Up', c:'Pull chest to the table edge, squeeze shoulder blades.', motion:'up'},
    {t:'Lower Control', c:'Return with control over 3 seconds.', motion:'down'}]},
  "Diamond Push-Up": { steps:[
    {t:'Top Position', c:'Hands in a diamond under your sternum.', motion:'start'},
    {t:'Lower Down', c:'Elbows tucked, chest to hands.', motion:'down'},
    {t:'Press Up', c:'Drive up to full lockout.', motion:'up'}]},
  "Chair Dips": { steps:[
    {t:'Top, Arms Long', c:'Hands on the chair edge, hips off the seat.', motion:'start'},
    {t:'Lower to 90°', c:'Bend elbows, drop straight down.', motion:'down'},
    {t:'Push Back Up', c:'Press to full extension.', motion:'up'}]},
  "Plank": { steps:[
    {t:'Line It Up', c:'Elbows under shoulders, straight line head to heel.', motion:'start'},
    {t:'Brace', c:'Belly button to spine, squeeze glutes.', motion:'up'},
    {t:'Hold', c:'Breathe steady, don\u2019t let the hips sag.', motion:'up'}]},
  "Crunches": { steps:[
    {t:'Lie Back', c:'Knees bent, hands lightly behind ears.', motion:'start'},
    {t:'Curl Up', c:'Lift shoulder blades off the floor, hold 1 sec.', motion:'up'},
    {t:'Lower Slow', c:'Control the descent back down.', motion:'down'}]},
  "Leg Raises": { steps:[
    {t:'Legs Flat', c:'Lie flat, hands under glutes.', motion:'start'},
    {t:'Raise to 90°', c:'Lift legs straight up.', motion:'up'},
    {t:'Lower Slow', c:'Stop just above the floor, don\u2019t arch the back.', motion:'down'}]},
  "Towel Curl (Door Curl)": { steps:[
    {t:'Lean Back', c:'Towel looped on the door, arms straight.', motion:'start'},
    {t:'Curl Up', c:'Bend elbows, curl body toward the door.', motion:'up'},
    {t:'Lower Slow', c:'3-second controlled descent.', motion:'down'}]},
  "Backpack Curl": { steps:[
    {t:'Arms Long', c:'Upper arms pinned to your sides.', motion:'start'},
    {t:'Curl Up', c:'Curl the bag to your shoulders, squeeze.', motion:'up'},
    {t:'Lower Slow', c:'3 seconds on the way down.', motion:'down'}]},
  "Pike Push-Up": { steps:[
    {t:'Inverted V', c:'Hips high, hands on the floor.', motion:'start'},
    {t:'Lower Head', c:'Bend elbows, head toward the floor.', motion:'down'},
    {t:'Press Up', c:'Push back to full extension.', motion:'up'}]},
  "Lateral Raise (Backpack or Bottles)": { steps:[
    {t:'Arms Down', c:'A bottle or bag in each hand at your sides.', motion:'start'},
    {t:'Raise to T', c:'Lift to shoulder height, lead with the elbows.', motion:'up'},
    {t:'Lower Slow', c:'Control it back down.', motion:'down'}]},
  "Wall Handstand Hold": { steps:[
    {t:'Kick Up', c:'Walk feet up the wall until vertical.', motion:'start'},
    {t:'Lock & Hold', c:'Arms locked, core tight, glutes squeezed.', motion:'up'},
    {t:'Come Down', c:'Walk down carefully with control.', motion:'down'}]},
  "Decline Push-Up": { steps:[
    {t:'Feet Elevated', c:'Feet on a chair or bed, hands on the floor.', motion:'start'},
    {t:'Lower Down', c:'Chest toward your hands.', motion:'down'},
    {t:'Press Up', c:'Drive back up through the upper chest.', motion:'up'}]},
  "Standard Push-Up": { steps:[
    {t:'Straight Line', c:'Hands just wider than shoulders.', motion:'start'},
    {t:'Lower Down', c:'Elbows at 45°, chest just above the floor.', motion:'down'},
    {t:'Drive Up', c:'Press up explosively, squeeze the chest.', motion:'up'}]},
  "Wide Push-Up": { steps:[
    {t:'Hands Wide', c:'About 1.5x shoulder width.', motion:'start'},
    {t:'Lower Down', c:'Lower chest between your hands.', motion:'down'},
    {t:'Press Up', c:'Drive back to the top.', motion:'up'}]},
  "Elevated Pike Push-Up": { steps:[
    {t:'Feet on Chair', c:'Pike position, feet elevated behind you.', motion:'start'},
    {t:'Lower Head', c:'Bend elbows under load.', motion:'down'},
    {t:'Press Up', c:'Push back to full extension.', motion:'up'}]},
  "Front Raise (Water Bottles)": { steps:[
    {t:'Arms Down', c:'Bottles held at your thighs.', motion:'start'},
    {t:'Raise Forward', c:'Lift to shoulder height in front.', motion:'up'},
    {t:'Lower Slow', c:'3-second controlled descent.', motion:'down'}]},
  "Wall Handstand Push-Up (Partial)": { steps:[
    {t:'Full Hold', c:'Vertical against the wall, arms locked.', motion:'start'},
    {t:'Lower a Few Inches', c:'Bend elbows slightly, head toward the floor.', motion:'down'},
    {t:'Press Back Up', c:'Even a partial rep loads the shoulders hard.', motion:'up'}]},
  "Tricep Push-Up (Elbows In)": { steps:[
    {t:'Elbows Tucked', c:'Hands under shoulders, elbows glued to ribs.', motion:'start'},
    {t:'Lower Down', c:'Keep elbows in the whole way down.', motion:'down'},
    {t:'Press Up', c:'Drive through the triceps.', motion:'up'}]},
  "Chair Dips — Slow Tempo": { steps:[
    {t:'Top, Arms Long', c:'Same setup as Monday\u2019s dips.', motion:'start'},
    {t:'4-Second Lower', c:'Take a full 4 seconds down.', motion:'down'},
    {t:'Drive Up', c:'Press back up explosively.', motion:'up'}]},
  "Diamond Push-Up — To Failure": { steps:[
    {t:'Diamond Hands', c:'Under your sternum, elbows in.', motion:'start'},
    {t:'Lower Down', c:'Clean reps until form breaks.', motion:'down'},
    {t:'Press Up', c:'Full lockout on every rep.', motion:'up'}]},
  "Bicycle Crunches": { steps:[
    {t:'Set Up', c:'Hands behind head, legs raised.', motion:'start'},
    {t:'Rotate', c:'Elbow to opposite knee, cycle through.', motion:'up'},
    {t:'Switch Sides', c:'Slow and deliberate, not rushed.', motion:'down'}]},
  "Reverse Crunch": { steps:[
    {t:'Knees at 90°', c:'Lower back pressed into the floor.', motion:'start'},
    {t:'Curl Hips Up', c:'Lift the tailbone off the floor.', motion:'up'},
    {t:'Lower Control', c:'Small movement, curl the pelvis.', motion:'down'}]},
  "Russian Twists": { steps:[
    {t:'Lean Back 45°', c:'Knees bent, clasp your hands.', motion:'start'},
    {t:'Rotate Right', c:'Twist from the waist, squeeze the oblique.', motion:'up'},
    {t:'Rotate Left', c:'Mirror to the other side.', motion:'down'}]},
  "Hollow Body Hold": { steps:[
    {t:'Press Back Flat', c:'Lower back pinned to the floor.', motion:'start'},
    {t:'Lift & Extend', c:'Shoulder blades and legs off the floor.', motion:'up'},
    {t:'Hold', c:'Full core tension, banana shape.', motion:'up'}]},
  "Towel Curl — Slow Negative": { steps:[
    {t:'Lean Back', c:'Same towel setup as Tuesday.', motion:'start'},
    {t:'Curl Up', c:'Bend the elbows, curl toward the door.', motion:'up'},
    {t:'4-Second Lower', c:'The slow negative is the whole point.', motion:'down'}]},
  "Chin-Up (Doorframe or Bar)": { steps:[
    {t:'Hang Extended', c:'Underhand grip, shoulder-width.', motion:'start'},
    {t:'Pull Chin Over', c:'Drive elbows down and back.', motion:'up'},
    {t:'Lower Control', c:'No dropping — control every rep.', motion:'down'}]},
  "Backpack Curl — Heavy": { steps:[
    {t:'Load It Up', c:'Heavier than Tuesday\u2019s session.', motion:'start'},
    {t:'Curl Up', c:'Squeeze hard at the top.', motion:'up'},
    {t:'Lower Slow', c:'3 seconds down, stay controlled.', motion:'down'}]},
  "Table Row — Feet Elevated": { steps:[
    {t:'Feet on Chair', c:'Body more horizontal, heavier load.', motion:'start'},
    {t:'Pull Up Hard', c:'Chest to the table edge.', motion:'up'},
    {t:'Lower Control', c:'3-second descent.', motion:'down'}]},
  "Superman — Pause Reps": { steps:[
    {t:'Lift Together', c:'Chest, arms, and legs off the floor.', motion:'start'},
    {t:'Hold 3 Seconds', c:'Pause eliminates momentum.', motion:'up'},
    {t:'Lower Slow', c:'Pure back muscle, no swinging.', motion:'down'}]},
  "Reverse Snow Angel — Drop Set": { steps:[
    {t:'15 Reps', c:'Full range reverse snow angels.', motion:'start'},
    {t:'Hold at Top', c:'Arms and chest up, hold to failure.', motion:'up'},
    {t:'Rest & Repeat', c:'90 seconds rest, go again.', motion:'down'}]},
  "Bulgarian Split Squat": { steps:[
    {t:'Foot Behind', c:'Back foot resting on a chair.', motion:'start'},
    {t:'Lower Down', c:'Back knee skims the floor.', motion:'down'},
    {t:'Drive Through', c:'Push through the front heel.', motion:'up'}]},
  "Single-Leg Romanian Deadlift": { steps:[
    {t:'Stand Tall', c:'Slight bend in the standing knee.', motion:'start'},
    {t:'Hinge Forward', c:'Free leg extends back for balance.', motion:'down'},
    {t:'Drive Hips Forward', c:'Squeeze the glute to stand back up.', motion:'up'}]},
  "Jump Squat — Finisher": { steps:[
    {t:'Load the Squat', c:'Bend the knees, sit back.', motion:'start'},
    {t:'Explode Up', c:'Jump straight up, arms drive up.', motion:'up'},
    {t:'Land Soft', c:'Bent knees, straight into the next rep.', motion:'down'}]},
  "Standing Calf Raise": { steps:[
    {t:'Feet Down', c:'Hip-width stance.', motion:'start'},
    {t:'Rise Up', c:'Onto the balls of your feet, hold 1-2 sec.', motion:'up'},
    {t:'Lower Slow', c:'3 seconds on the way down.', motion:'down'}]}
};

/* ---------- CARD BUILDER ---------- */
function motionBadge(motion){
  if (motion === 'up')   return { cls:'arrow-up',   glyph:'&#8593;' };   // ↑
  if (motion === 'down') return { cls:'arrow-down', glyph:'&#8595;' };   // ↓
  return { cls:'start-dot', glyph:'&#9679;' };                          // ●
}

function imgTagWithFallback(slug, stepIdx, altText, stockUrl){
  const id = `img-${slug}-${stepIdx}-${Math.random().toString(36).slice(2,7)}`;
  const attempts = IMG_EXT_FALLBACKS.map(ext => `${IMG_DIR}${slug}-${stepIdx}.${ext}`);
  if (stockUrl) attempts.push(stockUrl); // last resort before placeholder
  const first = attempts.shift();
  return `<img id="${id}" class="demo-photo" alt="${altText}" src="${first}"
      data-fallbacks='${JSON.stringify(attempts)}' data-tried="0"
      onerror="handleImgError(this)"/>`;
}

// Global error handler: walks the fallback list (local extensions, then
// the stock photo if one exists), then gives up gracefully to the placeholder.
function handleImgError(img){
  const fallbacks = JSON.parse(img.getAttribute('data-fallbacks') || '[]');
  let tried = parseInt(img.getAttribute('data-tried') || '0', 10);
  if (tried < fallbacks.length){
    img.src = fallbacks[tried];
    img.setAttribute('data-tried', tried + 1);
  } else {
    showPlaceholder(img);
  }
}

function showPlaceholder(img){
  img.onerror = null;
  const stage = img.closest('.demo-stage');
  if (stage) stage.classList.add('img-missing');
}

const VIDEO_DEMOS = {
  "Superman Hold": { id: "LZoWdePF1NQ", creator: "Live Lean TV Daily Exercises", title: "How To Do A Superman Hold" },
  "Reverse Snow Angel": { id: "0qLP2RNKX4A", creator: "Leap Fitness", title: "How to Do: Reverse Snow Angels" },
  "Table Row (Inverted Row)": { id: "GxwoqgbaBfQ", creator: "Danish Edit Explore", title: "Train Your Back at Home with Table Inverted Rows" },
  "Diamond Push-Up": { id: "nlNLm1hkErk", creator: "Xavrionx", title: "Tutorial for Perfect Diamond Push-Ups" },
  "Chair Dips": { id: "QW1I_GkWi4g", creator: "Buck Buhler Fitness & Wellness", title: "Chair Dips (Triceps)" },
  "Plank": { id: "Fcbw82ykBvY", creator: "Leap Fitness", title: "How to Do: Plank" },
  "Crunches": { id: "RUNrHkbP4Pc", creator: "Leap Fitness", title: "How to Do: Abdominal Crunches" },
  "Leg Raises": { id: "dGKbTKLnym4", creator: "Leap Fitness", title: "How to Do: Leg Raises" },
  "Towel Curl (Door Curl)": { id: "hgb-n75URPw", creator: "lawrence templar", title: "Doorway Bicep Curls with a Towel" },
  "Backpack Curl": { id: "EXnqsY5muq0", creator: "wearecult", title: "Backpack Biceps Curl" },
  "Pike Push-Up": { id: "-fN_MqhdPls", creator: "Kareem Fitness", title: "Learn Pike Push-Ups Step by Step" },
  "Lateral Raise (Backpack or Bottles)": { id: "XPPfnSEATJA", creator: "National Academy of Sports Medicine", title: "How to Do a Dumbbell Lateral Raise" },
  "Wall Handstand Hold": { id: "shQpgTONuos", creator: "Rohit Bodyweight Warrior", title: "Easy Wall Handstand Tutorial" },
  "Decline Push-Up": { id: "M_AGNlgHzKg", creator: "Daily Workout Official", title: "Decline Push-Ups: How to Perform Them Correctly" },
  "Standard Push-Up": { id: "WDIpL0pjun0", creator: "National Academy of Sports Medicine", title: "How to Do a Push-Up" },
  "Wide Push-Up": { id: "TqEufcj8SOs", creator: "Death Fitness", title: "How to Do Wide Pushups Properly" },
  "Elevated Pike Push-Up": { id: "BNWUYJCI3Io", creator: "GymPanda", title: "Elevated Pike Push-Up: Perfect Form" },
  "Front Raise (Water Bottles)": { id: "F0Sf87U-K5k", creator: "Amr's Health Hub", title: "Train Shoulders with Water Bottles" },
  "Wall Handstand Push-Up (Partial)": { id: "Yli2BiOUANI", creator: "Dominic Labelle", title: "Wall Handstand Push-Ups: Short Range of Motion" },
  "Tricep Push-Up (Elbows In)": { id: "0LZF3OY87uU", creator: "FIT.nl", title: "Close Grip Push-Up: Proper Form" },
  "Bicycle Crunches": { id: "VaL7XWK3MVE", creator: "Medbridge", title: "How to Do a Bicycle Crunch" },
  "Reverse Crunch": { id: "XY8KzdDcMFg", creator: "PureGym", title: "How to Do a Reverse Crunch" },
  "Russian Twists": { id: "vlsq-839C0U", creator: "Peloton", title: "Russian Twist Exercise Tutorial" },
  "Hollow Body Hold": { id: "0yPin8hSc8o", creator: "FIT.nl", title: "Hollow Body Hold: Proper Form" },
  "Chin-Up (Doorframe or Bar)": { id: "2S8jyfcnBmc", creator: "JayTaylor", title: "Chin Ups: Exercise Demo" },
  "Table Row — Feet Elevated": { id: "NeaONUgPfWk", creator: "E3 Rehab Exercise Library", title: "Feet Elevated Inverted Row" },
  "Bulgarian Split Squat": { id: "onUcwAbmZF8", creator: "Barbell Logic", title: "The Bulgarian Split Squat: How To" },
  "Single-Leg Romanian Deadlift": { id: "6pEL3KxnlEo", creator: "National Academy of Sports Medicine", title: "How to Do a Single-Leg Romanian Deadlift" },
  "Jump Squat — Finisher": { id: "txLE-jOCEsc", creator: "Leap Fitness", title: "How to Do: Jumping Squats" },
  "Standing Calf Raise": { id: "TQg5RUBb4Rw", creator: "G2 FIT", title: "Standing Calf Raise" }
};

const DEMO_CLIP_ALIASES = {
  "Chair Dips — Slow Tempo": "Chair Dips",
  "Diamond Push-Up — To Failure": "Diamond Push-Up",
  "Towel Curl — Slow Negative": "Towel Curl (Door Curl)",
  "Backpack Curl — Heavy": "Backpack Curl",
  "Superman — Pause Reps": "Superman Hold",
  "Reverse Snow Angel — Drop Set": "Reverse Snow Angel"
};

function buildDemoCard(card){
  const label = (card.getAttribute('aria-label') || '').replace(' demonstration','').trim();
  const clip = VIDEO_DEMOS[label] || VIDEO_DEMOS[DEMO_CLIP_ALIASES[label]];
  if (!clip) return;

  const sourceUrl = `https://www.youtube.com/watch?v=${clip.id}`;
  const embedUrl = `https://www.youtube-nocookie.com/embed/${clip.id}?autoplay=1&mute=1&loop=1&playlist=${clip.id}&controls=1&playsinline=1&rel=0`;
  card.innerHTML = `
    <div class="demo-card">
      <div class="demo-copy"><strong>${label}</strong><span>${clip.title} · ${clip.creator}</span></div>
      <div class="demo-video">
        <iframe src="${embedUrl}" title="${label} exercise demonstration" loading="lazy"
          allow="autoplay; encrypted-media; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
      </div>
      <a class="demo-source" href="${sourceUrl}" target="_blank" rel="noopener noreferrer">Watch source on YouTube <span aria-hidden="true">&#8599;</span></a>
    </div>`;
}

function renderAllDemos(){
  document.querySelectorAll('.demo-card').forEach(buildDemoCard);
}

/* Prints every filename the site expects, grouped by exercise —
   run slugManifest() in the browser console to get the full list. */
function slugManifest(){
  let out = '';
  Object.keys(STEP_DATA).forEach(name => {
    const slug = slugify(name);
    out += `${name}\n  ${slug}-1.jpg   (setup)\n  ${slug}-2.jpg   (working phase)\n  ${slug}-3.jpg   (return)\n\n`;
  });
  console.log(out);
  return out;
}

document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
window.addEventListener('load', () => { openHashDay(); renderAllDemos(); });