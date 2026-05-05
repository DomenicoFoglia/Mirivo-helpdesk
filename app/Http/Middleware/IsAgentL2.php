<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAgentL2
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if($request->user()->role !== 'agent' || $request->user()->level !== 2){
            return response()->json(['message' => 'Accesso negato'], 403);
        }

        return $next($request);
    }
}
