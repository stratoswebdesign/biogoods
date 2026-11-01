/*
 * Bio-Goods demo webshop – adatok
 * ---------------------------------------------------------------
 * A termékek, árak, kiszerelések és a települési minimumok a
 * bgmangalica.hu mostani (OneMin) webshopjából származnak (2026. november).
 * DEMO-ADAT: a járatnapok, az irányítószám-tartományok és a hiányzó
 * mezők mintaértékek – élesítés előtt az ügyféllel véglegesítendők.
 */
window.BG = window.BG || {};

BG.categories = [
  { id: 'tokehusok', name: 'Tőkehúsok', sub: 'Tarja, karaj, szűz, oldalas, csülök', img: 'csontos-tarja',
    intro: 'Tarja, karaj, szűz, comb, oldalas, csülök – a saját tenyészetünkből. A mangalica húsát a márványozottság teszi szaftossá: sütéshez, grillhez és lassú főzéshez is.' },
  { id: 'kolbaszok', name: 'Kolbászok, szalámik', sub: 'Füstölt, csemege, csípős, téli', img: 'fustolt-csemege-kolbasz',
    intro: 'Bükkfán füstölt kolbászok, téli- és paprikás szalámi, grillkolbász – hagyományos fűszerezéssel, mangalicahúsból.' },
  { id: 'szalonnak', name: 'Szalonnák, füstöltek', sub: 'Kolozsvári, abált, füstölt sonka', img: 'kolozsvari-szalonna',
    intro: 'Kolozsvári, abált, sós és füstölt szalonna, füstölt sonka és oldalas a gesztelyi füstölőből.' },
  { id: 'felvagottak', name: 'Felvágottak, kenhetők', sub: 'Párizsi, májas, tepertőkrém, rilette', img: 'parizsi',
    intro: 'Párizsi, virsli, uzsonnasonka, kenőmájas, tepertőkrém és rilette – reggelire, uzsonnára, hidegtálra.' },
  { id: 'hagyomanyos', name: 'Hagyományos', sub: 'Hurka, sütőkolbász, zsír, tepertő', img: 'hurka',
    intro: 'Hurka, sütőkolbász, mangalicazsír, tepertő, rakott fej és csülöksajt – a disznótoros ízek egész évben.' },
  { id: 'ajandek', name: 'Ajándékok', sub: 'Dobozok, kosarak, ajándékkártya', img: 'ajandekdoboz-10',
    intro: 'Összeállított ajándékdobozok és -kosarak, bőségtál és ajándékkártya – karácsonyra, céges ajándéknak.' }
];

BG.occasions = [
  { id: 'reggelire', name: 'Reggelire', img: 'parizsi', intro: 'Felvágottak, májasok és krémek a reggeli asztalra.' },
  { id: 'kerti-parti', name: 'Kerti parti', img: 'bbq-oldalas', intro: 'Grillkolbász, tomahawk, BBQ oldalas és hamburgerhús – minden, ami a rácsra kell, egy rendeléssel.' },
  { id: 'disznotoros', name: 'Disznótoros', img: 'hurka', intro: 'Hurka, sütőkolbász, kocsonyának és zsírnak való – a téli szezon klasszikusai.' },
  { id: 'uzsonnacsomag', name: 'Uzsonnacsomag', img: 'kalandor-csomag', intro: 'Összekészített uzsonnacsomagok iskolába, munkába, kirándulásra.' },
  { id: 'akciok', name: 'Akciók', img: 'zsirnakvalo', intro: 'Aktuális ajánlataink, amíg a készlet tart.' }
];

