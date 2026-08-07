<?php

namespace App\Support;

class BaliDistrictGuides
{
    public static function all(): array
    {
        return [
            self::guide('Чангу', 'Canggu', 'Canggu', 'bi-water', 'Сёрфинг, кофейни и закаты. Лучшие зоны — Batu Bolong и Berawa; передвигаться удобнее утром, вечером закладывайте время на пробки.', 'Surfing, cafés and sunsets. Focus on Batu Bolong and Berawa; travel in the morning and allow extra time for evening traffic.', 'Selancar, kafe, dan matahari terbenam. Jelajahi Batu Bolong dan Berawa; berangkat pagi dan siapkan waktu ekstra saat macet sore.'),
            self::guide('Семиньяк', 'Seminyak', 'Seminyak', 'bi-shop', 'Рестораны, бутики и пляжные клубы. Petitenget спокойнее центральной части; столик на ужин и лежаки лучше бронировать заранее.', 'Restaurants, boutiques and beach clubs. Petitenget is calmer than central Seminyak; reserve dinner and daybeds in advance.', 'Restoran, butik, dan beach club. Petitenget lebih tenang; reservasi makan malam dan daybed lebih awal.'),
            self::guide('Убуд', 'Ubud', 'Ubud', 'bi-tree', 'Культура, рисовые поля и wellness. Начните рано с храма или прогулки Campuhan, а центр и рынок оставьте на вторую половину дня.', 'Culture, rice fields and wellness. Start early with a temple or Campuhan walk, then visit the centre and market later.', 'Budaya, sawah, dan wellness. Mulai pagi di pura atau Campuhan, lalu kunjungi pusat kota dan pasar setelahnya.'),
            self::guide('Улувату', 'Uluwatu', 'Uluwatu', 'bi-sunset', 'Скалы, пляжи и сёрф. Посетите Padang Padang или Bingin, а к закату — храм Улувату; проверяйте приливы и берите наличные.', 'Cliffs, beaches and surf. Visit Padang Padang or Bingin, then Uluwatu Temple at sunset; check tides and carry cash.', 'Tebing, pantai, dan selancar. Kunjungi Padang Padang atau Bingin, lalu Pura Uluwatu saat senja; cek pasang dan bawa uang tunai.'),
            self::guide('Нуса-Дуа', 'Nusa Dua', 'Nusa Dua', 'bi-umbrella', 'Спокойные пляжи и семейный отдых. Для купания выбирайте утро, прогуляйтесь по Water Blow и набережной, вечером удобно ужинать у отеля.', 'Calm beaches and family time. Swim in the morning, walk to Water Blow and the promenade, then dine near the hotel.', 'Pantai tenang dan cocok untuk keluarga. Berenang pagi hari, jalan ke Water Blow dan promenade, lalu makan malam dekat hotel.'),
            self::guide('Санур', 'Sanur', 'Sanur', 'bi-sunrise', 'Рассветы, длинная набережная и спокойное море. Идеален для велосипеда и семей; отсюда удобно отправляться на Нуса-Пенида.', 'Sunrises, a long promenade and calm water. Great for cycling and families, with easy boat access to Nusa Penida.', 'Matahari terbit, promenade panjang, dan laut tenang. Cocok untuk bersepeda dan keluarga, serta akses kapal ke Nusa Penida.'),
            self::guide('Джимбаран', 'Jimbaran', 'Jimbaran', 'bi-fire', 'Рыбный рынок и ужины на песке. Приезжайте на пляж до заката, выбирайте морепродукты по весу и заранее уточняйте итоговую цену.', 'Fish market and seafood dinners on the sand. Arrive before sunset, choose seafood by weight and confirm the total price first.', 'Pasar ikan dan makan seafood di pantai. Datang sebelum senja, pilih berdasarkan berat, dan pastikan total harga lebih dulu.'),
            self::guide('Кута и Легиан', 'Kuta & Legian', 'Kuta & Legian', 'bi-bag', 'Шопинг, активный пляж и ночная жизнь. Подходит для первого урока сёрфинга; ценные вещи лучше оставлять в сейфе отеля.', 'Shopping, a lively beach and nightlife. Good for a first surf lesson; leave valuables in the hotel safe.', 'Belanja, pantai ramai, dan hiburan malam. Cocok untuk belajar selancar; simpan barang berharga di brankas hotel.'),
            self::guide('Сидемен', 'Sidemen', 'Sidemen', 'bi-mountains', 'Тихие деревни, рисовые террасы и вид на Агунг. Нужен автомобиль с водителем; выбирайте прогулку с местным гидом и не спешите.', 'Quiet villages, rice terraces and Mount Agung views. Hire a driver, walk with a local guide and keep the day unhurried.', 'Desa tenang, sawah terasering, dan pemandangan Gunung Agung. Gunakan sopir, berjalan dengan pemandu lokal, dan nikmati hari dengan santai.'),
            self::guide('Амед', 'Amed', 'Amed', 'bi-water', 'Снорклинг, дайвинг и вулканические пляжи. Лучшее море обычно утром; возьмите рифовые тапочки и планируйте длинный трансфер.', 'Snorkelling, diving and volcanic beaches. The sea is usually best in the morning; bring reef shoes and plan for a long transfer.', 'Snorkeling, diving, dan pantai vulkanik. Laut biasanya terbaik pagi hari; bawa sepatu karang dan siapkan perjalanan panjang.'),
            self::guide('Острова Гили', 'Gili Islands', 'Kepulauan Gili', 'bi-water', 'Три небольших острова у Ломбока для моря, снорклинга и отдыха без машин.', 'Three small islands off Lombok for beaches, snorkelling and car-free island life.', 'Tiga pulau kecil di dekat Lombok untuk pantai, snorkeling, dan suasana tanpa kendaraan bermotor.'),
            self::guide('Нуса-Пенида', 'Nusa Penida', 'Nusa Penida', 'bi-camera', 'Скалы, бухты и яркая природа к юго-востоку от Бали. Лучше планировать маршрут заранее.', 'Dramatic cliffs, coves and nature southeast of Bali. Plan the route before arriving.', 'Tebing dramatis, teluk, dan alam indah di tenggara Bali. Rencanakan rute sebelum tiba.'),
            self::guide('G-Land', 'G-Land', 'G-Land', 'bi-water', 'Удалённый серф-спот в национальном парке Alas Purwo в Восточной Яве для опытных сёрферов.', 'A remote advanced surf destination in Alas Purwo National Park, East Java.', 'Destinasi selancar terpencil untuk peselancar mahir di Taman Nasional Alas Purwo, Jawa Timur.'),
        ];
    }

