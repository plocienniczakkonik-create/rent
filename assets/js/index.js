// BANER COOKIES – wyświetlanie i obsługa szczegółowych zgód
document.addEventListener("DOMContentLoaded", function () {
  var banner = document.getElementById("cookieBanner");
  var form = document.getElementById("cookieConsentForm");
  var acceptAllBtn = document.getElementById("cookieAcceptAllBtn");
  var saveBtn = document.getElementById("cookieSaveBtn");
  var statsBox = document.getElementById("consent_stats");
  var marketingBox = document.getElementById("consent_marketing");
  if (!banner || !form || !acceptAllBtn || !saveBtn) return;

  // Odczytaj zgody z localStorage
  function getConsent() {
    try {
      return JSON.parse(localStorage.getItem("cookieConsent")) || {};
    } catch (e) {
      return {};
    }
  }
  function setConsent(consent) {
    localStorage.setItem("cookieConsent", JSON.stringify(consent));
  }

  var consent = getConsent();
  function showBanner() {
    banner.style.display = "block";
    setTimeout(function() {
      banner.classList.add("visible");
    }, 10);
  }
  function hideBanner() {
    banner.classList.remove("visible");
    setTimeout(function() {
      banner.style.display = "none";
    }, 450);
  }

  if (!consent || !consent.accepted) {
    showBanner();
    // Ustaw checkboxy wg poprzedniego wyboru
    if (typeof consent.stats === "boolean") statsBox.checked = consent.stats;
    if (typeof consent.marketing === "boolean") marketingBox.checked = consent.marketing;
  } else {
    // Jeśli zaakceptowano, ustaw checkboxy (np. po reloadzie)
    if (typeof consent.stats === "boolean") statsBox.checked = consent.stats;
    if (typeof consent.marketing === "boolean") marketingBox.checked = consent.marketing;
  }

  acceptAllBtn.addEventListener("click", function () {
    var newConsent = {
      accepted: true,
      stats: true,
      marketing: true,
      date: new Date().toISOString(),
    };
    setConsent(newConsent);
    statsBox.checked = true;
    marketingBox.checked = true;
    hideBanner();
    // TODO: tutaj można uruchomić skrypty stat/marketing jeśli są blokowane
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var newConsent = {
      accepted: true,
      stats: statsBox.checked,
      marketing: marketingBox.checked,
      date: new Date().toISOString(),
    };
    setConsent(newConsent);
    hideBanner();
    // TODO: tutaj można uruchomić skrypty stat/marketing jeśli są blokowane
  });
});
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

// FULLCALENDAR – inicjalizacja kalendarza w karcie produktu

document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("product-calendar");
  console.log("Test FullCalendar: calendarEl", calendarEl);
  console.log("Test FullCalendar: window.FullCalendar", window.FullCalendar);
  if (calendarEl && window.FullCalendar && window.FullCalendar.Calendar) {
    // Stan cen w aktualnym zakresie
    let dailyPrices = {};

    // Helper do klucza daty w formacie YYYY-MM-DD (lokalnie, bez UTC)
    const dateKey = (d) => {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, "0");
      const day = String(d.getDate()).padStart(2, "0");
      return `${y}-${m}-${day}`;
    };

    const calendar = new window.FullCalendar.Calendar(calendarEl, {
      initialView: "dayGridMonth",
      locale: "pl",
      height: 350,
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth",
      },
      events: [],
      firstDay: 1,
      buttonText: {
        today: "Dzisiaj",
        month: "Miesiąc",
      },
      datesSet: async function (info) {
        await fetchPricesForRange(info.start, info.end);
      },
      dayCellDidMount: function (arg) {
        const dStr = dateKey(arg.date);
        const entry = dailyPrices[dStr];
        // Usuń poprzednie wstawki, jeśli rerender
        const existing = arg.el.querySelector(".fc-day-price");
        if (existing) existing.remove();
        if (entry && typeof entry.final === "number") {
          const priceDiv = document.createElement("div");
          priceDiv.className = "fc-day-price" + (entry.promo ? " promo" : "");
          priceDiv.textContent = `${Math.round(entry.final)} PLN`;
          const top = arg.el.querySelector(".fc-daygrid-day-top") || arg.el;
          top.appendChild(priceDiv);
        }
      },
    });

    // Funkcja pobierająca ceny dla zadanego zakresu
    async function fetchPricesForRange(startDate, endDate) {
      try {
        const params = new URLSearchParams();
        const skuInput = document.querySelector('input[name="sku"]');
        if (!skuInput || !skuInput.value) return;
        params.set("sku", skuInput.value);
        params.set("start", startDate.toISOString().slice(0, 10));
        params.set("end", endDate.toISOString().slice(0, 10));
        const pickLoc =
          document.querySelector('select[name="pickup_location"]')?.value || "";
        const dropLoc =
          document.querySelector('select[name="dropoff_location"]')?.value ||
          "";
        if (pickLoc) params.set("pickup_location", pickLoc);
        if (dropLoc) params.set("dropoff_location", dropLoc);

        const res = await fetch(
          "/rental/pages/api/product-daily-prices.php?" + params.toString(),
          { cache: "no-store" }
        );
        if (!res.ok) throw new Error("HTTP " + res.status);
        const data = await res.json();
        dailyPrices = data.prices || {};
      } catch (e) {
        console.warn("Nie udało się pobrać cen dziennych", e);
        dailyPrices = {};
      } finally {
        calendar.rerenderDates();
      }
    }

    // Refetch po zmianie lokalizacji
    const pickupSelect = document.querySelector(
      'select[name="pickup_location"]'
    );
    const dropoffSelect = document.querySelector(
      'select[name="dropoff_location"]'
    );
    const onLocationChange = async () => {
      const view = calendar.view;
      await fetchPricesForRange(view.activeStart, view.activeEnd);
    };
    if (pickupSelect) pickupSelect.addEventListener("change", onLocationChange);
    if (dropoffSelect)
      dropoffSelect.addEventListener("change", onLocationChange);
    calendar.render();
    console.log("FullCalendar został zainicjalizowany");
  } else {
    console.warn("FullCalendar nie jest dostępny lub brak calendarEl");
  }
});
