// FullCalendar inicjalizacja na stronie produktu
// Wymaga: FullCalendar CDN

document.addEventListener("DOMContentLoaded", function () {
  var calendarEl = document.getElementById("product-calendar");
  if (!calendarEl) return;
  var calendar = new window.FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    locale: "pl",
    height: 400,
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek",
    },
    events: [], // Możesz tu dodać eventy z backendu
  });
  calendar.render();
});
