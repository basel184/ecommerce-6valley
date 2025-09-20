<?php

namespace App\Http\Middleware;

use App\Utils\Helpers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class APILocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Resolve locale from (highest priority first):
        // 1) Custom 'lang' header used by our frontend
        // 2) Query string ?locale=xx (or ?lang=xx) for direct REST calls
        // 3) Standard 'Accept-Language' header (first language)
        // 4) Fallback to Helpers::default_lang()

        $resolveLocale = function (?string $value): ?string {
            if (!$value) return null;
            // Normalize common forms like "ar", "ar-SA", "en-US"
            $value = trim($value);
            // If header contains multiple values like "ar,en;q=0.9", take first token
            $primary = explode(',', $value)[0];
            // Take the language subtag part before '-'
            $primary = explode(';', $primary)[0]; // drop any quality
            $primary = explode('-', $primary)[0];
            $primary = strtolower($primary);
            return $primary ?: null;
        };

        $candidate = null;

        // 1) Custom header 'lang'
        if ($request->hasHeader('lang')) {
            $candidate = $resolveLocale($request->header('lang'));
        }

        // 2) Query param 'locale' or fallback to 'lang'
        if (!$candidate) {
            $candidate = $resolveLocale($request->query('locale') ?? $request->query('lang'));
        }

        // 3) Accept-Language
        if (!$candidate && $request->hasHeader('Accept-Language')) {
            $candidate = $resolveLocale($request->header('Accept-Language'));
        }

        // 4) Fallback
        $local = $candidate ?: Helpers::default_lang();

        App::setLocale($local);
        return $next($request);
    }
}
