<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\AttentionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Supplies the header notification bell's items to every page.
 *
 * The bell lives in `layouts.app`, so it renders on every screen — but the list
 * was only ever passed by `DashboardController`. On the twenty-odd other pages
 * the variable was simply absent, and on the dashboard it did not survive the
 * hop into a component rendered from the layout either. Either way the bell
 * showed "nothing needs attention" and counted zero, everywhere, always.
 *
 * That is the worst possible failure for a notification: it looks healthy.
 *
 * Computing it here means a page cannot forget. The service is resolved from
 * the container once per request and memoises, so a controller that also wants
 * the list — the dashboard renders its own section — costs no extra queries.
 */
class AttentionComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        // Wrapped in a collection because the service hands back a plain array
        // and the component filters and counts it as one. The two have been
        // interchanged before and the mismatch surfaces as a fatal in the
        // header — on every page.
        $view->with('attentionItems', $user instanceof User
            ? collect(app(AttentionService::class)->itemsFor($user))
            : collect());
    }
}
