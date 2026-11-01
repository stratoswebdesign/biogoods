/* Bio-Goods demo webshop – alkalmazáslogika (build nélkül, sima böngésző-JS) */
(function () {
  'use strict';
  const BG = window.BG;
  const BASE = document.documentElement.getAttribute('data-base') || '';
  const PAGE = document.body.getAttribute('data-page') || '';

  /* ================= segédfüggvények ================= */
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const num = (n) => String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  const ft = (n) => num(n) + ' Ft';
  const norm = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
  const params = new URLSearchParams(location.search);
  const url = (p) => BASE + p;
  const img = (id) => BASE + 'assets/img/termekek/' + id + '.jpg';

  const store = {
    get(k, d) { try { const v = JSON.parse(localStorage.getItem(k)); return v == null ? d : v; } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* privát mód */ } },
    del(k) { try { localStorage.removeItem(k); } catch (e) { /* */ } }
  };

  const DAYS = ['vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat'];
  const MONTHS = ['jan.', 'febr.', 'márc.', 'ápr.', 'máj.', 'jún.', 'júl.', 'aug.', 'szept.', 'okt.', 'nov.', 'dec.'];
  const fmtDate = (d) => DAYS[d.getDay()] + ', ' + MONTHS[d.getMonth()] + ' ' + d.getDate() + '.';
  const parseYmd = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
  const fmtCut = (d) => DAYS[d.getDay()] + ' ' + String(d.getHours()).padStart(2, '0') + ':00';
  const ymd = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

  const ICON = {
    search: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>',
    user: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>',
    cart: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11h11.2L21 8H6.2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>',
    menu: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
    close: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
    trash: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>',
    truck: '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#CE8900" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 17V8h11v9"/><path d="M14 11h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>',
    home: '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#CE8900" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>',
    medal: '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#CE8900" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="9" r="6"/><path d="M8.5 14l-1.5 7 5-3 5 3-1.5-7"/><path d="M9.5 9l1.8 1.8L14.8 7.5"/></svg>',
    leaf: '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#CE8900" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V9"/><path d="M12 13c-3 0-5-2-5-5 3 0 5 2 5 5z"/><path d="M12 11c3 0 5-2 5-5-3 0-5 2-5 5z"/><path d="M12 17c-3 0-5-2-5-5 3 0 5 2 5 5z"/><path d="M12 15c3 0 5-2 5-5-3 0-5 2-5 5z"/></svg>',
    star: '<svg width="18" height="18" viewBox="0 0 24 24" fill="#CE8900" aria-hidden="true"><path d="M12 2.5l2.9 6.2 6.6.7-5 4.5 1.4 6.6L12 17.2l-5.9 3.3 1.4-6.6-5-4.5 6.6-.7z"/></svg>',
    fb: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 009 6.5V9H6.5v3.5H9V21h3.5v-8.5H15l.5-3.5h-3V7a1 1 0 011-1H15z"/></svg>',
    ig: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/></svg>'
  };

  /* ================= adatok ================= */
  const P = {};
  BG.products.forEach((p) => { P[p.id] = p; });
  const CAT = {};
  BG.categories.forEach((c) => { CAT[c.id] = c; });
  const OCC = {};
  BG.occasions.forEach((o) => { OCC[o.id] = o; });
  const ZONE = {};
  BG.areas.forEach((z) => { ZONE[z.id] = z; });

  // Admin felületen átírt minimumok (demó, ebben a böngészőben): kulcs = település|járatindex
  const overrides = store.get('bg_min_overrides2', {});
  BG.towns.forEach((t) => {
    t.runs.forEach((r, ix) => { const v = overrides[t.name + '|' + ix]; if (typeof v === 'number') r.min = v; });
    t.min = Math.min.apply(null, t.runs.map((r) => r.min));
  });
  const DAYNAMES = ['vasárnap', 'hétfő', 'kedd', 'szerda', 'csütörtök', 'péntek', 'szombat'];
  // Egy járat címkéje: „kedd” vagy „november 5., csütörtök (egyszeri)”
  const runLabel = (r) => r.date ? fmtDate(parseYmd(r.date)) + ' (egyszeri járat)' : DAYNAMES[r.day];
  // Település járatai szövegesen; ha a minimumok eltérnek, járatonként kiírva
  function runsText(t, withMin) {
    const same = t.runs.every((r) => r.min === t.runs[0].min);
    return t.runs.map((r) => runLabel(r) + (withMin && !same ? ' (min. ' + ft(r.min) + ')' : '')).join(', ');
  }
  function minText(t) {
    const same = t.runs.every((r) => r.min === t.runs[0].min);
    return same ? ft(t.min) : t.runs.map((r) => ft(r.min) + ' (' + runLabel(r) + ')').join(' · ');
  }

  const grams = (p) => {
    const m = String(p.pack).replace(',', '.').match(/([\d.]+)\s*(kg|g)/);
    if (!m) return null;
    return m[2] === 'kg' ? parseFloat(m[1]) * 1000 : parseFloat(m[1]);
  };
  const unitText = (p) => (p.unit ? num(p.unit) + ' Ft/kg' : '');
  const metaText = (p) => [p.pack, unitText(p)].filter(Boolean).join(' · ');

  /* ================= upsell, keresztajánlás ================= */
  // Ami illik egy termékhez: termékszintű lista, különben kategóriaszintű, végül a kiemeltek.
  function complements(p, exclude) {
    const ex = new Set((exclude || []).concat([p.id, p.variant].filter(Boolean)));
    const ids = (BG.pairByProduct[p.id] || []).concat(BG.pairByCat[p.cat] || [], BG.featured);
    const out = [];
    ids.forEach((id) => { if (P[id] && !ex.has(id) && !out.includes(id)) out.push(id); });
    return out.map((id) => P[id]);
  }
  // Ugyanaz a termék nagyobb kiszerelésben, kedvezőbb kilóárral (pl. zsír 1 kg → 5 kg).
  // Csak explicit párokból (BG.biggerPack), hogy ne ajánljunk más terméket „nagyobb kiszerelésként”.
  function biggerPack(p) {
    const x = P[(BG.biggerPack || {})[p.id]];
    return x && x.unit && p.unit && x.unit < p.unit && (grams(x) || 0) > (grams(p) || 0) ? x : null;
  }
  const savePct = (p, b) => Math.round((1 - b.unit / p.unit) * 100);
  function cartComplements(limit) {
    const c = getCart();
    const inCart = c.map((i) => i.id);
    const out = [];
    c.forEach((i) => complements(P[i.id], inCart).forEach((x) => { if (!out.includes(x)) out.push(x); }));
    return out.slice(0, limit || 4);
  }
  function recRow(p) {
    return '<div class="rec-row"><img src="' + img(p.img) + '" alt=""><div class="n"><a href="' + url('termek.html?id=' + p.id) + '">' + esc(p.name) + '</a><br><span class="muted">' + esc(p.pack) + ' · ' + ft(p.price) + '</span></div>' +
      '<button type="button" class="plus" data-add="' + p.id + '" aria-label="Kosárba: ' + esc(p.name) + '">+</button></div>';
  }
  function upsellBox(p) {
    const big = biggerPack(p);
    if (!big) return '';
    return '<div class="upsell-box"><img src="' + img(big.img) + '" alt=""><div class="txt"><strong>Nagyobb kiszerelés, jobb kilóár</strong> <span class="save">−' + savePct(p, big) + '% / kg</span><br>' +
      esc(big.name) + ' · ' + esc(big.pack) + ' · ' + ft(big.price) + ' (' + num(big.unit) + '\u00a0Ft/kg ' + num(p.unit) + '\u00a0Ft/kg helyett)</div>' +
      '<button type="button" class="btn btn-dark btn-sm" data-add="' + big.id + '" data-open-after>Ezt kérem</button></div>';
  }

  /* ================= település, járat ================= */
  function findTowns(q) {
    const n = norm(q);
    if (!n) return [];
    if (/^\d+$/.test(n)) {
      return BG.towns.filter((t) => t.zips.some((z) => n.startsWith(z) || z.startsWith(n)));
    }
    const starts = BG.towns.filter((t) => norm(t.name).startsWith(n));
    const contains = BG.towns.filter((t) => !starts.includes(t) && norm(t.name).includes(n));
    return starts.concat(contains);
  }
  const townByName = (name) => BG.towns.find((t) => t.name === name) || null;
  const getTown = () => townByName(store.get('bg_town', null));
  function setTown(name) {
    store.set('bg_town', name);
    document.dispatchEvent(new CustomEvent('bg:town'));
  }
  // A település következő járatai (a mostani webshop szerint: rendelés a kiszállítást megelőző napon délig).
  // Minden járatnak saját minimuma lehet (pl. Budapest: kedd 10 000 Ft, péntek 5 000 Ft).
  function nextDeliveries(t, count, from) {
    const out = [];
    if (!t || !t.runs) return out;
    const now = from || new Date();
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    for (let i = 1; i <= 70 && out.length < (count || 1); i++) {
      const day = new Date(d.getFullYear(), d.getMonth(), d.getDate() + i);
      const run = t.runs.find((r) => (r.date ? r.date === ymd(day) : r.day === day.getDay()));
      if (!run) continue;
      const cutoff = new Date(day.getFullYear(), day.getMonth(), day.getDate() - 1, BG.cutoffHour || 12, 0, 0);
      if (now < cutoff) out.push({ date: day, cutoff, min: run.min, once: !!run.date });
    }
    return out;
  }
  function townInfo(t, total) {
    if (!t) return null;
    const z = ZONE[t.zone];
    const list = nextDeliveries(t, 4);
    // az első járat, amelyhez a kosár már eléri a minimumot (ha nincs kosárösszeg: a legkorábbi)
    const next = (typeof total === 'number' ? list.find((o) => total >= o.min) : null) || list[0];
    return { town: t, zone: z, next, list, min: t.min };
  }

  /* ================= kosár ================= */
  const getCart = () => store.get('bg_cart', []).filter((i) => P[i.id] && i.qty > 0);
  function saveCart(c) { store.set('bg_cart', c); document.dispatchEvent(new CustomEvent('bg:cart')); }
  function addToCart(id, qty) {
    const c = getCart();
    const it = c.find((i) => i.id === id);
    if (it) it.qty = Math.min(99, it.qty + (qty || 1)); else c.push({ id, qty: qty || 1 });
    saveCart(c);
  }
  function setQty(id, qty) {
    let c = getCart();
    if (qty <= 0) c = c.filter((i) => i.id !== id); else c.forEach((i) => { if (i.id === id) i.qty = Math.min(99, qty); });
    saveCart(c);
  }
  const cartTotal = (c) => (c || getCart()).reduce((s, i) => s + P[i.id].price * i.qty, 0);
  const cartCount = (c) => (c || getCart()).reduce((s, i) => s + i.qty, 0);
  function coupon() { return store.get('bg_coupon', null); }
  function discountFor(total) { const c = coupon(); return c && c.pct ? Math.round(total * c.pct / 100) : 0; }

  /* ================= közös UI-darabok ================= */
  function productCard(p) {
    const tag = p.tag ? '<span class="tag' + (p.tag === 'Akció' ? ' gold' : '') + '">' + esc(p.tag) + '</span>' : '';
    const old = p.old ? '<span class="old">' + ft(p.old) + '</span>' : '';
    return '<article class="pcard">' +
      '<a class="media" href="' + url('termek.html?id=' + p.id) + '"><img loading="lazy" src="' + img(p.img) + '" alt="' + esc(p.name) + '">' + tag + '</a>' +
      '<div class="body"><span class="cat">' + esc(CAT[p.cat].name) + '</span>' +
      '<h3><a href="' + url('termek.html?id=' + p.id) + '">' + esc(p.name) + '</a></h3>' +
      '<p class="meta">' + esc(metaText(p)) + '</p>' +
      '<div class="foot"><span><span class="price">' + ft(p.price) + '</span>' + old + '</span>' +
      '<button type="button" class="add-btn" data-add="' + p.id + '" aria-label="Kosárba: ' + esc(p.name) + '">Kosárba</button></div></div></article>';
  }
  function tile(name, sub, imgId, href, small) {
    return '<a class="tile' + (small ? ' sm' : '') + '" href="' + href + '"><img loading="lazy" src="' + img(imgId) + '" alt="">' +
      '<div class="tile-bar"><div><p class="tile-name">' + esc(name) + '</p>' + (sub ? '<p class="tile-sub">' + esc(sub) + '</p>' : '') + '</div>' +
      (small ? '' : '<span class="arrow" aria-hidden="true">→</span>') + '</div></a>';
  }
  function toast(msg) {
    let t = $('#toast');
    if (!t) { t = document.createElement('div'); t.id = 'toast'; t.className = 'toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
    t.textContent = msg;
    t.classList.add('is-show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => t.classList.remove('is-show'), 2400);
  }

  /* ---------- fejléc, felső sáv ---------- */
  function topbarHTML() {
    const info = townInfo(getTown());
    if (info && info.next) {
      return '<span><strong>' + esc(info.town.name) + ':</strong> következő járat ' + esc(fmtDate(info.next.date)) + '</span>' +
        '<span>Rendelj ' + esc(fmtCut(info.next.cutoff)) + '-ig</span>' +
        '<span>Minimum rendelés: ' + ft(info.next.min) + ' · szállítási díj nincs</span>';
    }
    return '<span>Kiszállítás minden héten: kedd · szerda · csütörtök · péntek</span><span>Rendelés a kiszállítást megelőző napon délig</span><span>Szállítási díj nincs, településenként rendelési minimum</span>';
  }
  function renderChrome() {
    const nav = document.body.getAttribute('data-nav') || '';
    const navItems = [['termekek', 'Termékek', 'termekek.html'], ['ajandek', 'Ajándékok', 'termekek.html?kategoria=ajandek'], ['szallitas', 'Szállítás', 'szallitas.html'], ['gazdasag', 'A gazdaság', 'a-gazdasag.html'], ['receptek', 'Receptek', 'receptek.html']];
    const navHTML = navItems.map((n) => '<a href="' + url(n[2]) + '"' + (nav === n[0] ? ' aria-current="page"' : '') + '>' + n[1] + '</a>').join('');
    const head = document.createElement('div');
    head.innerHTML =
      '<a class="skip" href="#main">Ugrás a tartalomhoz</a>' +
      '<div class="topbar"><div class="wrap" id="topbar">' + topbarHTML() + '</div></div>' +
      '<header class="site-header"><div class="wrap">' +
      '<button class="icon-btn menu-btn" type="button" aria-label="Menü" aria-expanded="false" id="menu-btn">' + ICON.menu + '</button>' +
      '<a class="brand" href="' + url('index.html') + '" aria-label="Bio-Goods Mangalica – főoldal"><img src="' + url('assets/img/site/logo.png') + '" alt="Bio-Goods Mangalica"></a>' +
      '<nav class="mainnav" aria-label="Főmenü">' + navHTML + '</nav>' +
      '<div class="header-tools">' +
      '<button class="icon-btn" type="button" aria-label="Keresés" id="search-btn" aria-expanded="false">' + ICON.search + '</button>' +
      '<a class="icon-btn" href="' + url('fiokom.html') + '" aria-label="Fiókom">' + ICON.user + '</a>' +
      '<button class="cart-btn" type="button" id="cart-btn" aria-label="Kosár megnyitása">' + ICON.cart + '<span class="label">Kosár · </span><span id="cart-sum">' + ft(cartTotal()) + '</span><span class="count" id="cart-count">' + cartCount() + '</span></button>' +
      '</div></div>' +
      '<div class="searchbar" id="searchbar"><div class="wrap"><form action="' + url('termekek.html') + '" role="search"><label class="sr-only" for="hq">Keresés a termékek között</label><input id="hq" name="q" type="search" placeholder="Mit keresel? pl. tarja, kolbász, zsír"><button class="btn btn-gold btn-sm" type="submit">Keresés</button></form></div></div>' +
      '<nav class="mobile-nav" id="mobile-nav" aria-label="Mobil menü">' + navHTML + '<a href="' + url('fiokom.html') + '">Fiókom</a><a href="' + url('kapcsolat.html') + '">Kapcsolat</a></nav>' +
      '</header>';
    document.body.prepend(head);

    const foot = document.createElement('footer');
    foot.className = 'site-footer';
    foot.innerHTML = '<div class="wrap cols">' +
      '<div class="about"><img src="' + url('assets/img/site/logo.png') + '" alt="Bio-Goods Mangalica" style="width:110px;height:auto"><p>Mangalica-tenyésztés és -feldolgozás Gesztelyről, saját hűtött autókkal házhoz.</p></div>' +
      '<nav aria-label="Termékek"><p class="h">Termékek</p>' + BG.categories.map((c) => '<a href="' + url('termekek.html?kategoria=' + c.id) + '">' + esc(c.name) + '</a>').join('') + '</nav>' +
      '<nav aria-label="Vásárlás"><p class="h">Vásárlás</p><a href="' + url('szallitas.html') + '">Szállítási területek</a><a href="' + url('fizetes.html') + '">Fizetési módok</a><a href="' + url('gyik.html') + '">Gyakori kérdések</a><a href="' + url('ceges-ajandek.html') + '">Céges ajándék</a><a href="' + url('aszf.html') + '">ÁSZF</a><a href="' + url('aszf.html#adatkezeles') + '">Adatkezelés</a></nav>' +
      '<nav aria-label="Bio-Goods"><p class="h">Bio-Goods</p><a href="' + url('a-gazdasag.html') + '">A gazdaság</a><a href="' + url('dijaink.html') + '">Díjaink</a><a href="' + url('receptek.html') + '">Receptek</a><a href="' + url('kapcsolat.html') + '">Kapcsolat</a></nav>' +
      '<div><p class="h">Kapcsolat</p><span>3715 Gesztely, Aradi u. 22.</span><a href="tel:+36709470077">06 70 947 0077</a><a href="mailto:mangalica@bio-goods.hu">mangalica@bio-goods.hu</a>' +
      '<div class="social"><a href="https://www.facebook.com/biogoodsmangalica/" aria-label="Facebook" rel="noopener" target="_blank">' + ICON.fb + '</a><a href="https://www.instagram.com/mangalicatermekek/" aria-label="Instagram" rel="noopener" target="_blank">' + ICON.ig + '</a></div></div>' +
      '</div><div class="wrap bottom"><span>Bankkártya · OTP SZÉP · K&amp;H SZÉP · MKB SZÉP · SimplePay · készpénz</span>' +
      '<span><span class="demo-badge">DEMO</span> Egyes adatok mintaértékek · <a href="' + url('admin/index.html') + '">Admin (demó)</a> · © 2026 Bio-Goods Kft. · Készítette: Stratos Webdesign</span></div>';
    document.body.appendChild(foot);

    // mini-kosár fiók
    const dr = document.createElement('div');
    dr.innerHTML = '<div class="overlay" id="overlay"></div>' +
      '<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title" aria-hidden="true">' +
      '<div class="drawer-head"><h2 id="drawer-title">Kosár</h2><button class="icon-btn" type="button" id="drawer-close" aria-label="Bezárás" style="color:var(--ink)">' + ICON.close + '</button></div>' +
      '<div class="drawer-body" id="drawer-body"></div><div class="drawer-foot" id="drawer-foot"></div></aside>' +
      '<div class="mcartbar" id="mcartbar" hidden></div>';
    document.body.appendChild(dr);

    $('#cart-btn').addEventListener('click', openDrawer);
    $('#overlay').addEventListener('click', closeDrawer);
    $('#drawer-close').addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawer(); });
    $('#search-btn').addEventListener('click', () => {
      const sb = $('#searchbar');
      const open = sb.classList.toggle('is-open');
      $('#search-btn').setAttribute('aria-expanded', String(open));
      if (open) $('#hq').focus();
    });
    $('#menu-btn').addEventListener('click', () => {
      const m = $('#mobile-nav');
      const open = m.classList.toggle('is-open');
      $('#menu-btn').setAttribute('aria-expanded', String(open));
    });

    if (!store.get('bg_cookie', null)) renderCookie();
    updateChrome();
  }

  function renderCookie() {
    const c = document.createElement('div');
    c.className = 'cookie';
    c.setAttribute('role', 'dialog');
    c.setAttribute('aria-label', 'Sütibeállítások');
    c.innerHTML = '<div class="txt"><p class="t">Sütiket használunk</p><p>A működéshez szükséges sütik mindig aktívak. Statisztikai és marketing sütiket csak a hozzájárulásoddal használunk. <a href="' + url('aszf.html#sutik') + '">Részletek</a></p></div>' +
      '<div class="row"><button class="btn btn-ghost btn-sm" data-c="necessary" type="button">Csak a szükségesek</button><button class="btn btn-gold btn-sm" data-c="all" type="button">Mindet elfogadom</button></div>';
    document.body.appendChild(c);
    c.addEventListener('click', (e) => {
      const b = e.target.closest('[data-c]');
      if (!b) return;
      store.set('bg_cookie', { choice: b.getAttribute('data-c'), at: Date.now() });
      c.remove();
    });
  }

  function minState() {
    const c = getCart();
    const total = cartTotal(c);
    const info = townInfo(getTown(), total);
    if (!info) return { total, info: null, missing: null, pct: 0 };
    const missing = Math.max(0, info.min - total);
    return { total, info, missing, pct: Math.min(100, Math.round(total / info.min * 100)) };
  }

  function updateChrome() {
    const c = getCart();
    if ($('#cart-sum')) $('#cart-sum').textContent = ft(cartTotal(c));
    if ($('#cart-count')) $('#cart-count').textContent = cartCount(c);
    if ($('#topbar')) $('#topbar').innerHTML = topbarHTML();
    renderDrawer();
    renderCartBar();
  }

  function minbarHTML(compact) {
    const s = minState();
    if (!s.info) {
      return '<div class="minbar"><div class="head"><span><strong>Hová szállítsunk?</strong> A minimum és a járatnap településenként más.</span></div>' +
        '<div data-town-check="inline"></div></div>';
    }
    const t = s.info;
    const head = '<div class="head"><span><strong>' + esc(t.town.name) + '</strong> · minimum ' + esc(minText(t.town)) + (t.next ? ' · járat: ' + esc(fmtDate(t.next.date)) : '') + '</span>' +
      '<button type="button" class="link" data-change-town>Másik település</button></div>';
    const bar = '<div class="progress" role="progressbar" aria-label="Rendelési minimum" aria-valuemin="0" aria-valuemax="' + t.min + '" aria-valuenow="' + Math.min(s.total, t.min) + '"><span style="width:' + s.pct + '%"></span></div>';
    const msg = s.missing > 0
      ? '<p class="msg">Még <strong>' + ft(s.missing) + '</strong>, és rendelhetsz – szállítási díj nincs.</p>'
      : '<p class="msg"><strong>Megvan a minimum!</strong> ' + (t.next ? 'Első elérhető járat: ' + esc(fmtDate(t.next.date)) : 'Szállítási díj nincs.') + '</p>';
    return '<div class="minbar">' + (compact ? '' : head) + bar + msg + (compact ? head : '') + '</div>';
  }

  function renderDrawer() {
    const body = $('#drawer-body');
    if (!body) return;
    const c = getCart();
    if (!c.length) {
      body.innerHTML = '<div class="empty"><p><strong>Üres a kosarad.</strong></p><p class="muted" style="margin-top:6px">Nézz körül a kínálatban.</p><a class="btn btn-dark btn-sm" style="margin-top:14px" href="' + url('termekek.html') + '">Termékek</a></div>';
      $('#drawer-foot').innerHTML = '';
      return;
    }
    const note = renderDrawer.added ? '<div class="added-note" role="status">✓ Kosárba tettük: ' + esc(renderDrawer.added) + '</div>' : '';
    renderDrawer.added = null;
    const recs = cartComplements(3);
    body.innerHTML = note + minbarHTML() + c.map((i) => {
      const p = P[i.id];
      return '<div class="mini-item"><img src="' + img(p.img) + '" alt=""><div class="n"><strong>' + esc(p.name) + '</strong><br><span class="muted">' + esc(p.pack) + '</span></div>' +
        '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px"><span class="price" style="font-size:17px">' + ft(p.price * i.qty) + '</span>' +
        qtyHTML(i.id, i.qty, true) + '</div></div>';
    }).join('') + (recs.length ? '<div class="recs"><p class="h">Illik hozzá</p>' + recs.map(recRow).join('') + '</div>' : '');
    const s = minState();
    const can = s.info && s.missing === 0;
    $('#drawer-foot').innerHTML = '<div class="row" style="justify-content:space-between"><span>Részösszeg</span><strong>' + ft(s.total) + '</strong></div>' +
      (can ? '<a class="btn btn-gold btn-block" href="' + url('penztar.html') + '">Tovább a pénztárhoz · ' + ft(s.total) + '</a>'
        : '<button class="btn btn-block" disabled>' + (s.info ? 'Még ' + ft(s.missing) + ' a minimumig' : 'Válassz települést') + '</button>') +
      '<a class="btn btn-outline btn-block btn-sm" href="' + url('kosar.html') + '">Kosár részletei</a>';
    bindTownChecks(body);
  }
  function openDrawer(addedName) {
    renderDrawer.added = typeof addedName === 'string' ? addedName : null;
    renderDrawer();
    $('#drawer').classList.add('is-open');
    $('#drawer').setAttribute('aria-hidden', 'false');
    $('#overlay').classList.add('is-open');
    setTimeout(() => $('#drawer-close').focus(), 50);
  }
  function closeDrawer() {
    const d = $('#drawer');
    if (!d || !d.classList.contains('is-open')) return;
    d.classList.remove('is-open');
    d.setAttribute('aria-hidden', 'true');
    $('#overlay').classList.remove('is-open');
  }

  function renderCartBar() {
    const bar = $('#mcartbar');
    if (!bar) return;
    const c = getCart();
    if (!c.length || PAGE === 'cart' || PAGE === 'checkout' || PAGE === 'thanks') { bar.hidden = true; document.body.classList.remove('has-cartbar'); return; }
    const s = minState();
    bar.hidden = false;
    document.body.classList.add('has-cartbar');
    const line2 = !s.info ? 'Válaszd ki a településed a minimumhoz' : (s.missing > 0 ? 'Még ' + ft(s.missing) + ' a minimumig (' + esc(s.info.town.name) + ')' : 'Megvan a minimum – ingyenes szállítás');
    bar.innerHTML = '<div class="row"><div class="txt">' + cartCount(c) + ' tétel · <strong>' + ft(s.total) + '</strong><br>' + line2 + '</div>' +
      '<button class="btn btn-gold btn-sm" type="button" data-open-drawer>Kosár</button></div>' +
      (s.info ? '<div class="progress" aria-hidden="true"><span style="width:' + s.pct + '%"></span></div>' : '');
  }

  function qtyHTML(id, qty, small) {
    return '<div class="qty' + (small ? ' sm' : '') + '"><button type="button" data-qty-dec="' + id + '" aria-label="Kevesebb">−</button>' +
      '<label class="sr-only" for="q-' + (small ? 'd-' : '') + id + '">Mennyiség</label><input id="q-' + (small ? 'd-' : '') + id + '" inputmode="numeric" value="' + qty + '" data-qty-input="' + id + '">' +
      '<button type="button" data-qty-inc="' + id + '" aria-label="Több">+</button></div>';
  }

  /* ---------- település-ellenőrző widget ---------- */
  function townCheckHTML(mode) {
    const dark = mode !== 'light' && mode !== 'inline';
    const uid = 'tc' + Math.random().toString(36).slice(2, 7);
    return '<form class="' + (dark ? 'town-check' : 'stack') + '" data-tc-form autocomplete="off" style="' + (dark ? '' : 'gap:10px') + '">' +
      (mode === 'inline' ? '' : '<label class="title' + (dark ? '' : ' display') + '" for="' + uid + '" style="' + (dark ? '' : 'font-size:20px') + '">Szállítunk hozzád?</label>') +
      (mode === 'inline' ? '<label class="sr-only" for="' + uid + '">Irányítószám vagy település</label>' : '') +
      '<div class="row suggest"><input id="' + uid + '" class="input" type="text" placeholder="Irányítószám vagy település" aria-autocomplete="list" aria-controls="' + uid + '-l" style="flex:1 1 200px;min-width:0' + (dark ? '' : ';background:#fff;color:var(--ink)') + '">' +
      '<button type="submit" class="' + (dark ? '' : 'btn btn-gold btn-sm') + '">Ellenőrzöm</button>' +
      '<ul class="suggest-list" id="' + uid + '-l" role="listbox" hidden></ul></div>' +
      '<div class="town-result" data-tc-result aria-live="polite" ' + (dark ? '' : 'style="border-color:var(--ink-3)"') + '></div></form>';
  }
  function townResultHTML(t) {
    const i = townInfo(t);
    if (!i) return '';
    return '<span><strong>' + esc(t.name) + '</strong> – szállítunk · ' + esc(runsText(t)) + '</span>' +
      '<span>Következő járat: <strong>' + (i.next ? esc(fmtDate(i.next.date)) : 'jelenleg nincs meghirdetve') + '</strong></span>' +
      (i.next ? '<span>Határidő: ' + esc(fmtCut(i.next.cutoff)) + '</span>' : '') +
      '<span>Minimum: <strong>' + esc(minText(t)) + '</strong></span>';
  }
  function bindTownChecks(root) {
    $$('[data-town-check]', root).forEach((el) => {
      if (el.getAttribute('data-bound')) return;
      el.setAttribute('data-bound', '1');
      el.innerHTML = townCheckHTML(el.getAttribute('data-town-check'));
      const form = $('[data-tc-form]', el);
      const input = $('input', form);
      const list = $('.suggest-list', form);
      const res = $('[data-tc-result]', form);
      const cur = getTown();
      if (cur) { input.value = cur.name; res.innerHTML = townResultHTML(cur); } else res.style.display = 'none';
      let items = [];
      let active = -1;
      const choose = (t) => {
        input.value = t.name;
        list.hidden = true;
        res.style.display = '';
        res.classList.remove('no');
        res.innerHTML = townResultHTML(t);
        setTown(t.name);
      };
      const draw = () => {
        items = findTowns(input.value).slice(0, 8);
        if (!input.value.trim() || !items.length) { list.hidden = true; return; }
        list.innerHTML = items.map((t, ix) => '<li><button type="button" role="option" data-ix="' + ix + '" class="' + (ix === active ? 'is-active' : '') + '"><span>' + esc(t.name) + '</span><small>' + esc(runsText(t)) + ' · min. ' + ft(t.min) + '</small></button></li>').join('');
        list.hidden = false;
      };
      input.addEventListener('input', () => { active = -1; draw(); });
      input.addEventListener('keydown', (e) => {
        if (list.hidden) return;
        if (e.key === 'ArrowDown') { active = Math.min(items.length - 1, active + 1); draw(); e.preventDefault(); }
        if (e.key === 'ArrowUp') { active = Math.max(0, active - 1); draw(); e.preventDefault(); }
        if (e.key === 'Escape') list.hidden = true;
      });
      list.addEventListener('click', (e) => { const b = e.target.closest('[data-ix]'); if (b) choose(items[+b.getAttribute('data-ix')]); });
      const outside = (e) => { if (!form.isConnected) { document.removeEventListener('click', outside); return; } if (!form.contains(e.target)) list.hidden = true; };
      document.addEventListener('click', outside);
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const found = active >= 0 ? items[active] : findTowns(input.value)[0];
        if (found) { choose(found); return; }
        list.hidden = true;
        res.style.display = '';
        res.classList.add('no');
        res.innerHTML = '<span>Erre a településre jelenleg nem szállítunk. <a href="' + url('kapcsolat.html') + '" style="color:inherit;font-weight:700">Írj nekünk</a>, és szólunk, ha arra is indul járat.</span>';
      });
    });
  }

  /* ---------- globális eseménykezelők ---------- */
  document.addEventListener('click', (e) => {
    const add = e.target.closest('[data-add]');
    if (add) {
      const id = add.getAttribute('data-add');
      const qInput = add.getAttribute('data-qty-from') ? $(add.getAttribute('data-qty-from')) : null;
      const q = qInput ? Math.max(1, parseInt(qInput.value, 10) || 1) : 1;
      addToCart(id, q);
      const old = add.innerHTML;
      add.classList.add('is-added');
      if (add.classList.contains('plus')) add.textContent = '✓';
      else if (!add.classList.contains('add-btn')) add.textContent = '✓ Kosárban';
      setTimeout(() => { add.classList.remove('is-added'); add.innerHTML = old; }, 1400);
      if (add.hasAttribute('data-open-after')) openDrawer(P[id].name + (q > 1 ? ' × ' + q : ''));
      else toast('Kosárba tettük: ' + P[id].name + (q > 1 ? ' × ' + q : ''));
      return;
    }
    const sw = e.target.closest('[data-swap]');
    if (sw) {
      const [from, to] = sw.getAttribute('data-swap').split('>');
      setQty(from, 0);
      addToCart(to, 1);
      toast('Lecseréltük: ' + P[to].name);
      return;
    }
    const addMany = e.target.closest('[data-add-many]');
    if (addMany) {
      const list = JSON.parse(addMany.getAttribute('data-add-many'));
      list.forEach(([id, q]) => addToCart(id, q));
      toast(list.length + ' termék a kosárba került');
      openDrawer();
      return;
    }
    const inc = e.target.closest('[data-qty-inc]');
    const dec = e.target.closest('[data-qty-dec]');
    if (inc || dec) {
      const id = (inc || dec).getAttribute(inc ? 'data-qty-inc' : 'data-qty-dec');
      const local = (inc || dec).closest('[data-local-qty]');
      if (local) { // termékoldal: csak a mezőt állítja
        const inp = $('input', local);
        inp.value = Math.max(1, Math.min(99, (parseInt(inp.value, 10) || 1) + (inc ? 1 : -1)));
        inp.dispatchEvent(new Event('change', { bubbles: true }));
        return;
      }
      const it = getCart().find((i) => i.id === id);
      setQty(id, (it ? it.qty : 0) + (inc ? 1 : -1));
      return;
    }
    const rm = e.target.closest('[data-remove]');
    if (rm) { setQty(rm.getAttribute('data-remove'), 0); toast('Eltávolítottuk a kosárból'); return; }
    if (e.target.closest('[data-open-drawer]')) { openDrawer(); return; }
    if (e.target.closest('[data-change-town]')) { e.preventDefault(); store.del('bg_town'); document.dispatchEvent(new CustomEvent('bg:town')); return; }
  });
  document.addEventListener('change', (e) => {
    const inp = e.target.closest('[data-qty-input]');
    if (inp && !inp.closest('[data-local-qty]')) setQty(inp.getAttribute('data-qty-input'), parseInt(inp.value, 10) || 0);
  });
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (f.matches('[data-demo-form]')) {
      e.preventDefault();
      if (!f.checkValidity()) { f.reportValidity(); return; }
      toast(f.getAttribute('data-demo-form') || 'Köszönjük! (Demó: az űrlap nem küld e-mailt.)');
      f.reset();
    }
  });
  document.addEventListener('bg:cart', () => { updateChrome(); if (pageRerender) pageRerender(); });
  document.addEventListener('bg:town', () => { updateChrome(); $$('[data-town-check]').forEach((el) => el.removeAttribute('data-bound')); bindTownChecks(document); if (pageRerender) pageRerender(); });

  let pageRerender = null;

  /* ================= oldalak ================= */
  const pages = {};

  pages.home = function () {
    const fill = (sel, html) => { const el = $(sel); if (el) el.innerHTML = html; };
    fill('#home-cats', BG.categories.map((c) => tile(c.name, c.sub, c.img, url('termekek.html?kategoria=' + c.id))).join(''));
    fill('#home-occ', BG.occasions.map((o) => '<a class="chip" href="' + url('termekek.html?alkalom=' + o.id) + '">' + esc(o.name) + '</a>').join(''));
    fill('#home-featured', BG.featured.map((id) => productCard(P[id])).join(''));
    fill('#home-gifts', ['ajandekdoboz-10', 'ajandekkosar-12', 'bosegtal'].map((id) => {
      const p = P[id];
      return '<a class="card-dark" href="' + url('termek.html?id=' + id) + '" style="padding:0;overflow:hidden;text-decoration:none;color:var(--cream);border:1px solid var(--ink-3);background:var(--ink-2)">' +
        '<img loading="lazy" src="' + img(p.img) + '" alt="' + esc(p.name) + '" style="width:100%;aspect-ratio:1/1;object-fit:cover">' +
        '<div style="padding:14px 16px 18px;display:flex;flex-direction:column;gap:4px"><strong>' + esc(p.name) + '</strong><span class="price" style="font-size:22px;color:var(--gold)">' + ft(p.price) + '</span></div></a>';
    }).join(''));
    fill('#home-routes', routeCards());
    fill('#home-reviews', reviewsHTML());
    fill('#home-recipes', BG.recipes.slice(0, 3).map(recipeCard).join(''));
  };

  function routeCards() {
    return BG.areas.map((z) => {
      const towns = BG.towns.filter((t) => t.zone === z.id);
      if (!towns.length) return '';
      return '<div class="card route-card"><p class="display" style="font-size:24px;font-weight:500">' + esc(z.name) + '</p>' +
        '<p class="muted" style="font-size:15px">' + esc(towns.slice(0, 6).map((t) => t.name).join(', ')) + (towns.length > 6 ? ' és még ' + (towns.length - 6) : '') + '</p>' +
        '<div class="foot"><span>Járatnap: <strong>' + esc(areaDays(z.id)) + '</strong></span><span>Minimum: <strong>' + ft(Math.min.apply(null, towns.map((t) => t.min))) + '-tól</strong></span></div></div>';
    }).join('');
  }
  function areaDays(zoneId) {
    const runs = [].concat.apply([], BG.towns.filter((t) => t.zone === zoneId).map((t) => t.runs));
    const once = Array.from(new Set(runs.filter((r) => r.date).map((r) => r.date))).sort().map((d) => fmtDate(parseYmd(d)) + ' (egyszeri)');
    const days = Array.from(new Set(runs.filter((r) => !r.date).map((r) => r.day))).sort().map((d) => DAYNAMES[d]);
    return days.concat(once).join(', ');
  }
  function reviewsHTML() {
    return BG.reviews.map((r) => '<figure class="review"><span class="stars" role="img" aria-label="5 csillagból 5">★★★★★</span><blockquote>' + esc(r.text) + '</blockquote><figcaption>' + esc(r.name) + ' · Google</figcaption></figure>').join('');
  }
  function recipeCard(r) {
    return '<a class="rcard" href="' + url('recept.html?id=' + r.id) + '"><div style="position:relative"><img loading="lazy" src="' + img(r.img) + '" alt="">' +
      (r.video ? '<span class="tag gold" style="position:absolute;top:12px;left:12px">▶ Videó</span>' : '') + '</div>' +
      '<span class="meta">' + esc(r.meta) + '</span><span class="t">' + esc(r.title) + '</span></a>';
  }

  /* ---------- terméklista: kategória / alkalom / keresés ---------- */
  pages.list = function () {
    const cat = params.get('kategoria');
    const occ = params.get('alkalom');
    const q = params.get('q') || '';
    const head = $('#list-head');
    let base = BG.products.slice();
    let title = 'Összes termék';
    let intro = 'A teljes kínálat: ' + BG.products.length + ' mangalicatermék a gesztelyi kisüzemből.';
    let crumb = 'Termékek';
    if (cat && CAT[cat]) { base = base.filter((p) => p.cat === cat); title = CAT[cat].name; intro = CAT[cat].intro; crumb = CAT[cat].name; }
    if (occ && OCC[occ]) { base = base.filter((p) => p.occ.includes(occ)); title = OCC[occ].name; intro = OCC[occ].intro; crumb = OCC[occ].name; }
    if (q) {
      const n = norm(q);
      base = base.filter((p) => norm(p.name + ' ' + (p.ingr || '') + ' ' + CAT[p.cat].name).includes(n));
      title = base.length + ' találat erre: „' + q + '”';
      intro = '';
      crumb = 'Keresés';
    }
    document.title = (q ? 'Keresés: ' + q : title) + ' – Bio-Goods Mangalica';

    if (occ && OCC[occ]) {
      head.innerHTML = '<section class="dark-head"><img class="bg" src="' + img(OCC[occ].img) + '" alt=""><div class="wrap">' +
        '<nav class="crumbs" aria-label="Morzsamenü"><a href="' + url('index.html') + '">Főoldal</a><span aria-hidden="true">/</span><span>Alkalmak</span><span aria-hidden="true">/</span><span aria-current="page" style="color:var(--cream)">' + esc(crumb) + '</span></nav>' +
        '<h1>' + esc(title) + '</h1><p class="lead" style="color:#E8E0D2">' + esc(intro) + '</p></div></section>' + (occ === 'kerti-parti' ? bundleHTML() : '');
    } else {
      head.innerHTML = '<div class="wrap page-head"><nav class="crumbs" aria-label="Morzsamenü"><a href="' + url('index.html') + '">Főoldal</a><span aria-hidden="true">/</span>' +
        (cat ? '<a href="' + url('termekek.html') + '">Termékek</a><span aria-hidden="true">/</span>' : '') + '<span aria-current="page">' + esc(crumb) + '</span></nav>' +
        '<div class="row" style="justify-content:space-between;align-items:flex-end;margin-top:16px;gap:20px"><div style="max-width:760px"><h1 style="margin:0">' + esc(title) + '</h1>' + (intro ? '<p class="lead" style="margin-top:12px">' + esc(intro) + '</p>' : '') + '</div>' +
        '<div data-town-check="light" style="flex:0 1 420px"></div></div>' +
        (q ? '<form role="search" class="row" style="margin-top:20px;max-width:640px" action="termekek.html"><label class="sr-only" for="lq">Keresés</label><input id="lq" class="input" name="q" type="search" value="' + esc(q) + '" style="flex:1;height:56px;border-radius:28px;padding:0 22px;border:1.5px solid var(--ink)"><button class="btn btn-dark" type="submit">Keresés</button></form>' : '') +
        '</div>';
    }
    // kategória-chipek
    $('#list-chips').innerHTML = '<a class="chip' + (!cat && !occ ? ' is-active' : '') + '" href="' + url('termekek.html') + '">Mind</a>' +
      BG.categories.map((c) => '<a class="chip' + (c.id === cat ? ' is-active' : '') + '" href="' + url('termekek.html?kategoria=' + c.id) + '"' + (c.id === cat ? ' aria-current="page"' : '') + '>' + esc(c.name) + '</a>').join('') +
      BG.occasions.map((o) => '<a class="chip' + (o.id === occ ? ' is-active' : '') + '" href="' + url('termekek.html?alkalom=' + o.id) + '"' + (o.id === occ ? ' aria-current="page"' : '') + '>' + esc(o.name) + '</a>').join('');

    // szűrők
    const maxPrice = Math.ceil(Math.max.apply(null, base.map((p) => p.price).concat([1000])) / 100) * 100;
    const occOpts = BG.occasions.filter((o) => o.id !== occ && base.some((p) => p.occ.includes(o.id)));
    $('#filters').innerHTML =
      (occOpts.length ? '<fieldset class="filter-group"><legend>Alkalom</legend>' + occOpts.map((o) => '<label><input type="checkbox" name="occ" value="' + o.id + '">' + esc(o.name) + '</label>').join('') + '</fieldset>' : '') +
      '<fieldset class="filter-group"><legend>Kiszerelés</legend><label><input type="checkbox" name="size" value="s">500 g alatt</label><label><input type="checkbox" name="size" value="m">500 g – 1 kg alatt</label><label><input type="checkbox" name="size" value="l">1 kg és felette</label><label><input type="checkbox" name="size" value="x">Darabra / csomag</label></fieldset>' +
      '<fieldset class="filter-group"><legend>Jellemző</legend><label><input type="checkbox" name="hot" value="1">Csípős</label><label><input type="checkbox" name="grill" value="1">Grillre</label><label><input type="checkbox" name="smoked" value="1">Bükkfán füstölt</label></fieldset>' +
      '<div><label class="display" for="maxp" style="font-size:18px;display:block;margin-bottom:6px">Ár</label><span class="muted" id="maxp-l">Legfeljebb ' + ft(maxPrice) + '</span>' +
      '<input id="maxp" type="range" min="0" max="' + maxPrice + '" step="100" value="' + maxPrice + '" style="width:100%;accent-color:var(--ink)"></div>' +
      '<button type="button" class="btn btn-outline btn-sm" id="f-reset">Szűrők törlése</button>';

    const sizeOf = (p) => { const g = grams(p); if (!g) return 'x'; return g < 500 ? 's' : g < 1000 ? 'm' : 'l'; };
    function apply() {
      const f = $('#filters');
      const occs = $$('input[name="occ"]:checked', f).map((i) => i.value);
      const sizes = $$('input[name="size"]:checked', f).map((i) => i.value);
      const hot = $('input[name="hot"]', f).checked;
      const grill = $('input[name="grill"]', f).checked;
      const smoked = $('input[name="smoked"]', f).checked;
      const max = +$('#maxp').value;
      $('#maxp-l').textContent = 'Legfeljebb ' + ft(max);
      let list = base.filter((p) =>
        (!occs.length || occs.some((o) => p.occ.includes(o))) &&
        (!sizes.length || sizes.includes(sizeOf(p))) &&
        (!hot || p.tag === 'Csípős') && (!grill || p.tag === 'Grillre' || p.occ.includes('kerti-parti')) &&
        (!smoked || /füstöl/i.test(p.ingr || '')) && p.price <= max);
      const sort = $('#sort').value;
      if (sort === 'price') list.sort((a, b) => a.price - b.price);
      if (sort === 'price-desc') list.sort((a, b) => b.price - a.price);
      if (sort === 'unit') list.sort((a, b) => (a.unit || 1e9) - (b.unit || 1e9));
      if (sort === 'name') list.sort((a, b) => a.name.localeCompare(b.name, 'hu'));
      $('#count').innerHTML = '<strong style="color:var(--ink)">' + list.length + ' termék</strong>' + (list.length !== base.length ? ' · szűrve ' + base.length + '-ból' : '');
      $('#grid').innerHTML = list.length ? list.map(productCard).join('') :
        '<div class="empty" style="grid-column:1/-1"><p><strong>Nincs a szűrésnek megfelelő termék.</strong></p><p class="muted" style="margin-top:6px">Próbálj kevesebb szűrőt, vagy <a href="' + url('kapcsolat.html') + '">kérdezz minket</a>.</p></div>';
    }
    $('#filters').addEventListener('change', apply);
    $('#filters').addEventListener('input', (e) => { if (e.target.id === 'maxp') apply(); });
    $('#sort').addEventListener('change', apply);
    $('#f-reset').addEventListener('click', () => { $$('#filters input[type=checkbox]').forEach((i) => { i.checked = false; }); $('#maxp').value = maxPrice; apply(); });
    $('#filter-toggle').addEventListener('click', () => {
      const a = $('#filters');
      const open = a.classList.toggle('is-open');
      $('#filter-toggle').setAttribute('aria-expanded', String(open));
    });
    apply();
  };

  function bundleHTML() {
    const items = [['tomahawk', 2], ['bbq-oldalas', 1], ['grillkolbasz-magyaros', 1], ['hamburgerhus', 1]];
    const total = items.reduce((s, [id, q]) => s + P[id].price * q, 0);
    return '<section class="wrap" style="padding-top:56px"><div class="card" style="display:flex;flex-wrap:wrap;gap:32px;align-items:center;padding:32px">' +
      '<div style="flex:1 1 320px;display:flex;flex-direction:column;gap:10px"><p class="kicker" style="margin:0">Összeállítottuk</p><h2 style="font-size:40px">Grillcsomag 4–6 főre</h2>' +
      '<p class="muted">Egy kattintással a kosárba kerül mind, utána bármelyik tételt módosíthatod.</p>' +
      '<p><span class="price" style="font-size:40px">' + ft(total) + '</span> <span class="muted">' + items.length + ' termék</span></p>' +
      '<button type="button" class="btn btn-gold" style="align-self:flex-start" data-add-many=\'' + JSON.stringify(items) + '\'>Mind a kosárba</button></div>' +
      '<ul class="grid" style="flex:2 1 520px;list-style:none;margin:0;padding:0;--min:240px">' + items.map(([id, q]) => {
        const p = P[id];
        return '<li style="display:flex;align-items:center;gap:12px;padding:10px;border-radius:14px;background:var(--cream)"><img src="' + img(p.img) + '" alt="" style="width:64px;height:64px;border-radius:10px;object-fit:cover">' +
          '<div><strong>' + esc(p.name) + '</strong><br><span class="muted">' + q + ' × ' + esc(p.pack) + ' · ' + ft(p.price * q) + '</span></div></li>';
      }).join('') + '</ul></div></section>';
  }

  /* ---------- termékoldal ---------- */
  pages.product = function () {
    const p = P[params.get('id')];
    const root = $('#pdp');
    if (!p) {
      root.innerHTML = '<div class="empty" style="margin:56px 0"><h1 style="font-size:36px">Ez a termék nem található</h1><p class="muted" style="margin-top:8px">Lehet, hogy már nincs a kínálatban.</p><a class="btn btn-dark" style="margin-top:16px" href="' + url('termekek.html') + '">Vissza a termékekhez</a></div>';
      return;
    }
    document.title = p.name + ' – Bio-Goods Mangalica';
    const c = CAT[p.cat];
    const v = p.variant ? P[p.variant] : null;
    const occNames = p.occ.map((o) => OCC[o] && OCC[o].name).filter(Boolean);
    const recipe = BG.recipes.find((r) => r.shop.some(([id]) => id === p.id));
    const comps = complements(p);
    const fbt = [p].concat(comps.slice(0, 2));
    const related = comps.slice(2).concat(BG.products.filter((x) => x.cat === p.cat && x.id !== p.id && x.id !== p.variant))
      .filter((x, i, a) => a.indexOf(x) === i && !fbt.includes(x)).slice(0, 4);
    const variantHTML = v ? '<fieldset style="margin:0;padding:0;border:0"><legend style="padding:0;margin-bottom:8px;font-weight:700;font-size:15px">Kivitel</legend><div class="variants">' +
      [p, v].sort((a, b) => a.name.localeCompare(b.name, 'hu')).map((x) => '<a class="variant' + (x.id === p.id ? ' is-active' : '') + '" href="' + url('termek.html?id=' + x.id) + '"' + (x.id === p.id ? ' aria-current="true"' : '') + '><strong>' + (/csont nélkül/i.test(x.name) ? 'Csont nélkül' : 'Csontos') + '</strong><span>' + esc(x.pack) + ' · ' + ft(x.price) + '</span></a>').join('') + '</div></fieldset>' : '';
    root.innerHTML =
      '<nav class="crumbs" aria-label="Morzsamenü" style="padding-top:24px"><a href="' + url('index.html') + '">Főoldal</a><span aria-hidden="true">/</span><a href="' + url('termekek.html?kategoria=' + c.id) + '">' + esc(c.name) + '</a><span aria-hidden="true">/</span><span aria-current="page">' + esc(p.name) + '</span></nav>' +
      '<div class="pdp"><div class="gallery"><img class="main" src="' + img(p.img) + '" alt="' + esc(p.name) + '"></div>' +
      '<div class="info"><p class="kicker" style="margin:0">' + esc([c.name].concat(occNames).join(' · ')) + '</p>' +
      '<h1>' + esc(p.name) + '</h1>' +
      '<div class="pricebox"><span class="price">' + ft(p.price) + '</span>' + (p.old ? '<span class="muted" style="text-decoration:line-through">' + ft(p.old) + '</span>' : '') + '<span class="muted">' + esc(metaText(p)) + '</span></div>' +
      (p.desc ? '<p class="lead" style="font-size:17px">' + esc(p.desc) + '</p>' : '') +
      variantHTML +
      '<div class="row"><div class="qty" data-local-qty><button type="button" data-qty-dec="x" aria-label="Kevesebb">−</button><label class="sr-only" for="pq">Mennyiség</label><input id="pq" inputmode="numeric" value="1"><button type="button" data-qty-inc="x" aria-label="Több">+</button></div>' +
      '<button type="button" class="btn btn-gold" style="flex:1 1 220px;min-height:56px" data-add="' + p.id + '" data-qty-from="#pq" data-open-after id="pdp-add">Kosárba · ' + ft(p.price) + '</button></div>' +
      upsellBox(p) +
      '<div id="pdp-delivery"></div>' +
      '<ul class="bullets"><li>Saját tenyészet</li><li>MOE eredetigazolás</li><li>GMO-mentes takarmány</li><li>SZÉP-kártyával is fizethető</li></ul></div></div>';

    $('#pq').addEventListener('change', () => { const q = Math.max(1, parseInt($('#pq').value, 10) || 1); $('#pq').value = q; $('#pdp-add').textContent = 'Kosárba · ' + ft(p.price * q); });
    const drawDelivery = () => {
      const i = townInfo(getTown());
      $('#pdp-delivery').innerHTML = i
        ? '<div class="delivery-box"><div class="row" style="justify-content:space-between"><span class="t">Szállítás: ' + esc(i.town.name) + '</span><a href="#" data-change-town>Másik település</a></div>' +
          '<div class="d"><span>Következő járat: <strong>' + (i.next ? esc(fmtDate(i.next.date)) : 'egyeztetés alatt') + '</strong></span>' + (i.next ? '<span>Rendelj eddig: <strong>' + esc(fmtCut(i.next.cutoff)) + '</strong></span>' : '') + '<span>Minimum: <strong>' + esc(minText(i.town)) + '</strong></span></div></div>'
        : '<div data-town-check="dark"></div>';
      bindTownChecks($('#pdp-delivery'));
    };
    drawDelivery();
    pageRerender = drawDelivery;

    $('#pdp-details').innerHTML = '<div class="tabs" role="tablist" aria-label="Termékinformációk">' +
      '<button type="button" role="tab" aria-selected="true" aria-controls="tp1" id="tb1">Termékadatok</button>' +
      '<button type="button" role="tab" aria-selected="false" aria-controls="tp2" id="tb2">Tárolás és elkészítés</button>' +
      '<button type="button" role="tab" aria-selected="false" aria-controls="tp3" id="tb3">Szállítás és fizetés</button></div>' +
      '<div class="split" style="align-items:flex-start">' +
      '<div><div role="tabpanel" id="tp1" aria-labelledby="tb1"><dl class="spec">' +
      '<dt>Kiszerelés</dt><dd>' + esc(p.pack) + '</dd>' +
      (p.unit ? '<dt>Egységár</dt><dd>' + esc(unitText(p)) + '</dd>' : '') +
      '<dt>Összetevők</dt><dd>' + esc(p.ingr || '[az ügyféltől]') + '</dd>' +
      '<dt>Allergének</dt><dd>' + esc(p.allergen || '[megerősítendő]') + '</dd>' +
      '<dt>Származás</dt><dd>Saját tenyészet, gesztelyi feldolgozás, MOE eredetigazolás</dd></dl></div>' +
      '<div role="tabpanel" id="tp2" aria-labelledby="tb2" hidden><dl class="spec"><dt>Tárolás</dt><dd>[hőmérséklet – az ügyféltől]</dd><dt>Eltarthatóság</dt><dd>[nap, hűtőben / fagyasztva – az ügyféltől]</dd><dt>Csomagolás</dt><dd>[pl. vákuumozva – az ügyféltől]</dd></dl></div>' +
      '<div role="tabpanel" id="tp3" aria-labelledby="tb3" hidden><dl class="spec"><dt>Szállítás</dt><dd>Saját járattal, a településed járatnapján. Rendelés a járat előtti napon 12:00-ig. <a href="' + url('szallitas.html') + '">Szállítási területek</a></dd><dt>Fizetés</dt><dd>Bankkártya (SimplePay), OTP, K&amp;H és MKB SZÉP-kártya, készpénz a sofőrnél. <a href="' + url('fizetes.html') + '">Részletek</a></dd></dl></div></div>' +
      (recipe ? '<a class="card-dark" href="' + url('recept.html?id=' + recipe.id) + '" style="padding:0;overflow:hidden;text-decoration:none;color:var(--cream)"><img src="' + img(recipe.img) + '" alt="" style="width:100%;aspect-ratio:16/9;object-fit:cover"><div style="padding:20px 22px 24px;display:flex;flex-direction:column;gap:6px"><span class="kicker light" style="margin:0">Recept ehhez a termékhez</span><span class="display" style="font-size:26px;font-weight:500">' + esc(recipe.title) + '</span><span style="color:var(--on-dark)">Elolvasom →</span></div></a>' : '<div></div>') +
      '</div>';
    $('#pdp-details').addEventListener('click', (e) => {
      const t = e.target.closest('[role=tab]');
      if (!t) return;
      $$('#pdp-details [role=tab]').forEach((b) => b.setAttribute('aria-selected', String(b === t)));
      $$('#pdp-details [role=tabpanel]').forEach((pn) => { pn.hidden = pn.id !== t.getAttribute('aria-controls'); });
    });
    $('#pdp-related').innerHTML = related.map(productCard).join('');
    // Gyakran együtt vásárolják
    const fbtEl = $('#pdp-fbt');
    if (fbtEl) {
      fbtEl.innerHTML = '<h2 style="margin-bottom:20px">Gyakran együtt vásárolják</h2><div class="fbt"><div class="fbt-items">' +
        fbt.map((x, ix) => (ix ? '<span class="fbt-plus" aria-hidden="true">+</span>' : '') +
          '<label class="fbt-item"><input type="checkbox" checked value="' + x.id + '" aria-label="' + esc(x.name) + '"><img src="' + img(x.img) + '" alt="">' +
          '<span class="n">' + (ix === 0 ? 'Ez a termék: ' : '') + esc(x.name) + '</span><span class="m">' + esc(x.pack) + ' · <strong style="color:var(--ink)">' + ft(x.price) + '</strong></span></label>').join('') +
        '</div><div class="fbt-sum"><p class="muted">Együtt</p><p class="price" style="font-size:40px" id="fbt-total"></p><button type="button" class="btn btn-gold" id="fbt-add">A kijelöltek kosárba</button>' +
        '<p class="muted" style="font-size:13px">Egy rendelésben, ugyanazzal a járattal érkeznek.</p></div></div>';
      const sel = () => $$('#pdp-fbt input:checked').map((i) => i.value);
      const upd = () => {
        const ids = sel();
        $('#fbt-total').textContent = ft(ids.reduce((t, id) => t + P[id].price, 0));
        $('#fbt-add').disabled = !ids.length;
        $('#fbt-add').textContent = ids.length === fbt.length ? 'Mind a ' + ids.length + ' kosárba' : ids.length ? 'A kijelölt ' + ids.length + ' kosárba' : 'Jelölj ki egy terméket';
      };
      fbtEl.addEventListener('change', upd);
      $('#fbt-add').addEventListener('click', () => {
        const ids = sel();
        ids.forEach((id) => addToCart(id, 1));
        openDrawer(ids.length + ' termék');
      });
      upd();
    }
  };

  /* ---------- kosár ---------- */
  pages.cart = function () {
    const draw = () => {
      const c = getCart();
      const root = $('#cart-root');
      if (!c.length) {
        root.innerHTML = '<div class="empty" style="margin:24px 0 80px"><p class="display" style="font-size:28px">Üres a kosarad</p><p class="muted" style="margin-top:6px">Válogass a kínálatból – a kosár a böngésződben megmarad.</p><a class="btn btn-dark" style="margin-top:16px" href="' + url('termekek.html') + '">Termékek</a></div>';
        return;
      }
      const s = minState();
      const disc = discountFor(s.total);
      const pay = s.total - disc;
      const inCart = c.map((i) => i.id);
      const need = s.missing || 0;
      const up = need > 0 ? BG.products.filter((p) => !inCart.includes(p.id) && !p.giftcard && p.price >= need && p.price <= need + 3000).sort((a, b) => a.price - b.price).slice(0, 3) : [];
      const cross = cartComplements(6).filter((x) => !up.includes(x)).slice(0, 3);
      const ok = s.info && s.missing === 0;
      const cp = coupon();
      root.innerHTML = '<div class="cart-layout"><div class="main">' + minbarHTML() +
        '<div class="cart-list">' + c.map((i) => {
          const p = P[i.id];
          return '<div class="cart-item"><img src="' + img(p.img) + '" alt=""><div class="name"><a href="' + url('termek.html?id=' + p.id) + '">' + esc(p.name) + '</a><p class="muted" style="font-size:14px;margin-top:2px">' + esc(metaText(p)) + '</p></div>' +
            qtyHTML(p.id, i.qty) + '<span class="line-total">' + ft(p.price * i.qty) + '</span><button type="button" class="icon-btn" data-remove="' + p.id + '" aria-label="Törlés: ' + esc(p.name) + '" style="color:var(--muted)">' + ICON.trash + '</button>' +
            ((() => { const big = biggerPack(p); return big && !inCart.includes(big.id) ? '<div class="line-upsell"><span><strong>Tipp:</strong> ' + esc(big.name) + ' (' + esc(big.pack) + ', ' + ft(big.price) + ') – ' + num(big.unit) + '\u00a0Ft/kg <span class="save">−' + savePct(p, big) + '% / kg</span></span><button type="button" data-swap="' + p.id + '>' + big.id + '">Erre cserélem</button></div>' : ''; })()) + '</div>';
        }).join('') + '</div>' +
        (up.length ? '<div><h2 style="font-size:28px;margin-bottom:6px">Ezekkel meglesz a minimum</h2><p class="muted" style="margin-bottom:14px">Még ' + ft(need) + ' hiányzik – bármelyik elég hozzá.</p><div class="grid products" style="--min:200px">' + up.map(productCard).join('') + '</div></div>' : '') +
        (cross.length ? '<div><h2 style="font-size:28px;margin-bottom:14px">Illik a kosaradhoz</h2><div class="grid products" style="--min:200px">' + cross.map(productCard).join('') + '</div></div>' : '') +
        '</div><aside class="summary" aria-label="Összesítés"><h2 style="font-size:26px">Összesítés</h2>' +
        '<div class="line"><span>Részösszeg (' + cartCount(c) + ' db)</span><span>' + ft(s.total) + '</span></div>' +
        (disc ? '<div class="line" style="color:var(--ok)"><span>Kedvezmény (' + esc(cp.code) + ')</span><span>−' + ft(disc) + '</span></div>' : '') +
        '<div class="line"><span>Házhozszállítás</span><strong>' + (ok ? 'Ingyenes' : s.info ? 'a minimumtól ingyenes' : '–') + '</strong></div>' +
        (s.info && s.info.next ? '<div class="line muted" style="font-size:15px"><span>Járatnap</span><span>' + esc(fmtDate(s.info.next.date)) + ' · ' + esc(s.info.town.name) + '</span></div>' : '') +
        '<form class="field" id="coupon-form">Kupon vagy ajándékkártya<span class="row" style="flex-wrap:nowrap"><input name="code" class="input" style="flex:1;min-width:0;height:46px" placeholder="pl. APP5"' + (cp ? ' value="' + esc(cp.code) + '"' : '') + '><button class="btn btn-outline btn-sm" type="submit">' + (cp ? 'Törlés' : 'Beváltom') + '</button></span></form>' +
        '<div class="line total"><strong>Fizetendő</strong><span class="price">' + ft(pay) + '</span></div>' +
        (ok ? '<a class="btn btn-gold btn-block" href="' + url('penztar.html') + '">Tovább a pénztárhoz</a>'
          : '<button class="btn btn-block" disabled>' + (s.info ? 'Még ' + ft(s.missing) + ' a minimumig' : 'Előbb válassz települést') + '</button>') +
        '<p class="muted" style="font-size:13px">Fizetés: bankkártya, OTP, K&amp;H és MKB SZÉP-kártya, készpénz a sofőrnél.</p></aside></div>';
      bindTownChecks(root);
      $('#coupon-form').addEventListener('submit', (e) => {
        e.preventDefault();
        if (coupon()) { store.del('bg_coupon'); toast('A kupont eltávolítottuk'); draw(); return; }
        const code = norm(e.target.code.value).toUpperCase();
        if (code === 'APP5') { store.set('bg_coupon', { code: 'APP5', pct: 5 }); toast('Átállási kedvezmény: −5%'); draw(); } else toast('Ez a kód nem érvényes (demóban: APP5)');
      });
    };
    pageRerender = draw;
    draw();
  };

  /* ---------- pénztár ---------- */
  pages.checkout = function () {
    const c = getCart();
    const root = $('#checkout-root');
    if (!c.length) {
      root.innerHTML = '<div class="empty" style="margin:48px 0"><p class="display" style="font-size:28px">Üres a kosarad</p><a class="btn btn-dark" style="margin-top:16px" href="' + url('termekek.html') + '">Termékek</a></div>';
      return;
    }
    const town = getTown();
    const saved = store.get('bg_customer', {});
    root.innerHTML = '<div class="checkout"><form id="co" novalidate>' +
      '<h1 style="font-size:48px">Pénztár</h1>' +
      '<fieldset class="panel"><legend>1 · Elérhetőség</legend><p class="muted" style="font-size:14px">Vendégként is rendelhetsz. A demóban nincs valódi fiók.</p>' +
      '<div class="form-grid"><label class="field">E-mail-cím<input name="email" type="email" required autocomplete="email" value="' + esc(saved.email || '') + '"></label>' +
      '<label class="field">Telefonszám (a sofőrnek)<input name="phone" type="tel" required autocomplete="tel" value="' + esc(saved.phone || '') + '" placeholder="+36 30 123 4567"></label></div></fieldset>' +
      '<fieldset class="panel"><legend>2 · Szállítási cím</legend>' +
      '<label class="field">Név<input name="name" required autocomplete="name" value="' + esc(saved.name || '') + '"></label>' +
      '<div class="form-grid"><label class="field" style="max-width:180px">Irányítószám<input name="zip" inputmode="numeric" required autocomplete="postal-code" value="' + esc(saved.zip || '') + '"></label>' +
      '<label class="field" id="town-field">Település<input name="town" required autocomplete="address-level2" value="' + esc(town ? town.name : (saved.town || '')) + '" list="town-list"></label></div>' +
      '<datalist id="town-list">' + BG.towns.map((t) => '<option value="' + esc(t.name) + '">').join('') + '</datalist>' +
      '<p class="field-msg" id="town-msg" aria-live="polite"></p>' +
      '<label class="field">Utca, házszám<input name="street" required autocomplete="street-address" value="' + esc(saved.street || '') + '"></label>' +
      '<label class="field">Megjegyzés a sofőrnek (nem kötelező)<textarea name="note" rows="2" placeholder="Kapukód, emelet, mikor vagy otthon…"></textarea></label></fieldset>' +
      '<fieldset class="panel"><legend>3 · Járatnap</legend><p class="muted" style="font-size:14px" id="days-intro"></p><div class="radio-cards" id="days"></div></fieldset>' +
      '<fieldset class="panel"><legend>4 · Fizetés</legend><div class="radio-list">' +
      [['card', 'Bankkártya online', 'SimplePay – Visa, Mastercard, Maestro'], ['otpszep', 'OTP SZÉP-kártya', 'Az OTP SZÉP-kártya felületén'], ['khszep', 'K&H SZÉP-kártya', 'A K&H SZÉP-kártya felületén'], ['mkbszep', 'MKB SZÉP-kártya', 'Az MKB SZÉP-kártya felületén'], ['cash', 'Készpénz a sofőrnél', 'Pontos összeggel, átvételkor']]
        .map(([v, n, d], ix) => '<label class="radio-card"><input type="radio" name="pay" value="' + v + '"' + (ix === 0 ? ' checked' : '') + '><span><strong>' + esc(n) + '</strong><small style="display:block">' + esc(d) + '</small></span></label>').join('') +
      '</div></fieldset>' +
      '<fieldset class="panel"><legend>5 · Számlázás és jóváhagyás</legend>' +
      '<label class="check"><input type="checkbox" name="sameBilling" checked>A számlázási cím megegyezik a szállítási címmel</label>' +
      '<label class="check"><input type="checkbox" name="company" id="company-cb">Céges számlát kérek</label>' +
      '<div class="form-grid" id="company-fields" hidden><label class="field">Cégnév<input name="companyName"></label><label class="field">Adószám<input name="taxNo"></label></div>' +
      '<hr style="border:0;border-top:1px solid var(--line);margin:4px 0">' +
      '<label class="check"><input type="checkbox" name="terms" required><span>Elolvastam és elfogadom az <a href="' + url('aszf.html') + '">ÁSZF-et</a> és az <a href="' + url('aszf.html#adatkezeles') + '">adatkezelési tájékoztatót</a>. <span style="color:var(--error)">*</span></span></label>' +
      '<label class="check"><input type="checkbox" name="newsletter"><span>Kérek értesítést a járatokról és a szezonális ajánlatokról (nem kötelező).</span></label></fieldset>' +
      '<p class="field-msg err" id="co-err" aria-live="assertive"></p></form>' +
      '<aside class="summary" aria-label="Rendelés összesítése" id="co-sum"></aside></div>';

    const form = $('#co');
    const tInput = form.town;
    $('#company-cb').addEventListener('change', (e) => { $('#company-fields').hidden = !e.target.checked; });

    function currentTown() { return townByName(tInput.value.trim()) || findTowns(tInput.value)[0] || null; }
    function drawDays() {
      const t = currentTown();
      const fld = $('#town-field');
      const msg = $('#town-msg');
      const total = cartTotal();
      fld.classList.remove('is-ok', 'is-error');
      if (!tInput.value.trim()) { msg.textContent = ''; $('#days').innerHTML = ''; $('#days-intro').textContent = 'Előbb add meg a települést.'; drawSum(null); return; }
      if (!t) {
        fld.classList.add('is-error');
        msg.className = 'field-msg err';
        msg.textContent = 'Erre a településre jelenleg nem szállítunk.';
        $('#days').innerHTML = '';
        $('#days-intro').textContent = '';
        drawSum(null);
        return;
      }
      fld.classList.add('is-ok');
      const missing = Math.max(0, t.min - total);
      msg.className = 'field-msg ' + (missing ? 'err' : 'ok');
      msg.textContent = missing ? '✗ ' + t.name + ': a minimum ' + minText(t) + ' – még ' + ft(missing) + ' hiányzik.' : '✓ Szállítunk ide: ' + t.name + ' · minimum ' + minText(t) + ' · megvan';
      const opts = nextDeliveries(t, 4);
      const firstOk = opts.findIndex((o) => total >= o.min);
      $('#days-intro').textContent = t.name + ': ' + runsText(t) + '. Válaszd ki a kiszállítás napját.';
      $('#days').innerHTML = opts.map((o, ix) => {
        const ok = total >= o.min;
        return '<label class="radio-card' + (ok ? '' : ' is-disabled') + '"><span class="top"><input type="radio" name="day" value="' + ymd(o.date) + '" data-min="' + o.min + '"' + (ix === firstOk ? ' checked' : '') + (ok ? '' : ' disabled') + ' required>' + esc(fmtDate(o.date)) + (o.once ? ' (egyszeri)' : '') + '</span>' +
          '<small>Rendelés eddig: ' + esc(fmtCut(o.cutoff)) + ' · min. ' + ft(o.min) + (ok ? '' : ' – még ' + ft(o.min - total)) + '</small></label>';
      }).join('') || '<p class="muted">Erre a településre jelenleg nincs meghirdetett járat.</p>';
      drawSum(t);
    }
    function drawSum(t) {
      const cc = getCart();
      const total = cartTotal(cc);
      const disc = discountFor(total);
      const ok = t && total >= t.min;
      $('#co-sum').innerHTML = '<h2 style="font-size:24px">Rendelésed</h2><div class="mini-items">' + cc.map((i) => {
        const p = P[i.id];
        return '<div class="mini-item"><img src="' + img(p.img) + '" alt=""><span class="n"><strong>' + esc(p.name) + '</strong><br><span class="muted">' + esc(p.pack) + ' × ' + i.qty + '</span></span><strong>' + ft(p.price * i.qty) + '</strong></div>';
      }).join('') + '</div>' +
        '<div style="border-top:1px solid var(--line);padding-top:12px;display:flex;flex-direction:column;gap:6px;font-size:15px"><div class="line"><span>Részösszeg</span><span>' + ft(total) + '</span></div>' +
        (disc ? '<div class="line" style="color:var(--ok)"><span>Kedvezmény (' + esc(coupon().code) + ')</span><span>−' + ft(disc) + '</span></div>' : '') +
        '<div class="line"><span>Szállítás' + (t ? ' · ' + esc(t.name) : '') + '</span><strong>' + (ok ? 'Ingyenes' : '–') + '</strong></div></div>' +
        '<div class="line total"><strong>Fizetendő</strong><span class="price">' + ft(total - disc) + '</span></div>' +
        '<button type="submit" form="co" class="btn btn-gold btn-block"' + (ok ? '' : ' disabled') + '>Megrendelés fizetési kötelezettséggel</button>' +
        (ok ? '<p class="muted" style="font-size:13px;text-align:center">Demó: fizetés nem történik, a rendelés a böngésződben mentődik.</p>' : '<p class="muted" style="font-size:13px;text-align:center">' + (t ? 'A minimum még nincs meg. <a href="' + url('kosar.html') + '">Vissza a kosárhoz</a>' : 'Add meg a települést.') + '</p>');
    }
    tInput.addEventListener('input', drawDays);
    tInput.addEventListener('change', drawDays);
    form.zip.addEventListener('change', () => {
      if (tInput.value.trim()) return;
      const t = findTowns(form.zip.value)[0];
      if (t) { tInput.value = t.name; drawDays(); }
    });
    drawDays();

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const err = $('#co-err');
      const t = currentTown();
      if (!form.checkValidity()) { err.textContent = 'Kérjük, töltsd ki a csillaggal és pirossal jelölt mezőket, és fogadd el az ÁSZF-et.'; form.reportValidity(); return; }
      if (!t) { err.textContent = 'Erre a településre jelenleg nem szállítunk.'; return; }
      const cc = getCart();
      const total = cartTotal(cc);
      if (total < t.min) { err.textContent = 'A rendelés nem éri el a települési minimumot.'; return; }
      const dayInp = form.querySelector('input[name=day]:checked');
      if (!dayInp) { err.textContent = 'Válassz kiszállítási napot.'; return; }
      if (total < +dayInp.getAttribute('data-min')) { err.textContent = 'Erre a járatra a minimum ' + ft(+dayInp.getAttribute('data-min')) + '. Válassz másik napot, vagy tegyél még a kosárba.'; return; }
      const fd = new FormData(form);
      const orders = store.get('bg_orders', []);
      const id = String(1050 + orders.filter((o) => !o.demo).length);
      const disc = discountFor(total);
      const order = {
        id, created: new Date().toISOString(), status: 'processing', demo: false,
        items: cc.map((i) => ({ id: i.id, qty: i.qty, price: P[i.id].price })), subtotal: total, discount: disc, total: total - disc,
        town: t.name, zone: t.zone, day: fd.get('day') || null, name: fd.get('name'), email: fd.get('email'), phone: fd.get('phone'),
        zip: fd.get('zip'), street: fd.get('street'), note: fd.get('note'), pay: fd.get('pay')
      };
      orders.push(order);
      store.set('bg_orders', orders);
      store.set('bg_customer', { name: order.name, email: order.email, phone: order.phone, zip: order.zip, town: t.name, street: order.street });
      store.set('bg_town', t.name);
      store.del('bg_coupon');
      saveCart([]);
      location.href = url('koszonjuk.html?id=' + id);
    });
  };

  const PAYNAME = { card: 'Bankkártya (SimplePay)', otpszep: 'OTP SZÉP-kártya', khszep: 'K&H SZÉP-kártya', mkbszep: 'MKB SZÉP-kártya', cash: 'Készpénz a sofőrnél' };
  const parseDay = (s) => { if (!s) return null; const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };

  pages.thanks = function () {
    const o = store.get('bg_orders', []).find((x) => x.id === params.get('id'));
    const root = $('#thanks-root');
    if (!o) { root.innerHTML = '<div class="empty" style="margin:48px 0"><p class="display" style="font-size:28px">Nem találjuk ezt a rendelést</p><a class="btn btn-dark" style="margin-top:16px" href="' + url('index.html') + '">Főoldal</a></div>'; return; }
    const day = parseDay(o.day);
    const first = (o.name || '').trim().split(/\s+/).pop();
    root.innerHTML = '<section style="max-width:960px;margin:0 auto;padding:64px 0 24px;display:flex;flex-direction:column;align-items:center;text-align:center;gap:16px">' +
      '<span aria-hidden="true" style="width:76px;height:76px;border-radius:38px;background:var(--gold);display:flex;align-items:center;justify-content:center"><svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#161615" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>' +
      '<h1>Köszönjük' + (first ? ', ' + esc(first) : '') + '!</h1><p class="lead">Megkaptuk a rendelésed (<strong>#' + esc(o.id) + '</strong>). A visszaigazolást elküldjük a(z) ' + esc(o.email) + ' címre. <span class="demo-badge">DEMO</span></p></section>' +
      '<section style="max-width:960px;margin:0 auto;display:flex;flex-wrap:wrap;gap:20px;padding-bottom:32px">' +
      '<div class="card-dark" style="flex:1 1 400px;display:flex;flex-direction:column;gap:12px"><p class="kicker light" style="margin:0">Kiszállítás</p><p class="display" style="font-size:36px;color:var(--cream)">' + (day ? esc(fmtDate(day)) : 'Egyeztetés alatt') + '</p>' +
      '<p>' + esc(o.zip + ' ' + o.town + ', ' + o.street) + '<br>A sofőr a ' + esc(o.phone) + ' számon keres, ha kell.</p></div>' +
      '<div class="card" style="flex:1 1 300px;display:flex;flex-direction:column;gap:10px"><p class="kicker" style="margin:0">Fizetés</p><p class="price" style="font-size:36px">' + ft(o.total) + '</p>' +
      '<p class="muted">' + esc(PAYNAME[o.pay] || '') + (o.pay === 'cash' ? ' – átvételkor fizetendő: <strong>' + ft(o.total) + '</strong>' : ' – a demóban nem történt terhelés') + '</p></div></section>' +
      '<section style="max-width:960px;margin:0 auto;padding-bottom:48px"><h2 style="font-size:30px;margin-bottom:16px">Mi történik most?</h2><ol class="num-cards">' +
      '<li><span class="n">01</span><p><strong>Összekészítjük</strong></p><p class="muted">Gesztelyen, a járat előtti napon.</p></li>' +
      '<li><span class="n">02</span><p><strong>Szólunk előtte</strong></p><p class="muted">[E-mail/SMS a járat előtti napon – megerősítendő]</p></li>' +
      '<li><span class="n">03</span><p><strong>Kiszállítjuk</strong></p><p class="muted">Saját járattal, a választott napon.</p></li></ol></section>' +
      '<section style="max-width:960px;margin:0 auto;padding-bottom:80px"><div style="background:var(--gold);border-radius:20px;padding:28px;display:flex;flex-wrap:wrap;gap:20px;align-items:center;justify-content:space-between">' +
      '<div style="flex:1 1 360px"><h2 style="font-size:30px">Legközelebb egy kattintás</h2><p style="margin-top:6px">A Fiókomban a rendelést egy gombbal újrarendelheted.</p></div><a class="btn btn-dark" href="' + url('fiokom.html') + '">Fiókom</a></div></section>';
  };

  /* ---------- fiók ---------- */
  pages.account = function () {
    const draw = () => {
      const orders = store.get('bg_orders', []).filter((o) => !o.demo).sort((a, b) => b.created.localeCompare(a.created));
      const cust = store.get('bg_customer', null);
      const root = $('#account-root');
      if (!cust && !orders.length) {
        root.innerHTML = '<div class="split" style="padding:56px 0 80px;align-items:flex-start"><div class="stack"><h1>Fiókom</h1><p class="lead">A demóban a fiók az első rendeléssel jön létre, és a böngésződben tárolódik.</p><a class="btn btn-dark" style="align-self:flex-start" href="' + url('termekek.html') + '">Vásárlás</a></div>' +
          '<form class="card stack" data-demo-form="Demó: a belépés nincs bekötve."><h2 style="font-size:28px">Belépés</h2><label class="field">E-mail<input type="email" required></label><label class="field">Jelszó<input type="password" required></label><button class="btn btn-gold" type="submit">Belépés</button></form></div>';
        return;
      }
      const upcoming = orders.find((o) => o.status === 'processing');
      const name = cust ? cust.name : '';
      root.innerHTML = '<div style="display:flex;flex-wrap:wrap;gap:36px;align-items:flex-start;padding:40px 0 80px">' +
        '<nav class="side-nav" aria-label="Fiók menü" style="flex:1 1 220px;max-width:260px"><a href="#" aria-current="page">Áttekintés</a><a href="#rendelesek">Rendeléseim</a><a href="#cim">Szállítási cím</a><a href="#" id="logout" style="color:var(--muted)">Kijelentkezés (demo-adatok törlése)</a></nav>' +
        '<div style="flex:999 1 640px;min-width:0;display:flex;flex-direction:column;gap:24px"><h1 style="font-size:48px">Szia' + (name ? ', ' + esc(name.trim().split(/\s+/).pop()) : '') + '!</h1>' +
        '<div class="row" style="align-items:stretch;gap:20px">' +
        (upcoming ? '<div class="card-dark" style="flex:1 1 340px;display:flex;flex-direction:column;gap:8px"><p class="kicker light" style="margin:0">Úton hozzád</p><p class="display" style="font-size:30px;color:var(--cream)">' + (upcoming.day ? esc(fmtDate(parseDay(upcoming.day))) : '–') + '</p><p>#' + esc(upcoming.id) + ' · ' + upcoming.items.length + ' termék · ' + ft(upcoming.total) + '</p></div>' : '') +
        '<div class="card" style="flex:1 1 300px;border:2px dashed var(--gold);display:flex;flex-direction:column;gap:8px"><p class="kicker" style="margin:0">Átállási kedvezmény</p><p class="display" style="font-size:30px;font-weight:500">5% a következő rendelésre</p><p class="muted">Köszönjük, hogy az appból velünk jöttél. Kód: <strong style="color:var(--ink)">APP5</strong> <span class="demo-badge">DEMO</span></p></div></div>' +
        '<div id="rendelesek"><h2 style="font-size:28px;margin-bottom:12px">Rendeléseim</h2>' +
        (orders.length ? '<div class="table-box"><table class="data" style="min-width:640px"><thead><tr><th scope="col">Rendelés</th><th scope="col">Járatnap</th><th scope="col">Tartalom</th><th scope="col">Összeg</th><th scope="col">Állapot</th><th scope="col"><span class="sr-only">Művelet</span></th></tr></thead><tbody>' +
          orders.map((o) => '<tr><td><strong>#' + esc(o.id) + '</strong></td><td>' + (o.day ? esc(fmtDate(parseDay(o.day))) : '–') + '</td><td class="muted">' + esc(o.items.map((i) => P[i.id] ? P[i.id].name : i.id).slice(0, 2).join(', ')) + (o.items.length > 2 ? ', +' + (o.items.length - 2) : '') + '</td><td>' + ft(o.total) + '</td>' +
            '<td><span class="status' + (o.status === 'processing' ? ' live' : '') + '">' + (o.status === 'processing' ? 'Úton' : 'Kiszállítva') + '</span></td><td style="text-align:right"><button class="btn btn-outline btn-sm" type="button" data-reorder="' + esc(o.id) + '">Újrarendelem</button></td></tr>').join('') +
          '</tbody></table></div>' : '<p class="muted">Még nincs rendelésed.</p>') + '</div>' +
        (cust ? '<div class="card row" id="cim" style="justify-content:space-between"><div><p><strong>Alapértelmezett szállítási cím</strong></p><p class="muted">' + esc(cust.name + ' · ' + cust.zip + ' ' + cust.town + ', ' + cust.street) + '</p></div></div>' : '') +
        '</div></div>';
      $('#logout').addEventListener('click', (e) => {
        e.preventDefault();
        ['bg_orders', 'bg_customer', 'bg_cart', 'bg_town', 'bg_coupon'].forEach((k) => store.del(k));
        toast('A demo-adatokat töröltük');
        updateChrome();
        draw();
      });
    };
    document.addEventListener('click', (e) => {
      const b = e.target.closest('[data-reorder]');
      if (!b) return;
      const o = store.get('bg_orders', []).find((x) => x.id === b.getAttribute('data-reorder'));
      if (!o) return;
      o.items.forEach((i) => { if (P[i.id]) addToCart(i.id, i.qty); });
      store.set('bg_town', o.town);
      document.dispatchEvent(new CustomEvent('bg:town'));
      toast('A korábbi rendelés a kosárba került');
      openDrawer();
    });
    draw();
  };

  /* ---------- szállítás ---------- */
  pages.shipping = function () {
    $('#zones').innerHTML = BG.areas.map((z) => {
      const towns = BG.towns.filter((t) => t.zone === z.id);
      if (!towns.length) return '';
      return '<div class="card" style="padding:0;overflow:hidden"><div style="padding:20px 22px;border-bottom:1px solid var(--line);display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:8px">' +
        '<h2 style="font-size:28px">' + esc(z.name) + '</h2><span style="font-size:14px;font-weight:700;color:var(--gold-text)">' + towns.length + ' település</span></div>' +
        '<table class="data"><thead><tr><th scope="col">Település</th><th scope="col">Járatnap</th><th scope="col" style="text-align:right">Minimum rendelés</th></tr></thead><tbody>' +
        towns.map((t) => '<tr><td><a href="' + url('varos.html?v=' + encodeURIComponent(norm(t.name))) + '">' + esc(t.name) + '</a></td><td>' + esc(runsText(t)) + '</td><td style="text-align:right;font-weight:700">' + esc(minText(t)) + '</td></tr>').join('') + '</tbody></table></div>';
    }).join('');
  };

  pages.city = function () {
    const key = norm(params.get('v') || 'miskolc');
    const t = BG.towns.find((x) => norm(x.name) === key) || townByName('Miskolc');
    const i = townInfo(t);
    document.title = 'Mangalica házhozszállítás – ' + t.name + ' | Bio-Goods';
    $('#city-h1').textContent = 'Mangalica házhozszállítás – ' + t.name;
    $('#city-facts').innerHTML = [['Járatnap', runsText(t)], ['Minimum', minText(t)], ['Következő járat', i.next ? fmtDate(i.next.date) : 'jelenleg nincs meghirdetve']]
      .map(([k, v]) => '<div style="background:var(--ink-2);border-radius:12px;padding:14px"><dt style="font-size:13px;color:var(--on-dark-muted)">' + esc(k) + '</dt><dd style="margin:2px 0 0;font-weight:700;color:var(--cream)">' + esc(v) + '</dd></div>').join('');
    $('#city-cta').addEventListener('click', () => setTown(t.name));
    $('#city-products').innerHTML = ['miskolci-kolbasz', 'csontos-tarja', 'hurka', 'zsir-1kg'].map((id) => productCard(P[id])).join('');
    $('#city-faq').innerHTML = [
      ['Melyik napon jön a járat ide: ' + t.name + '?', runsText(t) + ' – a rendelést a kiszállítást megelőző napon délig kell leadni.'],
      ['Mekkora a minimum rendelés?', t.name + ': ' + minText(t) + '. Szállítási díj nincs.'],
      ['Átvehetem személyesen?', 'Személyes átvétel nincs: minden rendelést saját hűtött autóval viszünk házhoz.']
    ].map(([q, a]) => '<details><summary>' + esc(q) + '</summary><p>' + esc(a) + '</p></details>').join('');
    $('#city-near').innerHTML = BG.towns.filter((x) => x.zone === t.zone && x !== t).map((x) => '<a class="chip" href="' + url('varos.html?v=' + encodeURIComponent(norm(x.name))) + '">' + esc(x.name) + '</a>').join('');
  };

  /* ---------- receptek ---------- */
  pages.recipes = function () {
    const metas = ['Mind'].concat(Array.from(new Set(BG.recipes.map((r) => r.meta.split(' · ')[0].includes('chef') ? 'Videós' : r.meta))));
    let active = 'Mind';
    const draw = () => {
      $('#r-filters').innerHTML = metas.map((m) => '<button type="button" class="chip' + (m === active ? ' is-active' : '') + '" data-m="' + esc(m) + '">' + esc(m) + '</button>').join('');
      const list = BG.recipes.filter((r) => active === 'Mind' || (active === 'Videós' ? r.video : r.meta === active));
      $('#r-grid').innerHTML = list.map(recipeCard).join('');
    };
    $('#r-filters').addEventListener('click', (e) => { const b = e.target.closest('[data-m]'); if (b) { active = b.getAttribute('data-m'); draw(); } });
    draw();
  };
  pages.recipe = function () {
    const r = BG.recipes.find((x) => x.id === params.get('id')) || BG.recipes[0];
    document.title = r.title + ' – recept | Bio-Goods';
    const shopTotal = r.shop.reduce((s, [id, q]) => s + P[id].price * q, 0);
    $('#recipe-root').innerHTML =
      '<header style="max-width:1100px;margin:0 auto;padding:48px 0 28px;display:flex;flex-direction:column;gap:14px"><nav class="crumbs" aria-label="Morzsamenü"><a href="' + url('receptek.html') + '">Receptek</a><span aria-hidden="true">/</span><span aria-current="page">' + esc(r.meta) + '</span></nav>' +
      '<h1>' + esc(r.title) + '</h1><div class="row muted" style="gap:10px 28px;font-size:15px"><span><strong style="color:var(--ink)">' + esc(r.serves) + '</strong></span><span>Előkészítés: <strong style="color:var(--ink)">' + esc(r.prep) + '</strong></span>' + (r.wait !== '–' ? '<span>Várakozás: <strong style="color:var(--ink)">' + esc(r.wait) + '</strong></span>' : '') + (r.cook !== '–' ? '<span>Sütés/főzés: <strong style="color:var(--ink)">' + esc(r.cook) + '</strong></span>' : '') + '</div></header>' +
      '<img src="' + img(r.img) + '" alt="" style="max-width:1100px;width:100%;margin:0 auto;aspect-ratio:21/9;object-fit:cover;border-radius:22px">' +
      '<div style="max-width:1100px;margin:0 auto;padding:48px 0 64px;display:flex;flex-wrap:wrap;gap:48px;align-items:flex-start">' +
      '<aside style="flex:1 1 300px;max-width:360px;display:flex;flex-direction:column;gap:18px"><div class="card"><h2 style="font-size:26px;margin-bottom:12px">Hozzávalók</h2><ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px">' +
      r.ingredients.map((x, ix) => '<li><label class="check"><input type="checkbox" id="ing' + ix + '"><span>' + esc(x) + '</span></label></li>').join('') + '</ul></div>' +
      '<div class="card-dark" style="display:flex;flex-direction:column;gap:12px"><p class="display" style="font-size:20px;color:var(--cream)">A boltból ehhez</p>' +
      r.shop.map(([id, q]) => '<div class="mini-item"><img src="' + img(P[id].img) + '" alt=""><span class="n"><strong>' + esc(P[id].name) + '</strong><br><span style="color:var(--on-dark-muted)">' + q + ' × ' + esc(P[id].pack) + '</span></span><span>' + ft(P[id].price * q) + '</span></div>').join('') +
      '<button type="button" class="btn btn-gold" data-add-many=\'' + JSON.stringify(r.shop) + '\'>Mind a kosárba · ' + ft(shopTotal) + '</button></div></aside>' +
      '<div style="flex:999 1 520px;min-width:0;display:flex;flex-direction:column;gap:28px"><p class="lead" style="font-size:19px">' + esc(r.lead) + '</p><h2 style="font-size:32px">Elkészítés</h2>' +
      '<ol style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:22px">' + r.steps.map((s, ix) => '<li style="display:flex;gap:18px"><span style="flex:none;width:44px;height:44px;border-radius:22px;background:var(--ink);color:var(--gold);display:flex;align-items:center;justify-content:center;font-family:var(--display);font-size:20px">' + (ix + 1) + '</span><p style="margin-top:8px">' + esc(s) + '</p></li>').join('') + '</ol>' +
      (r.tip ? '<div class="card"><p><strong>Tipp</strong></p><p class="muted" style="margin-top:4px">' + esc(r.tip) + '</p></div>' : '') +
      (r.video ? '<div class="dark-ph">[A chef-videó beágyazása a Bio-Goods Facebook-oldaláról – süti-hozzájárulás után töltődik]</div>' : '') + '</div></div>';
    $('#recipe-more').innerHTML = BG.recipes.filter((x) => x.id !== r.id).slice(0, 3).map(recipeCard).join('');
  };

  /* ---------- admin ---------- */
  function seedDemoOrders() {
    let orders = store.get('bg_orders', []);
    if (orders.some((o) => o.demo)) return orders;
    const names = ['Kovács Anna', 'Tóth Éva', 'Szabó Péter', 'Nagy Gábor', 'Varga Zsuzsa', 'Kiss Márta', 'Fekete Ilona', 'Molnár Tamás', 'Horváth Dóra', 'Balogh István', 'Papp Réka', 'Takács Bence'];
    const plan = [['Debrecen', [['csontos-tarja', 2], ['hurka', 1]], 'card'], ['Debrecen', [['kolozsvari-szalonna', 2], ['tepertokrem', 1], ['parizsi', 3]], 'otpszep'],
      ['Nyíregyháza', [['grillkolbasz-magyaros', 2], ['zsir-1kg', 1]], 'cash'], ['Hajdúhadház', [['ajandekdoboz-10', 1], ['fustolt-csemege-kolbasz', 2]], 'cash'],
      ['Téglás', [['bbq-oldalas', 2], ['sutokolbasz', 1], ['hurka', 2]], 'khszep'], ['Miskolc', [['szuz', 1], ['fustolt-sonka', 2]], 'cash'],
      ['Miskolc', [['teliszalami', 2], ['virsli', 1]], 'card'], ['Gesztely', [['zsir-5kg', 1], ['teperto', 3]], 'cash'],
      ['Budapest', [['ajandekkosar-12', 2], ['bosegtal', 1]], 'card'], ['Budaörs', [['csontos-karaj', 2], ['paprikas-szalami', 2], ['kolozsvari-szalonna', 1]], 'cash'],
      ['Érd', [['tomahawk', 4], ['t-bone', 2], ['hamburgerhus', 1]], 'card'], ['Budapest', [['kalandor-csomag', 2], ['csibesz-csomag', 1]], 'cash']];
    plan.forEach(([townName, items, pay], ix) => {
      const t = townByName(townName);
      const total = items.reduce((s, [id, q]) => s + P[id].price * q, 0);
      const day = nextDeliveries(t, 4).find((o) => total >= o.min);
      orders.push({ id: String(1001 + ix), created: new Date(Date.now() - (ix + 1) * 3600e3).toISOString(), status: 'processing', demo: true,
        items: items.map(([id, q]) => ({ id, qty: q, price: P[id].price })), subtotal: total, discount: 0, total,
        town: townName, zone: t.zone, day: day ? ymd(day.date) : null, name: names[ix], email: '', phone: '+36 30 ··· ··' + String(11 + ix * 7).slice(-2),
        zip: (t.zips[0] + '000').slice(0, 4), street: '[utca, házszám]', note: ix % 3 === 0 ? 'Hívjon előtte' : '', pay });
    });
    store.set('bg_orders', orders);
    return orders;
  }
  function adminDays(orders) { return Array.from(new Set(orders.filter((o) => o.day && o.status === 'processing').map((o) => o.day))).sort(); }

  pages.adminRoute = function () {
    const orders = seedDemoOrders();
    const days = adminDays(orders);
    $('#f-day').innerHTML = days.map((d) => '<option value="' + d + '">' + esc(fmtDate(parseDay(d))) + '</option>').join('');
    $('#f-zone').innerHTML = '<option value="">Minden terület</option>' + BG.areas.map((z) => '<option value="' + z.id + '">' + esc(z.name) + '</option>').join('');
    const draw = () => {
      const all = store.get('bg_orders', []);
      const day = $('#f-day').value;
      const zone = $('#f-zone').value;
      const st = $('#f-status').value;
      const list = all.filter((o) => o.day === day && (!zone || o.zone === zone) && (st === 'all' || o.status === st))
        .sort((a, b) => a.zone.localeCompare(b.zone) || a.town.localeCompare(b.town, 'hu') || a.zip.localeCompare(b.zip));
      const cash = list.filter((o) => o.pay === 'cash').reduce((s, o) => s + o.total, 0);
      $('#kpis').innerHTML = '<div class="kpi"><p class="muted">Rendelés</p><p class="v">' + list.length + '</p></div><div class="kpi"><p class="muted">Település</p><p class="v">' + new Set(list.map((o) => o.town)).size + '</p></div>' +
        '<div class="kpi dark"><p style="color:var(--on-dark-muted)">Beszedendő készpénz</p><p class="v">' + ft(cash) + '</p></div><div class="kpi"><p class="muted">Online / SZÉP fizetve</p><p class="v">' + list.filter((o) => o.pay !== 'cash').length + '</p></div>';
      $('#rows').innerHTML = list.length ? list.map((o, ix) => '<tr><td class="no-print"><input type="checkbox" value="' + esc(o.id) + '" aria-label="Kijelölés: #' + esc(o.id) + '" style="width:18px;height:18px"></td><td class="display" style="font-size:18px">' + (ix + 1) + '</td>' +
        '<td><strong>' + esc(o.town) + '</strong><br><span class="muted">' + esc(o.zip) + '</span></td><td><strong>' + esc(o.name) + '</strong><br><span class="muted">' + esc(o.street) + ' · #' + esc(o.id) + (o.demo ? '' : ' · <span class="demo-badge">ÚJ</span>') + '</span></td>' +
        '<td><a href="tel:' + esc(o.phone) + '">' + esc(o.phone) + '</a></td><td>' + o.items.reduce((s, i) => s + i.qty, 0) + ' db</td><td>' + esc(PAYNAME[o.pay] || '') + '</td>' +
        '<td style="text-align:right;font-weight:700">' + (o.pay === 'cash' ? ft(o.total) : '–') + '</td><td class="muted">' + esc(o.note || '') + '</td><td><span class="status' + (o.status === 'processing' ? ' live' : '') + '">' + (o.status === 'processing' ? 'Feldolgozás' : 'Kiszállítva') + '</span></td></tr>').join('')
        : '<tr><td colspan="10" class="muted" style="padding:24px">Erre a napra nincs rendelés.</td></tr>';
    };
    ['#f-day', '#f-zone', '#f-status'].forEach((s) => $(s).addEventListener('change', draw));
    $('#mark-done').addEventListener('click', () => {
      const ids = $$('#rows input:checked').map((i) => i.value);
      if (!ids.length) { toast('Jelölj ki legalább egy rendelést'); return; }
      const all = store.get('bg_orders', []);
      all.forEach((o) => { if (ids.includes(o.id)) o.status = 'done'; });
      store.set('bg_orders', all);
      toast(ids.length + ' rendelés kiszállítva');
      draw();
    });
    $('#print').addEventListener('click', () => window.print());
    $('#csv').addEventListener('click', () => {
      const day = $('#f-day').value;
      const rows = store.get('bg_orders', []).filter((o) => o.day === day).map((o) => [o.id, o.town, o.zip, o.name, o.street, o.phone, o.pay, o.pay === 'cash' ? o.total : 0, o.note || '']);
      const csv = [['rendeles', 'telepules', 'irsz', 'nev', 'cim', 'telefon', 'fizetes', 'beszedendo', 'megjegyzes']].concat(rows).map((r) => r.map((x) => '"' + String(x).replace(/"/g, '""') + '"').join(';')).join('\n');
      const a = document.createElement('a');
      a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
      a.download = 'jaratlista-' + day + '.csv';
      a.click();
    });
    $('#reset-demo').addEventListener('click', () => { store.set('bg_orders', store.get('bg_orders', []).filter((o) => !o.demo)); seedDemoOrders(); toast('Mintarendelések visszaállítva'); draw(); });
    draw();
  };

  pages.adminPick = function () {
    const orders = seedDemoOrders();
    const days = adminDays(orders);
    $('#f-day').innerHTML = days.map((d) => '<option value="' + d + '">' + esc(fmtDate(parseDay(d))) + '</option>').join('');
    const draw = () => {
      const day = $('#f-day').value;
      const list = store.get('bg_orders', []).filter((o) => o.day === day && o.status === 'processing');
      const agg = {};
      list.forEach((o) => o.items.forEach((i) => { agg[i.id] = (agg[i.id] || 0) + i.qty; }));
      const byCat = {};
      Object.keys(agg).forEach((id) => { const p = P[id]; if (!p) return; (byCat[p.cat] = byCat[p.cat] || []).push([p, agg[id]]); });
      $('#pick').innerHTML = BG.categories.filter((c) => byCat[c.id]).map((c) => '<div class="box" style="overflow:hidden"><p class="display" style="font-size:18px;font-weight:500;padding:12px 16px;background:#F8F5EF;margin:0">' + esc(c.name) + '</p>' +
        '<table class="data"><thead><tr><th scope="col">Termék</th><th scope="col">Kiszerelés</th><th scope="col" style="text-align:right">Darab</th><th scope="col" style="text-align:right">Kész</th></tr></thead><tbody>' +
        byCat[c.id].sort((a, b) => b[1] - a[1]).map(([p, q]) => '<tr><td><strong>' + esc(p.name) + '</strong></td><td class="muted">' + esc(p.pack) + '</td><td style="text-align:right" class="display"><span style="font-size:20px">' + q + '</span></td><td style="text-align:right"><input type="checkbox" aria-label="Összekészítve: ' + esc(p.name) + '" style="width:20px;height:20px"></td></tr>').join('') +
        '</tbody></table></div>').join('') || '<p class="muted">Erre a napra nincs összekészítendő rendelés.</p>';
      $('#pick-sum').textContent = list.length + ' rendelés · ' + Object.values(agg).reduce((s, q) => s + q, 0) + ' tétel';
    };
    $('#f-day').addEventListener('change', draw);
    $('#print').addEventListener('click', () => window.print());
    draw();
  };

  pages.adminZones = function () {
    let zone = BG.areas[0].id;
    const draw = () => {
      $('#ztabs').innerHTML = BG.areas.map((z) => '<button type="button" class="chip' + (z.id === zone ? ' is-active' : '') + '" data-z="' + z.id + '">' + esc(z.name) + ' · ' + BG.towns.filter((t) => t.zone === z.id).length + '</button>').join('');
      $('#zinfo').innerHTML = 'Járatnap: <strong>' + esc(areaDays(zone)) + '</strong> · határidő: a kiszállítást megelőző nap 12:00 · szállítási díj: 0 Ft';
      $('#zrows').innerHTML = [].concat.apply([], BG.towns.filter((t) => t.zone === zone).map((t) => t.runs.map((r, ix) => '<tr><td><strong>' + esc(t.name) + '</strong></td><td>' + esc(runLabel(r)) + '</td><td class="muted">' + esc(t.zips.length ? t.zips.map((x) => x.length < 4 ? x + '…' : x).join(', ') : '[irányítószám megerősítendő]') + '</td>' +
        '<td><label style="display:flex;align-items:center;gap:6px"><span class="sr-only">Minimum: ' + esc(t.name) + ', ' + esc(runLabel(r)) + '</span><input data-run="' + esc(t.name) + '|' + ix + '" type="number" step="500" min="0" value="' + r.min + '" style="width:110px;height:36px;padding:0 8px;border-radius:6px;border:1px solid var(--line-2);text-align:right">Ft</label></td>' +
        '<td><input type="checkbox" checked aria-label="Aktív: ' + esc(t.name) + ', ' + esc(runLabel(r)) + '" style="width:20px;height:20px;accent-color:var(--ink)"></td></tr>'))).join('');
    };
    $('#ztabs').addEventListener('click', (e) => { const b = e.target.closest('[data-z]'); if (b) { zone = b.getAttribute('data-z'); draw(); } });
    $('#save').addEventListener('click', () => {
      const ov = store.get('bg_min_overrides2', {});
      $$('#zrows input[data-run]').forEach((i) => {
        const [name, ix] = i.getAttribute('data-run').split('|'); const t = townByName(name); const v = Math.max(0, parseInt(i.value, 10) || 0);
        ov[name + '|' + ix] = v; t.runs[+ix].min = v; t.min = Math.min.apply(null, t.runs.map((r) => r.min));
      });
      store.set('bg_min_overrides2', ov);
      toast('Mentve – a webshop minimumjai frissültek (ebben a böngészőben)');
    });
    $('#reset').addEventListener('click', () => { store.del('bg_min_overrides2'); toast('Alapértékek visszaállítva'); setTimeout(() => location.reload(), 500); });
    draw();
  };

  /* ================= indulás ================= */
  if (!document.body.classList.contains('admin-page') && PAGE !== 'checkout') renderChrome();
  else if (PAGE === 'checkout') { // minimális pénztár-fejléc, menü nélkül
    const h = document.createElement('div');
    h.innerHTML = '<a class="skip" href="#main">Ugrás a tartalomhoz</a><header class="checkout-header"><div class="wrap"><a href="' + url('index.html') + '" aria-label="Bio-Goods – főoldal"><img src="' + url('assets/img/site/logo.png') + '" alt="Bio-Goods Mangalica" style="height:54px;width:auto"></a>' +
      '<div class="row" style="gap:22px;font-size:14px"><span style="color:var(--on-dark)">🔒 Biztonságos fizetés</span><a href="' + url('kosar.html') + '">← Vissza a kosárhoz</a></div></div></header>';
    document.body.prepend(h);
  }
  const map = { home: 'home', list: 'list', product: 'product', cart: 'cart', checkout: 'checkout', thanks: 'thanks', account: 'account', shipping: 'shipping', city: 'city', recipes: 'recipes', recipe: 'recipe', 'admin-route': 'adminRoute', 'admin-pick': 'adminPick', 'admin-zones': 'adminZones' };
  if (map[PAGE] && pages[map[PAGE]]) pages[map[PAGE]]();
  bindTownChecks(document);
  $$('[data-reviews]').forEach((el) => { el.innerHTML = reviewsHTML(); });
  $$('[data-routes]').forEach((el) => { el.innerHTML = routeCards(); });
  $$('[data-recipes]').forEach((el) => { el.innerHTML = BG.recipes.slice(0, +(el.getAttribute('data-recipes') || 3)).map(recipeCard).join(''); });
  $$('[data-products]').forEach((el) => { el.innerHTML = el.getAttribute('data-products').split(',').map((id) => P[id.trim()]).filter(Boolean).map(productCard).join(''); });

  window.BGApp = { getCart, addToCart, cartTotal, nextDeliveries, findTowns, fmtDate };
})();