    public static function mapsFor(string $name): array
    {
        $places = [
            'Чангу' => ['Batu Bolong Beach', 'Echo Beach Bali', 'Pererenan Beach', 'Seseh Beach', 'Tanah Lot Temple'],
            'Семиньяк' => ['Double Six Beach', 'Petitenget Beach', 'Pura Petitenget', 'Jalan Kayu Aya Seminyak'],
            'Убуд' => ['Campuhan Ridge Walk', 'Sacred Monkey Forest Ubud', 'Tegallalang Rice Terrace', 'Tirta Empul Temple', 'Gunung Kawi Temple', 'Tibumana Waterfall'],
            'Улувату' => ['Suluban Beach', 'Nyang Nyang Beach', 'Bingin Beach', 'Melasti Beach', 'Uluwatu Temple'],
            'Нуса-Дуа' => ['Geger Beach', 'Mengiat Beach', 'Water Blow Nusa Dua', 'Puja Mandala', 'Sawangan Beach Bali'],
            'Санур' => ['Karang Beach Sanur', 'Mertasari Beach', 'Sindhu Night Market', 'Le Mayeur Museum', 'Serangan Island'],
            'Джимбаран' => ['Kedonganan Fish Market', 'Jimbaran Beach', 'Tegal Wangi Beach', 'Balangan Beach'],
            'Кута и Легиан' => ['Kuta Beach', 'Legian Beach', 'Waterbom Bali', 'Beachwalk Shopping Center', 'Legian Art Market'],
            'Сидемен' => ['Gembleng Waterfall', 'Sidemen Rice Terrace', 'Yellow Bridge Sidemen', 'Besakih Great Temple'],
            'Амед' => ['Jemeluk Bay', 'Lipah Beach', 'Amed Pyramids', 'Japanese Shipwreck Amed', 'Bunutan Point Amed'],
            'Острова Гили' => ['Turtle Point Gili Trawangan', 'Gili Meno Underwater Statues', 'Gili Air', 'Gili Meno Salt Lake'],
            'Нуса-Пенида' => ['Kelingking Beach', 'Diamond Beach Nusa Penida', 'Tembeling Beach and Forest', 'Goa Giri Putri Temple', 'Crystal Bay Nusa Penida', 'Gamat Bay'],
            'G-Land' => ['G-Land Plengkung Beach', 'Alas Purwo National Park', 'Grajagan Beach', 'Red Island Beach Banyuwangi'],
        ][$name] ?? [];

        return collect($places)->map(fn (string $place) => [
            'label' => $place,
            'url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($place.', Indonesia'),
        ])->all();
    }

    private static function guide(string $ru, string $en, string $id, string $icon, string $descriptionRu, string $descriptionEn, string $descriptionId): array
    {
        $expanded = self::expandedDescriptions()[$ru] ?? [];
        $extras = self::extraHighlights()[$ru] ?? [];

        return [
            'name' => $ru,
            'description' => ($expanded['ru'] ?? $descriptionRu).($extras['ru'] ?? ''),
            'icon' => $icon,
            'translations' => [
                'en' => ['name' => $en, 'description' => ($expanded['en'] ?? $descriptionEn).($extras['en'] ?? '')],
                'id' => ['name' => $id, 'description' => ($expanded['id'] ?? $descriptionId).($extras['id'] ?? '')],
                ...(self::secondaryTranslations()[$ru] ?? []),
            ],
        ];
    }

