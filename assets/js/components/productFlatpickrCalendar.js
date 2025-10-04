// Flatpickr z cenami pod dniami
// Wymaga: https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js

document.addEventListener("DOMContentLoaded", function () {
  // Kalendarz pod zdjęciem (tylko podgląd)
  flatpickr("#flatpickr-calendar", {
    inline: true,
    locale: "pl",
    minDate: "today",
    showMonths: 1,
    disableMobile: true,
  });

  // Flatpickr na polach formularza
  ["pickup_at", "return_at"].forEach(function (name) {
    var input = document.querySelector('input[name="' + name + '"]');
    if (input) {
      flatpickr(input, {
        enableTime: true,
        time_24hr: true,
        minuteIncrement: 10,
        locale: "pl",
        minDate: "today",
        disableMobile: true,
        dateFormat: "Y-m-d H:i",
        onClose: function (selectedDates, dateStr, instance) {
          // Zamknij picker po wyborze
        },
        onChange: function (selectedDates, dateStr, instance) {
          // Jeśli wybrano pełną datę i czas, zamknij picker
          if (
            selectedDates.length &&
            instance.isOpen &&
            instance.config.enableTime
          ) {
            var d = selectedDates[0];
            if (d instanceof Date && typeof d.getHours === "function") {
              instance.close();
            }
          }
        },
      });
    }
  });

  // Dynamiczna cena z dodatkami
  var basePrice = 0;
  var priceBox = document.querySelector(".price-box .price-value");
  if (priceBox) {
    var txt = priceBox.textContent.replace(/[^\d,.]/g, "").replace(",", ".");
    basePrice = parseFloat(txt) || 0;
  }
  function getDays() {
    var start = document.querySelector('input[name="pickup_at"]')?.value;
    var end = document.querySelector('input[name="return_at"]')?.value;
    if (!start || !end) return 1;
    var d1 = new Date(start);
    var d2 = new Date(end);
    var diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 1;
  }
  function updateTotalPrice() {
    var days = getDays();
    var total = basePrice * days;
    var extras = document.querySelectorAll('input[name="extra[]"]:checked');
    extras.forEach(function (chk) {
      var price = parseFloat(chk.getAttribute("data-price")) || 0;
      var type = chk.getAttribute("data-type");
      if (type === "per_day") {
        total += price * days;
      } else {
        total += price;
      }
    });
    if (priceBox) {
      priceBox.textContent = total.toFixed(2).replace(".", ",") + " PLN";
    }
  }
  document
    .querySelectorAll(
      'input[name="extra[]"], input[name="pickup_at"], input[name="return_at"]'
    )
    .forEach(function (el) {
      el.addEventListener("change", updateTotalPrice);
    });
  updateTotalPrice();
  document.querySelectorAll('input[name="extra[]"]').forEach(function (chk) {
    chk.addEventListener("change", updateTotalPrice);
  });
});
