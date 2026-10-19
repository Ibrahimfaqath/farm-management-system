@php
    /**
     * The dashboard's single answer to "what needs handling today".
     *
     * This used to be the full list — one card per item, in a three-column
     * grid. With ten items that is 400px, which is 44% of a 900px screen, and
     * it pushed the production charts 363px below the fold. It was also the same
     * ten items the header bell was already showing, twice over on one screen.
     *
     * The bell answers "is there anything?" — a count and a peek. This answers
     * "what should I deal with?", and it has to stay visible on the landing page
     * because somebody opening the app at seven in the morning should not have
     * to click something to find out that seven things are wrong. Removing it
     * outright would leave the dashboard looking healthy precisely when it is
     * not, which is the same failure the bell had for its whole life.
     *
     * So: one row. The count, the two most serious, and a way into the rest.
     * Errors are taken before warnings so the strip never shows a low-stock
     * warning while an overdue invoice sits unmentioned.
     *
     * Wrapped in `collect()` because the two callers disagree: the dashboard
     * controller passes the plain array `itemsFor()` returns, and the view
     * composer feeds the header a collection. Interchanged once already, and the
     * mismatch surfaced as a fatal in the middle of the dashboard.
     */
    $attentionItems = collect($attentionItems ?? []);

    $total = $attentionItems->count();

    $ranked = $attentionItems
        ->sortBy(fn (array $item) => ($item['tone'] ?? 'info') === 'error' ? 0 : 1)
        ->values();

    $shown = $ranked->take(2);
    $rest = $total - $shown->count();

    $worst = $shown->first();
    $worstTone = in_array($worst['tone'] ?? 'info', ['info', 'success', 'warning', 'error'], true)
        ? ($worst['tone'] ?? 'info')
        : 'info';

    $glyph = match ($worstTone) {
        'error' => 'error',
        'warning' => 'warning',
        'success' => 'success',
        default => 'info',
    };
@endphp

@if ($total > 0)
    <section class="mb-4">
        <div class="alert items-center gap-3 border-{{ $worstTone }}/35 bg-{{ $worstTone }}/10 px-3 py-2 text-base-content"
             @if ($worstTone === 'error') role="alert" @endif>
            <x-icon :name="$glyph" :size="18" class="shrink-0 text-tone-{{ $worstTone }}" />

            <p class="min-w-0 flex-1 text-sm leading-snug">
                <span class="font-semibold">{{ trans_choice('1 item needs attention|*:count items need attention', $total, ['count' => $total]) }}</span>

                {{-- Only the headline of each, not its description: the strip is
                     one line tall, and the detail lives in the bell. --}}
                @foreach ($shown as $item)
                    <a href="{{ $item['url'] }}" class="ml-2 underline decoration-base-content/25 underline-offset-2 hover:decoration-current">
                        {{ $item['title'] }}
                    </a>
                @endforeach

                @if ($rest > 0)
                    {{-- `__()` rather than `trans_choice`: with two identical
                         branches the plural lookup never finds the key and the
                         English source leaks through. Indonesian does not
                         inflect for number either, so nothing is lost. --}}
                    <span class="text-base-content/60">{{ __('and :count more', ['count' => $rest]) }}</span>
                @endif
            </p>

            {{-- Opens the bell, which carries every item in full. --}}
            <button type="button" class="btn btn-ghost btn-xs shrink-0 no-underline" data-open-attention>
                {{ __('See all') }}
            </button>
        </div>
    </section>
@endif

{{--
    Nothing at all when there is nothing. The empty strip used to carry a
    success row saying so, which pushed the real content down on the one screen
    where there was nothing wrong to look at.
--}}

@push('scripts')
    <script>
        (function () {
            document.addEventListener('click', function (event) {
                var trigger = event.target.closest && event.target.closest('[data-open-attention]');

                if (!trigger) {
                    return;
                }

                event.preventDefault();

                var bell = document.querySelector('.header-controls button[aria-haspopup]');

                if (bell) {
                    bell.focus();
                    bell.click();
                }
            });
        })();
    </script>
@endpush