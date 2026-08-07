<?php

namespace App\Support;

class ServiceTranslations
{
    public static function for(string $name): array
    {
        $translations = self::catalog()[$name] ?? [];

        foreach (self::additionalCatalog()[$name] ?? [] as $locale => $translation) {
            $translations[$locale] = $translation;
        }

        return $translations;
    }

    private static function additionalCatalog(): array
    {
        return [
            'Еда и напитки' => ['uk' => 'Їжа та напої', 'he' => 'אוכל ומשקאות'],
            'Завтрак' => ['uk' => 'Сніданок', 'he' => 'ארוחת בוקר'],
            'Континентальный завтрак' => [
                'uk' => ['name' => 'Континентальний сніданок', 'description' => 'Кава, випічка, фрукти та свіжий сік'],
                'he' => ['name' => 'ארוחת בוקר קונטיננטלית', 'description' => 'קפה, מאפים, פירות ומיץ טרי'],
            ],
            'Завтрак по-балийски' => [
                'uk' => ['name' => 'Балійський сніданок', 'description' => 'Насі горенг, фрукти та напій'],
                'he' => ['name' => 'ארוחת בוקר באלינזית', 'description' => 'נאסי גורנג, פירות ומשקה'],
            ],
            'Room service' => [
                'uk' => ['name' => 'Обслуговування номера', 'description' => 'Замовлення з меню ресторану до вашого номера'],
                'he' => ['name' => 'שירות חדרים', 'description' => 'הזמנה מתפריט המסעדה ישירות לחדר'],
            ],
            'Для номера' => ['uk' => 'Для номера', 'he' => 'ציוד לחדר'],
            'Дополнительные полотенца' => [
                'uk' => ['name' => 'Додаткові рушники', 'description' => 'Комплект банних рушників'],
                'he' => ['name' => 'מגבות נוספות', 'description' => 'סט מגבות רחצה'],
            ],
            'Уборка номера' => [
                'uk' => ['name' => 'Прибирання номера', 'description' => 'Повне прибирання у зручний для вас час'],
                'he' => ['name' => 'ניקיון החדר', 'description' => 'ניקיון מלא בזמן שנוח לך'],
            ],
            'Прачечная' => [
                'uk' => ['name' => 'Пральня', 'description' => 'Заберемо одяг і повернемо після прання'],
                'he' => ['name' => 'כביסה', 'description' => 'נאסוף את הבגדים ונחזיר אותם לאחר הכביסה'],
            ],
            'Транспорт' => ['uk' => 'Транспорт', 'he' => 'תחבורה'],
            'Трансфер в аэропорт' => [
                'uk' => ['name' => 'Трансфер до аеропорту', 'description' => 'Приватний автомобіль до аеропорту DPS'],
                'he' => ['name' => 'הסעה לשדה התעופה', 'description' => 'רכב פרטי לשדה התעופה DPS'],
            ],
        ];
    }

    private static function catalog(): array
    {
        return [
            'Еда и напитки' => self::names('Food & Drinks', 'Makanan & Minuman', 'الطعام والمشروبات', '餐饮', '음식 및 음료'),
            'Завтрак' => self::names('Breakfast', 'Sarapan', 'الإفطار', '早餐', '조식'),
            'Континентальный завтрак' => self::service('Continental breakfast', 'Sarapan kontinental', 'إفطار كونتيننتال', '欧陆式早餐', '컨티넨탈 조식', 'Coffee, pastries, fruit and fresh juice', 'Kopi, kue, buah, dan jus segar', 'قهوة ومعجنات وفواكه وعصير طازج', '咖啡、糕点、水果和鲜榨果汁', '커피, 페이스트리, 과일과 신선한 주스'),
            'Завтрак по-балийски' => self::service('Balinese breakfast', 'Sarapan khas Bali', 'إفطار بالي', '巴厘岛式早餐', '발리식 조식', 'Nasi goreng, fruit and a drink', 'Nasi goreng, buah, dan minuman', 'ناسي جورينج وفواكه ومشروب', '炒饭、水果和饮品', '나시고렝, 과일과 음료'),
            'Room service' => self::service('Room service', 'Layanan kamar', 'خدمة الغرف', '客房送餐', '룸서비스', 'Order from the restaurant menu to your room', 'Pesan dari menu restoran ke kamar', 'اطلب من قائمة المطعم إلى غرفتك', '从餐厅菜单点餐并送至客房', '레스토랑 메뉴를 객실로 주문하세요'),
            'Для номера' => self::names('Room essentials', 'Kebutuhan kamar', 'احتياجات الغرفة', '客房用品', '객실 용품'),
            'Дополнительные полотенца' => self::service('Extra towels', 'Handuk tambahan', 'مناشف إضافية', '额外毛巾', '추가 수건', 'A set of bath towels', 'Satu set handuk mandi', 'مجموعة من مناشف الحمام', '一套浴巾', '목욕 수건 세트'),
            'Уборка номера' => self::service('Room cleaning', 'Pembersihan kamar', 'تنظيف الغرفة', '客房清洁', '객실 청소', 'Full cleaning at a convenient time', 'Pembersihan lengkap pada waktu yang nyaman', 'تنظيف كامل في الوقت المناسب لك', '在方便的时间进行全面清洁', '원하는 시간에 전체 객실 청소'),
            'Прачечная' => self::service('Laundry', 'Laundry', 'غسيل الملابس', '洗衣服务', '세탁 서비스', 'We collect and return your clothes after washing', 'Kami mengambil dan mengembalikan pakaian setelah dicuci', 'نستلم ملابسك ونعيدها بعد الغسيل', '衣物洗净后送回客房', '세탁 후 옷을 객실로 돌려드립니다'),
            'Транспорт' => self::names('Transport', 'Transportasi', 'المواصلات', '交通', '교통'),
            'Трансфер в аэропорт' => self::service('Airport transfer', 'Transfer bandara', 'توصيل إلى المطار', '机场接送', '공항 이동', 'Private car to DPS airport', 'Mobil pribadi ke bandara DPS', 'سيارة خاصة إلى مطار بالي', '专车前往 DPS 机场', 'DPS 공항까지 전용 차량'),
        ];
    }

    private static function names(string $en, string $id, string $ar, string $zh, string $ko): array
    {
        return compact('en', 'id', 'ar', 'zh', 'ko');
    }

    private static function service(string $en, string $id, string $ar, string $zh, string $ko, string $descriptionEn, string $descriptionId, string $descriptionAr, string $descriptionZh, string $descriptionKo): array
    {
        return [
            'en' => ['name' => $en, 'description' => $descriptionEn],
            'id' => ['name' => $id, 'description' => $descriptionId],
            'ar' => ['name' => $ar, 'description' => $descriptionAr],
            'zh' => ['name' => $zh, 'description' => $descriptionZh],
            'ko' => ['name' => $ko, 'description' => $descriptionKo],
        ];
    }
}
