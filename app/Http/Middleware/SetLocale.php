<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');

        // Accettiamo solo lingue supportate, default it
        if (in_array($locale, ['it', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}