    private static function expandedDescriptions(): array
    {
        return [
            'Чангу' => [
                'ru' => "Атмосфера: молодой и активный район с сёрфингом, кофейнями, коворкингами и красивыми закатами.\n\nЧто посмотреть: пляжи Batu Bolong и Echo Beach, рисовые поля Pererenan и храм Tanah Lot неподалёку.\n\nЕда: здесь особенно много specialty coffee, healthy-кафе и современных индонезийских ресторанов.\n\nЛучшее время: пляж утром или после 16:30. Вечером на дорогах часто бывают пробки.\n\nСовет консьержа: для спокойного отдыха выбирайте Pererenan, для ресторанов и вечерней жизни — Batu Bolong или Berawa.",
                'en' => "Atmosphere: a young, lively area known for surfing, cafés, coworking spaces and sunset views.\n\nSee: Batu Bolong and Echo Beach, the rice fields around Pererenan and nearby Tanah Lot Temple.\n\nFood: one of Bali’s best areas for specialty coffee, healthy cafés and modern Indonesian dining.\n\nBest time: visit the beach in the morning or after 4:30 pm. Evening traffic can be heavy.\n\nConcierge tip: choose Pererenan for quiet stays, or Batu Bolong and Berawa for dining and nightlife.",
                'id' => "Suasana: kawasan muda dan ramai dengan selancar, kafe, coworking, dan pemandangan matahari terbenam.\n\nLihat: Pantai Batu Bolong dan Echo Beach, sawah Pererenan, serta Pura Tanah Lot di dekatnya.\n\nKuliner: pilihan terbaik untuk specialty coffee, healthy café, dan masakan Indonesia modern.\n\nWaktu terbaik: pagi hari atau setelah pukul 16.30. Lalu lintas sore sering padat.\n\nTips concierge: pilih Pererenan untuk suasana tenang, atau Batu Bolong dan Berawa untuk restoran dan hiburan malam.",
            ],
            'Семиньяк' => [
                'ru' => "Атмосфера: стильный курортный район с бутиками, ресторанами, spa и пляжными клубами.\n\nЧто посмотреть: пляж Petitenget, храм Pura Petitenget и дизайнерские магазины вдоль Jalan Kayu Aya.\n\nЕда: от авторской кухни до известных beach club — столик на ужин лучше бронировать заранее.\n\nЛучшее время: шопинг днём, пляж после 16:00, ужин после заката.\n\nСовет консьержа: Petitenget спокойнее центра, а район Oberoi удобнее для прогулок между ресторанами.",
                'en' => "Atmosphere: a polished resort area with boutiques, restaurants, spas and beach clubs.\n\nSee: Petitenget Beach, Pura Petitenget and designer shops along Jalan Kayu Aya.\n\nFood: everything from chef-led dining to famous beach clubs; reserve dinner in advance.\n\nBest time: shop during the day, reach the beach after 4 pm and dine after sunset.\n\nConcierge tip: Petitenget is quieter, while Oberoi is easier for walking between restaurants.",
                'id' => "Suasana: kawasan resor bergaya dengan butik, restoran, spa, dan beach club.\n\nLihat: Pantai Petitenget, Pura Petitenget, dan toko desainer di Jalan Kayu Aya.\n\nKuliner: dari restoran chef hingga beach club terkenal; reservasi makan malam lebih awal.\n\nWaktu terbaik: belanja siang hari, ke pantai setelah pukul 16.00, lalu makan malam setelah senja.\n\nTips concierge: Petitenget lebih tenang, sedangkan Oberoi nyaman untuk berjalan antarestoran.",
            ],
            'Убуд' => [
                'ru' => "Атмосфера: культурный центр Бали среди джунглей, рисовых полей, мастерских и wellness-пространств.\n\nЧто посмотреть: дворец Убуда, Monkey Forest, тропа Campuhan и рисовые террасы Tegallalang.\n\nЕда: местные warung, вегетарианские кафе и современная балийская кухня.\n\nЛучшее время: начинайте экскурсии до 9:00, пока прохладно и меньше посетителей.\n\nСовет консьержа: выделите минимум полный день и объединяйте места по одной стороне города — движение в центре медленное.",
                'en' => "Atmosphere: Bali’s cultural heart, surrounded by jungle, rice fields, workshops and wellness retreats.\n\nSee: Ubud Palace, Monkey Forest, Campuhan Ridge Walk and the Tegallalang rice terraces.\n\nFood: local warungs, vegetarian cafés and contemporary Balinese restaurants.\n\nBest time: begin before 9 am while it is cooler and quieter.\n\nConcierge tip: allow a full day and group stops on the same side of town, as central traffic moves slowly.",
                'id' => "Suasana: pusat budaya Bali yang dikelilingi hutan, sawah, studio seni, dan tempat wellness.\n\nLihat: Puri Ubud, Monkey Forest, Campuhan Ridge Walk, dan sawah terasering Tegallalang.\n\nKuliner: warung lokal, kafe vegetarian, dan restoran Bali modern.\n\nWaktu terbaik: mulai sebelum pukul 09.00 saat udara lebih sejuk dan belum ramai.\n\nTips concierge: siapkan satu hari penuh dan kelompokkan tujuan di sisi kota yang sama karena lalu lintas pusat cukup lambat.",
            ],
            'Улувату' => [
                'ru' => "Атмосфера: известняковые скалы, небольшие бухты, сильный сёрф и лучшие панорамные закаты юга Бали.\n\nЧто посмотреть: пляжи Padang Padang, Bingin и Melasti, храм Улувату и танец Kecak на закате.\n\nЕда: рестораны на скалах и рыбные кафе; популярные места требуют бронирования.\n\nЛучшее время: пляжи утром, храм после 16:00. Всегда проверяйте расписание приливов.\n\nСовет консьержа: расстояния между бухтами значительные — удобнее заранее заказать водителя на полдня.",
                'en' => "Atmosphere: limestone cliffs, small coves, powerful surf and some of South Bali’s best sunset views.\n\nSee: Padang Padang, Bingin and Melasti beaches, Uluwatu Temple and the sunset Kecak dance.\n\nFood: cliffside restaurants and seafood cafés; popular venues need reservations.\n\nBest time: beaches in the morning and the temple after 4 pm. Always check the tides.\n\nConcierge tip: the coves are spread out, so booking a driver for half a day is the easiest option.",
                'id' => "Suasana: tebing kapur, teluk kecil, ombak kuat, dan panorama senja terbaik di Bali selatan.\n\nLihat: Pantai Padang Padang, Bingin, dan Melasti, Pura Uluwatu, serta tari Kecak saat senja.\n\nKuliner: restoran di tebing dan kafe seafood; tempat populer perlu reservasi.\n\nWaktu terbaik: pantai pagi hari dan pura setelah pukul 16.00. Selalu cek pasang surut.\n\nTips concierge: jarak antarpantai cukup jauh, jadi sebaiknya pesan mobil dengan sopir untuk setengah hari.",
            ],
            'Нуса-Дуа' => [
                'ru' => "Атмосфера: ухоженный и спокойный курортный район с широкими пляжами и удобной инфраструктурой для семей.\n\nЧто посмотреть: Water Blow, набережную, пляжи Geger и Mengiat, музей Pasifika.\n\nЕда: рестораны при отелях, спокойные пляжные кафе и балийские блюда в районе Benoa.\n\nЛучшее время: купание утром, когда море спокойнее; прилив заранее проверяйте.\n\nСовет консьержа: хороший выбор для дня без дальних поездок — пляж, прогулка и spa легко объединяются в одном маршруте.",
                'en' => "Atmosphere: a polished, peaceful resort area with wide beaches and family-friendly facilities.\n\nSee: Water Blow, the promenade, Geger and Mengiat beaches, and Museum Pasifika.\n\nFood: hotel restaurants, relaxed beach cafés and Balinese dishes around Benoa.\n\nBest time: swim in the morning when the sea is calmer, and check the tide first.\n\nConcierge tip: ideal for an easy day—beach time, a promenade walk and spa can fit into one simple route.",
                'id' => "Suasana: kawasan resor yang rapi dan tenang dengan pantai luas serta fasilitas ramah keluarga.\n\nLihat: Water Blow, promenade, Pantai Geger dan Mengiat, serta Museum Pasifika.\n\nKuliner: restoran hotel, kafe pantai santai, dan masakan Bali di sekitar Benoa.\n\nWaktu terbaik: berenang pagi hari saat laut lebih tenang dan periksa pasang terlebih dahulu.\n\nTips concierge: cocok untuk hari santai—pantai, jalan kaki, dan spa dapat dinikmati dalam satu rute.",
            ],
            'Санур' => [
                'ru' => "Атмосфера: спокойный прибрежный район с рассветами, длинной набережной и неглубоким морем.\n\nЧто посмотреть: пляжную дорожку Sanur, музей Le Mayeur и утренний рынок Sindhu.\n\nЕда: семейные кафе, местные warung и рестораны с видом на море.\n\nЛучшее время: встречайте рассвет, затем катайтесь на велосипеде до наступления жары.\n\nСовет консьержа: отсюда отправляются лодки на Нуса-Пенида и Лембонган — приезжайте в порт с запасом времени.",
                'en' => "Atmosphere: a relaxed coastal area with sunrise views, a long promenade and shallow water.\n\nSee: the Sanur beachfront path, Le Mayeur Museum and Sindhu morning market.\n\nFood: family cafés, local warungs and easy-going seaside restaurants.\n\nBest time: arrive for sunrise, then cycle before the day gets hot.\n\nConcierge tip: boats leave from here for Nusa Penida and Lembongan, so arrive at the harbour early.",
                'id' => "Suasana: kawasan pantai yang santai dengan pemandangan matahari terbit, promenade panjang, dan laut dangkal.\n\nLihat: jalur tepi Pantai Sanur, Museum Le Mayeur, dan Pasar Sindhu pagi hari.\n\nKuliner: kafe keluarga, warung lokal, dan restoran santai di tepi laut.\n\nWaktu terbaik: datang saat matahari terbit lalu bersepeda sebelum cuaca panas.\n\nTips concierge: kapal ke Nusa Penida dan Lembongan berangkat dari sini, jadi tiba di pelabuhan lebih awal.",
            ],
            'Джимбаран' => [
                'ru' => "Атмосфера: спокойная бухта, рыбацкие лодки и знаменитые ужины с морепродуктами прямо на песке.\n\nЧто посмотреть: рыбный рынок Kedonganan, пляж Jimbaran и закат у южной части бухты.\n\nЕда: свежую рыбу выбирают по весу, затем готовят на гриле с рисом и sambal.\n\nЛучшее время: рынок утром, пляж и ужин — за час до заката.\n\nСовет консьержа: заранее уточняйте цену за килограмм, приготовление и налоги, чтобы итоговый счёт был понятен.",
                'en' => "Atmosphere: a calm bay with fishing boats and famous seafood dinners served directly on the sand.\n\nSee: Kedonganan fish market, Jimbaran Beach and sunset from the southern side of the bay.\n\nFood: choose fresh fish by weight, then have it grilled with rice and sambal.\n\nBest time: visit the market in the morning, or arrive for dinner one hour before sunset.\n\nConcierge tip: confirm the price per kilo, cooking fee and taxes before ordering.",
                'id' => "Suasana: teluk yang tenang dengan perahu nelayan dan makan malam seafood terkenal langsung di atas pasir.\n\nLihat: Pasar Ikan Kedonganan, Pantai Jimbaran, dan senja dari sisi selatan teluk.\n\nKuliner: pilih ikan segar berdasarkan berat lalu bakar dengan nasi dan sambal.\n\nWaktu terbaik: pasar pada pagi hari, atau datang makan malam satu jam sebelum senja.\n\nTips concierge: pastikan harga per kilogram, biaya memasak, dan pajak sebelum memesan.",
            ],
            'Кута и Легиан' => [
                'ru' => "Атмосфера: самый оживлённый туристический район с шопингом, широким пляжем и активной ночной жизнью.\n\nЧто посмотреть: пляж Куты, торговые центры Beachwalk и Discovery, улицы Легиана.\n\nЕда: недорогие кафе, международная кухня и множество мест для быстрого перекуса.\n\nЛучшее время: первый урок сёрфинга утром, покупки днём, закат на пляже.\n\nСовет консьержа: следите за личными вещами в людных местах и пользуйтесь официальным такси или приложением.",
                'en' => "Atmosphere: Bali’s busiest visitor area, with shopping, a broad beach and energetic nightlife.\n\nSee: Kuta Beach, Beachwalk and Discovery malls, and the streets of Legian.\n\nFood: affordable cafés, international restaurants and plenty of quick bites.\n\nBest time: take a first surf lesson in the morning, shop by day and watch sunset on the beach.\n\nConcierge tip: watch personal belongings in crowded places and use an official taxi or trusted ride app.",
                'id' => "Suasana: kawasan wisata paling ramai dengan pusat belanja, pantai luas, dan hiburan malam.\n\nLihat: Pantai Kuta, Beachwalk dan Discovery Mall, serta jalan-jalan di Legian.\n\nKuliner: kafe terjangkau, restoran internasional, dan banyak pilihan makanan cepat.\n\nWaktu terbaik: belajar selancar pagi hari, belanja siang, lalu menikmati senja di pantai.\n\nTips concierge: jaga barang pribadi di tempat ramai dan gunakan taksi resmi atau aplikasi transportasi tepercaya.",
            ],
            'Сидемен' => [
                'ru' => "Атмосфера: тихий сельский Бали с рисовыми террасами, долинами и видом на вулкан Агунг.\n\nЧто посмотреть: деревенские тропы, ткацкие мастерские, водопады и храм Besakih по пути.\n\nЕда: небольшие семейные warung и рестораны при эко-отелях с местными продуктами.\n\nЛучшее время: прогулка рано утром; после обеда возможен дождь и облака над вулканом.\n\nСовет консьержа: закажите автомобиль с водителем и местного проводника — достопримечательности разбросаны, а навигация непростая.",
                'en' => "Atmosphere: quiet rural Bali with rice terraces, green valleys and views of Mount Agung.\n\nSee: village trails, weaving workshops, waterfalls and Besakih Temple along the route.\n\nFood: small family warungs and eco-hotel restaurants using local produce.\n\nBest time: walk early in the morning; afternoon rain and clouds around the volcano are common.\n\nConcierge tip: arrange a driver and local guide, as sights are spread out and navigation can be difficult.",
                'id' => "Suasana: Bali pedesaan yang tenang dengan sawah terasering, lembah hijau, dan pemandangan Gunung Agung.\n\nLihat: jalur desa, tempat tenun, air terjun, dan Pura Besakih di sepanjang rute.\n\nKuliner: warung keluarga kecil dan restoran eco-hotel dengan bahan lokal.\n\nWaktu terbaik: berjalan pagi hari; hujan sore dan awan di sekitar gunung cukup umum.\n\nTips concierge: pesan mobil dengan sopir dan pemandu lokal karena lokasi tersebar dan navigasi tidak selalu mudah.",
            ],
            'Амед' => [
                'ru' => "Атмосфера: цепочка тихих рыбацких деревень с вулканическими пляжами, коралловыми садами и видом на Агунг.\n\nЧто посмотреть: Japanese Shipwreck, Jemeluk Bay, храм Lempuyang и соляные фермы.\n\nЕда: простые рыбные warung и рестораны с видом на залив; вечером выбор ограничен.\n\nЛучшее время: снорклинг и дайвинг утром, когда вода прозрачнее и море спокойнее.\n\nСовет консьержа: возьмите рифовые тапочки, наличные и планируйте поездку с ночёвкой — трансфер с юга острова занимает несколько часов.",
                'en' => "Atmosphere: a chain of quiet fishing villages with volcanic beaches, coral gardens and Mount Agung views.\n\nSee: the Japanese Shipwreck, Jemeluk Bay, Lempuyang Temple and traditional salt farms.\n\nFood: simple seafood warungs and bay-view restaurants; evening options are limited.\n\nBest time: snorkel or dive in the morning when visibility is clearer and the sea is calmer.\n\nConcierge tip: bring reef shoes and cash, and consider staying overnight—the transfer from South Bali takes several hours.",
                'id' => "Suasana: rangkaian desa nelayan yang tenang dengan pantai vulkanik, taman karang, dan pemandangan Gunung Agung.\n\nLihat: Japanese Shipwreck, Teluk Jemeluk, Pura Lempuyang, dan tambak garam tradisional.\n\nKuliner: warung seafood sederhana dan restoran menghadap teluk; pilihan malam hari terbatas.\n\nWaktu terbaik: snorkeling atau diving pagi hari saat air lebih jernih dan laut tenang.\n\nTips concierge: bawa sepatu karang dan uang tunai, serta pertimbangkan menginap karena perjalanan dari Bali selatan memakan beberapa jam.",
            ],
            'Острова Гили' => [
                'ru' => "Атмосфера: три небольших острова у северо-западного побережья Ломбока — без автомобилей и мотобайков, с белым песком и тёплым морем.\n\nКак выбрать: Gili Trawangan — самый оживлённый и с ночной жизнью; Gili Air — баланс кафе и спокойствия; Gili Meno — самый тихий и романтичный.\n\nЧто делать: снорклинг с черепахами, дайвинг, прогулка вокруг острова пешком или на велосипеде и закат на западном берегу.\n\nЛучшее время: закладывайте минимум одну ночь. Расписание скоростных лодок зависит от моря и погоды.\n\nСовет консьержа: берите минимум багажа, рифовые тапочки и наличные; заранее уточните, на какой именно Гили прибывает лодка.",
                'en' => "Atmosphere: three small islands off northwest Lombok, with no cars or motorbikes, white sand and warm tropical water.\n\nChoose your island: Gili Trawangan is the liveliest; Gili Air balances cafés and quiet; Gili Meno is the most secluded and romantic.\n\nDo: snorkel with turtles, dive, circle the island by bicycle or on foot, and watch sunset from the west coast.\n\nBest time: stay at least one night. Fast-boat schedules depend on sea and weather conditions.\n\nConcierge tip: travel light, bring reef shoes and cash, and confirm which Gili your boat stops at.",
                'id' => "Suasana: tiga pulau kecil di barat laut Lombok tanpa mobil atau motor, dengan pasir putih dan laut tropis yang hangat.\n\nPilih pulau: Gili Trawangan paling ramai; Gili Air seimbang antara kafe dan ketenangan; Gili Meno paling sepi dan romantis.\n\nAktivitas: snorkeling bersama penyu, diving, berkeliling dengan sepeda atau berjalan kaki, dan menikmati senja di pantai barat.\n\nWaktu terbaik: menginap setidaknya satu malam. Jadwal fast boat bergantung pada cuaca dan kondisi laut.\n\nTips concierge: bawa barang ringan, sepatu karang, dan uang tunai, serta pastikan kapal berhenti di Gili pilihan Anda.",
            ],
            'Нуса-Пенида' => [
                'ru' => "Атмосфера: большой и суровый остров к юго-востоку от Бали с высокими скалами, прозрачной водой и важными храмами.\n\nЧто посмотреть: западный маршрут включает Kelingking, Broken Beach и Angel’s Billabong; восточный — Diamond Beach, Atuh и Thousand Islands Viewpoint.\n\nМоре: для снорклинга популярны Crystal Bay и Manta Point, но течения бывают сильными — выбирайте лицензированного оператора.\n\nЛучшее время: отправляйтесь первой лодкой и не пытайтесь объединить восток и запад за один короткий день.\n\nСовет консьержа: дороги извилистые, спуски к пляжам крутые; удобная обувь, вода и заранее заказанный водитель обязательны.",
                'en' => "Atmosphere: a large, rugged island southeast of Bali, known for dramatic cliffs, clear water and important temples.\n\nSee: the west route covers Kelingking, Broken Beach and Angel’s Billabong; the east route includes Diamond Beach, Atuh and Thousand Islands Viewpoint.\n\nSea: Crystal Bay and Manta Point are popular, but currents can be strong—use a licensed operator.\n\nBest time: take the first boat and do not squeeze both east and west into one short day.\n\nConcierge tip: roads are winding and beach paths are steep; bring proper shoes and water, and book a driver in advance.",
                'id' => "Suasana: pulau besar dan berbukit di tenggara Bali dengan tebing dramatis, air jernih, dan pura penting.\n\nLihat: rute barat mencakup Kelingking, Broken Beach, dan Angel’s Billabong; rute timur mencakup Diamond Beach, Atuh, dan Thousand Islands Viewpoint.\n\nLaut: Crystal Bay dan Manta Point populer, tetapi arus bisa kuat—gunakan operator berizin.\n\nWaktu terbaik: naik kapal pertama dan jangan memaksakan rute timur serta barat dalam satu hari singkat.\n\nTips concierge: jalan berkelok dan akses pantai curam; gunakan sepatu yang baik, bawa air, dan pesan sopir lebih dulu.",
            ],
            'G-Land' => [
                'ru' => "Атмосфера: удалённый лагерьный серф-спот у пляжа Plengkung в национальном парке Alas Purwo, Восточная Ява. Это поездка ради волн и дикой природы, а не обычный пляжный день.\n\nДля кого: мощная длинная левая волна, риф и сильные течения подходят прежде всего уверенным и опытным сёрферам.\n\nСезон: лучшие условия обычно приходятся на сухой сезон с апреля по октябрь. Даже опытным гостям важно слушать местных гидов.\n\nКак ехать: дорога с Бали долгая и включает переезд на Яву; практичнее бронировать серф-лагерь и оставаться на несколько ночей.\n\nСовет консьержа: новичкам лучше выбрать урок на пляжах Бали. Для G-Land заранее организуйте трансфер, доску, страховку и проверку прогноза волн.",
                'en' => "Atmosphere: a remote surf-camp destination at Plengkung Beach in Alas Purwo National Park, East Java. This is a journey for waves and wilderness, not a casual beach day.\n\nWho it suits: the powerful, long left-hand wave, reef and strong currents are mainly for confident advanced surfers.\n\nSeason: the strongest conditions are generally during the dry season from April to October. Follow local guides even if experienced.\n\nGetting there: travel from Bali is long and includes crossing to Java; booking a surf camp for several nights is the practical choice.\n\nConcierge tip: beginners should surf in Bali instead. For G-Land, arrange transfers, board, insurance and a forecast check in advance.",
                'id' => "Suasana: destinasi surf camp terpencil di Pantai Plengkung, Taman Nasional Alas Purwo, Jawa Timur. Ini perjalanan untuk ombak dan alam liar, bukan wisata pantai biasa.\n\nUntuk siapa: ombak kiri yang panjang dan kuat, karang, serta arus deras terutama cocok untuk peselancar mahir.\n\nMusim: kondisi terbaik umumnya pada musim kering dari April hingga Oktober. Tetap ikuti arahan pemandu lokal.\n\nAkses: perjalanan dari Bali cukup panjang dan menyeberang ke Jawa; paling praktis memesan surf camp untuk beberapa malam.\n\nTips concierge: pemula sebaiknya berselancar di Bali. Untuk G-Land, atur transfer, papan, asuransi, dan cek prakiraan ombak lebih awal.",
            ],
        ];
    }

