/*
 * Bio-Goods – WordPress-híd a demóból örökölt felülethez.
 * 1) Útvonalak: a demó 'termek.html?id=x' alakú hivatkozásaiból WordPress-címek lesznek.
 * 2) Pénztár: a böngészőben épült kosarat a WooCommerce kosarába másolja (az árakat a szerver számolja),
 *    majd a WooCommerce pénztárra visz.
 * Az app.js előtt kell betölteni (a route-ot már az első kirajzolás használja).
 */
(function () {
  'use strict';
  var C = window.BG_WP_CFG || {};
  var home = (C.home || '/').replace(/\/?$/, '/');
  var assets = (C.assets || '').replace(/\/?$/, '/');
  var purl = C.purl || {};
  ((window.BG && window.BG.products) || []).forEach(function (p) { if (p.url) purl[p.id] = p.url; });
  var map = C.pages || {};

  function route(p) {
    p = String(p || '');
    if (/^(https?:)?\/\//.test(p) || p.charAt(0) === '#') return p;
    if (p.indexOf('assets/') === 0) return assets + p.slice(7);
    var hash = '', qs = '';
    var h = p.indexOf('#'); if (h >= 0) { hash = p.slice(h); p = p.slice(0, h); }
    var q = p.indexOf('?'); if (q >= 0) { qs = p.slice(q + 1); p = p.slice(0, q); }
    var name = p.replace(/\.html$/, '');
    if (name === 'termek') {
      var id = new URLSearchParams(qs).get('id');
      if (id) return (purl[id] || home + 'termek/' + encodeURIComponent(id) + '/') + hash;
    }
    if (name === 'admin/index' || name.indexOf('admin/') === 0) return C.admin || home + 'wp-admin/';
    if (name === 'index' || name === '') return home + (qs ? '?' + qs : '') + hash;
    var target = map[name] || home + name + '/';
    return target + (qs ? '?' + qs : '') + hash;
  }

  window.BG_WP = { route: route };

  // A demó kuponmezője helyett a WooCommerce pénztár kuponmezője él (ott a szerver ellenőriz).
  try { localStorage.removeItem('bg_coupon'); } catch (e) { /* */ }

  // Sikeres rendelés után a böngésző kosarát is ürítjük.
  if (C.orderReceived) {
    try { localStorage.removeItem('bg_cart'); localStorage.removeItem('bg_coupon'); } catch (e) { /* */ }
  }

  function isCheckoutLink(a) {
    if (!a || !a.href || !C.checkout || C.isCheckout) return false;
    var raw = a.getAttribute('href') || '';
    if (!raw || raw.charAt(0) === '#') return false;
    // a Google-címkék (_gl) és más paraméterek nem számítanak: csak az útvonalat hasonlítjuk
    try {
      var u = new URL(a.href, location.href), c = new URL(C.checkout, location.href);
      return u.origin === c.origin && u.pathname.replace(/\/?$/, '/') === c.pathname.replace(/\/?$/, '/');
    } catch (e) { return false; }
  }

  var busy = false;
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a');
    if (!isCheckoutLink(a)) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    if (busy) return;
    busy = true;
    var label = a.textContent;
    a.setAttribute('aria-busy', 'true');
    a.textContent = 'Pénztár betöltése…';
    var cart = [];
    try { cart = JSON.parse(localStorage.getItem('bg_cart')) || []; } catch (err) { cart = []; }
    var town = null;
    try { town = JSON.parse(localStorage.getItem('bg_town')); } catch (err) { town = null; }
    fetch(C.ajax + '?action=bg_sync_cart', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: cart, town: town })
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (res && res.success && res.data && res.data.checkout) { location.href = res.data.checkout; return; }
      throw new Error((res && res.data && res.data.message) || 'Hiba');
    }).catch(function (err) {
      busy = false;
      a.removeAttribute('aria-busy');
      a.textContent = label;
      alert('A pénztár most nem tölthető be (' + err.message + '). Kérjük, próbáld újra.');
    });
  }, true); // elfogó fázis: a felület többi kattintáskezelője előtt fusson
})();
