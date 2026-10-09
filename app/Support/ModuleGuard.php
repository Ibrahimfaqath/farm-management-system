<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware guarding whole modules, driven by the Role enum.
 *
 * Usage: ->middleware('module:feed,sales')
 */
class ModuleGuard
{
    public function __invoke(Request $request, Closure $next, string ...$modules): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = array_filter($modules, fn (string $module) => $user->canAccessModule($module));

        if ($allowed === []) {
            abort(403, 'Modul ini tidak tersedia untuk peran Anda.');
        }

        return $next($request);
    }
}
