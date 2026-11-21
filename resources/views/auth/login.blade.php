@extends('layouts.guest')

@section('title', 'Masuk ke Akun Anda · Pesta Kita')

@section('content')
<main id="konten" class="auth-split" tabindex="-1">
    @include('auth.partials.brand-panel', [
        'heading' => 'Pesta Anda, rapi dari awal.',
        'desc' => 'Konsep, vendor, venue, dan rundown dalam satu tempat. Jadwal bentrok dicek otomatis.',
        'points' => [
            'Pesan vendor & venue tanpa grup chat berantakan',
            'Satu tagihan gabungan, bukti bayar tercatat',
            'Rundown hari-H dibagikan ke semua penyedia',
        ],
    ])

    <section class="auth-panel">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Masuk ke akun Anda</h1>
            {{-- T1: otorisasi & redirect ditentukan server dari role di database. --}}
            <p class="auth-sub">Satu akun untuk semua portal — Anda diarahkan otomatis ke portal sesuai peran akun Anda.</p>

            @include('auth.partials.login-form')

            <hr class="auth-divider">
            <p class="auth-alt">Belum punya akun? <a href="{{ url('/register') }}">Daftar</a></p>
        </div>
    </section>
</main>
@endsection