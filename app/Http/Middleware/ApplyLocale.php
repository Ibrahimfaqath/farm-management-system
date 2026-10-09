<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen interface language before any controller,
 * view or enum renders.
 */
class ApplyLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        Locale::apply($request);

        return $next($request);
    }
}