/* p(id, név, kategória, alkalmak, kiszerelés, ár, Ft/kg, extra) */
(function () {
  const L = [];
  const p = (id, name, cat, occ, pack, price, unit, x) =>
    L.push(Object.assign({ id, name, cat, occ: occ || [], pack, price, unit: unit || null, img: id }, x || {}));

  /* Tőkehúsok */
  p('szuz', 'Szűzpecsenye', 'tokehusok', [], '400 g', 3600, 9000, { ingr: 'Mangalica szűzpecsenye' });
  p('csontos-karaj', 'Csontos karaj', 'tokehusok', [], '500 g', 3550, 7100, { ingr: 'Mangalica karaj, csontos' });
  p('karaj-csont-nelkul', 'Karaj csont nélkül', 'tokehusok', [], '500 g', 4100, 8200, { ingr: 'Mangalica karaj' });
  p('csontos-tarja', 'Csontos tarja', 'tokehusok', ['kerti-parti'], '500 g', 3550, 7100, { ingr: 'Mangalica tarja, csontos', variant: 'tarja-csont-nelkul',
    desc: 'A tarja a nyakrész, ahol a hús és a zsír rétegesen váltakozik. Csontosan sütve még szaftosabb: szeletelve grillre, egyben sütőbe.' });
  p('tarja-csont-nelkul', 'Tarja csont nélkül', 'tokehusok', ['kerti-parti'], '500 g', 4100, 8200, { ingr: 'Mangalica tarja', variant: 'csontos-tarja',
    desc: 'A tarja a nyakrész, ahol a hús és a zsír rétegesen váltakozik. Csont nélkül szeletelhető rostélyosnak, pecsenyének, gyrosnak.' });
  p('comb', 'Comb', 'tokehusok', [], '500 g', 2750, 5500, { ingr: 'Mangalica comb' });
  p('lapocka', 'Lapocka', 'tokehusok', [], '500 g', 2750, 5500, { ingr: 'Mangalica lapocka' });
  p('oldalas', 'Oldalas', 'tokehusok', [], '500 g', 2050, 4100, { ingr: 'Mangalica oldalas' });
  p('bbq-oldalas', 'BBQ oldalas', 'tokehusok', ['kerti-parti'], '1 kg', 5500, 5500, { ingr: 'Mangalica oldalas', tag: 'Grillre' });
  p('dagado', 'Dagadó', 'tokehusok', ['disznotoros'], '1,3 kg', 5590, 4300, { ingr: 'Mangalica dagadó' });
  p('csulok', 'Csülök', 'tokehusok', ['disznotoros'], '1,5 kg', 5550, 3700, { ingr: 'Mangalica csülök, csontos' });
  p('csulok-csont-nelkul', 'Csülök csont nélkül', 'tokehusok', [], '500 g', 2400, 4800, { ingr: 'Mangalica csülök' });
  p('daralthus', 'Darálthús (70% hús)', 'tokehusok', [], '500 g', 2400, 4800, { ingr: 'Mangalica hús 70%, mangalica szalonna' });
  p('csaszarhus', 'Császárhús', 'tokehusok', [], '500 g', 2300, 4600, { ingr: 'Mangalica császárhús' });
  p('tomahawk', 'Tomahawk szelet', 'tokehusok', ['kerti-parti'], 'kb. 250 g', 2175, 8700, { ingr: 'Mangalica karaj, bordával', tag: 'Grillre' });
  p('t-bone', 'T-bone szelet', 'tokehusok', ['kerti-parti'], 'kb. 250 g', 2175, 8700, { ingr: 'Mangalica karaj és szűz, csonttal', tag: 'Grillre' });
  p('hamburgerhus', 'Hamburgerhús (4 × 15 dkg)', 'tokehusok', ['kerti-parti'], '600 g', 3480, 5800, { ingr: 'Mangalica hús 80%, mangalica füstölt szalonna 10%, mangalica szalonna 10%' });
  p('fredi-szelet', 'Frédi szelet', 'tokehusok', ['kerti-parti'], '1,6 kg', 11520, 7200, { ingr: 'Mangalica comb, étkezési só, sous-vide fűszerkeverék' });
  p('mangalica-maj', 'Mangalica máj', 'tokehusok', [], '500 g', 1450, 2900, { ingr: 'Mangalica máj' });
  p('husos-csont', 'Húsos csont', 'tokehusok', ['disznotoros'], '1 kg', 1650, 1650, { ingr: 'Mangalica húsos csont' });
  p('zsirnakvalo', 'Zsírnakvaló', 'tokehusok', ['disznotoros'], '1 kg', 2800, 2800, { ingr: 'Mangalica szalonna' });
  p('kocsonya-csomag', 'Kocsonya csomag', 'tokehusok', ['disznotoros'], '1 kg', 2650, 2650, { ingr: 'Mangalica kocsonyának való részek [pontos összetétel az ügyféltől]' });
  p('korom', 'Köröm', 'tokehusok', ['disznotoros'], '1 kg', 1600, 1600, { ingr: 'Mangalica köröm' });

  /* Akciók */
  p('zsirnakvalo-akcio', 'Zsírnakvaló AKCIÓ (5 kg)', 'tokehusok', ['akciok', 'disznotoros'], '5 kg', 9000, 1800, { img: 'zsirnakvalo', ingr: 'Bőr nélküli mangalica szalonna', tag: 'Akció' });
  p('korom-akcio', 'Köröm csomag AKCIÓ (5 kg)', 'tokehusok', ['akciok', 'disznotoros'], '5 kg', 3500, 700, { img: 'korom', old: 6500, ingr: 'Konyhakész mangalica köröm', tag: 'Akció' });

  /* Kolbászok, szalámik */
  p('fustolt-csemege-kolbasz', 'Füstölt csemege kolbász', 'kolbaszok', ['disznotoros'], '1 pár', 4410, null, { ingr: 'Mangalica hús, mangalica szalonna, fűszerpaprika, étkezési só, fokhagyma, őrölt bors, kömény; bükkfán füstölve' });
  p('fustolt-csipos-kolbasz', 'Füstölt csípős kolbász', 'kolbaszok', [], '1 pár', 2940, null, { ingr: 'Mangalica hús, mangalica szalonna, fűszerpaprika, étkezési só, fokhagyma, őrölt bors, chili; bükkfán füstölve', tag: 'Csípős' });
  p('miskolci-kolbasz', 'Füstölt miskolci kolbász (paprikamentes)', 'kolbaszok', [], '1 pár', 4410, null, { ingr: 'Mangalica hús, mangalica szalonna, étkezési só, fokhagyma, őrölt bors, őrölt kömény; bükkfán füstölve' });
  p('csipos-snack-kolbasz', 'Füstölt csípős snack kolbász', 'kolbaszok', [], '1 csomag', 1635, null, { ingr: 'Mangalica hús, mangalica szalonna, nitrites só, téliszalámi-fűszer, fűszerpaprika, chili', allergen: 'nitrit', tag: 'Csípős' });
  p('paprikas-szalami', 'Paprikás szalámi', 'kolbaszok', [], '200 g', 2380, 11900, { ingr: '80% darálthús, fűszerpaprika, nitrites só, fokhagyma, fűszerkömény, őrölt bors', allergen: 'nitrit' });
  p('csipos-paprikas-szalami', 'Csípős paprikás szalámi', 'kolbaszok', [], '200 g', 2380, 11900, { ingr: '80% darálthús, fűszerpaprika, nitrites só, fokhagyma, fűszerkömény, őrölt bors, chiliszósz', allergen: 'nitrit', tag: 'Csípős' });
  p('teliszalami', 'Téliszalámi', 'kolbaszok', [], '200 g', 2380, 11900, { ingr: '80% darálthús, nitrites só, szalámi-fűszerkeverék', allergen: 'nitrit' });
  p('mangalica-turista', 'Mangalica turista', 'kolbaszok', [], '100 g', 680, 6800, { ingr: '70% mangalica hús, mangalica szalonna, nitrites só, őrölt bors, fűszerpaprika', allergen: 'nitrit' });
  p('grillkolbasz-magyaros', 'Grillkolbász (magyaros)', 'kolbaszok', ['kerti-parti'], '500 g', 3050, 6100, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, bors, fűszerpaprika, fokhagyma', allergen: 'nitrit', tag: 'Grillre' });
  p('grillkolbasz-bajor', 'Grillkolbász (bajor jellegű)', 'kolbaszok', ['kerti-parti'], '500 g', 3050, 6100, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, fokhagyma, őrölt bors, őrölt kömény', allergen: 'nitrit', tag: 'Grillre' });
  p('debreceni-paros', 'Debreceni páros', 'kolbaszok', ['reggelire'], '500 g', 2900, 5800, { ingr: 'Mangalica hús, nitrites pácsó, debreceni fűszerkeverék', allergen: 'nitrit' });
  p('gesztelyi-paros', 'Gesztelyi páros', 'kolbaszok', ['reggelire'], '500 g', 2900, 5800, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, fokhagyma, őrölt bors, őrölt kömény', allergen: 'nitrit' });

  /* Szalonnák, füstöltek */
  p('kolozsvari-szalonna', 'Kolozsvári szalonna', 'szalonnak', [], '200 g', 1220, 6100, { ingr: 'Mangalica szalonna, étkezési só; bükkfán füstölve' });
  p('fustolt-csemege-szalonna', 'Füstölt csemege szalonna', 'szalonnak', [], '200 g', 1020, 5100, { ingr: 'Mangalica szalonna, étkezési só; bükkfán füstölve' });
  p('abalt-szalonna', 'Abált szalonna', 'szalonnak', ['disznotoros'], '300 g', 1700, 5667, { ingr: 'Mangalica császárszalonna, étkezési só, őrölt bors, fokhagyma, fűszerpaprika' });
  p('formazott-nyers-szalonna', 'Formázott nyers szalonna', 'szalonnak', [], '500 g', 1850, 3700, { ingr: 'Mangalica szalonna' });
  p('sos-szalonna', 'Sós szalonna', 'szalonnak', [], '500 g', 2300, 4600, { ingr: 'Mangalica szalonna, étkezési só' });
  p('fustolt-sonka', 'Füstölt sonka', 'szalonnak', ['reggelire'], '200 g', 1940, 9700, { ingr: 'Mangalica hús, étkezési só; bükkfán füstölve' });
  p('fustolt-oldalas', 'Füstölt oldalas / bordaporc', 'szalonnak', [], '500 g', 1900, 3800, { ingr: 'Mangalica oldalas, bordaporc, étkezési só; bükkfán füstölve' });
  p('angol-szalonna', 'Angol szalonna, szeletelve', 'szalonnak', ['reggelire'], '100 g', 780, 7800, { ingr: 'Mangalica hátszalonna, karaj, étkezési só' });

  /* Felvágottak, kenhetők */
  p('dobozos-kenomajas', 'Dobozos kenőmájas', 'felvagottak', ['reggelire'], '[kiszerelés]', 1600, null, { ingr: 'Mangalica máj, vegyes belsőség, mangalica szalonna, vöröshagyma, nitrites pácsó, őrölt bors', allergen: 'nitrit' });
  p('tomlos-kenomajas', 'Tömlős kenőmájas (kenhető)', 'felvagottak', ['reggelire'], '[kiszerelés]', 1340, null, { ingr: 'Mangalica máj, vegyes belsőség, mangalica szalonna, vöröshagyma, nitrites pácsó, őrölt bors', allergen: 'nitrit' });
  p('parasztmajas', 'Parasztmájas (darabos)', 'felvagottak', ['reggelire'], '100 g', 610, 6100, { ingr: 'Mangalica máj, vegyes belsőség, mangalica szalonna, vöröshagyma, étkezési só, őrölt bors' });
  p('parizsi', 'Párizsi', 'felvagottak', ['reggelire', 'uzsonnacsomag'], '100 g', 680, 6800, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, párizsi fűszerkeverék', allergen: 'nitrit' });
  p('chilis-parizsi', 'Chilis párizsi', 'felvagottak', ['reggelire'], '100 g', 680, 6800, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, chilipehely, párizsi fűszerkeverék', allergen: 'nitrit', tag: 'Csípős' });
  p('fustolt-parizsi', 'Füstölt párizsi', 'felvagottak', ['reggelire'], '100 g', 680, 6800, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, párizsi fűszerkeverék; bükkfán füstölve', allergen: 'nitrit' });
  p('nyelves-felvagott', 'Nyelves felvágott', 'felvagottak', ['reggelire'], '100 g', 680, 6800, { ingr: 'Mangalica hús, mangalica nyelv, párizsi fűszerkeverék, nitrites pácsó, őrölt fehérbors', allergen: 'nitrit' });
  p('uzsonnasonka', 'Uzsonnasonka', 'felvagottak', ['reggelire', 'uzsonnacsomag'], '100 g', 680, 6800, { ingr: 'Mangalica comb, nitrites pácsó, sonka-fűszerkeverék', allergen: 'nitrit' });
  p('virsli', 'Virsli, 5 pár (ehető kollagénben)', 'felvagottak', ['reggelire'], '500 g', 3400, 6800, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, virsli-fűszerkeverék', allergen: 'nitrit' });
  p('fustolt-virsli', 'Füstölt virsli, 5 pár', 'felvagottak', ['reggelire'], '500 g', 3400, 6800, { ingr: 'Mangalica hús, mangalica szalonna, nitrites pácsó, virsli-fűszerkeverék; bükkfán füstölve', allergen: 'nitrit' });
  p('tepertokrem', 'Tepertőkrém', 'felvagottak', ['reggelire'], '[kiszerelés]', 1650, null, { ingr: 'Mangalica tepertő, mustár, étkezési só, bors, vöröshagyma', allergen: 'mustár' });
  p('fokhagymas-rilette', 'Fokhagymás rilette', 'felvagottak', ['reggelire'], '500 g', 3350, 6700, { ingr: 'Mangalica zsír, mangalica lapocka, fokhagyma, étkezési só' });
  p('chilis-rilette', 'Chilis rilette', 'felvagottak', ['reggelire'], '[kiszerelés]', 1750, null, { ingr: 'Mangalica zsír, mangalica lapocka, füstölt szalonna bőr nélkül, akácméz, étkezési só, chili', tag: 'Csípős' });
  p('kalandor-csomag', 'Kalandor uzsonnacsomag', 'felvagottak', ['uzsonnacsomag'], '1 csomag', 4490, null, { ingr: '80 g téliszalámi, 80 g uzsonnasonka, 80 g natúr párizsi, 130 g csemege snack kolbász […]', allergen: 'nitrit' });
  p('csibesz-csomag', 'Csibész uzsonnacsomag', 'felvagottak', ['uzsonnacsomag'], '1 csomag', 4490, null, { ingr: '80 g téliszalámi, 80 g uzsonnasonka, 80 g chilis párizsi, 130 g csípős snack kolbász […]', allergen: 'nitrit', tag: 'Csípős' });

  /* Hagyományos */
  p('hurka', 'Hurka', 'hagyomanyos', ['disznotoros'], '1 kg', 3990, 3990, { ingr: 'Főtt máj, fej, belsőség, rizs, vöröshagyma, étkezési só, majoranna, őrölt bors, fűszerpaprika' });
  p('sutokolbasz', 'Sütőkolbász', 'hagyomanyos', ['disznotoros'], '1 kg', 4800, 4800, { ingr: 'Mangalica hús, mangalica szalonna, fűszerpaprika, nitrites só, fokhagyma, bors, kömény', allergen: 'nitrit' });
  p('teperto', 'Tepertő', 'hagyomanyos', ['disznotoros'], '100 g', 870, 8700, { ingr: 'Mangalica szalonna, étkezési só' });
  p('zsir-1kg', 'Mangalicazsír', 'hagyomanyos', ['disznotoros'], '1 kg', 2500, 2500, { ingr: 'Mangalica zsírszalonna, étkezési só' });
  p('zsir-5kg', 'Mangalicazsír, 5 kg', 'hagyomanyos', ['disznotoros'], '5 kg', 10000, 2000, { ingr: 'Mangalica zsírszalonna, étkezési só' });
  p('rakott-fej', 'Rakott fej', 'hagyomanyos', ['disznotoros'], '100 g', 520, 5200, { ingr: 'Mangalica fejhús, abált lé, fokhagyma, fűszerpaprika, étkezési só, őrölt bors, őrölt kömény' });
  p('csuloksajt', 'Csülöksajt', 'hagyomanyos', ['disznotoros'], '100 g', 520, 5200, { ingr: 'Mangalica főtt első csülök, abált lé, fokhagyma, étkezési só, őrölt bors, őrölt kömény' });
  p('tepett-hus', 'Tépett hús', 'hagyomanyos', ['kerti-parti'], '500 g', 3350, 6700, { ingr: 'Mangalica hús, mangalica zsír, étkezési só, worcestershire-szósz, szójaszósz, fűszerpaprika', allergen: 'szója' });

  /* Ajándékok */
  p('ajandekdoboz-10', 'Ajándékdoboz (10 féle termékkel)', 'ajandek', [], '1 doboz', 15000, null, { ingr: 'Csemege és csípős snack kolbász, zsír, májas, szalonna és további mangalicatermékek […]' });
  p('ajandekkosar-12', 'Ajándékkosár (12 féle termékkel)', 'ajandek', [], '1 kosár', 22000, null, { ingr: '0,5 kg zsír, tömlős májas, 20 dkg angol szalonna, csemege snack kolbász és további mangalicatermékek […]' });
  p('ajandekdoboz-kicsi', 'Ajándékdoboz KICSI', 'ajandek', [], '10 féle termék', 32000, null, { ingr: '10 féle vákuumcsomagolt mangalicatermék' });
  p('ajandekdoboz-nagy', 'Ajándékdoboz NAGY', 'ajandek', [], '14 féle termék', 49000, null, { ingr: '14 féle vákuumcsomagolt mangalicatermék' });
  p('bosegtal', 'Bőségtál', 'ajandek', [], '1 tál', 25990, null, { ingr: 'Kenőmájas, tepertőkrém, miskolci kolbász, füstölt csemege kolbász, tepertő, füstölt sonka' });
  p('ajandekkartya-5000', 'Ajándékkártya 5 000 Ft', 'ajandek', [], 'kártya', 5000, null, { ingr: '5 000 Ft értékben levásárolható', giftcard: true });
  p('ajandekkartya-10000', 'Ajándékkártya 10 000 Ft', 'ajandek', [], 'kártya', 10000, null, { ingr: '10 000 Ft értékben levásárolható', giftcard: true });
  p('ajandekkartya-20000', 'Ajándékkártya 20 000 Ft', 'ajandek', [], 'kártya', 20000, null, { ingr: '20 000 Ft értékben levásárolható', giftcard: true });

  BG.products = L;
})();

BG.featured = ['fustolt-csemege-kolbasz', 'kolozsvari-szalonna', 'csontos-tarja', 'grillkolbasz-magyaros', 'paprikas-szalami', 'tepertokrem', 'szuz', 'tomahawk'];

/* Szállítás. days: 0 = vasárnap … 6 = szombat. DEMO: a járatnapok mintaértékek. */
BG.zones = [
  { id: 'budapest', name: 'Budapest és agglomeráció', days: [4] },
  { id: 'borsod', name: 'Borsod és Heves', days: [2, 5] },
  { id: 'hajdu', name: 'Hajdú-Bihar és Szabolcs', days: [3] },
  { id: 'dunantul', name: 'Dunántúl', days: [5], periodic: true }
];

/* Települések: név, zóna, minimum (Ft), irányítószám(ok). A minimumok a OneMin-pénztárból valók. */
BG.towns = [
  ['Budapest', 'budapest', 10000, ['1']],
  ['Budaörs', 'budapest', 10000, ['2040']],
  ['Érd', 'budapest', 10000, ['2030', '2035', '2036']],
  ['Dunakeszi', 'budapest', 10000, ['2120']],
  ['Diósd', 'budapest', 15000, ['2049']],
  ['Solymár', 'budapest', 15000, ['2083']],
  ['Fót', 'budapest', 20000, ['2151']],
  ['Miskolc', 'borsod', 5000, ['35']],
  ['Gesztely', 'borsod', 5000, ['3715']],
  ['Nyékládháza', 'borsod', 5000, ['3433']],
  ['Tiszaújváros', 'borsod', 5000, ['3580']],
  ['Tokaj', 'borsod', 5000, ['3910']],
  ['Eger', 'borsod', 10000, ['3300', '3304']],
  ['Mezőkövesd', 'borsod', 15000, ['3400']],
  ['Ostoros', 'borsod', 15000, ['3326']],
  ['Sárospatak', 'borsod', 40000, ['3950']],
  ['Debrecen', 'hajdu', 5000, ['40']],
  ['Bocskaikert', 'hajdu', 5000, ['4241']],
  ['Nyíregyháza', 'hajdu', 5000, ['44']],
  ['Hajdúhadház', 'hajdu', 20000, ['4242']],
  ['Téglás', 'hajdu', 20000, ['4243']],
  ['Győr', 'dunantul', 10000, ['90']],
  ['Székesfehérvár', 'dunantul', 10000, ['80']],
  ['Veszprém', 'dunantul', 10000, ['82']],
  ['Balatonfüred', 'dunantul', 10000, ['8230']],
  ['Siófok', 'dunantul', 10000, ['86']]
].map(([name, zone, min, zips]) => ({ name, zone, min, zips }));

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

BG.reviews = [
  { name: 'Gáborné Á.', text: 'A legfinomabb grillkolbászt készítik, évek óta tőlük vásárolunk. Mindig friss és nagyon finom.' },
  { name: 'Szilvi R.', text: 'Minden csillagos 5: termékek, kommunikáció, szállítás! Örülök, hogy megtaláltam ezt az oldalt.' },
  { name: 'Róbert T.', text: 'Amióta rájuk találtam, csak tőlük fogyasztok sertésterméket. Minden terméken érződik, hogy küldetésként élik meg, amit csinálnak.' }
];
