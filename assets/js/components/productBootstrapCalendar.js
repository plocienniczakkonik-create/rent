// Bootstrap Datepicker z cenami za dzień
// Wymaga: https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js
// oraz https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css

var prices = {
  "2025-10-04": 149,
  "2025-10-05": 149,
  "2025-10-06": 129, // promocja
  "2025-10-07": 149,
};
var promoDays = ["2025-10-06"];

function getDayPrice(date) {
  var y = date.getFullYear();
  var m = (date.getMonth() + 1).toString().padStart(2, "0");
  var d = date.getDate().toString().padStart(2, "0");
  var key = `${y}-${m}-${d}`;
  var price = prices[key];
  return price ? price : null;
}

$(function () {
  if ($("#bootstrap-calendar").length) {
    $("#bootstrap-calendar").datepicker({
      format: "yyyy-mm-dd",
      todayHighlight: true,
      autoclose: true,
      templates: {
        leftArrow: "&laquo;",
        rightArrow: "&raquo;",
      },
      beforeShowDay: function (date) {
        var price = getDayPrice(date);
        var tooltip = price
          ? promoDays.includes(date.toISOString().slice(0, 10))
            ? `Promocja: ${price} PLN`
            : `Cena: ${price} PLN`
          : "";
        var classes = price
          ? promoDays.includes(date.toISOString().slice(0, 10))
            ? "promo-day"
            : "price-day"
          : "";
        return {
          tooltip: tooltip,
          classes: classes,
        };
      },
      inline: true,
    });
    // Inicjalizacja Bootstrap Tooltip na dniach kalendarza
    setTimeout(function () {
      $(
        "#bootstrap-calendar td.day, #bootstrap-calendar td.price-day, #bootstrap-calendar td.promo-day"
      ).each(function () {
        var title = $(this).attr("title");
        if (title) {
          $(this).tooltip({ container: "body", placement: "top" });
        }
      });
    }, 500);
  }
});
