<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetGuestLocale
{
    public const LOCALES = ['ru', 'id', 'en', 'ar', 'zh', 'ko', 'he', 'uk'];

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, self::LOCALES, true)) {
            $request->session()->put('guest_locale', $requested);
        }

        $locale = $request->session()->get('guest_locale', 'ru');
        App::setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'ru');

        return $next($request);
    }
}
