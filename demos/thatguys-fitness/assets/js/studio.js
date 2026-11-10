/* ============================================================
   THATGUYS FITNESS — AI COACH STUDIO
   Frame-by-frame exercise demonstrations with step controls,
   full coaching notes and an optional browser voice guide.
   No dependencies. No fake video: every frame is a real,
   separately generated still of the same AI instructor.
   ============================================================ */

(function () {
  'use strict';

  var IMG = 'assets/coaches/';
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var EXERCISES = [
    {
      id: 'pushup',
      name: 'Push-Up',
      pattern: 'push',
      patternLabel: 'Push',
      muscles: 'Chest · Triceps · Front delts · Core',
      equipment: 'None — floor only',
      dose: '3 sets × 8–15 reps',
      rest: '60–90 s rest',
      tempo: '2 s down · 1 s pause · 1 s up',
      summary: 'The benchmark upper-body press. It trains the chest, triceps and shoulders while the core works overtime to keep the body rigid.',
      start: 'Kneel down and place your hands slightly wider than shoulder-width, fingers spread and pointing forward. Step your feet back to hip width, squeeze your glutes and brace your abs so your body forms one straight line from heels to head. Look at the floor about 30 cm in front of your hands.',
      frames: [
        { label: 'Set up', caption: 'Hands under shoulders, body in one straight line, core braced.' },
        { label: 'Lower', caption: 'Chest travels toward the floor, elbows about 45° from the ribs, hips level.' },
        { label: 'Press', caption: 'Drive the floor away until the arms are straight, ribs still down.' }
      ],
      steps: [
        { title: 'Set up', text: 'Hands under the shoulders, feet hip-width, glutes and abs switched on. Head in line with the spine.' },
        { title: 'Lower with control', text: 'Bend the elbows back at roughly 45°, not straight out. Chest stops a fist above the floor and the hips do not drop.' },
        { title: 'Press back up', text: 'Push the floor away, finish with straight arms and shoulders stacked over the hands. Do not let the ribs flare.' }
      ],
      breath: 'Inhale on the way down, hold the brace for a beat at the bottom, exhale as you press up. Never hold your breath for the whole set.',
      mistakes: [
        'Hips sagging or piking — the body stops being one line.',
        'Elbows flared straight out to 90°, which strains the shoulder.',
        'Head craning forward to “reach” the floor instead of chest lowering.',
        'Shallow half reps that never train the bottom range.'
      ],
      safety: [
        'Drop to your knees the moment the hips start sagging — a clean knee push-up beats a sloppy full one.',
        'Stop if you feel sharp wrist or shoulder pain. Wrist pain often improves with hands on dumbbells or a book.',
        'Keep the neck neutral; do not push the chin toward the chest.',
        'Leave 1–2 reps in reserve on every set.'
      ]
    },
    {
      id: 'squat',
      name: 'Bodyweight Squat',
      pattern: 'legs',
      patternLabel: 'Legs',
      muscles: 'Quads · Glutes · Adductors · Core',
      equipment: 'None — floor only',
      dose: '3 sets × 12–20 reps',
      rest: '60–90 s rest',
      tempo: '3 s down · 1 s pause · 1 s up',
      summary: 'The foundation of every leg day. The squat teaches you to sit into your hips, keep your knees tracking and stand up under control.',
      start: 'Stand with your feet shoulder-width apart and toes turned out slightly. Weight spread across the whole foot — big toe, little toe and heel. Arms relaxed at your sides, chest up, eyes forward.',
      frames: [
        { label: 'Stand tall', caption: 'Feet shoulder-width, toes slightly out, weight through the whole foot.' },
        { label: 'Sit back', caption: 'Hips travel back and down, knees track over the toes, back stays flat.' },
        { label: 'Full depth', caption: 'Thighs parallel to the floor, chest lifted, heels planted.' }
      ],
      steps: [
        { title: 'Stand tall', text: 'Feet shoulder-width, toes slightly out, ribs stacked over the pelvis, weight through the whole foot.' },
        { title: 'Sit back and down', text: 'Start the movement by pushing your hips back, then bend the knees. Let the arms come forward as a counterweight and keep the chest proud.' },
        { title: 'Stand up', text: 'Push the floor away through the whole foot and squeeze the glutes at the top. Stop just short of locking the knees.' }
      ],
      breath: 'Breathe in at the top, brace, and exhale as you stand up. If you are doing high reps, take a breath at the top of every rep.',
      mistakes: [
        'Knees caving inward as you stand up.',
        'Heels lifting off the floor — usually a depth or ankle-mobility issue.',
        'Lower back rounding at the bottom instead of the hips folding.',
        'Cutting depth to a quarter squat and calling it a rep.'
      ],
      safety: [
        'Only go as deep as you can keep a flat back and planted heels.',
        'If balance is a problem, hold a doorframe or squat to a chair, touching it lightly before standing.',
        'Knee discomfort: reduce depth, slow down, and keep the knees tracking over the middle toes.',
        'Never bounce out of the bottom.'
      ]
    },
    {
      id: 'lunge',
      name: 'Reverse Lunge',
      pattern: 'legs',
      patternLabel: 'Legs',
      muscles: 'Quads · Glutes · Hamstrings · Balance',
      equipment: 'None — floor only',
      dose: '3 sets × 10 per leg',
      rest: '60 s rest',
      tempo: '2 s step back · 1 s pause · 1 s drive up',
      summary: 'A single-leg strength builder that also exposes side-to-side differences. Stepping backwards is easier on the knees than a forward lunge.',
      start: 'Stand tall with feet hip-width apart, hands on your hips. Chest up, ribs down, and eyes fixed on a point in front of you for balance.',
      frames: [
        { label: 'Stand tall', caption: 'Feet hip-width, hands on hips, chest lifted.' },
        { label: 'Step back', caption: 'Rear knee lowers straight down, front shin vertical, torso tall.' },
        { label: 'Knee hover', caption: 'Front thigh parallel, rear knee just above the floor.' }
      ],
      steps: [
        { title: 'Step back', text: 'Take a confident step straight back and land on the ball of the rear foot. Keep the front foot planted.' },
        { title: 'Lower straight down', text: 'Both knees bend together. The rear knee drops toward the floor while the front shin stays close to vertical and the torso stays tall.' },
        { title: 'Drive up', text: 'Push through the front heel to return to standing, bringing the rear foot back under your hips. Finish fully upright before the next rep.' }
      ],
      breath: 'Inhale as you step back and lower, exhale as you drive up on the front leg. Keep the core braced so the torso does not wobble.',
      mistakes: [
        'Front knee collapsing inward on the way up.',
        'Leaning the torso forward over the front thigh.',
        'Taking too short a step, which crowds the front knee.',
        'Letting the rear knee crash into the floor.'
      ],
      safety: [
        'Hold a chair or wall lightly if balance is new — that is training, not cheating.',
        'Do the movement on a mat so the rear knee has a soft landing.',
        'Stop if the front knee or hip pinches; reduce depth and range instead of pushing through it.',
        'Complete all reps on one leg, then switch sides.'
      ]
    },
    {
      id: 'plank',
      name: 'Forearm Plank',
      pattern: 'core',
      patternLabel: 'Core',
      muscles: 'Abs · Obliques · Shoulders · Glutes',
      equipment: 'Exercise mat (towel works)',
      dose: '3 sets × 30–60 s hold',
      rest: '45 s rest',
      tempo: 'Hold still · rebuild the brace every 10 s',
      summary: 'An isometric core hold that teaches your trunk to resist extension. Quality beats duration every single time.',
      start: 'Kneel on a mat and place your forearms down with elbows directly beneath the shoulders, hands in loose fists. Tuck your toes, then lift your knees so your body forms a straight line from heels to head.',
      frames: [
        { label: 'Set up', caption: 'Forearms down, elbows under shoulders, knees still on the mat.' },
        { label: 'Extend', caption: 'Legs straighten, toes tucked, hips lift into one line.' },
        { label: 'Hold', caption: 'Ribs down, glutes tight, steady breathing.' }
      ],
      steps: [
        { title: 'Set the frame', text: 'Elbows under the shoulders, forearms flat, hands in loose fists, knees resting on the mat.' },
        { title: 'Build the line', text: 'Tuck the toes and lift the knees. Squeeze glutes, tuck ribs toward the hips and pull the shoulders away from the ears.' },
        { title: 'Hold and breathe', text: 'Hold the position with the hips level. Every 10 seconds, re-check: ribs down, glutes on, neck long.' }
      ],
      breath: 'Breathe normally and slowly — in through the nose for 3 seconds, out for 3. Holding your breath is the fastest way to lose the brace.',
      mistakes: [
        'Hips sagging so the lower back takes the load.',
        'Hips piked high like a tent.',
        'Shoulders shrugged up around the ears.',
        'Holding the breath and tensing the neck.'
      ],
      safety: [
        'If the lower back aches, you have lost the brace — drop to your knees or end the set.',
        'Keep the neck neutral and look at the mat just ahead of your hands.',
        'Build up in 10-second blocks rather than chasing a minute on day one.',
        'Stop if your shoulders or elbows hurt on the hard floor.'
      ]
    },
    {
      id: 'bridge',
      name: 'Glute Bridge',
      pattern: 'hinge',
      patternLabel: 'Hinge',
      muscles: 'Glutes · Hamstrings · Core',
      equipment: 'Exercise mat (towel works)',
      dose: '3 sets × 12–15 reps',
      rest: '60 s rest',
      tempo: '1 s up · 2 s squeeze · 2 s down',
      summary: 'The simplest way to train hip extension without loading the spine. It wakes up glutes that hours of sitting have switched off.',
      start: 'Lie on your back on a mat with knees bent and feet flat, heels about a hand span from your glutes. Arms rest at your sides, palms down. Tuck your chin slightly and let the lower back keep its natural small arch.',
      frames: [
        { label: 'Set up', caption: 'On your back, knees bent, feet flat, palms down.' },
        { label: 'Lift', caption: 'Hips press up, shins vertical, shoulders anchored.' },
        { label: 'Squeeze', caption: 'Full hip extension, glutes tight, ribs down.' }
      ],
      steps: [
        { title: 'Set up', text: 'Lie on your back, knees bent, feet flat and hip-width apart. Heels close enough that your fingertips can brush them.' },
        { title: 'Drive the hips up', text: 'Push through the heels and lift the hips until your body forms a straight line from knees to shoulders. Do not arch the lower back to get higher.' },
        { title: 'Squeeze and lower', text: 'Hold the top for two seconds with the glutes tight, then lower with control until the hips lightly touch the mat.' }
      ],
      breath: 'Exhale as you lift and squeeze, inhale as you lower. Keep the ribs down so the movement comes from the hips, not the lower back.',
      mistakes: [
        'Over-arching the lower back at the top instead of extending the hips.',
        'Pushing through the toes and cramping the hamstrings.',
        'Letting the knees splay outward.',
        'Bouncing the hips off the floor between reps.'
      ],
      safety: [
        'If you feel it in your lower back rather than your glutes, lower the height and tuck the ribs down.',
        'Stop if the hamstrings cramp — bring the heels slightly closer and reduce the range.',
        'Keep the neck relaxed on the mat, chin slightly tucked.',
        'Use a folded towel under the tailbone if the floor is hard.'
      ]
    },
    {
      id: 'row',
      name: 'Bent-Over Dumbbell Row',
      pattern: 'pull',
      patternLabel: 'Pull',
      muscles: 'Lats · Mid-back · Rear delts · Biceps',
      equipment: '2 dumbbells (or 2 filled water bottles)',
      dose: '3 sets × 8–12 reps',
      rest: '75 s rest',
      tempo: '1 s pull · 1 s squeeze · 2 s lower',
      summary: 'The main horizontal pull in the plan. It balances all the pressing and builds the mid-back that posture depends on.',
      start: 'Stand with a dumbbell in each hand, palms facing each other. Soften the knees, push the hips back and hinge forward to about 45°, keeping a flat back. Let the dumbbells hang directly below the shoulders.',
      frames: [
        { label: 'Hinge and hang', caption: 'Hips back, flat back, dumbbells under the shoulders.' },
        { label: 'Drive elbows', caption: 'Elbows pull back past the ribs, back stays flat.' },
        { label: 'Squeeze', caption: 'Dumbbells at the lower ribs, shoulder blades pinched.' }
      ],
      steps: [
        { title: 'Hinge into position', text: 'Hips back, chest forward, flat back at roughly 45°. Dumbbells hang under the shoulders with a neutral grip.' },
        { title: 'Pull to the ribs', text: 'Drive the elbows straight back past your sides, bringing the dumbbells toward your lower ribs. Do not shrug.' },
        { title: 'Squeeze and lower', text: 'Pinch the shoulder blades at the top for a second, then lower slowly until the arms are straight. The torso does not move.' }
      ],
      breath: 'Exhale as you pull, inhale as you lower. Breathe into the belly, not the chest, so the back stays braced.',
      mistakes: [
        'Rounding the upper back as you pull.',
        'Swinging the torso to heave the weight up.',
        'Shrugging the shoulders toward the ears instead of pulling the elbows back.',
        'Standing too upright, which turns it into a shrug.'
      ],
      safety: [
        'Stop the set when you can no longer keep a flat back — that is the real failure point.',
        'Use lighter weight and perfect reps rather than heavy sloppy ones.',
        'If the lower back fatigues first, support one hand on a chair or bench for a single-arm row.',
        'Keep the neck long and eyes on the floor ahead, not up at a mirror.'
      ]
    },
    {
      id: 'press',
      name: 'Standing Shoulder Press',
      pattern: 'push',
      patternLabel: 'Push',
      muscles: 'Shoulders · Triceps · Upper chest · Core',
      equipment: '2 dumbbells (or 2 filled water bottles)',
      dose: '3 sets × 8–12 reps',
      rest: '75 s rest',
      tempo: '2 s up · 1 s pause · 2 s down',
      summary: 'A vertical press that builds shoulder strength and teaches the core to keep the ribcage stacked while the arms work overhead.',
      start: 'Stand with feet hip-width apart and knees soft. Hold a dumbbell in each hand at shoulder height, elbows slightly in front of the body and palms facing forward. Brace the abs, tuck the ribs down and squeeze the glutes.',
      frames: [
        { label: 'Rack', caption: 'Dumbbells at shoulder height, elbows slightly forward.' },
        { label: 'Press', caption: 'Elbows drive up, wrists stacked over elbows, ribs stay down.' },
        { label: 'Lockout', caption: 'Arms overhead, biceps near the ears, body tall.' }
      ],
      steps: [
        { title: 'Rack the dumbbells', text: 'Dumbbells at shoulder height with palms forward, wrists stacked over the elbows. Ribs down, glutes on.' },
        { title: 'Press overhead', text: 'Drive both dumbbells up and slightly back, letting the elbows travel under the wrists. Keep the torso still — no leaning back.' },
        { title: 'Lower with control', text: 'Bring the dumbbells back to shoulder height over two seconds, stopping just before the elbows drop below the ribs.' }
      ],
      breath: 'Inhale and brace before the press, exhale as the arms extend overhead, inhale as you lower. Keep the ribs down so the lower back stays protected.',
      mistakes: [
        'Leaning back and turning the press into an incline press.',
        'Flaring the elbows straight out to the sides.',
        'Pressing behind the head instead of over it.',
        'Stopping the lockout halfway.'
      ],
      safety: [
        'If your lower back arches, the weight is too heavy or the ribs are not down.',
        'Press seated with back support if standing feels unstable.',
        'Do not push through a pinching sensation at the front or top of the shoulder.',
        'Lower the dumbbells to the thighs before letting go.'
      ]
    },
    {
      id: 'rdl',
      name: 'Dumbbell Romanian Deadlift',
      pattern: 'hinge',
      patternLabel: 'Hinge',
      muscles: 'Hamstrings · Glutes · Lower back · Grip',
      equipment: '2 dumbbells (or 2 filled water bottles)',
      dose: '3 sets × 8–12 reps',
      rest: '90 s rest',
      tempo: '3 s down · 1 s pause · 1 s up',
      summary: 'A hip hinge that loads the hamstrings through a long range. It is the best teacher of the “push the hips back” pattern that protects the lower back.',
      start: 'Stand tall with a dumbbell in each hand in front of your thighs, overhand grip. Feet hip-width, knees soft, shoulders pulled back and down, ribs stacked over the pelvis.',
      frames: [
        { label: 'Stand tall', caption: 'Dumbbells at the thighs, shoulders packed, ribs down.' },
        { label: 'Hinge', caption: 'Hips travel straight back, dumbbells slide down the legs.' },
        { label: 'Stretch', caption: 'Torso near parallel, flat back, hamstrings loaded.' }
      ],
      steps: [
        { title: 'Stand tall', text: 'Dumbbells in front of the thighs, knees soft, shoulders back and ribs down. Take a breath and brace.' },
        { title: 'Hinge the hips back', text: 'Push the hips straight back and let the torso tip forward. The dumbbells slide down the front of the legs and the knees only bend slightly.' },
        { title: 'Stand up', text: 'When you feel a strong hamstring stretch and cannot go further without rounding, drive the hips forward to stand tall and squeeze the glutes.' }
      ],
      breath: 'Inhale at the top, hold the brace as you hinge down, exhale as you drive the hips forward to stand. Never let the breath out at the bottom.',
      mistakes: [
        'Turning it into a squat by bending the knees too much.',
        'Rounding the upper back to reach lower.',
        'Letting the dumbbells drift away from the legs.',
        'Leaning back and hyperextending at the top.'
      ],
      safety: [
        'Depth is dictated by a flat back, not by how low you can reach.',
        'Stop the set as soon as you feel the back round or the grip fail.',
        'Keep the neck neutral and look at a point on the floor 2 metres ahead.',
        'Start light — the hamstrings take more load than people expect.'
      ]
    }
  ];

  /* ---------------- DOM refs ---------------- */
  var grid = document.getElementById('studioGrid');
  var countEl = document.getElementById('studioCount');
  var filters = Array.prototype.slice.call(document.querySelectorAll('.chip[data-filter]'));
  var dialog = document.getElementById('studioPlayer');
  var closeBtn = document.getElementById('playerClose');
  var stageEl = document.getElementById('playerStage');
  var frameEls = Array.prototype.slice.call(document.querySelectorAll('.stage-frame'));
  var badgeRec = document.getElementById('stageRec');
  var captionIdx = document.getElementById('captionIdx');
  var captionTitle = document.getElementById('captionTitle');
  var captionText = document.getElementById('captionText');
  var timelineEl = document.getElementById('playerTimeline');
  var stepPill = document.getElementById('stepPill');
  var playBtn = document.getElementById('playToggle');
  var playIcon = document.getElementById('playIcon');
  var playLabel = document.getElementById('playLabel');
  var prevBtn = document.getElementById('prevStep');
  var nextBtn = document.getElementById('nextStep');
  var loopToggle = document.getElementById('loopToggle');
  var speedSel = document.getElementById('speedSelect');
  var voiceBtn = document.getElementById('voiceToggle');
  var elTitle = document.getElementById('playerTitle');
  var elPattern = document.getElementById('playerPattern');
  var elSummary = document.getElementById('playerSummary');
  var elMuscles = document.getElementById('playerMuscles');
  var elEquipment = document.getElementById('playerEquipment');
  var elDose = document.getElementById('playerDose');
  var elTempo = document.getElementById('playerTempo');
  var elStart = document.getElementById('playerStart');
  var elSteps = document.getElementById('playerSteps');
  var elBreath = document.getElementById('playerBreath');
  var elMistakes = document.getElementById('playerMistakes');
  var elSafety = document.getElementById('playerSafety');

  var state = { list: EXERCISES, filter: 'all', ex: null, step: 0, playing: false, timer: null, speed: 1, speaking: false };
  var BASE_MS = 1750;

  /* ---------------- Helpers ---------------- */
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function frameSrc(ex, i) { return IMG + ex.id + '-0' + (i + 1) + '.webp'; }
  function frameAlt(ex, i) {
    return 'AI-generated fitness coach demonstrating the ' + ex.name + ' — phase ' + (i + 1) + ' of 3, ' + ex.frames[i].label.toLowerCase() + '.';
  }

  /* ---------------- Card grid ---------------- */
  function cardHTML(ex, i) {
    return '' +
      '<button class="studio-card" type="button" data-open="' + ex.id + '" style="animation-delay:' + (i * 45) + 'ms"' +
      ' aria-label="Open the ' + esc(ex.name) + ' demonstration">' +
        '<span class="card-media">' +
          '<img src="' + frameSrc(ex, 0) + '" alt="' + esc(frameAlt(ex, 0)) + '" width="900" height="900" loading="lazy">' +
          '<span class="card-pattern">' + esc(ex.patternLabel) + '</span>' +
          '<span class="card-play" aria-hidden="true">' + playSVG(18) + '</span>' +
        '</span>' +
        '<span class="card-body">' +
          '<h3>' + esc(ex.name) + '</h3>' +
          '<p class="card-muscles">' + esc(ex.muscles) + '</p>' +
          '<span class="card-foot"><span class="card-dose">' + esc(ex.dose) + '</span><span class="card-steps">3-phase demo</span></span>' +
        '</span>' +
      '</button>';
  }

  function playSVG(size) {
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72c0 .8.87 1.3 1.56.88l11-6.86a1.03 1.03 0 0 0 0-1.76l-11-6.86A1.03 1.03 0 0 0 8 5.14Z"/></svg>';
  }
  function pauseSVG(size) {
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4h3.5v16H7zM13.5 4H17v16h-3.5z"/></svg>';
  }

  function renderGrid() {
    state.list = state.filter === 'all' ? EXERCISES : EXERCISES.filter(function (e) { return e.pattern === state.filter; });
    grid.innerHTML = state.list.map(cardHTML).join('');
    countEl.textContent = state.list.length + (state.list.length === 1 ? ' demonstration' : ' demonstrations');
  }

  /* ---------------- Player ---------------- */
  function renderInfo(ex) {
    elTitle.textContent = ex.name;
    elPattern.textContent = ex.patternLabel + ' pattern';
    elSummary.textContent = ex.summary;
    elMuscles.textContent = ex.muscles;
    elEquipment.textContent = ex.equipment;
    elDose.textContent = ex.dose + ' · ' + ex.rest;
    elTempo.textContent = ex.tempo;
    elStart.textContent = ex.start;
    elBreath.textContent = ex.breath;
    elMistakes.innerHTML = ex.mistakes.map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('');
    elSafety.innerHTML = ex.safety.map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('');
    elSteps.innerHTML = ex.steps.map(function (s, i) {
      return '<li data-step="' + i + '"><span><strong>' + esc(s.title) + '</strong><span>' + esc(s.text) + '</span></span></li>';
    }).join('');
    timelineEl.innerHTML = ex.frames.map(function (f, i) {
      return '<button type="button" data-jump="' + i + '">' + (i + 1) + '. ' + esc(f.label) + '</button>';
    }).join('');

    frameEls.forEach(function (img, i) {
      img.src = frameSrc(ex, i);
      img.alt = frameAlt(ex, i);
      img.classList.toggle('is-active', i === 0);
    });

    // Preload the remaining phases so stepping never flickers.
    ex.frames.forEach(function (_, i) {
      var im = new Image();
      im.src = frameSrc(ex, i);
    });
  }

  function setStep(i, opts) {
    if (!state.ex) return;
    var total = state.ex.frames.length;
    state.step = (i + total) % total;
    frameEls.forEach(function (img, k) { img.classList.toggle('is-active', k === state.step); });
    captionIdx.textContent = String(state.step + 1);
    captionTitle.textContent = state.ex.frames[state.step].label;
    captionText.textContent = state.ex.frames[state.step].caption;
    stepPill.textContent = 'Phase ' + (state.step + 1) + ' of ' + total;
    prevBtn.disabled = !loopToggle.checked && state.step === 0;
    nextBtn.disabled = !loopToggle.checked && state.step === total - 1;
    Array.prototype.forEach.call(timelineEl.children, function (btn, k) {
      if (k === state.step) btn.setAttribute('aria-current', 'step');
      else btn.removeAttribute('aria-current');
    });
    Array.prototype.forEach.call(elSteps.children, function (li, k) {
      li.classList.toggle('is-current', k === state.step);
    });
  }

  function tick() {
    if (!state.ex || !state.playing) return;
    var total = state.ex.frames.length;
    var next = state.step + 1;
    if (next >= total) {
      if (!loopToggle.checked) { setPlaying(false); return; }
      next = 0;
    }
    setStep(next);
    state.timer = window.setTimeout(tick, BASE_MS / state.speed);
  }

  function setPlaying(on) {
    state.playing = on;
    window.clearTimeout(state.timer);
    stageEl.classList.toggle('is-playing', on);
    playBtn.classList.toggle('primary', !on);
    playIcon.innerHTML = on ? pauseSVG(15) : playSVG(15);
    playLabel.textContent = on ? 'Pause' : 'Play';
    playBtn.setAttribute('aria-label', on ? 'Pause the demonstration' : 'Play the demonstration');
    if (on) state.timer = window.setTimeout(tick, BASE_MS / state.speed);
  }

  function openPlayer(id) {
    var ex = EXERCISES.filter(function (e) { return e.id === id; })[0];
    if (!ex) return;
    state.ex = ex;
    state.step = 0;
    state.speed = parseFloat(speedSel.value) || 1;
    renderInfo(ex);
    setStep(0);
    stopVoice();
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    document.body.classList.add('studio-lock');
    if (!reduceMotion) setPlaying(true);
    else setPlaying(false);
    dialog.scrollTop = 0;
    closeBtn.focus();
    if (history.replaceState) history.replaceState(null, '', '#' + ex.id);
  }

  function closePlayer() {
    setPlaying(false);
    stopVoice();
    document.body.classList.remove('studio-lock');
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
    if (history.replaceState) history.replaceState(null, '', location.pathname + location.search);
    var opener = document.querySelector('.studio-card[data-open="' + (state.ex ? state.ex.id : '') + '"]');
    if (opener) opener.focus();
  }

  /* ---------------- Voice guide ---------------- */
  function stopVoice() {
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    state.speaking = false;
    voiceBtn.setAttribute('aria-pressed', 'false');
    voiceBtn.querySelector('span:last-child').textContent = 'Hear the coach';
  }

  function pickVoice() {
    if (!('speechSynthesis' in window)) return null;
    var voices = window.speechSynthesis.getVoices();
    if (!voices.length) return null;
    var prefs = [/en[-_]NG/i, /en[-_]GB/i, /en[-_]ZA/i, /en[-_]US/i, /^en/i];
    for (var p = 0; p < prefs.length; p++) {
      var found = voices.filter(function (v) { return prefs[p].test(v.lang); })[0];
      if (found) return found;
    }
    return voices[0];
  }

  function readCoach() {
    if (!('speechSynthesis' in window)) return;
    if (state.speaking) { stopVoice(); return; }
    if (!state.ex) return;
    var ex = state.ex;
    var script =
      ex.name + '. ' +
      'Targets: ' + ex.muscles + '. ' +
      'Starting position. ' + ex.start + ' ' +
      'Step one. ' + ex.steps[0].title + '. ' + ex.steps[0].text + ' ' +
      'Step two. ' + ex.steps[1].title + '. ' + ex.steps[1].text + ' ' +
      'Step three. ' + ex.steps[2].title + '. ' + ex.steps[2].text + ' ' +
      'Breathing. ' + ex.breath + ' ' +
      'Watch out for: ' + ex.mistakes[0] + ' ' +
      'Recommended: ' + ex.dose + ', with ' + ex.rest + '.';

    var utter = new SpeechSynthesisUtterance(script);
    var v = pickVoice();
    if (v) utter.voice = v;
    utter.rate = 0.96;
    utter.pitch = 1;
    utter.onend = stopVoice;
    utter.onerror = stopVoice;

    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utter);
    state.speaking = true;
    voiceBtn.setAttribute('aria-pressed', 'true');
    voiceBtn.querySelector('span:last-child').textContent = 'Stop voice';
  }

  /* ---------------- Events ---------------- */
  grid.addEventListener('click', function (e) {
    var card = e.target.closest('[data-open]');
    if (card) openPlayer(card.getAttribute('data-open'));
  });

  filters.forEach(function (btn) {
    btn.addEventListener('click', function () {
      state.filter = btn.getAttribute('data-filter');
      filters.forEach(function (b) { b.setAttribute('aria-pressed', String(b === btn)); });
      renderGrid();
    });
  });

  closeBtn.addEventListener('click', closePlayer);
  dialog.addEventListener('cancel', function (e) { e.preventDefault(); closePlayer(); });
  dialog.addEventListener('click', function (e) { if (e.target === dialog) closePlayer(); });

  playBtn.addEventListener('click', function () { setPlaying(!state.playing); });
  prevBtn.addEventListener('click', function () { setPlaying(false); setStep(state.step - 1); });
  nextBtn.addEventListener('click', function () { setPlaying(false); setStep(state.step + 1); });
  timelineEl.addEventListener('click', function (e) {
    var b = e.target.closest('[data-jump]');
    if (!b) return;
    setPlaying(false);
    setStep(parseInt(b.getAttribute('data-jump'), 10));
  });

  speedSel.addEventListener('change', function () {
    state.speed = parseFloat(speedSel.value) || 1;
    if (state.playing) setPlaying(true);
  });
  loopToggle.addEventListener('change', function () { setStep(state.step); });
  voiceBtn.addEventListener('click', readCoach);

  dialog.addEventListener('keydown', function (e) {
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'select' || tag === 'input' || tag === 'textarea') return;
    if (e.code === 'Space' && tag !== 'button') { e.preventDefault(); setPlaying(!state.playing); }
    else if (e.code === 'ArrowLeft') { e.preventDefault(); setPlaying(false); setStep(state.step - 1); }
    else if (e.code === 'ArrowRight') { e.preventDefault(); setPlaying(false); setStep(state.step + 1); }
  });

  if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
      window.speechSynthesis.onvoiceschanged = function () { window.speechSynthesis.getVoices(); };
    }
  }

  /* ---------------- Boot ---------------- */
  renderGrid();
  var heroPlay = document.querySelector('[data-open-hero]');
  if (heroPlay) heroPlay.addEventListener('click', function () { openPlayer('pushup'); });
  if (location.hash.length > 1) openPlayer(location.hash.slice(1));
})();
