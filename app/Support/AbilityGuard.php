<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware guarding a single business ability.
 *
 * Usage: ->middleware('ability:feed.adjust')
 */
class AbilityGuard
{
    public function __invoke(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = array_filter($abilities, fn (string $ability) => $user->hasAbility($ability));

        if ($allowed === []) {
            abort(403, 'Anda tidak memiliki izin untuk tindakan ini.');
        }

        return $next($request);
    }
}
