export function initStickyNavbar({ threshold = 16 } = {}) {
  const nav = document.getElementById('siteNav');
  if (!nav) return;

  const apply = () => {
    if (window.scrollY > threshold) {
      nav.classList.add('is-stuck');
    } else {
      nav.classList.remove('is-stuck');
    }
  };

  // start + nasłuch
  apply();
  window.addEventListener('scroll', apply, { passive: true });
}
