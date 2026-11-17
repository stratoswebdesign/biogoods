/*
 * Bio-Goods demo webshop – adatok
 * ---------------------------------------------------------------
 * A termékek, árak, kiszerelések és a települési minimumok a
 * bgmangalica.hu mostani (OneMin) webshopjából származnak (2026. november).
 * A járatok, minimumok és termékadatok a bgmangalica.hu OneMin-adataiból valók (2026-11-02).
 * Terméklista, összetevők, tárolás: az ügyfél „Új weboldal termékek” táblázata (2026-11-17).
 * A hiányzó mezők [megerősítendő] jelöléssel szerepelnek.
 */
window.BG = window.BG || {};

BG.categories = [
  { id: 'tokehusok', name: 'Tőkehúsok', sub: 'Tarja, karaj, szűz, oldalas, csülök', img: 'csontos-tarja',
    intro: 'Tarja, karaj, szűz, comb, oldalas, csülök – a saját tenyészetünkből. A mangalica húsát a márványozottság teszi szaftossá: sütéshez, grillhez és lassú főzéshez is.' },
  { id: 'reggelire', name: 'Reggelire', sub: 'Májasok, krémek, párizsi, virsli', img: 'parizsi',
    intro: 'Kenőmájas, rilette, tepertőkrém, párizsi, uzsonnasonka, virsli és páros kolbász – reggelire, uzsonnára, hidegtálra.' },
  { id: 'kerti-parti', name: 'Kerti parti', sub: 'Grillkolbász, hamburgerhús, Frédi szelet', img: 'grillkolbasz-magyaros',
    intro: 'Grillkolbász, hamburgerhús, tépett hús és Frédi szelet – minden, ami a rácsra kell, egy rendeléssel.' },
  { id: 'szalonnak', name: 'Szalonnák, füstöltek', sub: 'Kolozsvári, abált, füstölt sonka', img: 'kolozsvari-szalonna',
    intro: 'Kolozsvári, abált, sós és füstölt szalonna, füstölt sonka, csülök és oldalas a gesztelyi füstölőből.' },
  { id: 'klasszikusok', name: 'Klasszikusok', sub: 'Hurka, sütőkolbász, zsír, tepertő', img: 'hurka',
    intro: 'Hurka, sütőkolbász, mangalicazsír, tepertő, rakott fej és csülöksajt – a disznótoros ízek egész évben.' },
  { id: 'kolbaszok', name: 'Szalámik, kolbászok', sub: 'Füstölt, csemege, csípős, téli', img: 'fustolt-csemege-kolbasz',
    intro: 'Bükkfán füstölt kolbászok, téli- és paprikás szalámi, snack kolbász és turista – hagyományos fűszerezéssel, mangalicahúsból.' },
  { id: 'akcio', name: 'Akció', sub: 'Nagy kiszerelések, csomagok', img: 'zsirnakvalo',
    intro: 'Aktuális ajánlataink, amíg a készlet tart.' },
  { id: 'ajandek', name: 'Ajándék ötletek', sub: 'Dobozok, kosarak, ajándékkártya', img: 'ajandekdoboz-10',
    intro: 'Összeállított ajándékdobozok és -kosarak, valamint ajándékkártya – karácsonyra, céges ajándéknak.' }
];

BG.occasions = [
  { id: 'disznotoros', name: 'Disznótoros', img: 'hurka', intro: 'Hurka, sütőkolbász, kocsonyának és zsírnak való – a téli szezon klasszikusai.' },
  { id: 'uzsonnacsomag', name: 'Uzsonnacsomag', img: 'kalandor-csomag', intro: 'Összekészített uzsonnacsomagok iskolába, munkába, kirándulásra.' }
];

/* Kategóriaszintű tárolási megjegyzések (forrás: az ügyfél terméktáblázata) */
BG.catNotes = {
  tokehusok: ['Szeletelt tőkehús felbontás után 1 napig tartható el.', 'Kiolvasztás kíméletesen, hűtőszekrényben – soha ne szobahőmérsékleten.']
};

/* p(id, név, kategória, alkalmak, kiszerelés, ár, Ft/kg, extra)
   A terméknevek, kiszerelések, összetevők, allergének, tárolási és fagyasztási adatok, valamint a választható
   opciók forrása az ügyfél „Új weboldal termékek” táblázata (2026-11-17). A táblázatban nincs ár: az árak a
   OneMin-webshopból valók; ahol a kiszerelés változott, a kilós árból számolva. Az ár nélküli új termékek noPrice jelölést kaptak.
   Extra mezők: pk csomagolás · st tárolási hőmérséklet · shelf szavatosság · open felbontás után · fr fagyasztás · frt fagyasztási idő
   · heat hőkezelést igényel · opts választható opciók · noPrice nincs még ár */
