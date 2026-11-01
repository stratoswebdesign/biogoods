# Bio-Goods Mangalica – demo webshop

Kattintható bemutató webshop a bgmangalica.hu WooCommerce-átállásához, a Stratos Webdesign által készített designterv alapján.

**Nem éles webshop.** Nincs backend: a kosár, a választott település, a leadott rendelések és az admin beállításai a böngésző helyi tárolójában (localStorage) maradnak. Fizetés nem történik, e-mail nem megy ki.

## Mit tud a demó

- **Termékkatalógus:** 76 termék a mostani webshop valós áraival, kiszereléseivel és Ft/kg egységárával. 6 kategória és 5 alkalom (Reggelire, Kerti parti, Disznótoros, Uzsonnacsomag, Akciók), szűrők, rendezés, keresés.
- **Település és járat:** a településkereső irányítószámra vagy névre megadja a következő járatnapot, a rendelési határidőt (a járat előtti nap 12:00) és a települési minimumot.
- **Kosár:** minimumsáv és hiányzó összeg, ajánlott termékek a minimumhoz, mini-kosár fiók, mobilon rögzített kosársáv. Kupon: `APP10` (−10%, az app-felhasználók átállási kedvezménye).
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

- Járatnapok zónánként (`BG.zones` a `data.js`-ben). Ezek most mintaértékek.
- A teljes, kb. 70 települést tartalmazó lista irányítószámokkal. A minimumok a OneMin-pénztárból valók.
- Allergének, tárolás, eltarthatóság, csomagolás; hiányzó kiszerelések (`[kiszerelés]`).
- SZÉP-kártya alszámla, számlázás, súlyeltérés elszámolása, személyes átvétel ideje.
- Díjak, cégadatok, jogi szövegek (ügyvéd véglegesíti).

A termékfotók a mostani webshopból származnak, és csak bemutatási célt szolgálnak.

---
Készítette: [Stratos Webdesign](https://stratosweb.hu)
