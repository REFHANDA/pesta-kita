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
            <h1 style="font-size:24px;font-weight:800;margin-bottom:6px">Masuk ke akun Anda</h1>
            <p style="color:var(--muted);font-size:14px;margin-bottom:20px">Pilih peran akun untuk melihat antarmuka portal masing-masing:</p>

            <!-- 4 Role Selector Tabs -->
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:20px">
                <button type="button" onclick="selectRole('klien')" id="role-klien-btn" class="pill" style="justify-content:center;background:var(--brand);color:#fff;border-color:var(--brand);cursor:pointer;font-size:13.5px">👤 Klien</button>
                <button type="button" onclick="selectRole('vendor')" id="role-vendor-btn" class="pill" style="justify-content:center;cursor:pointer;font-size:13.5px">🍴 Vendor</button>
                <button type="button" onclick="selectRole('venue')" id="role-venue-btn" class="pill" style="justify-content:center;cursor:pointer;font-size:13.5px">🏛️ Pemilik Venue</button>
                <button type="button" onclick="selectRole('admin')" id="role-admin-btn" class="pill" style="justify-content:center;cursor:pointer;font-size:13.5px">🛡️ Admin</button>
            </div>

            <div id="role-badge-info" style="margin-bottom:18px;padding:8px 12px;background:var(--brand-50);border-radius:10px;font-size:13px;color:var(--brand-d);font-weight:600">
                Akses: Portal Klien (Penyelenggara Acara Ulang Tahun)
            </div>

            <form class="stack" style="gap:16px" method="POST" action="{{ url('/login') }}">
                @csrf
                <div class="field">
                    <label for="email" style="font-weight:600;font-size:14px;margin-bottom:6px;display:block">Email</label>
                    <input class="input" id="email" name="email" type="email" placeholder="nama@email.com" autocomplete="email" required value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
                    @error('email')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password" style="font-weight:600;font-size:14px;margin-bottom:6px;display:block">Kata sandi</label>
                    <input class="input" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                    @error('password')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label class="check" style="font-weight:500;font-size:14px;margin:0">
                        <input type="checkbox" name="remember" value="1" checked> Ingat saya di perangkat ini
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
// Tab role hanya penanda visual (T1): otorisasi dan redirect ditentukan
// server berdasarkan role akun di database, bukan pilihan tab.
const btnLabels = {
    klien: "Masuk ke Portal Klien",
    vendor: "Masuk ke Panel Vendor",
    venue: "Masuk ke Panel Pemilik Venue",
    admin: "Masuk ke Panel Admin"
};
const roleInfos = {
    klien: "Akses: Portal Klien (Penyelenggara Acara Ulang Tahun)",
    vendor: "Akses: Panel Vendor (Katering, Dekorasi, Hiburan & Peralatan)",
    venue: "Akses: Panel Pemilik Venue (Manajemen Gedung & Denah Ruangan)",
    admin: "Akses: Panel Admin Perusahaan (Operasional & Manajemen Transaksi)"
};

function selectRole(role) {
    ['klien', 'vendor', 'venue', 'admin'].forEach(r => {
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
    document.getElementById('submit-btn').textContent = btnLabels[role];
    document.getElementById('role-badge-info').textContent = roleInfos[role];
}
</script>
@endsection
