# Bio-Goods Admin (WordPress-bővítmény)

A Bio-Goods Mangalica WooCommerce-webshop napi kezelőfelülete. A szerződés (BG-2026-10) alapján készült:

- **05. tétel – Napi járatlista a sofőrnek (egyedi fejlesztés):** rendelések szűrése járatnap és zóna szerint, nyomtatható és exportálható lista.
- **2. sz. melléklet (adatbekérő), 9. fejezet:** „Az admin felületet (rendelések, járatlista, komissiózó lista) arra szabjuk, aki naponta dolgozik vele.”
- **4.4. pont:** a termékek, az árak és a készlet kezelése a Megrendelőé; a témát, a bővítményeket és a beállításokat egyeztetés nélkül nem módosítja.

## Mit tud

| Rész | Tartalom |
|------|----------|
| **„Bio-Goods kezelő” szerepkör** | A WooCommerce boltkezelő jogai, **mínusz**: WooCommerce-beállítások, állapot, bővítmények, téma, felhasználók, blog és oldalak, import/export, rendelés végleges törlése. Marad: rendelések, termékek és készlet, kategóriák, értékelések, kuponok, riportok, médiafeltöltés, saját profil. |
| **Szűkített menü** | A kezelő csak ezt látja: *Bio-Goods* (Mai nap, Rendelések, Járatlista, Komissiózó lista, Kuponok, Riport), *Termékek*, *Profil*. Belépés után és a vezérlőpult helyett a **Mai nap** nyílik. A felső sávból kikerül az „Új”, a hozzászólások és a WordPress-menü. |
| **Mai nap** | Feldolgozásra váró rendelések, a következő járat rendelésszáma és beszedendő készpénze, személyes átvételre várók, legutóbbi rendelések, járatnap nélküli rendelések figyelmeztetése, alacsony készlet. |
| **Járatlista** (05. tétel) | Szűrés járatnapra, zónára és állapotra; zónánként csoportosítva, település és irányítószám szerint rendezve; telefonszám, tételszám, fizetési mód, **beszedendő összeg** (utánvét/készpénz), megjegyzés. **Nyomtatás** (A4 fekvő, „Átadva” aláírási oszloppal), **CSV** Excelhez (UTF-8, pontosvessző), **„Kijelöltek kiszállítva”** → Teljesítve állapot és rendelésjegyzet. Alatta a személyes átvételre várók (Gesztely) „Átvéve” jelöléssel. |
| **Komissiózó lista** | Egy járatnap összes tétele termékenként és választott kivitelenként összesítve, kategóriánként, cikkszámmal, „Kész” jelölőnégyzettel; opcionálisan a személyes átvételre várókkal együtt; nyomtatható. |

Az adminisztrátorok (Stratos) a teljes WordPress-admint és mellette a Bio-Goods menüt is látják.

## Adatszerződés a járatlogikával (04. tétel)

A pénztár (járatnap-választás) a rendeléshez ezeket a meta-mezőket írja; a bővítmény ezekből dolgozik:

| Meta-kulcs | Érték | Szűrő, ha más a név |
|------------|-------|---------------------|
| `_bg_delivery_date` | járatnap, `ÉÉÉÉ-HH-NN` | `bg_admin_delivery_date_key` |
| `_bg_zone` | zóna azonosító, pl. `borsod` | `bg_admin_zone_key` |
| `_bg_mode` | `pickup` személyes átvételnél | – (a `local_pickup` / `pickup_location` szállítási mód is átvételnek számít) |

További szűrők: `bg_admin_zone_labels` (zónanevek), `bg_admin_open_statuses` (alapból `processing`, `on-hold`), `bg_admin_pay_on_delivery_methods` (alapból `cod`), `bg_admin_role_caps`, `bg_admin_kezelo_menus`.

## Telepítés

1. `bg-admin.zip` → Bővítmények → Új hozzáadása → Bővítmény feltöltése → Bekapcsolás (a WooCommerce legyen aktív).
2. Felhasználók → Új hozzáadása → Szerepkör: **Bio-Goods kezelő**.
3. A kikapcsolás nem törli a szerepkört (a felhasználók megmaradnak).

HPOS-kompatibilis (egyedi rendeléstáblák és régi tároló is), WordPress 6.4+, PHP 8.0+. Tesztelve: WordPress 7.1.5, WooCommerce 11.3.1, mindkét rendeléstárolóval.

## Biztonság

- Minden oldal és művelet `edit_shop_orders` jogot kér; a műveletek nonce-szal védettek, rendelésenként `edit_shop_order` ellenőrzéssel.
- A CSV-exportban a képletnek látszó szövegek (`=`, `+`, `-`, `@`) aposztrófot kapnak (CSV-injekció ellen).
- A menük elrejtése csak kényelmi funkció; a hozzáférést a szerepkör jogai korlátozzák (a tiltott oldalak 403-at adnak).

---
Készítette: [Stratos Webdesign](https://stratosweb.hu). A szerzői vagyoni jogok a szerződés 7.1. pontja szerint szállnak át.
