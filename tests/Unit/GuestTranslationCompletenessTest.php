<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestTranslationCompletenessTest extends TestCase
{
    #[DataProvider('locales')]
    public function test_guest_translation_has_every_key_and_placeholder(string $locale): void
    {
        $english = $this->flatten(require lang_path('en/guest.php'));
        $translated = $this->flatten(require lang_path($locale.'/guest.php'));

        $this->assertSame(array_keys($english), array_keys($translated), "Guest translation keys differ for locale {$locale}.");

        foreach ($english as $key => $value) {
            preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $value, $expected);
            preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $translated[$key], $actual);
            sort($expected[1]);
            sort($actual[1]);

            $this->assertSame($expected[1], $actual[1], "Translation placeholders differ for {$locale}.{$key}.");
        }
    }

    public static function locales(): array
    {
        return array_map(fn (string $locale) => [$locale], ['ru', 'uk', 'id', 'ar', 'he', 'zh', 'ko']);
    }

    private function flatten(array $translations, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($translations as $key => $value) {
            $path = $prefix === '' ? $key : $prefix.'.'.$key;
            $flattened = is_array($value)
                ? [...$flattened, ...$this->flatten($value, $path)]
                : [...$flattened, $path => $value];
        }

        return $flattened;
    }
}
