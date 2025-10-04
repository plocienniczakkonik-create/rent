// XDSoft DateTimePicker na karcie produktu
// Wymaga: jQuery i XDSoft DateTimePicker

$(document).ready(function () {
  // DateTimePicker na polach formularza rezerwacji
  $('input[name="pickup_at"], input[name="return_at"]').datetimepicker({
    format: "Y-m-d H:i",
    formatTime: "H:i",
    step: 30,
    minDate: 0,
    lang: "pl",
    timepicker: true,
    datepicker: true,
  });

  // Dynamiczna cena z dodatkami
  var basePrice = 0;
  var priceBox = $(".price-box .price-value");
  if (priceBox.length) {
    var txt = priceBox
      .text()
      .replace(/[^\d,.]/g, "")
      .replace(",", ".");
    basePrice = parseFloat(txt) || 0;
  }
  var priceBoxFinal = $(".price-box .price-value-final");
  var priceBoxOld = $(".price-box .price-value-old");
  if (priceBoxOld.length) {
    var txt = priceBoxOld
      .text()
      .replace(/[^\d,.]/g, "")
      .replace(",", ".");
    basePrice = parseFloat(txt) || 0;
  }

  function getDays() {
    var start = $('input[name="pickup_at"]').val();
    var end = $('input[name="return_at"]').val();
    if (!start || !end) return 1;
    var d1 = new Date(start);
    var d2 = new Date(end);
    var diff = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 1;
  }

  function updateTotalPrice() {
    var days = getDays();
    var total = basePrice * days;
    var extras = $('input[name="extra[]"]:checked');
    extras.each(function () {
      var price = parseFloat($(this).data("price")) || 0;
      var type = $(this).data("type");
      if (type === "per_day") {
        total += price * days;
      } else {
        total += price;
      }
    });

    // Pobierz typ i wartość rabatu z data-* na .price-box
    var discountType = $(".price-box").data("discountType");
    var discountVal = parseFloat($(".price-box").data("discountVal")) || 0;
    var promoTotal = total;
    if (discountType === "percent" && discountVal > 0) {
      promoTotal = total * Math.max(0, 1 - discountVal / 100);
    } else if (discountType === "amount" && discountVal > 0) {
      promoTotal = Math.max(0, total - discountVal);
    }

    // Aktualizuj cenę promocyjną
    if (priceBoxFinal.length) {
      priceBoxFinal.text(promoTotal.toFixed(2).replace(".", ",") + " PLN");
    }
    // Aktualizuj cenę standardową
    if (priceBoxOld.length) {
      priceBoxOld.text(total.toFixed(2).replace(".", ",") + " PLN");
    }
  }

  $(
    'input[name="extra[]"], input[name="pickup_at"], input[name="return_at"]'
  ).on("change", updateTotalPrice);
  updateTotalPrice();
});
