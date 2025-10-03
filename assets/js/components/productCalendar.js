// Kalendarz rezerwacji z cenami za dzień (Flatpickr)
// Wersja bez importów, do użycia z Flatpickr z CDN

var prices = {
  "2025-10-04": 149,
  "2025-10-05": 149,
  "2025-10-06": 129, // promocja
  "2025-10-07": 149,
};
var promoDays = ["2025-10-06"];

function initProductCalendar(inputSelector, priceBoxSelector) {
  if (typeof flatpickr === 'undefined') return;
  flatpickr(inputSelector, {
    inline: true,
    minDate: "today",
    locale: "pl",
    onChange: function(selectedDates, dateStr) {
      var price = prices[dateStr] || prices[Object.keys(prices)[0]];
      var isPromo = promoDays.includes(dateStr);
      var priceBox = document.querySelector(priceBoxSelector);
      if (priceBox) {
        priceBox.innerHTML = isPromo
          ? "<span class='promo-price'>" + price + " PLN <span class='badge bg-success ms-2'>Promocja!</span></span>"
          : "<span>" + price + " PLN</span>";
      }
    },
    onDayCreate: function(dObj, dStr, fp, dayElem) {
      // Dodaj cenę pod każdym dniem
      var date = dayElem.dateObj.toISOString().slice(0, 10);
      var price = prices[date];
      if (price) {
        var priceDiv = document.createElement('div');
        priceDiv.className = promoDays.includes(date) ? 'calendar-day-price promo' : 'calendar-day-price';
        priceDiv.innerText = price + ' PLN';
        dayElem.appendChild(priceDiv);
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  if (document.querySelector('#calendar-input')) {
    initProductCalendar('#calendar-input', '#calendar-price-box');
  }
});
