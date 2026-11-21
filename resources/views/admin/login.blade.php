@extends('layouts.guest')

@section('title', 'Masuk Panel Admin · Pesta Kita')

@section('content')
<main id="konten" class="auth-split" tabindex="-1">
    @include('auth.partials.brand-panel', [
        'heading' => 'Operasional platform dalam kendali.',
        'desc' => 'Verifikasi mitra, tinjau transaksi, dan mediasi keberatan dari satu panel.',
    ])

    <section class="auth-panel">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Masuk ke Panel Admin</h1>
            <p class="auth-sub">Khusus staf internal Pesta Kita.</p>

            @include('auth.partials.login-form')

            {{-- Tidak ada registrasi Admin publik (aturan terkunci). --}}
            <hr class="auth-divider">
            <p class="auth-note">Akun Admin dibuat oleh perusahaan. Tidak ada pendaftaran publik.</p>
        </div>
    </section>
</main>
@endsection