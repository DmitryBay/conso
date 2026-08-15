<?php

namespace Database\Seeders;

use App\Enums\ServiceNodeType;
use App\Models\Company;
use App\Models\ServiceNode;
use App\Support\ServiceOptionCatalog;
use App\Support\ServiceTranslations;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'nusa-bay-hotel')->firstOrFail();

        foreach ($this->catalog() as $node) {
            $this->upsertNode($company, $node);
        }

        ServiceNode::where('company_id', $company->id)
            ->whereIn('name', ['Завтрак', 'Для номера'])
            ->whereDoesntHave('requests')
            ->delete();
    }

    private function upsertNode(Company $company, array $definition, ?ServiceNode $parent = null): ServiceNode
    {
        $node = ServiceNode::where('company_id', $company->id)
            ->where('name', $definition['name']['ru'])
            ->oldest('id')
            ->first() ?? new ServiceNode(['company_id' => $company->id]);

        $translations = collect($definition['name'])->except('ru')->map(fn (string $name, string $locale) => $definition['type'] === ServiceNodeType::Service
            ? ['name' => $name, 'description' => $this->genericDescription($locale)]
            : $name)->all();
        $translations = array_replace($translations, ServiceTranslations::for($definition['name']['ru']));

        $node->fill([
            'parent_id' => $parent?->id,
            'type' => $definition['type'],
            'name' => $definition['name']['ru'],
            'description' => $definition['description'] ?? null,
            'translations' => $translations,
            'option_keys' => $definition['type'] === ServiceNodeType::Service
                ? ServiceOptionCatalog::defaultsFor($definition['name']['ru'], $definition['background'] ?? null)
                : null,
            'icon' => $definition['icon'],
            'background_key' => $definition['background'] ?? null,
            'price_minor' => $definition['price'] ?? null,
            'sla_minutes' => $definition['sla'] ?? null,
            'is_active' => true,
            'sort_order' => $definition['order'],
        ])->save();

        foreach ($definition['children'] ?? [] as $child) {
            $this->upsertNode($company, $child, $node);
        }

        return $node;
    }

    private function catalog(): array
    {
        return [
            $this->category($this->t('Еда и напитки', 'Food & Drinks', 'Makanan & Minuman', 'الطعام والمشروبات', '餐饮', '음식 및 음료', 'Їжа та напої', 'אוכל ומשקאות'), 'bi-cup-hot', 10, [
                $this->category($this->t('Завтраки', 'Breakfast', 'Sarapan', 'الإفطار', '早餐', '조식', 'Сніданки', 'ארוחות בוקר'), 'bi-egg-fried', 10, [
                    $this->service($this->t('Континентальный завтрак', 'Continental breakfast', 'Sarapan kontinental', 'إفطار كونتيننتال', '欧陆式早餐', '컨티넨탈 조식', 'Континентальний сніданок', 'ארוחת בוקר קונטיננטלית'), 'Кофе, выпечка, фрукты и свежий сок', 185000, 30, 'bi-egg-fried', 10),
                    $this->service($this->t('Завтрак по-балийски', 'Balinese breakfast', 'Sarapan khas Bali', 'إفطار بالي', '巴厘岛式早餐', '발리식 조식', 'Балійський сніданок', 'ארוחת בוקר באלינזית'), 'Nasi goreng, фрукты и напиток', 210000, 35, 'bi-bowl-hot', 20),
                    $this->service($this->t('Тропический healthy bowl', 'Tropical healthy bowl', 'Healthy bowl tropis', 'وعاء صحي استوائي', '热带健康碗', '트로피컬 헬시 볼', 'Тропічний healthy bowl', 'קערת בריאות טרופית'), 'Йогурт, гранола, сезонные фрукты и кокос', 160000, 25, 'bi-heart-pulse', 30),
                ]),
                $this->category($this->t('Ресторан отеля', 'Hotel restaurant', 'Restoran hotel', 'مطعم الفندق', '酒店餐厅', '호텔 레스토랑', 'Ресторан готелю', 'מסעדת המלון'), 'bi-shop', 20, [
                    $this->service($this->t('Nasi Goreng', 'Nasi Goreng', 'Nasi Goreng', 'ناسي جورينج', '印尼炒饭', '나시고렝', 'Nasi Goreng', 'נאסי גורנג'), 'Индонезийский жареный рис с яйцом и сезонными овощами', 125000, 35, 'bi-bowl-hot', 10),
                    $this->service($this->t('Mie Goreng', 'Mie Goreng', 'Mie Goreng', 'مي جورينج', '印尼炒面', '미고렝', 'Mie Goreng', 'מי גורנג'), 'Жареная лапша с овощами и выбранным белком', 120000, 35, 'bi-bowl', 20),
                    $this->service($this->t('Рыба на гриле по-джимбарански', 'Jimbaran-style grilled fish', 'Ikan bakar gaya Jimbaran', 'سمك مشوي على طريقة جيمباران', '金巴兰风味烤鱼', '짐바란식 생선구이', 'Риба-гриль по-джимбаранськи', 'דג צלוי בסגנון ג׳ימבראן'), 'Рыба дня, sambal matah, рис и овощи', 195000, 45, 'bi-fire', 30),
                    $this->service($this->t('Клаб-сэндвич', 'Club sandwich', 'Club sandwich', 'كلوب ساندويتش', '总汇三明治', '클럽 샌드위치', 'Клаб-сендвіч', 'קלאב סנדוויץ׳'), 'Курица, яйцо, салат, томат и картофель фри', 145000, 30, 'bi-stack', 40),
                    $this->service($this->t('Овощное карри', 'Vegetable curry', 'Kari sayuran', 'كاري الخضار', '蔬菜咖喱', '채소 커리', 'Овочеве карі', 'קארי ירקות'), 'Кокосовое карри с овощами и рисом, vegan', 135000, 35, 'bi-flower1', 50),
                    $this->service($this->t('Room service', 'Room service menu', 'Menu layanan kamar', 'قائمة خدمة الغرف', '客房送餐菜单', '룸서비스 메뉴', 'Меню Room service', 'תפריט שירות חדרים'), 'Запрос полного меню ресторана для заказа в номер', null, 15, 'bi-bell', 60),
                ]),
                $this->category($this->t('Местная кухня Бали', 'Balinese local cuisine', 'Masakan lokal Bali', 'المطبخ البالي المحلي', '巴厘岛本地美食', '발리 현지 요리', 'Місцева кухня Балі', 'מטבח באלינזי מקומי'), 'bi-pin-map', 30, [
                    $this->service($this->t('Nasi Campur Bali', 'Balinese Nasi Campur', 'Nasi Campur Bali', 'ناسي كامبور بالي', '巴厘岛什锦饭', '발리 나시 참푸르', 'Nasi Campur Bali', 'נאסי צ׳מפור באלי'), 'Рис, Ayam Betutu, Sate Lilit, Lawar и Sambal Matah', 145000, 40, 'bi-bowl-hot', 10),
                    $this->service($this->t('Ayam Betutu', 'Ayam Betutu', 'Ayam Betutu', 'أيام بيتوتو', '巴厘香料鸡', '아얌 베투투', 'Ayam Betutu', 'אייאם בטוטו'), 'Курица в base genep, медленно приготовленная в банановом листе', 175000, 50, 'bi-fire', 20),
                    $this->service($this->t('Sate Lilit', 'Sate Lilit', 'Sate Lilit', 'ساتيه ليليت', '巴厘肉末沙嗲', '사테 릴릿', 'Sate Lilit', 'סאטה ליליט'), 'Рыбный фарш с кокосом и лаймом на стебле лемонграсса', 160000, 40, 'bi-fire', 30),
                    $this->service($this->t('Bebek Betutu', 'Bebek Betutu', 'Bebek Betutu', 'بيبيك بيتوتو', '巴厘香料鸭', '베벡 베투투', 'Bebek Betutu', 'בבק בטוטו'), 'Утка со специями, медленно приготовленная до мягкости', 210000, 55, 'bi-fire', 40),
                    $this->service($this->t('Babi Guling — содержит свинину', 'Babi Guling — contains pork', 'Babi Guling — mengandung babi', 'بابي جولينج — يحتوي على لحم الخنزير', '巴厘烤乳猪 — 含猪肉', '바비 굴링 — 돼지고기 포함', 'Babi Guling — містить свинину', 'באבי גולינג — מכיל חזיר'), 'Бали́йская запечённая свинина с рисом, lawar и sambal', 185000, 45, 'bi-fire', 50),
                    $this->service($this->t('Jaje Laklak', 'Jaje Laklak', 'Jaje Laklak', 'جاجي لاكلاك', '巴厘米粉椰糖煎饼', '자제 락락', 'Jaje Laklak', 'ג׳אג׳ה לקלאק'), 'Рисовые панкейки с кокосом и пальмовым сахаром', 75000, 25, 'bi-cookie', 60),
                ]),
                $this->category($this->t('Напитки', 'Drinks', 'Minuman', 'المشروبات', '饮品', '음료', 'Напої', 'משקאות'), 'bi-cup-straw', 40, [
                    $this->service($this->t('Свежий кокос', 'Fresh coconut', 'Kelapa muda', 'جوز هند طازج', '新鲜椰子', '신선한 코코넛', 'Свіжий кокос', 'קוקוס טרי'), 'Охлаждённый молодой кокос', 55000, 15, 'bi-cup-straw', 10),
                    $this->service($this->t('Тропический сок', 'Tropical juice', 'Jus tropis', 'عصير استوائي', '热带果汁', '트로피컬 주스', 'Тропічний сік', 'מיץ טרופי'), 'Манго, арбуз, ананас или смешанный', 65000, 15, 'bi-cup-straw', 20),
                    $this->service($this->t('Балийский Jamu', 'Balinese Jamu', 'Jamu Bali', 'جامو بالي', '巴厘草本饮品 Jamu', '발리 자무', 'Балійський Jamu', 'ג׳אמו באלינזי'), 'Традиционный травяной напиток с куркумой и имбирём', 60000, 15, 'bi-flower2', 30),
                    $this->service($this->t('Кофе или чай', 'Coffee or tea', 'Kopi atau teh', 'قهوة أو شاي', '咖啡或茶', '커피 또는 차', 'Кава або чай', 'קפה או תה'), 'Балийский кофе, эспрессо или чай на выбор', 45000, 15, 'bi-cup-hot', 40),
                ]),
            ]),
            $this->category($this->t('Номер и уборка', 'Room & Housekeeping', 'Kamar & Housekeeping', 'الغرفة والتنظيف', '客房与清洁', '객실 및 하우스키핑', 'Номер і прибирання', 'חדר וניקיון'), 'bi-house-heart', 20, [
                $this->category($this->t('Уборка', 'Housekeeping', 'Housekeeping', 'تنظيف الغرف', '客房清洁', '하우스키핑', 'Прибирання', 'ניקיון'), 'bi-stars', 10, [
                    $this->service($this->t('Уборка номера', 'Room cleaning', 'Pembersihan kamar', 'تنظيف الغرفة', '客房清洁', '객실 청소', 'Прибирання номера', 'ניקיון החדר'), 'Полная уборка в удобное время', null, 45, 'bi-stars', 10, 'room'),
                    $this->service($this->t('Вечерняя подготовка номера', 'Evening turndown', 'Persiapan kamar malam', 'تجهيز الغرفة مساءً', '夜床服务', '턴다운 서비스', 'Вечірня підготовка номера', 'סידור חדר לערב'), 'Подготовим спальню и освежим номер ко сну', null, 30, 'bi-moon-stars', 20, 'room'),
                ]),
                $this->category($this->t('Бельё и принадлежности', 'Linen & amenities', 'Linen & amenitas', 'البياضات والمستلزمات', '布草与用品', '침구 및 어메니티', 'Білизна та приладдя', 'מצעים ואביזרים'), 'bi-basket', 20, [
                    $this->service($this->t('Дополнительные полотенца', 'Extra towels', 'Handuk tambahan', 'مناشف إضافية', '额外毛巾', '추가 수건', 'Додаткові рушники', 'מגבות נוספות'), 'Комплект банных полотенец', null, 15, 'bi-droplet', 10, 'room'),
                    $this->service($this->t('Смена постельного белья', 'Change bed linen', 'Ganti seprai', 'تغيير بياضات السرير', '更换床品', '침구 교체', 'Заміна постільної білизни', 'החלפת מצעים'), 'Свежий комплект постельного белья', null, 30, 'bi-layers', 20, 'room'),
                    $this->service($this->t('Детская кроватка', 'Baby cot', 'Tempat tidur bayi', 'سرير أطفال', '婴儿床', '아기 침대', 'Дитяче ліжечко', 'מיטת תינוק'), 'Подготовим кроватку для ребёнка', null, 30, 'bi-person-hearts', 30, 'room'),
                ]),
                $this->category($this->t('Прачечная', 'Laundry', 'Laundry', 'غسيل الملابس', '洗衣服务', '세탁', 'Пральня', 'כביסה'), 'bi-bag', 30, [
                    $this->service($this->t('Стандартная стирка', 'Standard laundry', 'Laundry standar', 'غسيل عادي', '标准洗衣', '일반 세탁', 'Стандартне прання', 'כביסה רגילה'), 'Заберём вещи и вернём после стирки', 75000, 240, 'bi-basket', 10, 'room'),
                    $this->service($this->t('Экспресс-стирка', 'Express laundry', 'Laundry ekspres', 'غسيل سريع', '加急洗衣', '익스프레스 세탁', 'Експрес-прання', 'כביסה מהירה'), 'Возврат вещей в тот же день', 140000, 180, 'bi-lightning', 20, 'room'),
                ]),
            ]),
            $this->category($this->t('Транспорт', 'Transport', 'Transportasi', 'المواصلات', '交通', '교통', 'Транспорт', 'תחבורה'), 'bi-car-front', 30, [
                $this->category($this->t('Аэропорт', 'Airport', 'Bandara', 'المطار', '机场', '공항', 'Аеропорт', 'שדה התעופה'), 'bi-airplane', 10, [
                    $this->service($this->t('Трансфер в аэропорт', 'Airport transfer', 'Transfer bandara', 'توصيل إلى المطار', '机场接送', '공항 이동', 'Трансфер до аеропорту', 'הסעה לשדה התעופה'), 'Частный автомобиль до аэропорта DPS', 350000, 60, 'bi-airplane', 10, 'transport'),
                    $this->service($this->t('Встреча в аэропорту', 'Airport meet & greet', 'Penjemputan bandara', 'استقبال في المطار', '机场迎接服务', '공항 미팅 서비스', 'Зустріч в аеропорту', 'קבלת פנים בשדה התעופה'), 'Водитель встретит с табличкой в зоне прилёта', 450000, 90, 'bi-person-badge', 20, 'transport'),
                ]),
                $this->category($this->t('Поездки по острову', 'Island transport', 'Transportasi pulau', 'التنقل في الجزيرة', '岛内交通', '섬 내 교통', 'Поїздки островом', 'נסיעות באי'), 'bi-map', 20, [
                    $this->service($this->t('Автомобиль с водителем на 8 часов', 'Car with driver for 8 hours', 'Mobil dengan sopir 8 jam', 'سيارة مع سائق لمدة 8 ساعات', '包车司机 8 小时', '차량 및 기사 8시간', 'Автомобіль із водієм на 8 годин', 'רכב עם נהג ל-8 שעות'), 'Индивидуальный маршрут по Бали', 850000, 120, 'bi-car-front', 10, 'transport'),
                    $this->service($this->t('Аренда скутера на день', 'Scooter rental for a day', 'Sewa skuter sehari', 'تأجير سكوتر ليوم', '踏板车日租', '스쿠터 1일 대여', 'Оренда скутера на день', 'השכרת קטנוע ליום'), 'Скутер и два шлема, требуются действующие права', 150000, 45, 'bi-bicycle', 20, 'transport'),
                    $this->service($this->t('Заказать такси', 'Request a taxi', 'Pesan taksi', 'طلب سيارة أجرة', '叫出租车', '택시 요청', 'Замовити таксі', 'הזמנת מונית'), 'Стойка подберёт машину и подтвердит время подачи', null, 20, 'bi-taxi-front', 30, 'transport'),
                ]),
            ]),
            $this->category($this->t('Wellness и Spa', 'Wellness & Spa', 'Wellness & Spa', 'العافية والسبا', '康养与水疗', '웰니스 및 스파', 'Wellness і Spa', 'וולנס וספא'), 'bi-flower1', 40, [
                $this->category($this->t('Массаж и Spa', 'Massage & Spa', 'Pijat & Spa', 'المساج والسبا', '按摩与水疗', '마사지 및 스파', 'Масаж і Spa', 'עיסוי וספא'), 'bi-flower2', 10, [
                    $this->service($this->t('Балийский массаж, 60 минут', 'Balinese massage, 60 min', 'Pijat Bali, 60 menit', 'مساج بالي، 60 دقيقة', '巴厘式按摩 60 分钟', '발리 마사지 60분', 'Балійський масаж, 60 хвилин', 'עיסוי באלינזי, 60 דקות'), 'Мягкий массаж с ароматическим маслом', 350000, 60, 'bi-flower1', 10, 'wellness'),
                    $this->service($this->t('Глубокий массаж, 60 минут', 'Deep tissue massage, 60 min', 'Pijat deep tissue, 60 menit', 'مساج الأنسجة العميقة، 60 دقيقة', '深层组织按摩 60 分钟', '딥 티슈 마사지 60분', 'Глибокий масаж, 60 хвилин', 'עיסוי רקמות עמוק, 60 דקות'), 'Интенсивная работа с мышечным напряжением', 425000, 60, 'bi-heart-pulse', 20, 'wellness'),
                    $this->service($this->t('Массаж для пары, 60 минут', 'Couples massage, 60 min', 'Pijat pasangan, 60 menit', 'مساج للزوجين، 60 دقيقة', '双人按摩 60 分钟', '커플 마사지 60분', 'Масаж для пари, 60 хвилин', 'עיסוי זוגי, 60 דקות'), 'Одновременный массаж для двух гостей', 700000, 75, 'bi-hearts', 30, 'wellness'),
                ]),
                $this->category($this->t('Йога и медитация', 'Yoga & Meditation', 'Yoga & Meditasi', 'اليوغا والتأمل', '瑜伽与冥想', '요가 및 명상', 'Йога та медитація', 'יוגה ומדיטציה'), 'bi-sunrise', 20, [
                    $this->service($this->t('Индивидуальная йога', 'Private yoga session', 'Yoga privat', 'جلسة يوغا خاصة', '私人瑜伽课', '개인 요가', 'Індивідуальна йога', 'יוגה פרטית'), 'Практика с инструктором для любого уровня', 300000, 90, 'bi-person-arms-up', 10, 'wellness'),
                    $this->service($this->t('Медитация с инструктором', 'Guided meditation', 'Meditasi terpandu', 'تأمل بإرشاد مدرب', '引导式冥想', '가이드 명상', 'Медитація з інструктором', 'מדיטציה מודרכת'), 'Спокойная индивидуальная сессия', 250000, 75, 'bi-peace', 20, 'wellness'),
                ]),
            ]),
            $this->category($this->t('Впечатления и бронирования', 'Experiences & Reservations', 'Pengalaman & Reservasi', 'التجارب والحجوزات', '体验与预订', '체험 및 예약', 'Враження та бронювання', 'חוויות והזמנות'), 'bi-compass', 50, [
                $this->category($this->t('Рестораны', 'Restaurants', 'Restoran', 'المطاعم', '餐厅', '레스토랑', 'Ресторани', 'מסעדות'), 'bi-calendar-check', 10, [
                    $this->service($this->t('Забронировать местный ресторан', 'Book a local restaurant', 'Pesan restoran lokal', 'حجز مطعم محلي', '预订当地餐厅', '현지 레스토랑 예약', 'Забронювати місцевий ресторан', 'הזמנת מסעדה מקומית'), 'Консьерж подберёт ресторан и подтвердит столик', null, 60, 'bi-calendar-check', 10, 'food'),
                    $this->service($this->t('Приватный ужин', 'Private dinner', 'Makan malam privat', 'عشاء خاص', '私人晚餐', '프라이빗 디너', 'Приватна вечеря', 'ארוחת ערב פרטית'), 'Ужин для двух гостей в приватной зоне', 950000, 240, 'bi-stars', 20, 'food'),
                ]),
                $this->category($this->t('Откройте Бали', 'Discover Bali', 'Jelajahi Bali', 'اكتشف بالي', '探索巴厘岛', '발리 체험', 'Відкрийте Балі', 'לגלות את באלי'), 'bi-map', 20, [
                    $this->service($this->t('Кулинарный мастер-класс', 'Balinese cooking class', 'Kelas memasak Bali', 'درس طبخ بالي', '巴厘岛烹饪课', '발리 요리 교실', 'Кулінарний майстер-клас', 'סדנת בישול באלינזי'), 'Рынок, специи и приготовление местных блюд', 650000, 180, 'bi-egg-fried', 10, 'food'),
                    $this->service($this->t('Храмы и рисовые террасы', 'Temples & rice terraces', 'Pura & sawah terasering', 'المعابد ومدرجات الأرز', '寺庙与梯田', '사원 및 계단식 논', 'Храми та рисові тераси', 'מקדשים וטרסות אורז'), 'Частная экскурсия на половину дня', 900000, 240, 'bi-camera', 20, 'transport'),
                    $this->service($this->t('Урок сёрфинга', 'Surf lesson', 'Kelas selancar', 'درس ركوب الأمواج', '冲浪课程', '서핑 레슨', 'Урок серфінгу', 'שיעור גלישה'), 'Индивидуальное занятие с инструктором и доской', 550000, 120, 'bi-water', 30, 'wellness'),
                ]),
            ]),
            $this->category($this->t('Помощь гостю', 'Guest Assistance', 'Bantuan Tamu', 'مساعدة النزلاء', '住客协助', '투숙객 지원', 'Допомога гостю', 'סיוע לאורח'), 'bi-headset', 60, [
                $this->category($this->t('Стойка размещения', 'Reception', 'Resepsionis', 'الاستقبال', '前台', '리셉션', 'Рецепція', 'קבלה'), 'bi-bell', 10, [
                    $this->service($this->t('Звонок-будильник', 'Wake-up call', 'Panggilan bangun', 'مكالمة إيقاظ', '叫醒服务', '모닝콜', 'Дзвінок-будильник', 'שיחת השכמה'), 'Позвоним в номер в указанное время', null, 10, 'bi-alarm', 10, 'room'),
                    $this->service($this->t('Помощь с багажом', 'Luggage assistance', 'Bantuan bagasi', 'مساعدة الأمتعة', '行李协助', '수하물 지원', 'Допомога з багажем', 'עזרה עם מזוודות'), 'Заберём или доставим багаж', null, 15, 'bi-suitcase', 20, 'room'),
                    $this->service($this->t('Поздний выезд', 'Late check-out request', 'Permintaan check-out terlambat', 'طلب مغادرة متأخرة', '延迟退房申请', '레이트 체크아웃 요청', 'Пізній виїзд', 'בקשת עזיבה מאוחרת'), 'Проверим доступность и сообщим условия', null, 30, 'bi-clock-history', 30, 'room'),
                ]),
                $this->category($this->t('Техническая помощь', 'Maintenance', 'Bantuan teknis', 'الصيانة', '维修支持', '시설 지원', 'Технічна допомога', 'תחזוקה'), 'bi-tools', 20, [
                    $this->service($this->t('Проблема с кондиционером', 'Air conditioner issue', 'Masalah AC', 'مشكلة في التكييف', '空调问题', '에어컨 문제', 'Проблема з кондиціонером', 'בעיה במיזוג'), 'Инженер проверит кондиционер в номере', null, 20, 'bi-snow', 10, 'room'),
                    $this->service($this->t('Сантехника', 'Plumbing issue', 'Masalah saluran air', 'مشكلة في السباكة', '管道问题', '배관 문제', 'Сантехніка', 'בעיית אינסטלציה'), 'Протечка, слив или отсутствие горячей воды', null, 20, 'bi-wrench-adjustable', 20, 'room'),
                    $this->service($this->t('Электричество или Wi‑Fi', 'Electricity or Wi-Fi issue', 'Masalah listrik atau Wi-Fi', 'مشكلة في الكهرباء أو Wi-Fi', '电力或 Wi-Fi 问题', '전기 또는 Wi-Fi 문제', 'Електрика або Wi‑Fi', 'בעיה בחשמל או ב-Wi-Fi'), 'Помощь с освещением, розетками или интернетом', null, 20, 'bi-wifi', 30, 'room'),
                ]),
            ]),
        ];
    }

    private function category(array $name, string $icon, int $order, array $children): array
    {
        return compact('name', 'icon', 'order', 'children') + ['type' => ServiceNodeType::Category];
    }

    private function service(array $name, string $description, ?int $price, int $sla, string $icon, int $order, string $background = 'food'): array
    {
        return compact('name', 'description', 'price', 'sla', 'icon', 'order', 'background') + ['type' => ServiceNodeType::Service];
    }

    private function t(string $ru, string $en, string $id, string $ar, string $zh, string $ko, string $uk, string $he): array
    {
        return compact('ru', 'en', 'id', 'ar', 'zh', 'ko', 'uk', 'he');
    }

    private function genericDescription(string $locale): string
    {
        return match ($locale) {
            'en' => 'Prepared by the hotel team. Add preferences when sending the request.',
            'id' => 'Disiapkan oleh tim hotel. Tambahkan preferensi saat mengirim permintaan.',
            'ar' => 'يجهزها فريق الفندق. أضف تفضيلاتك عند إرسال الطلب.',
            'zh' => '由酒店团队安排。发送请求时可添加偏好。',
            'ko' => '호텔 팀이 준비합니다. 요청을 보낼 때 선호 사항을 추가하세요.',
            'uk' => 'Послугу підготує команда готелю. Додайте побажання під час надсилання запиту.',
            'he' => 'צוות המלון יטפל בשירות. אפשר להוסיף העדפות בעת שליחת הבקשה.',
            default => '',
        };
    }
}
