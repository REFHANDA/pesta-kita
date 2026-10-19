
@props([
    'variant' => 'primary',
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-[#5B2887] text-white hover:bg-[#48206C]',
        'secondary' => 'bg-[#F8B936] text-[#211631] hover:bg-[#E8A923]',
        'danger' => 'bg-[#BC241A] text-white hover:bg-[#991D15]',
        'outline' => 'border border-[#5B2887] text-[#5B2887] hover:bg-[#F4EFFB]',
    ];

    $style = $variants[$variant] ?? $variants['primary'];
@endphp

<button
    type="button"
    @disabled($disabled)
    {{ $attributes->class([
        'inline-flex items-center justify-center rounded-full px-6 py-3 font-semibold transition-colors',
        $style,
        'cursor-not-allowed opacity-50' => $disabled,
    ]) }}
>
    {{ $slot }}
</button>
