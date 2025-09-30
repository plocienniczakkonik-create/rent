// Upewnij się, że ten plik JS ładuje się z <script defer src="..."></script>
// albo wstaw kod tuż przed </body>
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('backToTop');
  if (!btn) return;

  var showAfter = 10; // pokaż po 10px przewinięcia (prawie od razu)

  function toggleBtn() {
    if (window.scrollY > showAfter) {
      btn.classList.add('show');
    } else {
      btn.classList.remove('show');
    }
  }

  // pokaż/ukryj na scroll
  window.addEventListener('scroll', toggleBtn, { passive: true });

  // klik = płynny scroll do góry
  btn.addEventListener('click', function () {
    if ('scrollBehavior' in document.documentElement.style) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    }
  });

  // sprawdź od razu (gdy wchodzimy w połowie strony)
  toggleBtn();
});

