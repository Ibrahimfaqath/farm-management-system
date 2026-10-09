@props(['label', 'class' => 'badge-ghost', 'dot' => false, 'size' => 'badge-sm'])

{{--
    Status badge.

    `class` expects a real DaisyUI badge modifier (badge-soft badge-success,
    badge-error, badge-ghost, ...). Enums return these from badgeClass().
--}}
<span {{ $attributes->class(['badge border font-medium', $size, $class]) }}>
    @if ($dot)<span class="mr-1 size-1.5 rounded-full bg-current"></span>@endif{{ $label }}
</span>