    private static function extraHighlights(): array
    {
        return [
            'Чангу' => self::extras(
                'Кайфовые места: тихий пляж Seseh, дорожки среди рисовых полей Pererenan, закат на Echo Beach и поездка к Tanah Lot. Для красивой прогулки без толпы сверните с главных улиц в сторону Cemagi.',
                'Идеальный день: ранний сёрф или прогулка, поздний завтрак, массаж после обеда, рисовые поля перед закатом и ужин в Berawa.',
                'More places: quiet Seseh Beach, Pererenan rice-field lanes, Echo Beach at sunset and nearby Tanah Lot.',
                'Perfect day: early surf, late breakfast, an afternoon massage, rice fields at golden hour and dinner in Berawa.',
                'Tempat menarik lain: Pantai Seseh, jalan sawah Pererenan, senja di Echo Beach, dan Tanah Lot.',
                'Hari ideal: selancar pagi, brunch, pijat sore, sawah saat golden hour, lalu makan malam di Berawa.'
            ),
            'Семиньяк' => self::extras(
                'Кайфовые места: прогулка от Double Six до Petitenget, небольшие галереи вокруг Kerobokan, тихие дворики со spa и закат на северной части пляжа.',
                'Идеальный день: неспешный завтрак, бутики и галереи, spa во второй половине дня, закат у океана и красивый ужин.',
                'More places: Double Six Beach, Petitenget, small Kerobokan galleries and the quieter northern beachfront.',
                'Perfect day: breakfast, boutiques, an afternoon spa, ocean sunset and a special dinner.',
                'Tempat menarik lain: Double Six, Petitenget, galeri kecil Kerobokan, dan pantai utara yang lebih tenang.',
                'Hari ideal: sarapan, butik, spa sore, senja di pantai, lalu makan malam istimewa.'
            ),
            'Убуд' => self::extras(
                'Кайфовые места: Gunung Kawi, священные источники Tirta Empul, водопады Tibumana и Kanto Lampo, деревня Mas с резчиками по дереву и спокойные поля Sari Organic.',
                'Идеальный день: храм или водопад к открытию, рисовые террасы, обед с видом на джунгли, галерея или мастерская и вечерний массаж.',
                'More places: Gunung Kawi, Tirta Empul, Tibumana and Kanto Lampo waterfalls, Mas village and quiet rice-field walks.',
                'Perfect day: an early temple or waterfall, rice terraces, jungle-view lunch, an art workshop and evening massage.',
                'Tempat menarik lain: Gunung Kawi, Tirta Empul, Air Terjun Tibumana dan Kanto Lampo, Desa Mas, serta jalur sawah.',
                'Hari ideal: pura atau air terjun pagi, sawah, makan siang menghadap hutan, workshop seni, lalu pijat.'
            ),
            'Улувату' => self::extras(
                'Кайфовые места: пещерный выход к Suluban, лестницы Nyang Nyang, спокойный Balangan, белый песок Melasti и смотровые площадки над Bingin.',
                'Идеальный день: пляж до жары, обед на скале, отдых у бассейна, храм Улувату и Kecak, затем ужин в Jimbaran.',
                'More places: Suluban cave, Nyang Nyang, Balangan, Melasti and the cliff viewpoints above Bingin.',
                'Perfect day: morning beach, cliffside lunch, a swim, Uluwatu Temple and Kecak, then dinner in Jimbaran.',
                'Tempat menarik lain: gua Suluban, Nyang Nyang, Balangan, Melasti, dan viewpoint tebing Bingin.',
                'Hari ideal: pantai pagi, makan siang di tebing, berenang, Pura Uluwatu dan Kecak, lalu makan malam di Jimbaran.'
            ),
            'Нуса-Дуа' => self::extras(
                'Кайфовые места: спокойный Geger, длинный Mengiat, менее туристический Sawangan, рассвет у Water Blow и комплекс храмов разных религий Puja Mandala.',
                'Идеальный день: утреннее купание, прогулка по набережной, неспешный обед, spa и коктейль у моря перед ужином.',
                'More places: Geger, Mengiat, quieter Sawangan, Water Blow at sunrise and the Puja Mandala worship complex.',
                'Perfect day: morning swim, promenade walk, relaxed lunch, spa and a drink by the sea.',
                'Tempat menarik lain: Geger, Mengiat, Sawangan yang lebih sepi, Water Blow saat pagi, dan Puja Mandala.',
                'Hari ideal: berenang pagi, jalan di promenade, makan siang santai, spa, lalu minuman di tepi laut.'
            ),
            'Санур' => self::extras(
                'Кайфовые места: утренний Sindhu Market, пляжи Karang и Mertasari, мангровая зона на юге, остров Serangan и вечерний Sindhu Night Market.',
                'Идеальный день: рассвет, велосипед вдоль моря, завтрак, лодка или spa после обеда и локальная еда на ночном рынке.',
                'More places: Sindhu Market, Karang and Mertasari beaches, the southern mangroves, Serangan and Sindhu Night Market.',
                'Perfect day: sunrise, a beachfront bicycle ride, breakfast, an afternoon boat or spa, then local night-market food.',
                'Tempat menarik lain: Pasar Sindhu, Pantai Karang dan Mertasari, mangrove selatan, Serangan, serta Sindhu Night Market.',
                'Hari ideal: matahari terbit, bersepeda, sarapan, kapal atau spa sore, lalu kuliner pasar malam.'
            ),
            'Джимбаран' => self::extras(
                'Кайфовые места: рыбный рынок Kedonganan, спокойный север бухты, видовая точка Tegal Wangi и пляж Balangan неподалёку.',
                'Идеальный день: рынок и рыбацкие лодки утром, пляж после обеда, закат и ужин со свежей рыбой прямо на песке.',
                'More places: Kedonganan fish market, the quiet north bay, Tegal Wangi viewpoint and nearby Balangan Beach.',
                'Perfect day: morning market, an afternoon on the beach, sunset and freshly grilled fish on the sand.',
                'Tempat menarik lain: Pasar Ikan Kedonganan, sisi utara teluk, viewpoint Tegal Wangi, dan Pantai Balangan.',
                'Hari ideal: pasar pagi, pantai sore, senja, lalu ikan bakar segar di atas pasir.'
            ),
            'Кута и Легиан' => self::extras(
                'Кайфовые места: утренний пляж Легиана, Waterbom, рынок сувениров, Beachwalk, длинная прогулка по песку до Семиньяка и закат с молодым кокосом.',
                'Идеальный день: урок сёрфинга, завтрак, водные горки или шопинг, массаж ног и закат на менее людной части Legian Beach.',
                'More places: Legian Beach, Waterbom, the art market, Beachwalk and the long beachfront walk toward Seminyak.',
                'Perfect day: surf lesson, breakfast, water park or shopping, foot massage and sunset on quieter Legian Beach.',
                'Tempat menarik lain: Pantai Legian, Waterbom, pasar seni, Beachwalk, dan jalan pantai menuju Seminyak.',
                'Hari ideal: belajar selancar, sarapan, water park atau belanja, pijat kaki, lalu senja di Legian.'
            ),
            'Сидемен' => self::extras(
                'Кайфовые места: водопад Gembleng, прогулки вдоль ирригационных каналов, ткацкие дома, мост Yellow Bridge и панорамы Агунга на рассвете.',
                'Идеальный день: прогулка с местным гидом, купание у водопада, обед над долиной, мастерская ткачества и тихий вечер в отеле.',
                'More places: Gembleng Waterfall, irrigation paths, weaving homes, Yellow Bridge and sunrise views of Mount Agung.',
                'Perfect day: guided village walk, waterfall swim, valley lunch, weaving workshop and a quiet hotel evening.',
                'Tempat menarik lain: Air Terjun Gembleng, jalur irigasi, rumah tenun, Yellow Bridge, dan panorama Gunung Agung.',
                'Hari ideal: jalan bersama pemandu, berenang di air terjun, makan siang di lembah, belajar menenun, lalu bersantai.'
            ),
            'Амед' => self::extras(
                'Кайфовые места: Jemeluk Bay, Lipah Beach, Amed Pyramids, Japanese Shipwreck, Bunutan Point и рассвет с традиционной лодки jukung.',
                'Идеальный день: лодка на рассвете, снорклинг до жары, рыбный обед, отдых или yoga, а вечером смотровая площадка с видом на Агунг.',
                'More places: Jemeluk Bay, Lipah Beach, Amed Pyramids, Japanese Shipwreck, Bunutan Point and a sunrise jukung ride.',
                'Perfect day: sunrise boat, morning snorkelling, seafood lunch, yoga or rest, then a Mount Agung viewpoint.',
                'Tempat menarik lain: Teluk Jemeluk, Pantai Lipah, Amed Pyramids, Japanese Shipwreck, Bunutan Point, dan jukung saat fajar.',
                'Hari ideal: naik jukung, snorkeling pagi, makan ikan, yoga atau istirahat, lalu viewpoint Gunung Agung.'
            ),
            'Острова Гили' => self::extras(
                'Кайфовые места: Turtle Point, подводные статуи у Gili Meno, коралловые сады Gili Air, западный берег Trawangan и крошечное солёное озеро Meno.',
                'Идеальный день: ранний снорклинг, завтрак босиком у воды, круг острова на велосипеде, ленивый пляж и закат на западном берегу.',
                'More places: Turtle Point, the underwater statues off Gili Meno, Gili Air coral gardens and west-coast sunsets.',
                'Perfect day: early snorkelling, barefoot breakfast, an island bicycle loop, lazy beach time and sunset.',
                'Tempat menarik lain: Turtle Point, patung bawah air Gili Meno, taman karang Gili Air, dan senja di pantai barat.',
                'Hari ideal: snorkeling pagi, sarapan di pantai, keliling pulau dengan sepeda, bersantai, lalu menikmati senja.'
            ),
            'Нуса-Пенида' => self::extras(
                'Кайфовые места: Kelingking, Diamond и Atuh, Tembeling Forest с природными бассейнами, Goa Giri Putri, Crystal Bay и тихая бухта Gamat.',
                'Идеальный день: первая лодка из Sanur, один выбранный берег без спешки, обед с видом, закат у Crystal Bay и возвращение либо ночёвка.',
                'More places: Kelingking, Diamond, Atuh, Tembeling Forest, Goa Giri Putri, Crystal Bay and quieter Gamat Bay.',
                'Perfect day: first boat from Sanur, one coast at an easy pace, a view lunch and sunset at Crystal Bay.',
                'Tempat menarik lain: Kelingking, Diamond, Atuh, Hutan Tembeling, Goa Giri Putri, Crystal Bay, dan Gamat Bay.',
                'Hari ideal: kapal pertama dari Sanur, jelajahi satu sisi pulau, makan siang dengan pemandangan, lalu senja di Crystal Bay.'
            ),
            'G-Land' => self::extras(
                'Кайфовые места: главная волна Plengkung, дикий пляж, лес Alas Purwo, Grajagan и остановка на Red Island по пути через Banyuwangi.',
                'Идеальный день: рассвет и проверка волн, первая сессия, восстановление в лагере, вторая сессия по приливу и ранний отдых без городской суеты.',
                'More places: Plengkung’s main break, wild beach, Alas Purwo forest, Grajagan and a Red Island stop near Banyuwangi.',
                'Perfect day: dawn forecast check, first surf, camp recovery, a tide-timed second session and an early night.',
                'Tempat menarik lain: ombak utama Plengkung, pantai liar, hutan Alas Purwo, Grajagan, dan Pulau Merah dekat Banyuwangi.',
                'Hari ideal: cek ombak saat fajar, sesi pertama, istirahat di camp, sesi kedua mengikuti pasang, lalu tidur lebih awal.'
            ),
        ];
    }

