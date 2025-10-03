// Flatpickr na polach daty w wyszukiwarce
// Wymaga: https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('input.search-date').forEach(function(input) {
    flatpickr(input, {
      enableTime: true,
      time_24hr: true,
      minuteIncrement: 10,
      timeInput: true,
      locale: "pl",
      minDate: "today",
      disableMobile: true,
      dateFormat: "Y-m-d H:i",
      onChange: function(selectedDates, dateStr, instance) {
        if (selectedDates.length && instance.isOpen && instance.config.enableTime) {
          var d = selectedDates[0];
          if (d instanceof Date && typeof d.getHours === 'function') {
            instance.close();
          }
        }
      }
    });
  });
});
