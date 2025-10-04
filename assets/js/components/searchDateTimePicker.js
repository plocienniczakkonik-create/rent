// XDSoft DateTimePicker na polach daty w wyszukiwarce
// Wymaga: jQuery i XDSoft DateTimePicker

$(document).ready(function () {
  // Wyłącz podpowiedzi przeglądarki i ręczne wpisywanie
  $("input.search-date")
    .attr({ autocomplete: "off", spellcheck: "false" })
    // readonly blokuje klawiaturę ekranową i wpisywanie, ale pozwolimy na fokus i otwarcie pickera
    .prop("readonly", true)
    .on("keydown", function (e) {
      // Zablokuj wpisywanie z klawiatury
      e.preventDefault();
      return false;
    });

  $("input.search-date").datetimepicker({
    format: "Y-m-d H:i",
    formatTime: "H:i",
    step: 30,
    minDate: 0,
    lang: "pl",
    timepicker: true,
    datepicker: true,
  });

  // Pozwól kliknięciem otworzyć picker na polu readonly
  $(document).on("click", "input.search-date", function () {
    try {
      $(this).datetimepicker("show");
    } catch (e) {
      // bezpieczne pominięcie jeśli plugin nie jest załadowany
    }
  });
});