(function () {
  const L = [];
  const p = (id, name, cat, occ, pack, price, unit, x) =>
    L.push(Object.assign({ id, name, cat, occ: occ || [], pack, price, unit: unit || null, img: id }, x || {}));
  const V = 'vákuumfólia', D = 'műanyag doboz', T5 = '0 – +5 °C', F = '−18 °C', NF = 'Nem ajánlott (minőségromlás)';
  /* friss tőkehús: 8 nap, felbontva 2 nap */
  const raw = (frt, x) => Object.assign({ pk: V, st: T5, shelf: '8 nap', open: '2 nap', fr: F, frt }, x || {});
  const SLICE = ['egészben', 'szeletelve'];
  const NIT = 'nitrit';

  /* TŐKEHÚSOK */
  p('szuz', 'Szűz', 'tokehusok', [], '400 g', 3600, 9000, raw('12 hónap', { ingr: 'Mangalica szűz' }));
  p('csontos-karaj', 'Csontos karaj', 'tokehusok', [], '500 g', 3550, 7100, raw('10–12 hónap', { ingr: 'Mangalica karaj' }));
  p('karaj-csont-nelkul', 'Karaj csont nélkül', 'tokehusok', [], '500 g', 4100, 8200, raw('12 hónap', { ingr: 'Mangalica karaj' }));
  p('csontos-tarja', 'Csontos tarja', 'tokehusok', [], '500 g', 3550, 7100, raw('8–10 hónap', { ingr: 'Mangalica tarja', variant: 'tarja-csont-nelkul',
    desc: 'A tarja a nyakrész, ahol a hús és a zsír rétegesen váltakozik. Csontosan sütve még szaftosabb: szeletelve grillre, egyben sütőbe.' }));
  p('tarja-csont-nelkul', 'Tarja csont nélkül', 'tokehusok', [], '500 g', 4100, 8200, raw('9–12 hónap', { ingr: 'Mangalica tarja', variant: 'csontos-tarja',
    desc: 'A tarja a nyakrész, ahol a hús és a zsír rétegesen váltakozik. Csont nélkül szeletelhető rostélyosnak, pecsenyének, gyrosnak.' }));
  p('comb', 'Comb', 'tokehusok', [], '500 g', 2750, 5500, raw('12 hónap', { ingr: 'Mangalica comb' }));
  p('lapocka', 'Lapocka', 'tokehusok', [], '500 g', 2750, 5500, raw('8–12 hónap', { ingr: 'Mangalica lapocka' }));
  p('oldalas', 'Oldalas', 'tokehusok', [], '500 g', 2050, 4100, raw('6–9 hónap', { ingr: 'Mangalica oldalas' }));
  p('bbq-oldalas', 'BBQ oldalas', 'tokehusok', [], '1 kg', 5500, 5500, raw('6–9 hónap', { ingr: 'Mangalica oldalas', tag: 'Grillre' }));
  p('dagado', 'Dagadó', 'tokehusok', ['disznotoros'], '1,3 kg', 5590, 4300, raw('6–8 hónap', { ingr: 'Mangalica dagadó' }));
  p('csulok', 'Csülök', 'tokehusok', ['disznotoros'], '1,5 kg', 5550, 3700, raw('6–8 hónap', { ingr: 'Mangalica csülök' }));
  p('csulok-csont-nelkul', 'Csülök csont nélkül', 'tokehusok', [], '500 g', 2400, 4800, raw('8–10 hónap', { ingr: 'Mangalica csülök' }));
  p('husos-csont', 'Húsos csont', 'tokehusok', ['disznotoros'], '1 kg', 1650, 1650, raw('5–6 hónap', { ingr: 'Mangalica húsos csont', shelf: '5 nap' }));
  p('zsirnakvalo', 'Zsírnak való', 'tokehusok', ['disznotoros'], '1 kg', 2800, 2800, { ingr: 'Mangalica zsírszalonna', st: T5, shelf: '4 nap', fr: F,
    opts: ['bőrös, egyben', 'bőr nélkül, egyben', 'bőr nélkül, kockázva'] });
  p('daralthus', 'Darálthús (70% hús)', 'tokehusok', [], '500 g', 2400, 4800, raw('4–6 hónap', { ingr: 'Mangalica hús 80%, mangalica zsiradék 20%', st: '0 – +2 °C', shelf: '4 nap', open: '1 nap' }));
  p('csaszarhus', 'Császárhús', 'tokehusok', [], '500 g', 2300, 4600, raw('6–8 hónap', { ingr: 'Mangalica császárhús' }));
  p('kocsonya-csomag', 'Kocsonya csomag', 'tokehusok', ['disznotoros'], '2,5 kg', 6625, 2650, raw('5–6 hónap', { ingr: 'Mangalica darabolt csülök, köröm, bőr' }));
  p('tomahawk', 'Tomahawk szelet', 'tokehusok', [], '250 g', 2175, 8700, raw('9–12 hónap', { ingr: 'Mangalica karaj bordacsonttal', tag: 'Grillre' }));
  p('t-bone', 'T-bone szelet', 'tokehusok', [], '250 g', 2175, 8700, raw('9–12 hónap', { ingr: 'Mangalica rövid karaj csonttal, szűzzel', tag: 'Grillre' }));
  p('mangalica-maj', 'Mangalica máj', 'tokehusok', [], '500 g', 1450, 2900, raw('4–6 hónap', { ingr: 'Mangalica máj', st: '0 – +4 °C', shelf: '4–5 nap', open: '1 nap' }));
  p('korom', 'Köröm', 'tokehusok', ['disznotoros'], '1 kg', 1600, 1600, raw('6–8 hónap', { ingr: 'Mangalica köröm', opts: ['darabolva', 'félbe vágva', 'egészben'] }));

  /* REGGELIRE */
  const MAJAS = 'Mangalica máj, belsőség, abált lé, vöröshagyma, nitrites só, őrölt bors, majoranna';
  p('dobozos-kenomajas', 'Dobozos kenőmájas', 'reggelire', [], '250 g', 1600, 6100, { ingr: MAJAS, allergen: NIT, pk: D, st: T5, shelf: '30 nap', open: '2–3 nap', fr: NF });
  p('tomlos-kenomajas', 'Tömlős kenőmájas (kenhető)', 'reggelire', [], '1 db (200–230 g)', 1340, 6100, { ingr: MAJAS, allergen: NIT, pk: 'tömlő', st: T5, shelf: '30 nap', open: '2–3 nap', fr: NF });
  p('aszalt-szilvas-kenomajas', 'Aszalt szilvás kenőmájas (kenhető)', 'reggelire', [], '1 db (200–230 g)', 0, null, { img: 'placeholder', noPrice: true,
    ingr: 'Mangalica máj, belsőség, abált lé, vöröshagyma, aszalt szilva, nitrites só, őrölt bors', allergen: NIT, pk: 'tömlő', st: T5, shelf: '30 nap', open: '2–3 nap', fr: NF });
  p('parasztmajas', 'Parasztmájas (darabos)', 'reggelire', [], '100 g', 610, 6100, { ingr: 'Mangalica máj, belsőség, vöröshagyma, étkezési só, majoranna, őrölt bors, fűszerpaprika', pk: 'műbél', st: T5, shelf: '20 nap', open: '2–3 nap', fr: NF });
  p('tepertokrem', 'Tepertőkrém', 'reggelire', [], '250 g', 1650, 6500, { ingr: 'Mangalica tepertő, mustár, étkezési só, őrölt bors, vöröshagyma', allergen: 'mustár', pk: D, st: T5, shelf: '30 nap', open: '3 nap', fr: F + ' (felengedés után át kell keverni)', frt: '2–3 hónap' });
  p('paprikas-kolbaszkrem', 'Paprikás kolbászkrém', 'reggelire', [], '500 g', 0, null, { img: 'placeholder', noPrice: true,
    ingr: '70% mangalica hús, ipari szalonna, fűszerpaprika, étkezési só, fokhagyma, őrölt bors, őrölt kömény, mangalica sertészsír', pk: D, st: T5, shelf: '30 nap', open: '3 nap', fr: F + ' (felengedés után át kell keverni)', frt: '2–3 hónap' });
  p('fokhagymas-rilette', 'Fokhagymás rilette', 'reggelire', [], '250 g', 1675, 6700, { ingr: 'Mangalica sertéshús, fokhagyma, étkezési só', pk: D, st: T5, shelf: '30 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  p('chilis-rilette', 'Mézes-chilis rilette', 'reggelire', [], '250 g', 1750, 6700, { ingr: 'Mangalica sertéshús, mangalica füstölt szalonna bőr nélkül, akácméz, étkezési só, fűszerpaprika, őrölt kömény, chiliszósz', tag: 'Csípős', pk: D, st: T5, shelf: '30 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  const cold = (x) => Object.assign({ allergen: NIT, pk: V, st: T5, shelf: '8 nap', open: '3 nap', fr: NF, opts: SLICE }, x);
  p('parizsi', 'Párizsi', 'reggelire', [], '100 g', 680, 6800, cold({ ingr: 'Mangalica sertéshús, ipari szalonna, párizsi fűszerkeverék, nitrites só' }));
  p('chilis-parizsi', 'Chilis párizsi', 'reggelire', [], '100 g', 680, 6800, cold({ ingr: 'Mangalica sertéshús, ipari szalonna, párizsi fűszerkeverék, nitrites só, chilipehely, fehér bors', tag: 'Csípős' }));
  p('fustolt-parizsi', 'Füstölt párizsi', 'reggelire', [], '100 g', 680, 6800, cold({ ingr: 'Mangalica sertéshús, ipari szalonna, párizsi fűszerkeverék, nitrites só; bükkfán füstölve' }));
  p('nyelves-felvagott', 'Nyelves felvágott', 'reggelire', [], '100 g', 680, 6800, cold({ ingr: 'Mangalica sertéshús, ipari szalonna, nyelv, párizsi fűszerkeverék, nitrites só' }));
  p('uzsonnasonka', 'Uzsonnasonka', 'reggelire', [], '100 g', 680, 6800, cold({ ingr: 'Mangalica sertéshús, nitrites só, sonka-fűszerkeverék' }));
  const VIRSLI = 'Mangalica sertéshús, ipari szalonna, nitrites só, virsli-fűszerkeverék, ehető kollagénbél';
  p('virsli', 'Virsli (ehető kollagénben)', 'reggelire', [], '500 g', 3400, 6800, { ingr: VIRSLI, allergen: NIT, heat: true, pk: V, st: T5, shelf: '16 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  p('fustolt-virsli', 'Füstölt virsli (ehető kollagénben, bükkfán füstölve)', 'reggelire', [], '500 g', 3400, 6800, { ingr: VIRSLI + '; bükkfán füstölve', allergen: NIT, heat: true, pk: V, st: T5, shelf: '16 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  p('debreceni-paros', 'Debreceni páros kolbász', 'reggelire', [], '500 g', 2900, 5800, { ingr: 'Mangalica sertéshús, ipari szalonna, debreceni fűszerkeverék, nitrites só, sertésbél', allergen: NIT, heat: true, pk: V, st: T5, shelf: '20 nap', open: '3 nap', fr: F, frt: '2–4 hónap' });
  p('gesztelyi-paros', 'Gesztelyi páros kolbász', 'reggelire', [], '500 g', 2900, 5800, { ingr: 'Mangalica sertéshús, nitrites só, fokhagyma, őrölt bors, őrölt kömény, majoranna', allergen: NIT, heat: true, pk: V, st: T5, shelf: '20 nap', open: '3 nap', fr: F, frt: '2–4 hónap' });

  /* KERTI PARTI */
  p('hamburgerhus', 'Hamburgerhús', 'kerti-parti', [], '4 × 15 dkg (600 g)', 3480, 5800, { ingr: 'Mangalica hús 80%, mangalica füstölt szalonna 10%, mangalica szalonna 10%, étkezési só, őrölt feketebors', heat: true, tag: 'Grillre', pk: V, st: T5, shelf: '30 nap', open: '1 nap', fr: F, frt: '10–12 hónap' });
  p('tepett-hus', 'Tépett hús', 'kerti-parti', [], '500 g', 3350, 6700, { ingr: 'Mangalica sertéshús, ketchup, vöröshagyma, cukor, mustár, étkezési só, worcestershire-szósz, szójaszósz, fűszerpaprika, fokhagyma, őrölt bors, őrölt kömény, koriander, római kömény, oregánó, majoranna', allergen: 'mustár, szója', pk: V, st: T5, open: '3 nap', fr: F, frt: '6–8 hónap' });
  p('fredi-szelet', 'Frédi szelet', 'kerti-parti', [], '1,6 kg', 11520, 7200, { ingr: 'Mangalica bőrös comb, sous vide fűszerkeverék, étkezési só, fokhagyma, őrölt bors, oregánó, római kömény, majoranna, koriander', heat: true, tag: 'Grillre', pk: V, st: T5, open: '3 nap', fr: F, frt: '8–10 hónap' });
  p('grillkolbasz-bajor', 'Grillkolbász (bajor jellegű)', 'kerti-parti', [], '500 g', 3050, 6100, { ingr: 'Mangalica sertéshús, ipari szalonna, nitrites só, fokhagyma, őrölt bors, őrölt kömény, majoranna, juhbél', allergen: NIT, heat: true, tag: 'Grillre', pk: V, st: T5, open: '3 nap', fr: F, frt: '6–8 hónap' });
  p('grillkolbasz-magyaros', 'Grillkolbász (magyaros)', 'kerti-parti', [], '500 g', 3050, 6100, { ingr: 'Mangalica sertéshús, ipari szalonna, nitrites só, fűszerpaprika, fokhagyma, őrölt bors, őrölt kömény, majoranna, juhbél', allergen: NIT, tag: 'Grillre', pk: V, st: T5, open: '3 nap', fr: F, frt: '6–8 hónap' });

  /* SZALONNÁK, FÜSTÖLTEK */
  const smoke = (x) => Object.assign({ pk: V, st: T5, shelf: '60 nap', fr: F, frt: '6–8 hónap' }, x);
  p('kolozsvari-szalonna', 'Kolozsvári szalonna', 'szalonnak', [], '200 g', 1220, 6100, smoke({ ingr: 'Mangalica császárhús, szalonna, étkezési só; bükkfán füstölve', opts: SLICE }));
  p('fustolt-csemege-szalonna', 'Füstölt csemege szalonna', 'szalonnak', [], '200 g', 1020, 5100, smoke({ ingr: 'Mangalica hátszalonna, étkezési só; bükkfán füstölve' }));
  p('angol-szalonna', 'Angol szalonna szeletelve', 'szalonnak', [], '100 g', 780, 7800, smoke({ ingr: 'Mangalica bőrös karaj, étkezési só; bükkfán füstölve', open: '4–5 nap', frt: '3–4 hónap' }));
  p('abalt-szalonna', 'Abált szalonna', 'szalonnak', ['disznotoros'], '300 g', 1700, 5700, { ingr: 'Mangalica császárszalonna, étkezési só, őrölt bors, fokhagyma, fűszerpaprika', pk: V, st: T5, shelf: '21 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  p('formazott-nyers-szalonna', 'Formázott nyers szalonna', 'szalonnak', [], '500 g', 1850, 3700, { ingr: 'Mangalica szalonna', pk: V, st: T5, shelf: '4 nap', open: '4–5 nap', fr: F, frt: '4–6 hónap' });
  p('sos-szalonna', 'Sós szalonna', 'szalonnak', [], '500 g', 2300, 4600, { ingr: 'Mangalica szalonna, étkezési só', pk: V, st: T5, shelf: '30 nap', fr: F, frt: '6–8 hónap' });
  p('fustolt-csulok', 'Füstölt csülök', 'szalonnak', [], '1,3 kg / db', 0, null, smoke({ img: 'placeholder', noPrice: true, ingr: 'Mangalica csülök, étkezési só; bükkfán füstölve' }));
  p('fustolt-csulok-csont-nelkul', 'Füstölt csülök csont nélkül', 'szalonnak', [], '1 kg', 0, null, smoke({ img: 'placeholder', noPrice: true, ingr: 'Mangalica csülök, étkezési só; bükkfán füstölve' }));
  p('fustolt-sonka', 'Füstölt sonka', 'szalonnak', [], '200 g', 1940, 9700, smoke({ ingr: 'Mangalica lapocka/comb, étkezési só; bükkfán füstölve' }));
  p('fustolt-oldalas', 'Füstölt oldalas / bordaporc', 'szalonnak', [], '500 g', 1900, 3800, smoke({ ingr: 'Mangalica oldalas, étkezési só; bükkfán füstölve' }));

  /* KLASSZIKUSOK */
  p('hurka', 'Hurka', 'klasszikusok', ['disznotoros'], '1 kg', 3990, 3990, { ingr: 'Mangalica főtt fej, hús, belsőség, főtt máj, rizs, vöröshagyma, étkezési só, majoranna, őrölt bors, fűszerpaprika, őrölt kömény, sertésbél', heat: true, pk: V, st: T5, shelf: '14 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  p('sutokolbasz', 'Sütőkolbász', 'klasszikusok', ['disznotoros'], '1 kg', 4800, 4800, { ingr: 'Mangalica sertéshús, ipari szalonna, fűszerpaprika, nitrites só, fokhagyma, őrölt bors, őrölt kömény', allergen: NIT, heat: true, pk: V, st: T5, shelf: '10 nap', open: '3 nap', fr: F, frt: '2–3 hónap' });
  p('teperto', 'Tepertő', 'klasszikusok', ['disznotoros'], '100 g', 870, 8700, { ingr: 'Mangalica zsírszalonna, étkezési só', pk: D, st: T5, shelf: '14 nap', fr: F, frt: '4–6 hónap' });
  p('zsir-1kg', 'Zsír (1 kg)', 'klasszikusok', ['disznotoros'], '1 kg', 2500, 2500, { ingr: 'Mangalica zsírszalonna', pk: 'műanyag vödör', st: T5, shelf: '360 nap', fr: F, frt: '8–12 hónap' });
  p('zsir-5kg', 'Zsír (5 kg)', 'klasszikusok', ['disznotoros'], '5 kg', 10000, 2000, { ingr: 'Mangalica zsírszalonna', pk: 'műanyag vödör', st: T5, shelf: '360 nap', fr: F, frt: '8–12 hónap' });
  p('rakott-fej', 'Rakott fej', 'klasszikusok', ['disznotoros'], '100 g', 520, 5200, { ingr: 'Mangalica főtt fej, abált lé, fokhagyma, étkezési só, fűszerpaprika, őrölt bors, őrölt kömény', pk: V, st: T5, shelf: '14 nap', open: '3 nap', fr: NF });
  p('csuloksajt', 'Csülöksajt', 'klasszikusok', ['disznotoros'], '100 g', 520, 5200, { ingr: 'Mangalica főtt első csülök, abált lé, fokhagyma, étkezési só, őrölt bors, őrölt kömény', pk: V, st: T5, shelf: '14 nap', open: '3 nap', fr: NF });
  p('husveti-kotozott', 'Húsvéti kötözött mangalica', 'klasszikusok', [], '[kiszerelés az ügyféltől]', 0, null, { img: 'placeholder', noPrice: true, ingr: 'Mangalica comb/lapocka, étkezési só; bükkfán füstölve', pk: V, st: T5, shelf: '90 nap', fr: F, frt: '6–8 hónap' });

  /* SZALÁMIK, KOLBÁSZOK */
  const dry = (x) => Object.assign({ pk: V, st: T5, shelf: '90 nap', fr: F, frt: '4–6 hónap' }, x);
  p('paprikas-szalami', 'Paprikás szalámi', 'kolbaszok', [], '200 g', 2380, 11900, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, fűszerpaprika, nitrites só, fokhagyma, fűszerkömény, őrölt bors; bükkfán füstölve', allergen: NIT, open: '4–5 nap (szeletelve)', opts: SLICE }));
  p('csipos-paprikas-szalami', 'Csípős paprikás szalámi', 'kolbaszok', [], '200 g', 2380, 11900, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, fűszerpaprika, nitrites só, fokhagyma, fűszerkömény, őrölt bors, chiliszósz; bükkfán füstölve', allergen: NIT, tag: 'Csípős', open: '4–5 nap (szeletelve)', opts: SLICE }));
  p('teliszalami', 'Téliszalámi', 'kolbaszok', [], '200 g', 2380, 11900, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, nitrites só, téliszalámi-fűszerkeverék; bükkfán füstölve', allergen: NIT, open: '4–5 nap (szeletelve)', opts: SLICE }));
  p('fustolt-csemege-kolbasz', 'Füstölt csemege kolbász', 'kolbaszok', ['disznotoros'], '1 pár (450 g)', 4410, 9800, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, fűszerpaprika, étkezési só, fokhagyma, őrölt bors, őrölt kömény, sertésbél; bükkfán füstölve' }));
  p('fustolt-csipos-kolbasz', 'Füstölt csípős kolbász', 'kolbaszok', [], '1 pár (300 g)', 2940, 9800, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, fűszerpaprika, étkezési só, fokhagyma, őrölt bors, őrölt kömény, chiliszósz, sertésbél; bükkfán füstölve', tag: 'Csípős' }));
  p('miskolci-kolbasz', 'Füstölt miskolci kolbász (paprikamentes)', 'kolbaszok', [], '1 pár (450 g)', 4410, 9800, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, étkezési só, fokhagyma, őrölt bors, őrölt kömény, sertésbél; bükkfán füstölve' }));
  p('csemege-snack-kolbasz', 'Füstölt csemege snack kolbász', 'kolbaszok', [], '150 g', 0, null, dry({ img: 'placeholder', noPrice: true, ingr: 'Mangalica sertéshús, ipari szalonna, nitrites só, téliszalámi-fűszerkeverék; bükkfán füstölve', allergen: NIT }));
  p('csipos-snack-kolbasz', 'Füstölt csípős snack kolbász', 'kolbaszok', [], '150 g', 1635, 10900, dry({ ingr: 'Mangalica sertéshús, ipari szalonna, nitrites só, téliszalámi-fűszerkeverék, fűszerpaprika, chiliszósz; bükkfán füstölve', allergen: NIT, tag: 'Csípős' }));
  p('mangalica-turista', 'Mangalica turista', 'kolbaszok', [], '100 g', 680, 6800, { ingr: 'Mangalica sertéshús, ipari szalonna, nitrites só, szoluprát, őrölt bors, fűszerpaprika, fokhagyma-granulátum, szegfűbors; bükkfán füstölve', pk: V, st: T5, shelf: '14 nap', open: '2–3 nap', fr: F, frt: '2–3 hónap', opts: SLICE });

  /* AKCIÓ */
  p('zsirnakvalo-akcio', 'Zsírnak való AKCIÓ (5 kg)', 'akcio', ['disznotoros'], '5 kg', 9000, 1800, { img: 'zsirnakvalo', ingr: 'Mangalica zsírszalonna', tag: 'Akció', st: T5, shelf: '4 nap',
    opts: ['bőrös, egyben', 'bőr nélkül, egyben', 'bőr nélkül, kockázva'] });
  p('korom-akcio', 'Köröm csomag AKCIÓ (5 kg)', 'akcio', ['disznotoros'], '5 kg', 3500, 700, { img: 'korom', old: 6500, ingr: 'Mangalica köröm', tag: 'Akció', st: T5, shelf: '4 nap', opts: ['darabolva', 'félbe vágva', 'egészben'] });
  p('augusztusi-grillcsomag', 'Augusztusi grillcsomag', 'akcio', [], '1 csomag', 0, null, { img: 'placeholder', noPrice: true, tag: 'Akció', st: T5,
    ingr: 'A csomag tartalma: 2 pár bajor grillkolbász, 2 pár paprikás grillkolbász, 4 db hamburgerhús-pogácsa, 1,3 kg Frédi szelet, 25 dkg chilis rilette' });
  p('kalandor-csomag', 'Kalandor uzsonnacsomag', 'akcio', ['uzsonnacsomag'], '1 csomag', 4490, null, { st: T5, ingr: 'A csomag tartalma: 80 g téliszalámi, 80 g uzsonnasonka, 80 g natúr párizsi, 130 g csemege snack kolbász, 110 g kenőmájas', allergen: NIT });
  p('csibesz-csomag', 'Csibész uzsonnacsomag', 'akcio', ['uzsonnacsomag'], '1 csomag', 4490, null, { st: T5, ingr: 'A csomag tartalma: 80 g téliszalámi, 80 g uzsonnasonka, 80 g chilis párizsi, 130 g csípős snack kolbász, 110 g kenőmájas', allergen: NIT, tag: 'Csípős' });

  /* AJÁNDÉK ÖTLETEK */
  p('ajandekkartya-5000', 'Ajándékkártya 5 000 Ft', 'ajandek', [], '1 db', 5000, null, { ingr: '5 000 Ft értékben levásárolható', giftcard: true });
  p('ajandekkartya-10000', 'Ajándékkártya 10 000 Ft', 'ajandek', [], '1 db', 10000, null, { ingr: '10 000 Ft értékben levásárolható', giftcard: true });
  p('ajandekkartya-20000', 'Ajándékkártya 20 000 Ft', 'ajandek', [], '1 db', 20000, null, { ingr: '20 000 Ft értékben levásárolható', giftcard: true });
  p('ajandekdoboz-10', 'Ajándékdoboz (10 féle mangalica termékkel)', 'ajandek', [], '1 doboz', 15000, null, { ingr: 'Az ajándékdoboz tartalma: 1 csomag csemege snack kolbász, 1 csomag csípős snack kolbász, 0,5 kg zsír, 25 dkg rilette, 25 dkg kenőmájas, 20 dkg kolozsvári szalonna, 20 dkg parasztmájas, 2 pár virsli, 10 dkg füstölt sonka, 1 doboz tepertőkrém' });
  p('ajandekkosar-12', 'Ajándékkosár (12 féle mangalica termékkel)', 'ajandek', [], '1 kosár', 22000, null, { ingr: 'Az ajándékkosár tartalma: 0,5 kg zsír, 1 db tömlős májas, 20 dkg angol szalonna, 1 csomag csemege snack, 20 dkg füstölt sonka, 25 dkg kolozsvári, 25 dkg fokhagymás rilette, 25 dkg chilis rilette, 10 dkg tepertő, 30 dkg abált szalonna, 1 db tömlős szilvás májas, 20 dkg parasztmájas' });
  p('ajandekdoboz-kicsi', 'Ajándékdoboz / KICSI', 'ajandek', [], '1 doboz', 32000, null, { ingr: 'Az ajándékdoboz tartalma: 1 db tömlős májas, 1 doboz rilette (25 dkg), 1 doboz tepertőkrém (25 dkg), 30 dkg abált szalonna, 1 pár csípős kolbász, 1 pár csemege kolbász, 20 dkg kolozsvári, 10 dkg füstölt sonka szelet, 0,5 kg virsli, 25 dkg parasztmájas, 20 dkg paprikás szalámi szelet' });
  p('ajandekdoboz-nagy', 'Ajándékdoboz / NAGY', 'ajandek', [], '1 doboz', 49000, null, { ingr: 'Az ajándékdoboz tartalma: 1 db tömlős májas, 1 doboz rilette (25 dkg), 1 pár csípős kolbász, 1 pár csemege kolbász, 1 pár miskolci kolbász, 30 dkg kolozsvári szalonna, 30 dkg csemege szalonna, 30 dkg abált szalonna, 30 dkg füstölt sonka szelet, 20 dkg téliszalámi szelet, 20 dkg paprikás szalámi szelet, 2 × 0,5 kg virsli (sima + füstölt), 30 dkg párizsi szelet, 30 dkg uzsonnasonka szelet' });

  BG.products = L;
})();


/* Keresztajánlás: mi illik egy termékhez (termékoldal, kosár, mini-kosár). Termékszintű lista felülírja a kategóriaszintűt. */
BG.pairByCat = {
  tokehusok: ['zsir-1kg', 'grillkolbasz-magyaros', 'kolozsvari-szalonna', 'fustolt-csemege-kolbasz'],
  kolbaszok: ['kolozsvari-szalonna', 'tepertokrem', 'paprikas-szalami', 'fustolt-sonka'],
  szalonnak: ['fustolt-csemege-kolbasz', 'tepertokrem', 'parasztmajas', 'teliszalami'],
  reggelire: ['fustolt-sonka', 'teliszalami', 'tepertokrem', 'kolozsvari-szalonna'],
  'kerti-parti': ['bbq-oldalas', 'tomahawk', 'kolozsvari-szalonna', 'zsir-1kg'],
  akcio: ['zsir-1kg', 'hurka', 'sutokolbasz', 'teperto'],
  klasszikusok: ['sutokolbasz', 'hurka', 'zsir-1kg', 'abalt-szalonna'],
  ajandek: ['ajandekkartya-5000', 'ajandekdoboz-kicsi', 'fustolt-csemege-kolbasz', 'teliszalami']
};
BG.pairByProduct = {
  'csontos-tarja': ['bbq-oldalas', 'grillkolbasz-magyaros', 'zsir-1kg', 'kolozsvari-szalonna'],
  'tarja-csont-nelkul': ['grillkolbasz-magyaros', 'bbq-oldalas', 'zsir-1kg', 'kolozsvari-szalonna'],
  'bbq-oldalas': ['grillkolbasz-bajor', 'tomahawk', 'hamburgerhus', 'csontos-tarja'],
  'tomahawk': ['t-bone', 'bbq-oldalas', 'grillkolbasz-magyaros', 'hamburgerhus'],
  't-bone': ['tomahawk', 'bbq-oldalas', 'grillkolbasz-magyaros', 'hamburgerhus'],
  'grillkolbasz-magyaros': ['grillkolbasz-bajor', 'tomahawk', 'bbq-oldalas', 'hamburgerhus'],
  'grillkolbasz-bajor': ['grillkolbasz-magyaros', 'bbq-oldalas', 'hamburgerhus', 'tomahawk'],
  'hamburgerhus': ['grillkolbasz-magyaros', 'kolozsvari-szalonna', 'bbq-oldalas', 'tomahawk'],
  'hurka': ['sutokolbasz', 'zsir-1kg', 'teperto', 'abalt-szalonna'],
  'sutokolbasz': ['hurka', 'zsir-1kg', 'teperto', 'abalt-szalonna'],
  'zsirnakvalo': ['zsir-1kg', 'teperto', 'hurka', 'sutokolbasz'],
  'kocsonya-csomag': ['korom', 'husos-csont', 'csulok', 'hurka'],
  'parizsi': ['uzsonnasonka', 'virsli', 'tepertokrem', 'parasztmajas'],
  'virsli': ['fustolt-virsli', 'parizsi', 'uzsonnasonka', 'debreceni-paros'],
  'tepertokrem': ['teperto', 'kolozsvari-szalonna', 'parasztmajas', 'fokhagymas-rilette'],
  'ajandekdoboz-10': ['ajandekkartya-5000', 'ajandekdoboz-kicsi', 'ajandekkosar-12', 'fustolt-csemege-kolbasz'],
  'ajandekkosar-12': ['ajandekkartya-10000', 'ajandekdoboz-nagy', 'ajandekdoboz-10', 'teliszalami']
};

/* Ugyanaz a termék nagyobb kiszerelésben, jobb kilóárral – csak valódi párok */
BG.biggerPack = { 'zsir-1kg': 'zsir-5kg', 'zsirnakvalo': 'zsirnakvalo-akcio', 'korom': 'korom-akcio' };

/* Fehér hátterű termékfotók (assets/img/termekek-feher/, a mostani fotókból háttéreltávolítással). A többi termék az eredeti fotót használja. */
BG.whiteImg = ['abalt-szalonna','ajandekdoboz-10','ajandekdoboz-kicsi','ajandekdoboz-nagy','ajandekkosar-12','angol-szalonna','bbq-oldalas','bosegtal','chilis-parizsi','chilis-rilette','comb','csaszarhus','csipos-paprikas-szalami','csipos-snack-kolbasz','csontos-karaj','csontos-tarja','csulok','csulok-csont-nelkul','csuloksajt','dagado','daralthus','debreceni-paros','dobozos-kenomajas','fokhagymas-rilette','formazott-nyers-szalonna','fredi-szelet','fustolt-csemege-kolbasz','fustolt-csemege-szalonna','fustolt-csipos-kolbasz','fustolt-oldalas','fustolt-parizsi','fustolt-sonka','gesztelyi-paros','grillkolbasz-bajor','grillkolbasz-magyaros','hamburgerhus','hurka','husos-csont','karaj-csont-nelkul','kocsonya-csomag','kolozsvari-szalonna','korom','lapocka','mangalica-maj','mangalica-turista','miskolci-kolbasz','nyelves-felvagott','oldalas','paprikas-szalami','parasztmajas','parizsi','rakott-fej','sos-szalonna','sutokolbasz','szuz','t-bone','tarja-csont-nelkul','teliszalami','tepertokrem','tepett-hus','tomahawk','tomlos-kenomajas','uzsonnasonka','virsli','zsir-1kg','zsir-5kg','zsirnakvalo'];

BG.featured = ['fustolt-csemege-kolbasz', 'kolozsvari-szalonna', 'csontos-tarja', 'grillkolbasz-magyaros', 'paprikas-szalami', 'tepertokrem', 'szuz', 'tomahawk'];

/* Szállítás – pontosan a mostani bgmangalica.hu (OneMin) szerint: kiszállítás kedd–péntek,
   rendelés a kiszállítást megelőző napon délig. Szállítási díj nincs, településenként és járatonként rendelési minimum van. */
BG.cutoffHour = 12;

BG.areas = [
  { id: 'pest', name: 'Budapest és környéke' },
  { id: 'borsod', name: 'Borsod-Abaúj-Zemplén' },
  { id: 'heves', name: 'Heves' },
  { id: 'hajdu', name: 'Hajdú-Bihar' },
  { id: 'szabolcs', name: 'Szabolcs-Szatmár-Bereg' },
  { id: 'dunantul', name: 'Dunántúl (november 5.)' }
];

/* Települések a OneMin 92 szállítási régiójából (2026-11-02): név, terület, járatok [{ day: 2 = kedd … 5 = péntek | date: egyszeri járat, min: rendelési minimum Ft }], irányítószámok (hu-postal-codes, Magyar Posta, MIT). */
BG.towns = [
  ['Biatorbágy', 'pest', [{ day: 2, min: 10000 }], ['2051']],
  ['Budakeszi', 'pest', [{ day: 2, min: 10000 }], ['2092']],
  ['Budaörs', 'pest', [{ day: 2, min: 10000 }], ['2040']],
  ['Budapest', 'pest', [{ day: 2, min: 10000 }, { day: 5, min: 5000 }], ['1']],
  ['Csömör', 'pest', [{ day: 5, min: 10000 }], ['2141']],
  ['Diósd', 'pest', [{ day: 2, min: 15000 }], ['2049']],
  ['Dunakeszi', 'pest', [{ day: 2, min: 10000 }], ['2120']],
  ['Érd', 'pest', [{ day: 2, min: 10000 }, { day: 5, min: 10000 }], ['2030']],
  ['Etyek', 'pest', [{ day: 2, min: 10000 }], ['2091']],
  ['Fót', 'pest', [{ day: 2, min: 20000 }], ['2151']],
  ['Gödöllő', 'pest', [{ day: 5, min: 20000 }], ['2100']],
  ['Gyál', 'pest', [{ day: 5, min: 10000 }], ['2360']],
  ['Halásztelek', 'pest', [{ day: 5, min: 10000 }], ['2314']],
  ['Kistarcsa', 'pest', [{ day: 5, min: 10000 }], ['2143']],
  ['Mogyoród', 'pest', [{ day: 5, min: 20000 }], ['2146']],
  ['Nagytarcsa', 'pest', [{ day: 5, min: 10000 }], ['2142']],
  ['Solymár', 'pest', [{ day: 2, min: 15000 }], ['2083']],
  ['Szentjakab Park', 'pest', [{ day: 5, min: 10000 }], []],
  ['Szigetszentmiklós', 'pest', [{ day: 5, min: 20000 }], ['2310']],
  ['Törökbálint', 'pest', [{ day: 2, min: 10000 }], ['2045']],
  ['Üröm', 'pest', [{ day: 2, min: 10000 }], ['2096']],
  ['Vecsés', 'pest', [{ day: 5, min: 20000 }], ['2220']],
  ['Veresegyház', 'pest', [{ day: 5, min: 15000 }], ['2112']],
  ['Alsózsolca', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3571']],
  ['Arnót', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3713']],
  ['Bekecs', 'borsod', [{ day: 4, min: 5000 }], ['3903']],
  ['Bőcs', 'borsod', [{ day: 5, min: 5000 }], ['3574']],
  ['Bodrogkeresztúr', 'borsod', [{ day: 4, min: 5000 }], ['3916']],
  ['Emőd', 'borsod', [{ day: 3, min: 5000 }], ['3432']],
  ['Encs', 'borsod', [{ day: 5, min: 10000 }], ['3860']],
  ['Felsőzsolca', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3561']],
  ['Gesztely', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3715', '3923']],
  ['Hernádkak', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3563']],
  ['Hernádnémeti', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3564']],
  ['Kazincbarcika', 'borsod', [{ day: 3, min: 5000 }], ['3700']],
  ['Kesznyéten', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3579']],
  ['Kistokaj', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3553']],
  ['Mád', 'borsod', [{ day: 4, min: 5000 }], ['3909']],
  ['Mályi', 'borsod', [{ day: 3, min: 10000 }, { day: 5, min: 10000 }], ['3434']],
  ['Mályinka', 'borsod', [{ day: 3, min: 10000 }], ['3645']],
  ['Mezőkövesd', 'borsod', [{ day: 3, min: 15000 }], ['3400']],
  ['Miskolc', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3500', '3501', '3508', '3515', '3516', '3517', '3518', '3519', '3521', '3524', '3525', '3526', '3527', '3528', '3529', '3530', '3531', '3532', '3533', '3534', '3535', '3558']],
  ['Monok', 'borsod', [{ day: 4, min: 5000 }], ['3905']],
  ['Nyékládháza', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3433']],
  ['Onga', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3562']],
  ['Sajóbábony', 'borsod', [{ day: 3, min: 5000 }], ['3792']],
  ['Sajóörös', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3586']],
  ['Sajószentpéter', 'borsod', [{ day: 3, min: 10000 }], ['3770']],
  ['Sajószöged', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3599']],
  ['Sárospatak', 'borsod', [{ day: 4, min: 40000 }], ['3950']],
  ['Szerencs', 'borsod', [{ day: 4, min: 5000 }], ['3900', '3902']],
  ['Tarcal', 'borsod', [{ day: 4, min: 5000 }], ['3915']],
  ['Tardona', 'borsod', [{ day: 3, min: 10000 }], ['3644']],
  ['Tiszalúc', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3565']],
  ['Tiszaújváros', 'borsod', [{ day: 3, min: 5000 }, { day: 5, min: 5000 }], ['3580']],
  ['Tokaj', 'borsod', [{ day: 4, min: 5000 }], ['3910']],
  ['Újcsanálos', 'borsod', [{ day: 2, min: 5000 }, { day: 5, min: 5000 }], ['3716']],
  ['Eger', 'heves', [{ day: 3, min: 10000 }], ['3300']],
  ['Gyöngyös', 'heves', [{ day: 2, min: 10000 }], ['3200', '3221', '3232', '3233']],
  ['Hatvan', 'heves', [{ day: 5, min: 20000 }], ['3000']],
  ['Maklár', 'heves', [{ day: 3, min: 10000 }], ['3397']],
  ['Ostoros', 'heves', [{ day: 3, min: 15000 }], ['3326']],
  ['Szihalom', 'heves', [{ day: 3, min: 5000 }], ['3377']],
  ['Szilvásvárad', 'heves', [{ day: 3, min: 10000 }], ['3348']],
  ['Bocskaikert', 'hajdu', [{ day: 4, min: 5000 }], ['4241']],
  ['Debrecen', 'hajdu', [{ day: 4, min: 5000 }], ['4000', '4002', '4024', '4025', '4026', '4027', '4028', '4029', '4030', '4031', '4032', '4033', '4034', '4042', '4063', '4078', '4079']],
  ['Görbeháza', 'hajdu', [{ day: 4, min: 5000 }], ['4075']],
  ['Hajdúböszörmény', 'hajdu', [{ day: 4, min: 5000 }], ['4074', '4086', '4220', '4224']],
  ['Hajdúhadház', 'hajdu', [{ day: 4, min: 20000 }], ['4242']],
  ['Pallag', 'hajdu', [{ day: 4, min: 5000 }], []],
  ['Polgár', 'hajdu', [{ day: 4, min: 5000 }], ['4090']],
  ['Téglás', 'hajdu', [{ day: 4, min: 20000 }], ['4243']],
  ['Vámospércs', 'hajdu', [{ day: 4, min: 10000 }], ['4287']],
  ['Nyíregyháza', 'szabolcs', [{ day: 4, min: 5000 }], ['4246', '4400', '4405', '4431', '4432', '4433', '4481', '4551']],
  ['Nyírtelek', 'szabolcs', [{ day: 4, min: 5000 }], ['4461']],
  ['Rakamaz', 'szabolcs', [{ day: 4, min: 5000 }], ['4465']],
  ['Tiszavasvári', 'szabolcs', [{ day: 4, min: 10000 }], ['4440']],
  ['Újfehértó', 'szabolcs', [{ day: 4, min: 5000 }], ['4244']],
  ['Balatonalmádi', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['8220']],
  ['Balatonfüred', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['8230']],
  ['Balatonkenese', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['8174']],
  ['Győr', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['9000', '9011', '9012', '9019', '9021', '9022', '9023', '9024', '9025', '9026', '9027', '9028', '9029', '9030']],
  ['Székesfehérvár', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['8000', '8019']],
  ['Velence', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['2481']],
  ['Veszprém', 'dunantul', [{ date: '2026-11-05', min: 10000 }], ['8200', '8411', '8412']]
].map(([name, zone, runs, zips]) => ({ name, zone, runs, zips, min: Math.min.apply(null, runs.map((r) => r.min)) }));

BG.recipes = [
  { id: 'tarja-grillen', title: 'Mangalicatarja grillen, fokhagymás páccal', meta: 'Grill', img: 'csontos-tarja', serves: '4 főre', prep: '15 perc', wait: '2 óra pácolás', cook: '15–20 perc',
    lead: 'A mangalica tarjáját a zsírrétegek viszik: ha nem sütöd túl, a grillen is szaftos marad. A titok a rövid, erős hő és a pihentetés.',
    ingredients: ['1 kg mangalica csontos tarja (2 csomag)', '4 gerezd fokhagyma', '2 evőkanál olvasztott mangalicazsír', '1 citrom leve', '1 teáskanál só', '1 teáskanál frissen őrölt bors', '1 teáskanál füstölt pirospaprika', '1 ág rozmaring'],
    shop: [['csontos-tarja', 2], ['zsir-1kg', 1]],
    steps: ['A tarját vágd kb. 2 cm vastag szeletekre. A csontos részeknél a csont mentén haladj.',
      'Keverd össze a zúzott fokhagymát, az olvasztott zsírt, a citromlevet és a fűszereket. Kend be vele a szeleteket, és pácold legalább 2 órát a hűtőben.',
      'Sütés előtt fél órával vedd ki a hűtőből, hogy szobahőmérsékletű legyen.',
      'Forró rácson oldalanként 4–5 percig süsd, majd tedd az indirekt zónára, amíg a maghőmérséklet eléri a 63 °C-ot.',
      'Vedd le, takard le lazán, és pihentesd 5 percig, mielőtt felszeleted.'],
    tip: 'Csontos tarjánál a csont melletti rész lassabban sül, ezért azt told az indirekt zónába tovább.' },
  { id: 'bbq-oldalas', title: 'BBQ oldalas lassan, füstön', meta: 'Grill', img: 'bbq-oldalas', serves: '4 főre', prep: '20 perc', wait: '1 éjszaka', cook: '3–4 óra',
    lead: 'A mangalica-oldalasnak idő kell: alacsony hőfokon, füstön puhul omlósra.',
    ingredients: ['2 kg mangalica BBQ oldalas', '2 evőkanál barna cukor', '1 evőkanál füstölt pirospaprika', '1 evőkanál só', '1 teáskanál őrölt bors', '1 teáskanál fokhagymapor', '200 ml barbecue-szósz'],
    shop: [['bbq-oldalas', 2]],
    steps: ['Húzd le az oldalas belső oldaláról a hártyát.', 'Keverd össze a száraz fűszereket, dörzsöld be vele a húst, és hagyd a hűtőben egy éjszakán át.', 'Indirekt hőn, 110–120 °C-on, füstfával süsd 3 órán át.', 'Kend meg a szósszal, és süsd még 30–40 percig, amíg ragacsos réteg nem képződik.', 'Pihentesd 10 percig, majd vágd szét a csontok mentén.'],
    tip: 'Ha a csontok végéről 1 cm-re visszahúzódott a hús, kész.' },
  { id: 'hurka-kolbasz', title: 'Hurka, sütőkolbász, párolt káposzta', meta: 'Disznótoros', img: 'hurka', serves: '4 főre', prep: '15 perc', wait: '–', cook: '45 perc',
    lead: 'A disznótoros klasszikus egyszerűen, sütőben – mangalicazsírral.',
    ingredients: ['1 kg mangalica hurka', '1 kg mangalica sütőkolbász', '2 evőkanál mangalicazsír', '1 kg savanyú káposzta', '1 fej vöröshagyma', '1 teáskanál köménymag'],
    shop: [['hurka', 1], ['sutokolbasz', 1], ['zsir-1kg', 1]],
    steps: ['A hurkát és a kolbászt tűzálló tálba tedd, locsold meg olvasztott zsírral, és szurkáld meg néhány helyen a kolbászt.', '200 °C-on süsd 35–40 percig, félidőben fordítsd meg.', 'Közben a hagymát zsíron dinszteld meg, add hozzá a kicsit átmosott káposztát és a köményt, és párold puhára.', 'Tálald forrón, mustárral és friss kenyérrel.'],
    tip: 'A hurkát ne szurkáld meg, mert kifolyik a töltelék.' },
  { id: 'zsirsutes', title: 'Házi zsírsütés mangalica zsírnakvalóból', meta: 'Alapok', img: 'zsir-1kg', serves: 'kb. 0,7 l zsír', prep: '20 perc', wait: '–', cook: '1,5–2 óra',
    lead: 'A mangalicazsír krémes, fehér és tartós – házilag is egyszerű kisütni, a végén tepertővel.',
    ingredients: ['1 kg mangalica zsírnakvaló', '1 dl víz', '1 dl tej (a tepertő színéhez)', 'só ízlés szerint'],
    shop: [['zsirnakvalo', 1]],
    steps: ['Vágd a szalonnát 2 × 2 cm-es kockákra.', 'Széles, vastag aljú lábasba tedd a vízzel, és lassú tűzön, gyakran kevergetve olvaszd ki.', 'Amikor a kockák aranysárgák, öntsd hozzá a tejet, és süsd tovább, amíg a tepertő ropogós nem lesz.', 'Szűrd le a zsírt tiszta üvegekbe, a tepertőt sózd meg.'],
    tip: 'A tej szép barnára süti a tepertőt, de a zsír ettől nem lesz tejes.' },
  { id: 'sonkas-zsemle', title: 'Sonkás zsemle, ahogy nálunk készül', meta: 'Dávid chef · videó', img: 'fustolt-sonka', video: true, serves: '4 db', prep: '10 perc', wait: '–', cook: '–',
    lead: 'Dávid chef recept-videója a Bio-Goods Facebook-oldalán. [A recept szövege a videóból készül.]',
    ingredients: ['4 friss zsemle', '20 dkg mangalica füstölt sonka', 'tepertőkrém', '[további hozzávalók a videóból]'],
    shop: [['fustolt-sonka', 1], ['tepertokrem', 1]],
    steps: ['[Lépések a videó alapján.]'], tip: '' },
  { id: 'mangalicaragu', title: 'Mangalicaragu kamadóban, bográcsban', meta: 'Lázár chef · videó', img: 'lapocka', video: true, serves: '6 főre', prep: '20 perc', wait: '–', cook: '2–3 óra',
    lead: 'Lázár chef ünnepi fogása hickory füstölőfával – a videó a Bio-Goods Facebook-oldalán. [A recept szövege a videóból készül.]',
    ingredients: ['1,5 kg mangalica lapocka', '[további hozzávalók a videóból]'],
    shop: [['lapocka', 3]],
    steps: ['[Lépések a videó alapján.]'], tip: '' }
];

/* Termékdíjak – forrás: céges bemutató PDF (2026). */
BG.awards = [
  { id: 'dobozos-kenomajas', name: 'Mangalica kenőmájas', award: 'Budapesti Mangalica Fesztivál, 2018' },
  { id: 'virsli', name: 'Mangalica virsli', award: 'Év kiváló terméke, Szakmai Elit díj, 2022' },
  { id: 'fokhagymas-rilette', name: 'Fokhagymás rilette', award: 'SVÉT Gasztronómiai Fesztivál, II. hely, 2023' },
  { id: 'parizsi', name: 'Mangalica párizsi', award: 'Év kiváló terméke, 2024' }
];

BG.reviews = [
  { name: 'Gáborné Á.', text: 'A legfinomabb grillkolbászt készítik, évek óta tőlük vásárolunk. Mindig friss és nagyon finom.' },
  { name: 'Szilvi R.', text: 'Minden csillagos 5: termékek, kommunikáció, szállítás! Örülök, hogy megtaláltam ezt az oldalt.' },
  { name: 'Róbert T.', text: 'Amióta rájuk találtam, csak tőlük fogyasztok sertésterméket. Minden terméken érződik, hogy küldetésként élik meg, amit csinálnak.' }
];
