<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePgCredentials
{
    /**
     * Redirect to login if session has no PostgreSQL credentials.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('pg_credentials') || ! $request->session()->get('pg_current_db')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
