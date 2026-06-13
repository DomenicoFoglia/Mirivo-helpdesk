<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminOrAgentL2
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isAdmin = $user->role === 'admin';
        $isAgentL2 = $user->role === 'agent' && $user->level === 2;

        if (!$isAdmin && !$isAgentL2) {
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        return $next($request);
    }
}
