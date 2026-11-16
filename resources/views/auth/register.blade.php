@extends('layouts.guest')

@section('title', 'Daftar Akun Baru · Pesta Kita')

@section('content')
<main id="konten" class="guest" tabindex="-1">
    <div class="box">
        <a class="brand" href="{{ url('/') }}" style="justify-content:center;margin-bottom:20px">
            <span class="logo" aria-hidden="true"></span>
            <span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span>
        </a>

        <div class="card" style="padding:32px">
            <h1 style="font-size:24px;font-weight:800;margin-bottom:8px">Buat akun baru</h1>
            <p style="color:var(--muted);font-size:14.5px;margin-bottom:20px">Bergabung dengan ekosistem perencanaan acara Pesta Kita.</p>

            <form class="stack" style="gap:16px" method="POST" action="{{ url('/register') }}">
                @csrf
                <fieldset class="role-opts" style="border:1px solid var(--line);border-radius:12px;padding:14px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">
                    <legend style="font-size:13px;font-weight:700;color:var(--muted);padding:0 6px">Saya mendaftar sebagai</legend>
                    <label style="cursor:pointer;display:flex;flex-direction:column;gap:3px">
                        <input type="radio" name="role" value="klien" @checked(old('role', 'klien') === 'klien') onchange="handleRoleChange('klien')">
                        <b>Klien</b>
                        <small style="color:var(--muted)">Penyelenggara pesta</small>
                    </label>
                    <label style="cursor:pointer;display:flex;flex-direction:column;gap:3px">
                        <input type="radio" name="role" value="vendor" @checked(old('role') === 'vendor') onchange="handleRoleChange('vendor')">
                        <b>Vendor</b>
                        <small style="color:var(--muted)">Katering, dekorasi, MC</small>
                    </label>
                    <label style="cursor:pointer;display:flex;flex-direction:column;gap:3px">
                        <input type="radio" name="role" value="venue" @checked(old('role') === 'venue') onchange="handleRoleChange('venue')">
                        <b>Pemilik venue</b>
                        <small style="color:var(--muted)">Menyewakan tempat</small>
                    </label>
                </fieldset>
                @error('role')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror

                <p id="vendor-hint" class="hint" style="margin:0;display:none;background:var(--brand-50);padding:10px 14px;border-radius:10px;color:var(--brand-d);font-weight:600">
                    ℹ️ Akun mitra penyedia (vendor/venue) dapat langsung digunakan untuk masuk. Usaha baru ditampilkan kepada Klien setelah diverifikasi oleh Admin Pesta Kita.
                </p>

                <div class="field">
                    <label for="nm" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Nama lengkap</label>
                    <input class="input" id="nm" name="name" type="text" autocomplete="name" required placeholder="Contoh: Rina Putri" value="{{ old('name') }}" @error('name') aria-invalid="true" @enderror>
                    @error('name')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="em" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Email</label>
                    <input class="input" id="em" name="email" type="email" autocomplete="email" required placeholder="nama@email.com" value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
                    @error('email')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="tl" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Nomor telepon / WhatsApp</label>
                    <input class="input" id="tl" name="phone" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}" @error('phone') aria-invalid="true" @enderror>
                    @error('phone')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bagian Khusus Vendor / Venue -->
                <div id="vendor-extra-fields" style="display:none;padding:16px;background:var(--bg);border:1px solid var(--line);border-radius:12px">
                    <b id="extra-title" style="display:block;font-size:14.5px;margin-bottom:12px;color:var(--brand)">Informasi Usaha Vendor</b>
                    <div class="stack" style="gap:14px">
                        <div class="field">
                            <label for="nu" id="label-nu" style="font-weight:600;font-size:13.5px;margin-bottom:4px;display:block">Nama usaha vendor</label>
                            <input class="input" id="nu" name="business_name" type="text" placeholder="Contoh: Dapur Nusantara" value="{{ old('business_name') }}" @error('business_name') aria-invalid="true" @enderror>
                            @error('business_name')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field" id="wrap-ka">
                            <label for="ka" style="font-weight:600;font-size:13.5px;margin-bottom:4px;display:block">Kategori layanan</label>
                            <select class="input" id="ka" name="category" @error('category') aria-invalid="true" @enderror>
                                <option value="Katering &amp; F&amp;B" @selected(old('category') === 'Katering & F&B')>Katering &amp; F&amp;B</option>
                                <option value="Dekorasi Pesta" @selected(old('category') === 'Dekorasi Pesta')>Dekorasi Pesta</option>
                                <option value="Sound System &amp; Audio" @selected(old('category') === 'Sound System & Audio')>Sound System &amp; Audio</option>
                                <option value="MC &amp; Hiburan" @selected(old('category') === 'MC & Hiburan')>MC &amp; Hiburan</option>
                                <option value="Fotografi &amp; Dokumentasi" @selected(old('category') === 'Fotografi & Dokumentasi')>Fotografi &amp; Dokumentasi</option>
                            </select>
                            @error('category')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field">
                            <label for="al" style="font-weight:600;font-size:13.5px;margin-bottom:4px;display:block">Alamat operasional</label>
                            <input class="input" id="al" name="address" type="text" placeholder="Jl. Sudirman No. 12, Kediri" value="{{ old('address') }}" @error('address') aria-invalid="true" @enderror>
                            @error('address')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="p1" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Kata sandi</label>
                    <input class="input" id="p1" name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Minimal 8 karakter" @error('password') aria-invalid="true" @enderror>
                    @error('password')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="p2" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Ulangi kata sandi</label>
                    <input class="input" id="p2" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Ulangi kata sandi">
                </div>

                <button type="submit" class="btn" style="min-height:46px;font-size:15px;font-weight:700">Daftar Sekarang</button>
            </form>

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--line);text-align:center;font-size:14px">
                <p style="margin:0">Sudah punya akun? <a href="{{ url('/login') }}" style="font-weight:700">Masuk di sini</a></p>
            </div>
        </div>
    </div>
</main>

<script>
function handleRoleChange(role) {
    const extra = document.getElementById('vendor-extra-fields');
    const hint = document.getElementById('vendor-hint');
    const title = document.getElementById('extra-title');
    const labelNu = document.getElementById('label-nu');
    const wrapKa = document.getElementById('wrap-ka');

    if (role === 'klien') {
        extra.style.display = 'none';
        hint.style.display = 'none';
    } else if (role === 'vendor') {
        extra.style.display = 'block';
        hint.style.display = 'block';
        title.textContent = 'Informasi Usaha Vendor';
        labelNu.textContent = 'Nama usaha vendor';
        wrapKa.style.display = 'block';
    } else if (role === 'venue') {
        extra.style.display = 'block';
        hint.style.display = 'block';
        title.textContent = 'Informasi Gedung / Tempat Acara';
        labelNu.textContent = 'Nama venue / gedung';
        wrapKa.style.display = 'none';
    }
}

// Pulihkan tampilan bagian usaha sesuai role terakhir saat validasi gagal.
handleRoleChange(@json(old('role', 'klien')));
</script>
@endsection
