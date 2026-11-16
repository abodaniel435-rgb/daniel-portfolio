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
