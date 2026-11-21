@extends('layouts.guest')

@section('title', 'Masuk Akun Penyedia · Pesta Kita')

@section('content')
<main id="konten" class="auth-split" tabindex="-1">
    @include('auth.partials.brand-panel', [
        'heading' => 'Kelola usaha Anda di satu tempat.',
        'desc' => 'Katalog, pesanan, dan rundown dalam satu panel. Jadwal bentrok dicek otomatis.',
    ])

    <section class="auth-panel">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Masuk ke akun penyedia</h1>
            <p class="auth-sub">Untuk vendor layanan dan pemilik venue — portal mengikuti peran akun Anda.</p>

            @include('auth.partials.login-form')

            <hr class="auth-divider">
            <p class="auth-alt">Belum punya akun? <a href="{{ url('/register') }}">Daftar sebagai penyedia</a></p>
        </div>
    </section>
</main>
@endsection