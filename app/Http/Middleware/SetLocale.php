<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honour the client's Accept-Language header so API-provided strings (block
 * prompts, disclaimers, validation messages) come back in the caller's
 * language. Falls back to the app default (Arabic). (§7)
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['ar', 'en']);
        $locale = $request->getPreferredLanguage($supported) ?: config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }
}
