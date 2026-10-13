@php
    /** @var \Illuminate\Support\Collection<int, array{tone: string, title: string, description?: string, url: string}> $attentionItems */
    $attentionItems = $attentionItems ?? collect();
    $attention = $attentionItems->filter(fn (array $item) => ($item['tone'] ?? 'info') !== 'info');
@endphp

<div class="dropdown dropdown-end">
    <button tabindex="0" type="button"
            class="btn btn-ghost btn-sm btn-square relative" aria-label="{{ __('Items needing attention') }}">
        <x-icon name="bell" :size="20" />
        @if ($attention->isNotEmpty())
            <span class="text-meta badge badge-error badge-xs absolute -right-0.5 -top-0.5 border-base-100 px-1">
                {{ $attention->count() }}
            </span>
            <span class="sr-only">{{ $attention->count() }} {{ __('items need attention') }}</span>
        @endif
    </button>

    <div tabindex="0" class="dropdown-content z-50 mt-2 max-h-[70vh] w-[min(20rem,calc(100vw-2rem))] rounded-box border border-base-300 bg-base-100 p-2 shadow-lg">
        <p class="text-label px-2 pb-2 pt-1 text-base-content/45">
            {{ __('Needs attention') }}
        </p>

        @if ($attentionItems->isEmpty())
            <p class="px-2 pb-2 text-xs text-base-content/55">{{ __('Nothing needs follow-up right now.') }}</p>
        @else
            <ul class="max-h-64 space-y-1 overflow-y-auto">
                @foreach ($attentionItems as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="block rounded-field px-2 py-1.5 transition-colors hover:bg-base-200">
                            <span class="block text-xs font-medium">{{ $item['title'] }}</span>
                            @if (! empty($item['description']))
                                <span class="text-meta block text-base-content/55">{{ $item['description'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>