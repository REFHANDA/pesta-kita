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

            <form class="stack" style="gap:16px" onsubmit="handleRegister(event)">
                <fieldset class="role-opts" style="border:1px solid var(--line);border-radius:12px;padding:14px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">
                    <legend style="font-size:13px;font-weight:700;color:var(--muted);padding:0 6px">Saya mendaftar sebagai</legend>
                    <label style="cursor:pointer;display:flex;flex-direction:column;gap:3px">
                        <input type="radio" name="role" value="klien" checked onchange="toggleVendorFields(false)">
                        <b>Klien</b>
                        <small style="color:var(--muted)">Merencanakan acara</small>
                    </label>
                    <label style="cursor:pointer;display:flex;flex-direction:column;gap:3px">
                        <input type="radio" name="role" value="vendor" onchange="toggleVendorFields(true)">
                        <b>Vendor</b>
                        <small style="color:var(--muted)">Katering, dekorasi, dll</small>
                    </label>
                    <label style="cursor:pointer;display:flex;flex-direction:column;gap:3px">
                        <input type="radio" name="role" value="venue" onchange="toggleVendorFields(true)">
                        <b>Pemilik venue</b>
                        <small style="color:var(--muted)">Menyewakan tempat</small>
                    </label>
                </fieldset>

                <p id="vendor-hint" class="hint" style="margin:0;display:none;background:var(--brand-50);padding:10px 14px;border-radius:10px;color:var(--brand-d)">
                    Akun vendor dan pemilik venue aktif setelah diverifikasi Admin Pesta Kita.
                </p>

                <div class="field">
                    <label for="nm" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Nama lengkap</label>
                    <input class="input" id="nm" type="text" autocomplete="name" required placeholder="Contoh: Rina Putri">
                </div>

                <div class="field">
                    <label for="em" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Email</label>
                    <input class="input" id="em" type="email" autocomplete="email" required placeholder="nama@email.com">
                </div>

                <div class="field">
                    <label for="tl" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Nomor telepon / WhatsApp</label>
                    <input class="input" id="tl" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx">
                </div>

                <!-- Bagian Khusus Vendor / Venue -->
                <div id="vendor-extra-fields" style="display:none;padding:16px;background:var(--bg);border:1px solid var(--line);border-radius:12px">
                    <b style="display:block;font-size:14.5px;margin-bottom:12px;color:var(--brand)">Informasi Usaha (Khusus Vendor & Pemilik Venue)</b>
                    <div class="stack" style="gap:14px">
                        <div class="field">
                            <label for="nu" style="font-weight:600;font-size:13.5px;margin-bottom:4px;display:block">Nama usaha / Venue</label>
                            <input class="input" id="nu" type="text" placeholder="Contoh: Dapur Nusantara / Graha Melati">
                        </div>
                        <div class="field">
                            <label for="ka" style="font-weight:600;font-size:13.5px;margin-bottom:4px;display:block">Kategori usaha</label>
                            <select class="input" id="ka">
                                <option>Katering & F&B</option>
                                <option>Dekorasi Pesta</option>
                                <option>Venue / Aula Pertemuan</option>
                                <option>Sound System & Audio</option>
                                <option>MC & Hiburan</option>
                                <option>Fotografi & Videografi</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="al" style="font-weight:600;font-size:13.5px;margin-bottom:4px;display:block">Alamat lengkap operasional</label>
                            <input class="input" id="al" type="text" placeholder="Jl. Sudirman No. 12, Jakarta">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="p1" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Kata sandi</label>
                    <input class="input" id="p1" type="password" autocomplete="new-password" required minlength="8" placeholder="Minimal 8 karakter">
                </div>

                <div class="field">
                    <label for="p2" style="font-weight:600;font-size:14px;margin-bottom:4px;display:block">Ulangi kata sandi</label>
                    <input class="input" id="p2" type="password" autocomplete="new-password" required placeholder="Ulangi kata sandi">
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
function toggleVendorFields(isVendor) {
    document.getElementById('vendor-extra-fields').style.display = isVendor ? 'block' : 'none';
    document.getElementById('vendor-hint').style.display = isVendor ? 'block' : 'none';
}

function handleRegister(e) {
    e.preventDefault();
    const role = document.querySelector('input[name="role"]:checked').value;
    if (role === 'klien') {
        window.location.href = "{{ url('/klien') }}";
    } else {
        window.location.href = "{{ url('/penyedia/verifikasi-penyedia') }}";
    }
}
</script>
@endsection
