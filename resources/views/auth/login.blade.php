@extends('layouts.guest')

@section('title', 'Masuk ke Akun Anda · Pesta Kita')

@section('content')
<main id="konten" class="guest" tabindex="-1">
    <div class="box">
        <a class="brand" href="{{ url('/') }}" style="justify-content:center;margin-bottom:20px">
            <span class="logo" aria-hidden="true"></span>
            <span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span>
        </a>

        <div class="card" style="padding:32px">
            <h1 style="font-size:24px;font-weight:800;margin-bottom:8px">Masuk ke akun Anda</h1>
            <p style="color:var(--muted);font-size:14.5px;margin-bottom:24px">Pilih portal yang ingin Anda tuju:</p>

            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-bottom:24px">
                <button type="button" onclick="selectRole('klien')" id="role-klien-btn" class="pill" style="justify-content:center;background:var(--brand);color:#fff;border-color:var(--brand);cursor:pointer">Klien</button>
                <button type="button" onclick="selectRole('penyedia')" id="role-penyedia-btn" class="pill" style="justify-content:center;cursor:pointer">Penyedia</button>
                <button type="button" onclick="selectRole('admin')" id="role-admin-btn" class="pill" style="justify-content:center;cursor:pointer">Admin</button>
            </div>

            <form class="stack" style="gap:16px" onsubmit="handleLogin(event)">
                <div class="field">
                    <label for="email" style="font-weight:600;font-size:14px;margin-bottom:6px;display:block">Email</label>
                    <input class="input" id="email" type="email" placeholder="nama@email.com" autocomplete="email" required value="klien@pestakita.test">
                </div>

                <div class="field">
                    <label for="password" style="font-weight:600;font-size:14px;margin-bottom:6px;display:block">Kata sandi</label>
                    <input class="input" id="password" type="password" placeholder="••••••••" autocomplete="current-password" required value="rahasia123">
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label class="check" style="font-weight:500;font-size:14px;margin:0">
                        <input type="checkbox" checked> Ingat saya di perangkat ini
                    </label>
                </div>

                <button type="submit" id="submit-btn" class="btn" style="min-height:46px;font-size:15px;font-weight:700">Masuk ke Portal Klien</button>
            </form>

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--line);text-align:center;font-size:14px">
                <p style="margin:0">Belum punya akun? <a href="{{ url('/register') }}" style="font-weight:700">Daftar sekarang</a></p>
            </div>
        </div>
    </div>
</main>

<script>
let currentRole = 'klien';
const targetUrls = {
    klien: "{{ url('/klien') }}",
    penyedia: "{{ url('/penyedia') }}",
    admin: "{{ url('/admin') }}"
};
const defaultEmails = {
    klien: "klien@pestakita.test",
    penyedia: "vendor@pestakita.test",
    admin: "admin@pestakita.test"
};
const btnLabels = {
    klien: "Masuk ke Portal Klien",
    penyedia: "Masuk ke Panel Penyedia",
    admin: "Masuk ke Panel Admin"
};

function selectRole(role) {
    currentRole = role;
    ['klien', 'penyedia', 'admin'].forEach(r => {
        const btn = document.getElementById('role-' + r + '-btn');
        if (r === role) {
            btn.style.background = 'var(--brand)';
            btn.style.color = '#fff';
            btn.style.borderColor = 'var(--brand)';
        } else {
            btn.style.background = '#fff';
            btn.style.color = 'var(--ink)';
            btn.style.borderColor = 'var(--line)';
        }
    });
    document.getElementById('email').value = defaultEmails[role];
    document.getElementById('submit-btn').textContent = btnLabels[role];
}

function handleLogin(e) {
    e.preventDefault();
    window.location.href = targetUrls[currentRole];
}
</script>
@endsection
