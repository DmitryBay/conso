<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetWorkspaceLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = ['ru', 'id', 'en', 'ar', 'zh', 'ko', 'he', 'uk'];
        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, $locales, true)) {
            $request->session()->put('workspace_locale', $requested);
            if ($request->user()?->preferred_locale !== $requested) {
                $request->user()?->update(['preferred_locale' => $requested]);
            }
        }

        $locale = $request->session()->get('workspace_locale', $request->user()?->preferred_locale ?? 'ru');
        $locale = in_array($locale, $locales, true) ? $locale : 'ru';
        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
