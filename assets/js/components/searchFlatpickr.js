// XDSoft DateTimePicker na polach daty w wyszukiwarce
// Wymaga: jQuery i XDSoft DateTimePicker

$(document).ready(function () {
  $("input.search-date").datetimepicker({
    format: "Y-m-d H:i",
    formatTime: "H:i",
    step: 10,
    minDate: 0,
    lang: "pl",
    timepicker: true,
    datepicker: true,
    closeOnDateSelect: false,
    validateOnBlur: false,
    mask: false,
  });
});
