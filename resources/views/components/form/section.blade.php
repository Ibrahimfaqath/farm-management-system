@props(['title' => null, 'description' => null, 'subtitle' => null])

<fieldset class="space-y-3">
    @if ($title || $subtitle || $description)
        <legend class="contents">
            @if ($title)
                <span class="block text-xs font-semibold uppercase tracking-wide text-base-content/50">{{ $title }}</span>
            @endif
            @if ($subtitle)
                <span class="mt-0.5 block text-sm font-medium text-base-content/80">{{ $subtitle }}</span>
            @endif
            @if ($description)
                <span class="mt-0.5 block text-xs text-base-content/55">{{ $description }}</span>
            @endif
        </legend>
    @endif
    {{ $slot }}
</fieldset>
