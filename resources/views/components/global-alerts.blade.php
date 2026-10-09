@php
    /** @var \Illuminate\Support\Collection<int, array{tone: string, title: string, description?: string, url: string}> $attentionItems */
    $attentionItems = $attentionItems ?? collect();
    $unseen = $attentionItems->where('tone', '!=', 'info')->count();
@endphp

<div class="dropdown dropdown-end">
    <button tabindex="0" type="button"
            class="btn btn-ghost btn-sm btn-square relative" aria-label="{{ __('Attention') }}">
        <x-icon name="bell" :size="20" />
        @if ($unseen > 0)
            <span class="badge badge-error badge-xs absolute -right-0.5 -top-0.5 border-base-100 px-1 text-[10px]">{{ $unseen }}</span>
        @endif
    </button>
    <div tabindex="0" class="dropdown-content z-50 mt-2 w-80 rounded-box border border-base-300 bg-base-100 p-2 shadow-lg">
        <p class="px-2 pb-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-base-content/45">
            {{ __('Needs attention') }}
        </p>
        @if ($attentionItems->isEmpty())
            <p class="px-2 pb-2 text-xs text-base-content/55">{{ __('Nothing needs follow-up right now.') }}</p>
        @else
            <ul class="max-h-80 space-y-1 overflow-y-auto">
                @foreach ($attentionItems as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="block rounded-field px-2 py-1.5 transition-colors hover:bg-base-200">
                            <span class="block text-xs font-medium">{{ $item['title'] }}</span>
                            @if (! empty($item['description']))
                                <span class="block text-[11px] text-base-content/55">{{ $item['description'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
