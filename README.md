# Bio-Goods Mangalica – demo webshop

Kattintható bemutató webshop a bgmangalica.hu WooCommerce-átállásához, a Stratos Webdesign által készített designterv alapján.

**Nem éles webshop.** Nincs backend: a kosár, a választott település, a leadott rendelések és az admin beállításai a böngésző helyi tárolójában (localStorage) maradnak. Fizetés nem történik, e-mail nem megy ki.

## Mit tud a demó

- **Termékkatalógus:** 76 termék a mostani webshop valós áraival, kiszereléseivel és Ft/kg egységárával. 6 kategória és 5 alkalom (Reggelire, Kerti parti, Disznótoros, Uzsonnacsomag, Akciók), szűrők, rendezés, keresés.
- **Település és járat:** a településkereső irányítószámra vagy névre megadja a következő járatnapot, a rendelési határidőt (a járat előtti nap 12:00) és a települési minimumot.
- **Kosár:** minimumsáv és hiányzó összeg, ajánlott termékek a minimumhoz, mini-kosár fiók, mobilon rögzített kosársáv. Kupon: `APP5` (−5%, az app-felhasználók átállási kedvezménye).
- **Pénztár:** egyoldalas, vendégként is használható. Település-ellenőrzés, járatnap-választó, fizetési módok (bankkártya, OTP és K&H SZÉP, készpénz), céges számla, jogszerű hozzájárulások.
- **Köszönő oldal és Fiókom:** rendelési előzmények, egy kattintásos újrarendelés.
- **Tartalmi oldalak:** szállítási területek, városi oldal (`varos.html?v=miskolc`), fizetési módok, GYIK, A gazdaság, Díjaink, receptek (hozzávalók kosárba), céges ajándék, kapcsolat, ÁSZF-sablon, 404, süti-sáv.
- **Admin (`/admin/`):**
  - napi járatlista a sofőrnek: szűrés, nyomtatás, CSV, beszedendő készpénz, „Kiszállítva” jelölés
  - komissiózó lista
  - zóna- és minimumkezelő: az átírt minimum azonnal érvényes a webshopban

## Futtatás

Nincs build lépés, sima HTML/CSS/JS.

```bash
npx serve .          # vagy: python3 -m http.server 8000
```

A fájl közvetlen megnyitásával (`file://`) is működik. Telepíthető GitHub Pages-re és Netlify-ra is, gyökérből vagy `/biogoods/` alútvonalról.

## Szerkezet

```
index.html            főoldal
termekek.html         lista: ?kategoria= · ?alkalom= · ?q=
termek.html?id=       termékoldal
kosar.html · penztar.html · koszonjuk.html · fiokom.html
szallitas.html · varos.html?v= · fizetes.html · gyik.html
a-gazdasag.html · dijaink.html · receptek.html · recept.html?id=
ceges-ajandek.html · kapcsolat.html · aszf.html · 404.html
admin/                járatlista, komissió, zónák
assets/js/data.js     termékek, kategóriák, zónák, települések, receptek
assets/js/app.js      teljes logika
assets/css/styles.css arculat (fekete #161615 · arany #CE8900 · krém #F5F0E6; Oswald + Manrope)
```

## Demóadatok – élesítés előtt az ügyféllel véglegesítendő

- A szállítás pontosan a mostani webshop szerint működik (`BG.towns` a `data.js`-ben, 2026-11-02): a OneMin 92 szállítási régiója 85 településre és 104 járatra bontva, járatonkénti minimummal (pl. Budapest: kedd 10 000 Ft, péntek 5 000 Ft), a november 5-i dunántúli egyszeri járattal; rendelés a kiszállítást megelőző napon délig; szállítási díj nincs.
- Irányítószámok: hu-postal-codes (Magyar Posta-lista, MIT). Pallagnak és a Szentjakab Parknak nincs saját irányítószáma, ezek név alapján kereshetők. Megerősítendő: a /terulet oldal „Tiszalök”-öt említ, a régiók között viszont Tiszalúc szerepel.
- Megerősítve (2026-11-02): saját hűtött autók és sofőrök; csak a listán szereplő településekre; személyes átvétel nincs; cégadatok a cégjegyzékből (BIO-GOODS Kft., 05-09-023530 – a cégnév megerősítendő); elérhetőség H–Cs 7–15, P 7–13 (Google-cégprofil).
- Tárolás, eltarthatóság, csomagolás. A kiszerelések, árak, összetevők és allergének a bgmangalica.hu OneMin-katalógusából valók (2026-11-02).
- A kilóár öt terméknél nem egyezik az ár és a kiszerelés hányadosával. Ezeket az ügyféllel javítani kell: dobozos kenőmájas (kiírt 6100, valós 6400 Ft/kg), tömlős kenőmájas (6100 vs. 5826), tepertőkrém (6500 vs. 6600), chilis rilette (6700 vs. 7000), abált szalonna (5700 vs. 5667). A demó most a kiírt értéket mutatja.
- A kocsonyacsomag összetevői a forrásban sem szerepelnek.
- SZÉP-kártya alszámla, számlázás, súlyeltérés elszámolása – a nyitott kérdések az ügyfél-adatbekérőben.
- Díjak, cégadatok, jogi szövegek (ügyvéd véglegesíti).

A termékfotók a mostani webshopból származnak, és csak bemutatási célt szolgálnak.

---
Készítette: [Stratos Webdesign](https://stratosweb.hu)
