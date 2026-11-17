const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const revealItems = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window && !reduceMotion) {
  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12 });
  revealItems.forEach((item) => revealObserver.observe(item));
} else {
  revealItems.forEach((item) => item.classList.add('is-visible'));
}

/* script.js writes the real proof numbers into the DOM; this only decides how they
   arrive. The final value is already in place, so a page that never runs this block
   still reads correctly. */
if (!reduceMotion) {
  document.querySelectorAll('[data-count]').forEach((node) => {
    const target = Number(node.dataset.count);
    const start = performance.now();
    const step = (now) => {
      const t = Math.min((now - start) / 900, 1);
      // A one-digit target keeps its own width (2 -> "2"), a padded target keeps the
      // zero ("01" -> "00", "01"), so the row never jitters in length as it counts.
      const format = (value) => (node.dataset.count.startsWith('0') && value < 10 ? '0' + value : String(value));
      node.textContent = format(Math.round(target * (1 - Math.pow(1 - t, 3))));
      if (t < 1) window.requestAnimationFrame(step);
      else node.textContent = node.dataset.count;
    };
    window.requestAnimationFrame(step);
  });
}

// The plate's keyframe owns `transform`, so the scroll offset rides in on `--sy`,
// which the `translate` property composes with it instead of replacing.
const plate = document.querySelector('.bg-plate');
if (plate && !reduceMotion) {
  let queued = false;
  const track = () => {
    plate.style.setProperty('--sy', (-window.scrollY * 0.06).toFixed(1) + 'px');
    queued = false;
  };
  window.addEventListener('scroll', () => {
    if (queued) return;
    queued = true;
    window.requestAnimationFrame(track);
  }, { passive: true });
  track();
}
