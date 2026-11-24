# Bio-Goods Mangalica – éles webshop (WordPress + WooCommerce)

Ez a `wordpress` ág az éles webshop kódja. A `main` ág a jóváhagyott demó (GitHub Pages).

## Felépítés

- `wp-content/themes/bg-mangalica` – saját sablon. A demó felülete egy az egyben (ugyanaz a CSS és a felületet kirajzoló `app.js`), a demó oldalai a `demo-pages/` mappában. A `bg-wp.js` a demó `*.html` hivatkozásait WordPress-címekre fordítja, és a pénztár előtt a böngésző kosarát átmásolja a WooCommerce kosarába.
- `wp-content/plugins/bg-core` – a Bio-Goods-specifikus logika:
  - `delivery.php` – 85 település, járatnapok, előző nap 12:00-s határidő, járatonkénti minimum, díjmentes szállítási mód
  - `checkout.php` – település- és napválasztás a pénztárban, szerveroldali ellenőrzés (határidő, járat, minimum, irányítószám), mentés a rendelésbe, megjelenítés az adminban és az e-mailekben
  - `cart.php` – kosárszinkron (az árat mindig a szerver számolja), kivitel (szeletelve/egészben) a rendelési tételen
  - `coupons.php` – karácsonyi BG10 kártya: vásárlónként egyszer a kampányból, teljes visszatérítésnél felszabadul
  - `security.php` – XML-RPC ki, felhasználónév-felderítés ellen, belépéskorlát, biztonsági fejlécek, erős jelszó adminnak
  - `catalog-data.php` – élő termékadatok a sablonnak (ár, akció, készlet a WooCommerce-ből)
  - `cli.php` – `wp bg setup`, `wp bg import-products`, `wp bg import-coupons`
- `tools/` – a demó adatainak exportja (`export.js` → `bg-data.json`) és a böngészős tesztek (`smoke.py`, `e2e.py`)

## Telepítés (Kinsta)

1. WordPress telepítése a MyKinsta felületen (PHP 8.3, magyar nyelv), WooCommerce bővítmény.
2. A sablon és a bővítmény feltöltése (SFTP vagy Kinsta Git), aktiválás.
3. `wp bg setup` – WooCommerce-beállítások, oldalak, szállítási zóna, utánvét.
4. `wp bg import-products tools/bg-data.json --images=<demó assets/img mappa>` – 76 termék (6 rejtve), fotók a médiatárba.
5. `wp bg import-coupons BG_karacsonyi_kuponkodok_200db.csv` – a 200 kód (a CSV nincs a repóban).
6. SimplePay és számlázz.hu bővítmény + kulcsok, jogi szövegek, adminfelhasználók 2FA-val.

## Tesztelt (helyi WordPress 7.1.5 + WooCommerce 11.3.1, 2026-11-24)

- főoldal, terméklista, kategória, termékoldal, szállítás: JS-hiba és 404 nélkül
- normál rendelés utánvéttel, kivitel a tételen, kiszállítási nap a rendelésben
- rossz irányítószám → hiba; minimum alatt a kosár nem enged tovább
- BG10: 15 000 Ft alatt elutasítva; kisbetűvel/szóközzel beírva is elfogadva; ugyanaz a kód másodszor elutasítva; ugyanaz az e-mail másik kóddal elutasítva; teljes visszatérítésnél a kód felszabadul