    private static function extras(string $ruPlaces, string $ruDay, string $enPlaces, string $enDay, string $idPlaces, string $idDay): array
    {
        return [
            'ru' => "\n\n{$ruPlaces}\n\n{$ruDay}",
            'en' => "\n\n{$enPlaces}\n\n{$enDay}",
            'id' => "\n\n{$idPlaces}\n\n{$idDay}",
        ];
    }

    private static function secondaryTranslations(): array
    {
        return [
            'Чангу' => [
                'uk' => ['name' => 'Чангу', 'description' => 'Серфінг, кав’ярні та заходи сонця. Обирайте Batu Bolong і Berawa; вранці дороги вільніші, увечері можливі затори.'],
                'ar' => ['name' => 'تشانغو', 'description' => 'ركوب الأمواج والمقاهي والغروب. استكشف باتو بولونغ وبيراوا صباحاً، واترك وقتاً إضافياً لازدحام المساء.'],
                'he' => ['name' => 'צ׳אנגו', 'description' => 'גלישה, בתי קפה ושקיעות. מומלץ לבקר בבאטו בולונג ובבראווה בבוקר ולהיערך לפקקים בערב.'],
                'zh' => ['name' => '长谷', 'description' => '冲浪、咖啡馆与日落。推荐 Batu Bolong 和 Berawa；早晨出行更顺畅，傍晚请预留堵车时间。'],
                'ko' => ['name' => '짱구', 'description' => '서핑, 카페와 일몰의 지역입니다. 바투 볼롱과 베라와를 둘러보고 저녁 교통 체증에 여유를 두세요.'],
            ],
            'Семиньяк' => [
                'uk' => ['name' => 'Семіньяк', 'description' => 'Ресторани, бутики та пляжні клуби. Petitenget спокійніший; вечерю й лежаки краще бронювати заздалегідь.'],
                'ar' => ['name' => 'سيمينياك', 'description' => 'مطاعم ومتاجر ونوادٍ شاطئية. بيتيتنغيت أكثر هدوءاً، واحجز العشاء وكراسي الشاطئ مسبقاً.'],
                'he' => ['name' => 'סמיניאק', 'description' => 'מסעדות, בוטיקים ומועדוני חוף. פטיטנגט רגועה יותר; כדאי להזמין ארוחת ערב ומיטות שיזוף מראש.'],
                'zh' => ['name' => '水明漾', 'description' => '餐厅、精品店和海滩俱乐部。Petitenget 较安静，晚餐和躺椅建议提前预订。'],
                'ko' => ['name' => '스미냑', 'description' => '레스토랑, 부티크와 비치클럽이 모여 있습니다. 페티텐겟은 비교적 조용하며 저녁과 데이베드는 미리 예약하세요.'],
            ],
            'Убуд' => [
                'uk' => ['name' => 'Убуд', 'description' => 'Культура, рисові поля та wellness. Почніть рано з храму або стежки Campuhan, а центр залиште на другу половину дня.'],
                'ar' => ['name' => 'أوبود', 'description' => 'ثقافة وحقول أرز وعافية. ابدأ مبكراً بمعبد أو ممشى كامبوهان، ثم زر المركز والسوق لاحقاً.'],
                'he' => ['name' => 'אובוד', 'description' => 'תרבות, שדות אורז וולנס. התחילו מוקדם במקדש או בשביל קמפוהאן ובקרו במרכז ובשוק בהמשך.'],
                'zh' => ['name' => '乌布', 'description' => '文化、稻田与康养。清晨先去寺庙或 Campuhan 步道，下午再逛中心和市场。'],
                'ko' => ['name' => '우붓', 'description' => '문화, 논과 웰니스의 중심지입니다. 아침 일찍 사원이나 캄푸한 산책로를 찾고 오후에 시내와 시장을 둘러보세요.'],
            ],
            'Улувату' => [
                'uk' => ['name' => 'Улувату', 'description' => 'Скелі, пляжі та серфінг. Відвідайте Padang Padang або Bingin, а на захід сонця — храм; перевіряйте припливи.'],
                'ar' => ['name' => 'أولواتو', 'description' => 'منحدرات وشواطئ وركوب أمواج. زر بادانغ بادانغ أو بينغين ثم معبد أولواتو عند الغروب، وتحقق من المد.'],
                'he' => ['name' => 'אולוואטו', 'description' => 'מצוקים, חופים וגלישה. בקרו בפאדאנג פאדאנג או בינגין ובמקדש בשקיעה, ובדקו את זמני הגאות.'],
                'zh' => ['name' => '乌鲁瓦图', 'description' => '悬崖、海滩与冲浪。可去 Padang Padang 或 Bingin，日落前到寺庙，并提前查看潮汐。'],
                'ko' => ['name' => '울루와투', 'description' => '절벽, 해변과 서핑 명소입니다. 파당파당이나 빙인을 즐긴 뒤 일몰에는 사원을 찾고 조수 시간을 확인하세요.'],
            ],
            'Нуса-Дуа' => [
                'uk' => ['name' => 'Нуса-Дуа', 'description' => 'Спокійні пляжі й сімейний відпочинок. Купайтеся вранці, прогуляйтеся до Water Blow та набережною.'],
                'ar' => ['name' => 'نوسا دوا', 'description' => 'شواطئ هادئة مناسبة للعائلات. اسبح صباحاً وتمشّ إلى ووتر بلو والممشى البحري.'],
                'he' => ['name' => 'נוסה דואה', 'description' => 'חופים רגועים וחופשה משפחתית. מומלץ לשחות בבוקר ולטייל אל Water Blow ולאורך הטיילת.'],
                'zh' => ['name' => '努沙杜瓦', 'description' => '安静海滩，适合家庭。建议早晨游泳，再步行前往 Water Blow 和海滨步道。'],
                'ko' => ['name' => '누사두아', 'description' => '잔잔한 해변과 가족 여행에 좋습니다. 아침에 수영하고 워터 블로우와 산책로를 걸어보세요.'],
            ],
            'Санур' => [
                'uk' => ['name' => 'Санур', 'description' => 'Світанки, довга набережна та спокійне море. Зручно для велосипеда, сімей і поїздок на Нуса-Пеніду.'],
                'ar' => ['name' => 'سانور', 'description' => 'شروق الشمس وممشى طويل وبحر هادئ. مناسب للدراجات والعائلات والانطلاق إلى نوسا بينيدا.'],
                'he' => ['name' => 'סאנור', 'description' => 'זריחות, טיילת ארוכה וים רגוע. מצוין לרכיבה, למשפחות וליציאה בסירה לנוסה פנידה.'],
                'zh' => ['name' => '沙努尔', 'description' => '日出、长海滨步道与平静海面。适合骑行和家庭，也是前往佩尼达岛的便利起点。'],
                'ko' => ['name' => '사누르', 'description' => '일출, 긴 산책로와 잔잔한 바다가 매력입니다. 자전거와 가족 여행, 누사 페니다 이동에 편리합니다.'],
            ],
            'Джимбаран' => [
                'uk' => ['name' => 'Джимбаран', 'description' => 'Рибний ринок і вечеря на піску. Приїдьте до заходу сонця, обирайте морепродукти за вагою та уточнюйте ціну.'],
                'ar' => ['name' => 'جيمباران', 'description' => 'سوق سمك وعشاء بحري على الرمال. تعال قبل الغروب واختر المأكولات بالوزن وأكد السعر النهائي.'],
                'he' => ['name' => 'ג׳ימבאראן', 'description' => 'שוק דגים וארוחת פירות ים על החול. הגיעו לפני השקיעה, בחרו לפי משקל ואשרו את המחיר הכולל.'],
                'zh' => ['name' => '金巴兰', 'description' => '鱼市与沙滩海鲜晚餐。日落前抵达，按重量挑选海鲜，并先确认总价。'],
                'ko' => ['name' => '짐바란', 'description' => '수산시장과 모래사장 해산물 저녁이 유명합니다. 일몰 전에 도착해 무게와 총가격을 확인하세요.'],
            ],
            'Кута и Легиан' => [
                'uk' => ['name' => 'Кута та Легіан', 'description' => 'Шопінг, жвавий пляж і нічне життя. Добре для першого уроку серфінгу; цінні речі залишайте в сейфі.'],
                'ar' => ['name' => 'كوتا وليجيان', 'description' => 'تسوق وشاطئ نشط وحياة ليلية. مناسب لأول درس ركوب أمواج؛ اترك الأشياء الثمينة في خزنة الفندق.'],
                'he' => ['name' => 'קוטה ולגיאן', 'description' => 'קניות, חוף תוסס וחיי לילה. מתאים לשיעור גלישה ראשון; השאירו חפצים יקרי ערך בכספת.'],
                'zh' => ['name' => '库塔与勒吉安', 'description' => '购物、热闹海滩和夜生活。适合第一次冲浪课；贵重物品请留在酒店保险箱。'],
                'ko' => ['name' => '꾸따 & 르기안', 'description' => '쇼핑, 활기찬 해변과 밤문화가 특징입니다. 첫 서핑 수업에 좋으며 귀중품은 호텔 금고에 보관하세요.'],
            ],
            'Сидемен' => [
                'uk' => ['name' => 'Сідемен', 'description' => 'Тихі села, рисові тераси й вид на Агунг. Найміть авто з водієм і прогуляйтеся з місцевим гідом.'],
                'ar' => ['name' => 'سيديمين', 'description' => 'قرى هادئة ومدرجات أرز وإطلالات على أغونغ. استأجر سيارة بسائق وتنزه مع مرشد محلي.'],
                'he' => ['name' => 'סידמן', 'description' => 'כפרים שקטים, טרסות אורז ונוף להר אגונג. שכרו רכב עם נהג וצאו להליכה עם מדריך מקומי.'],
                'zh' => ['name' => '席德门', 'description' => '宁静村庄、梯田与阿贡火山景色。建议包车并跟随当地向导徒步，放慢节奏。'],
                'ko' => ['name' => '시데멘', 'description' => '조용한 마을, 계단식 논과 아궁산 전망이 있습니다. 기사 차량과 현지 가이드 산책을 추천합니다.'],
            ],
            'Амед' => [
                'uk' => ['name' => 'Амед', 'description' => 'Снорклінг, дайвінг і вулканічні пляжі. Море найкраще вранці; візьміть взуття для рифів і врахуйте довгий трансфер.'],
                'ar' => ['name' => 'أميد', 'description' => 'غطس وغوص وشواطئ بركانية. البحر أفضل صباحاً؛ أحضر حذاء للشعاب وخطط لرحلة انتقال طويلة.'],
                'he' => ['name' => 'אמד', 'description' => 'שנורקלינג, צלילה וחופים געשיים. הים בדרך כלל טוב בבוקר; הביאו נעלי ריף והיערכו לנסיעה ארוכה.'],
                'zh' => ['name' => '艾湄湾', 'description' => '浮潜、潜水与火山沙滩。海况通常早晨最佳，请带礁石鞋并预留较长车程。'],
                'ko' => ['name' => '아메드', 'description' => '스노클링, 다이빙과 화산 해변이 유명합니다. 바다는 아침이 좋고 리프 슈즈와 긴 이동 시간을 준비하세요.'],
            ],
            'Острова Гили' => [
                'uk' => ['name' => 'Острови Гілі', 'description' => 'Три острови без автомобілів: жвавий Trawangan, збалансований Air і тихий Meno. Найкраще залишитися хоча б на одну ніч.'],
                'ar' => ['name' => 'جزر جيلي', 'description' => 'ثلاث جزر بلا سيارات: تراوانغان النابضة بالحياة، وآير المتوازنة، ومينو الهادئة. يُفضل البقاء ليلة واحدة على الأقل.'],
                'he' => ['name' => 'איי גילי', 'description' => 'שלושה איים ללא מכוניות: טרוואנגן התוססת, אייר המאוזנת ומנו השקטה. מומלץ להישאר לפחות לילה אחד.'],
                'zh' => ['name' => '吉利群岛', 'description' => '三个无机动车小岛：热闹的 Trawangan、均衡的 Air 和宁静的 Meno。建议至少住一晚。'],
                'ko' => ['name' => '길리 제도', 'description' => '차량이 없는 세 섬으로, 활기찬 트라왕안, 균형 잡힌 아이르, 조용한 메노가 있습니다. 최소 1박을 추천합니다.'],
            ],
            'Нуса-Пенида' => [
                'uk' => ['name' => 'Нуса-Пеніда', 'description' => 'Острів зі стрімкими скелями та прозорою водою. Оберіть східний або західний маршрут і замовте водія заздалегідь.'],
                'ar' => ['name' => 'نوسا بينيدا', 'description' => 'جزيرة ذات منحدرات شاهقة ومياه صافية. اختر المسار الشرقي أو الغربي واحجز سائقاً مسبقاً.'],
                'he' => ['name' => 'נוסה פנידה', 'description' => 'אי של מצוקים דרמטיים ומים צלולים. בחרו במסלול המזרחי או המערבי והזמינו נהג מראש.'],
                'zh' => ['name' => '佩尼达岛', 'description' => '以壮观悬崖和清澈海水闻名。请选择东线或西线，并提前预订司机。'],
                'ko' => ['name' => '누사 페니다', 'description' => '극적인 절벽과 맑은 바다의 섬입니다. 동부 또는 서부 코스를 선택하고 기사를 미리 예약하세요.'],
            ],
            'G-Land' => [
                'uk' => ['name' => 'G-Land', 'description' => 'Віддалений серф-спот у Східній Яві для досвідчених серферів. Потрібні кілька ночей, трансфер і перевірка прогнозу хвиль.'],
                'ar' => ['name' => 'G-Land', 'description' => 'وجهة ركوب أمواج نائية في جاوة الشرقية للمتقدمين. خطط لعدة ليالٍ ورتب النقل وتحقق من توقعات الأمواج.'],
                'he' => ['name' => 'G-Land', 'description' => 'אתר גלישה מרוחק במזרח ג׳אווה למתקדמים. תכננו כמה לילות, הסעה ובדיקת תחזית גלים מראש.'],
                'zh' => ['name' => 'G-Land', 'description' => '位于东爪哇的偏远高级冲浪地。建议安排数晚住宿、接送并提前查看浪况。'],
                'ko' => ['name' => 'G-Land', 'description' => '동부 자바의 외딴 상급자용 서핑 명소입니다. 여러 날 숙박, 이동편과 파도 예보 확인이 필요합니다.'],
            ],
        ];
    }
}
