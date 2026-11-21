@extends('layouts.guest')

@section('title', 'Daftar Akun Baru · Pesta Kita')

@section('content')
<main id="konten" class="auth-split" tabindex="-1">
    @include('auth.partials.brand-panel', [
        'heading' => 'Mulai susun pesta pertama Anda.',
        'desc' => 'Konsep, vendor, venue, dan rundown dalam satu tempat. Jadwal bentrok dicek otomatis.',
        'points' => [
            'Gratis untuk klien — bayar hanya saat memesan',
            'Usaha penyedia tampil setelah diverifikasi Admin',
            'Satu akun, satu peran, langsung bisa dipakai',
        ],
    ])

    <section class="auth-panel">
        <div class="auth-form-wrap">
            <h1 class="auth-title">Buat akun baru</h1>
            <p class="auth-sub">Bergabung dengan ekosistem perencanaan acara Pesta Kita.</p>

            <form class="auth-form" method="POST" action="{{ url('/register') }}">
                @csrf

                <div>
                    <span class="role-label" id="role-label">Saya mendaftar sebagai</span>
                    <div class="role-cards" role="radiogroup" aria-labelledby="role-label">
                        <label class="role-card">
                            <input type="radio" name="role" value="klien" @checked(old('role', 'klien') === 'klien') onchange="handleRoleChange('klien')">
                            <b>Klien</b>
                            <small>Merencanakan acara</small>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role" value="vendor" @checked(old('role') === 'vendor') onchange="handleRoleChange('vendor')">
                            <b>Vendor</b>
                            <small>Katering, dekorasi, peralatan</small>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role" value="venue" @checked(old('role') === 'venue') onchange="handleRoleChange('venue')">
                            <b>Pemilik venue</b>
                            <small>Menyewakan tempat</small>
                        </label>
                    </div>
                    @error('role')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                    {{-- T2: akun langsung aktif; verifikasi hanya untuk tampilnya usaha. --}}
                    <p class="role-note">Akun vendor dan pemilik venue bisa langsung digunakan. Usaha baru ditampilkan kepada Klien setelah diverifikasi Admin.</p>
                </div>

                <div class="auth-field">
                    <label for="nm">Nama lengkap</label>
                    <input class="auth-input" id="nm" name="name" type="text" autocomplete="name" required placeholder="Contoh: Rina Putri" value="{{ old('name') }}" @error('name') aria-invalid="true" @enderror>
                    @error('name')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="em">Email</label>
                    <input class="auth-input" id="em" name="email" type="email" autocomplete="email" required placeholder="nama@email.com" value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
                    @error('email')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="tl">Nomor telepon / WhatsApp</label>
                    <input class="auth-input" id="tl" name="phone" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}" @error('phone') aria-invalid="true" @enderror>
                    @error('phone')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bagian khusus vendor / pemilik venue --}}
                <div id="vendor-extra-fields" class="provider-box" style="display:none">
                    <b id="extra-title">Khusus vendor &amp; pemilik venue</b>
                    <div class="auth-form">
                        <div class="auth-field">
                            <label for="nu" id="label-nu">Nama usaha vendor</label>
                            <input class="auth-input" id="nu" name="business_name" type="text" placeholder="Contoh: Dapur Nusantara" value="{{ old('business_name') }}" @error('business_name') aria-invalid="true" @enderror>
                            @error('business_name')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="auth-field" id="wrap-ka">
                            <label for="ka">Kategori layanan</label>
                            <select class="auth-input" id="ka" name="category" @error('category') aria-invalid="true" @enderror>
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
                        <div class="auth-field">
                            <label for="al">Alamat operasional</label>
                            <input class="auth-input" id="al" name="address" type="text" placeholder="Jl. Sudirman No. 12, Kediri" value="{{ old('address') }}" @error('address') aria-invalid="true" @enderror>
                            @error('address')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="p1">Kata sandi</label>
                    <input class="auth-input" id="p1" name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Minimal 8 karakter" @error('password') aria-invalid="true" @enderror>
                    @error('password')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="p2">Ulangi kata sandi</label>
                    <input class="auth-input" id="p2" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Ulangi kata sandi">
                </div>

                <button type="submit" class="auth-submit">Daftar Sekarang</button>
            </form>

            <hr class="auth-divider">
            <p class="auth-alt">Sudah punya akun? <a href="{{ url('/login') }}">Masuk</a></p>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
function handleRoleChange(role) {
    const extra = document.getElementById('vendor-extra-fields');
    const title = document.getElementById('extra-title');
    const labelNu = document.getElementById('label-nu');
    const wrapKa = document.getElementById('wrap-ka');

    // Tampilan kartu terpilih (fallback untuk browser tanpa :has()).
    document.querySelectorAll('.role-card').forEach((card) => {
        card.classList.toggle('is-selected', card.querySelector('input').value === role);
    });

    if (role === 'klien') {
        extra.style.display = 'none';
    } else if (role === 'vendor') {
        extra.style.display = 'block';
        title.textContent = 'Khusus vendor — informasi usaha';
        labelNu.textContent = 'Nama usaha vendor';
        wrapKa.style.display = 'block';
    } else if (role === 'venue') {
        extra.style.display = 'block';
        title.textContent = 'Khusus pemilik venue — informasi gedung';
        labelNu.textContent = 'Nama venue / gedung';
        wrapKa.style.display = 'none';
    }
}

// Pulihkan tampilan bagian usaha sesuai role terakhir saat validasi gagal.
handleRoleChange(@json(old('role', 'klien')));
</script>
@endpush