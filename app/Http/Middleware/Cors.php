<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        // Gelen isteğin origin'ini al, eğer yoksa senin frontend URL'ini default kabul et
        $origin = $request->header('Origin') ?: 'http://localhost:3000';

        $response = $next($request);

        return $response
            ->header('Access-Control-Allow-Origin', $origin) // '*' yerine dinamik origin
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true'); // BU SATIR KRİTİK: Cookie izni verir
    }
}
