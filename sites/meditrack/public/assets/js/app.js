/* MediTrack — small progressive enhancements, no dependencies. */
(() => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // The plate is a fixed layer, so the scroll offset has to be applied by hand.
  const plate = document.querySelector('.bg-plate');
  if (plate && !reduce) {
    let queued = false;
    const paint = () => {
      plate.style.setProperty('--sy', `${(window.scrollY * 0.055).toFixed(1)}px`);
      queued = false;
    };
    window.addEventListener('scroll', () => {
      if (!queued) { queued = true; requestAnimationFrame(paint); }
    }, { passive: true });
    paint();
  }

  // Blocks reveal as they reach the viewport rather than all at once on load.
  // The class on <html> is what arms the hidden start state, so a browser that
  // never runs this script still shows everything (see the .js-io rules).
  // This is a guard around the observer, not an early return: the menu, booking
  // and flash enhancements below still have to attach either way.
  const items = document.querySelectorAll('main .reveal, main .panel, main .card, main .stat, main .table-wrap, main .auth-form');
  if (items.length && !reduce && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('js-io');
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.06 });
    items.forEach((el) => io.observe(el));
  }

  const toggle = document.querySelector('.menu-btn');
  const links = document.getElementById('navlinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  // Booking: day tabs → slot panels → hidden date/time inputs
  const booking = document.querySelector('[data-booking]');
  if (booking) {
    const dateInput = booking.querySelector('input[name="appointment_date"]');
    const timeInput = booking.querySelector('input[name="appointment_time"]');
    const summaryDate = booking.querySelector('[data-summary-date]');
    const summaryTime = booking.querySelector('[data-summary-time]');
    const submit = booking.querySelector('button[type="submit"]');

    const update = () => {
      if (summaryDate && dateInput) summaryDate.textContent = dateInput.value || 'Not selected';
      if (summaryTime) summaryTime.textContent = timeInput.value || 'Not selected';
      if (submit) submit.disabled = !(dateInput.value && timeInput.value);
    };

    document.querySelectorAll('.day-chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.day-chip').forEach((c) => c.classList.remove('active'));
        chip.classList.add('active');
        document.querySelectorAll('.slot-panel').forEach((p) => p.classList.remove('active'));
        const panel = document.querySelector(`.slot-panel[data-date="${chip.dataset.date}"]`);
        if (panel) panel.classList.add('active');
        if (dateInput) dateInput.value = chip.dataset.date;
        if (timeInput) timeInput.value = '';
        document.querySelectorAll('.slot').forEach((s) => s.classList.remove('active'));
        update();
      });
    });

    document.querySelectorAll('.slot').forEach((slot) => {
      slot.addEventListener('click', () => {
        document.querySelectorAll('.slot').forEach((s) => s.classList.remove('active'));
        slot.classList.add('active');
        if (timeInput) timeInput.value = slot.dataset.time;
        update();
      });
    });
    update();
  }

  // Confirm destructive actions without inline JS
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  // Auto-hide flash messages after 7s
  document.querySelectorAll('.flash').forEach((flash) => {
    setTimeout(() => {
      flash.style.transition = 'opacity .4s, transform .4s';
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-6px)';
      setTimeout(() => flash.remove(), 450);
    }, 7000);
  });
})();
