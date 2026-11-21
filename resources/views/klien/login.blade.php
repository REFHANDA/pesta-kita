@extends('layouts.guest')

@section('title', 'Masuk Portal Klien · Pesta Kita')

@section('content')
<main id="konten" class="auth-split" tabindex="-1">
    @include('auth.partials.brand-panel', [
        'heading' => 'Pesta Anda, rapi dari awal.',
        'desc' => 'Konsep, vendor, venue, dan rundown dalam satu tempat. Jadwal bentrok dicek otomatis.',
    ])

    <section class="auth-panel">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Masuk ke akun klien</h1>
            <p class="auth-sub">Rencanakan acara, pesan penyedia, dan pantau tagihan Anda.</p>

            @include('auth.partials.login-form')

            <hr class="auth-divider">
            <p class="auth-alt">Belum punya akun? <a href="{{ url('/register') }}">Daftar</a></p>
        </div>
    </section>
</main>
@endsection