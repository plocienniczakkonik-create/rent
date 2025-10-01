// BACK TO TOP – szybki init (opcjonalnie, jeśli chcesz go zachować w teście)
(function initBackToTop() {
  const btn = document.getElementById("backToTop");
  if (!btn) return;
  const showAfter = 10;
  const toggleBtn = () => {
    if (window.scrollY > showAfter) btn.classList.add("show");
    else btn.classList.remove("show");
  };
  window.addEventListener("scroll", toggleBtn, { passive: true });
  btn.addEventListener("click", () => {
    if ("scrollBehavior" in document.documentElement.style) {
      window.scrollTo({ top: 0, behavior: "smooth" });
    } else {
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    }
  });
  toggleBtn();
})();

// STICKY NAVBAR – bez importów
(function initStickyNavbar() {
  const nav = document.getElementById("siteNav");
  if (!nav) {
    console.warn("stickyNavbar: #siteNav not found");
    return;
  }
  const apply = () => {
    const shouldStick = window.scrollY > 16;
    nav.classList.toggle("is-stuck", shouldStick);
  };
  apply();
  window.addEventListener("scroll", apply, { passive: true });
})();
