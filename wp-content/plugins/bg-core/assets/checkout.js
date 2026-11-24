/* Pénztár: a település választása frissíti a kiszállítási napok listáját. A szerver újra ellenőriz. */
(function ($) {
  'use strict';
  var CFG = window.BG_DAYS || {}; var DAYS = CFG.towns || {}; var SUB = +CFG.subtotal || 0;
  function fill() {
    var town = $('#billing_city').val();
    var $day = $('#bg_day');
    if (!$day.length) return;
    var cur = $day.val();
    var list = DAYS[town] || [];
    $day.empty();
    if (!town) { $day.append(new Option('Előbb válassz települést', '')); return; }
    if (!list.length) { $day.append(new Option('Jelenleg nincs meghirdetett járat', '')); return; }
    $day.append(new Option('Válassz napot…', ''));
    list.forEach(function (d) { $day.append(new Option(d[1], d[0], false, d[0] === cur)); });
    if (!$day.val()) { var ok = list.filter(function (d) { return SUB >= d[2]; })[0]; if (ok) $day.val(ok[0]); }
  }
  $(document.body).on('change', '#billing_city', fill);
  $(fill);
})(jQuery);
