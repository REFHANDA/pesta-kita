@props(['items' => [], 'label' => 'Menu utama'])
{{-- Menu sidebar portal: satu daftar datar tanpa label seksi. --}}
<nav class="nav" aria-label="{{ $label }}" data-portal-nav>
    @foreach($items as $navItem)
        <a href="{{ $navItem['href'] }}" @if($navItem['active']) aria-current="page" @endif>
            <span class="ic i-{{ $navItem['icon'] }}" aria-hidden="true"></span>{{ $navItem['label'] }}
        </a>
    @endforeach
</nav>
