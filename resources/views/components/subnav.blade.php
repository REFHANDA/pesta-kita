@props(['items' => [], 'label' => 'Sub-navigasi'])
{{-- Tab horizontal untuk menggabungkan beberapa halaman di bawah satu menu sidebar. --}}
<nav class="tabs card subnav" aria-label="{{ $label }}">
    @foreach($items as $tabItem)
        <a href="{{ $tabItem['href'] }}" @if($tabItem['active'] ?? false) aria-current="page" @endif>{{ $tabItem['label'] }}@isset($tabItem['count'])<span class="n">{{ $tabItem['count'] }}</span>@endisset</a>
    @endforeach
</nav>
