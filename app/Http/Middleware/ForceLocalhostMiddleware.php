<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceLocalhostMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && $request->getHost() === '127.0.0.1') {
            return redirect()->to('http://localhost:8000'.$request->getRequestUri(), 302);
        }

        return $next($request);
    }
}
