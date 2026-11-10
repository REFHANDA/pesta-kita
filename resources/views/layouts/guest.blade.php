<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pesta Kita — Rencanakan Pesta Ulang Tahun')</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/penyedia.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/klien.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/keselarasan.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.demo-bar', ['portalTitle' => 'Publik & Autentikasi'])
    <a class="skip" href="#konten">Lewati ke konten</a>

    @yield('content')

    @stack('scripts')
</body>
</html>
