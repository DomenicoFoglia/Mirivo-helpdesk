<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se l'utente non è admin lo blocca
        if($request->user()->role !== 'admin'){
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        // Altrimenti passa
        return $next($request);
    }
}
