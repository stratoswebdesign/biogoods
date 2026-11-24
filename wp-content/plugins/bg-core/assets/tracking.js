/* Bio-Goods mérés: GA4/Ads (Consent Mode v2) és Meta Pixel a sütihozzájárulás szerint, e-commerce eseményekkel. */
(function () {
  'use strict';
  var T = window.BG_TRACK || {};
  var EV = window.BG_TRACK_EVENTS || {};
  var CUR = T.currency || 'HUF';
  var queue = [];
  var metaLoaded = false;

  function consent() {
    try { var c = JSON.parse(localStorage.getItem('bg_cookie')); return c && c.choice; } catch (e) { return null; }
  }
  function gt() { if (typeof window.gtag === 'function') window.gtag.apply(null, arguments); }

  function loadMeta() {
    if (metaLoaded || !T.meta) return;
    metaLoaded = true;
    /* eslint-disable */
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    /* eslint-enable */
    window.fbq('init', T.meta);
    window.fbq('track', 'PageView');
    queue.forEach(function (q) { window.fbq.apply(null, q); });
    queue = [];
  }
  function fb() {
    var args = Array.prototype.slice.call(arguments);
    if (!T.meta) return;
    if (metaLoaded) window.fbq.apply(null, args); else queue.push(args);
  }

  function grant(choice) {
    var g = choice === 'all' ? 'granted' : 'denied';
    gt('consent', 'update', { ad_storage: g, analytics_storage: g, ad_user_data: g, ad_personalization: g });
    if (choice === 'all') loadMeta();
  }

  if (consent() === 'all') loadMeta();
  document.addEventListener('bg:consent', function (e) { grant(e.detail); });

  function product(id) {
    var P = (window.BG && window.BG.products) || [];
    for (var i = 0; i < P.length; i++) if (P[i].id === id) return P[i];
    return null;
  }
  function item(p, qty) { return { item_id: p.id, item_name: p.name, price: p.price, quantity: qty || 1 }; }

  // termékoldal (a termékadatok a sablon szkriptjeiből jönnek, ezért a DOM betöltése után)
  document.addEventListener('DOMContentLoaded', function () {
    var pid = document.body.getAttribute('data-id');
    if (!pid || document.body.getAttribute('data-page') !== 'product') return;
    var p = product(pid);
    if (!p) return;
    gt('event', 'view_item', { currency: CUR, value: p.price, items: [item(p)] });
    fb('track', 'ViewContent', { content_ids: [p.id], content_type: 'product', value: p.price, currency: CUR });
  });
  // kosárba tétel
  document.addEventListener('bg:add', function (e) {
    var p = product(e.detail.id);
    if (!p) return;
    var q = e.detail.qty || 1;
    gt('event', 'add_to_cart', { currency: CUR, value: p.price * q, items: [item(p, q)] });
    fb('track', 'AddToCart', { content_ids: [p.id], content_type: 'product', value: p.price * q, currency: CUR });
  });
  // pénztár
  if (EV.begin_checkout) {
    gt('event', 'begin_checkout', { currency: CUR, value: EV.begin_checkout.value, items: EV.begin_checkout.items });
    fb('track', 'InitiateCheckout', { content_ids: EV.begin_checkout.items.map(function (i) { return i.item_id; }), num_items: EV.begin_checkout.items.length, value: EV.begin_checkout.value, currency: CUR });
  }
  // vásárlás (egyszer, a köszönőoldalon)
  if (EV.purchase) {
    var pu = EV.purchase;
    gt('event', 'purchase', { transaction_id: pu.transaction_id, currency: CUR, value: pu.value, coupon: pu.coupon || undefined, items: pu.items });
    if (T.ads && T.adsLabel) gt('event', 'conversion', { send_to: T.ads + '/' + T.adsLabel, value: pu.value, currency: CUR, transaction_id: pu.transaction_id });
    fb('track', 'Purchase', { content_ids: pu.items.map(function (i) { return i.item_id; }), content_type: 'product', value: pu.value, currency: CUR }, { eventID: 'order-' + pu.transaction_id });
  }
})();
