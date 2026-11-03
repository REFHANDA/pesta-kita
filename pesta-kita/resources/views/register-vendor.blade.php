
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Vendor / Pemilik Venue | Pesta Kita</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FCFAFE] font-sans text-[#211631]">

<main class="grid min-h-screen lg:grid-cols-2">

    <!-- PANEL KIRI -->
    <section class="flex min-h-[300px] flex-col bg-[#5B2887] px-8 py-8 text-white lg:min-h-screen lg:px-12">
        <a href="/" class="text-lg font-extrabold">Pesta Kita</a>

        <div class="my-auto py-12">
            <span class="rounded-full bg-[#F8B936] px-3 py-1 text-xs font-bold text-[#211631]">
                Untuk pesta ulang tahun
            </span>

            <h1 class="mt-5 max-w-sm text-4xl font-extrabold leading-tight">
                Mulai susun pesta pertama Anda.
            </h1>

            <p class="mt-4 max-w-sm text-sm leading-6 text-white/75">
                Konsep, vendor, venue, dan rundown dalam satu tempat.
                Jadwal bentrok dicek otomatis.
            </p>
        </div>

        <p class="text-xs text-white/60">© 2026 Pesta Kita</p>
    </section>

    <!-- PANEL KANAN -->
    <section class="flex items-center justify-center px-6 py-12 lg:px-12">
        <div class="w-full max-w-[430px]">

            <h2 class="text-2xl font-extrabold">Buat akun baru</h2>

            <form id="vendorForm" class="mt-6 space-y-4">

                <!-- PILIH PERAN -->
                <fieldset>
                    <legend class="mb-2 text-sm font-semibold">
                        Saya mendaftar sebagai
                    </legend>

                    <div class="grid grid-cols-3 gap-2">

                        <a href="/register"
                           class="rounded-xl border border-[#D9D1E2] bg-white p-3 hover:border-[#5B2887]">
                            <p class="text-xs font-bold">Klien</p>
                            <p class="mt-1 text-[10px] text-[#756B80]">Perencanaan acara</p>
                        </a>

                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="vendor"
                                   class="peer sr-only" checked>
                            <span class="block h-full rounded-xl border border-[#D9D1E2] bg-white p-3 peer-checked:border-2 peer-checked:border-[#5B2887] peer-checked:bg-[#F5EFFB]">
                                <span class="block text-xs font-bold">Vendor</span>
                                <span class="mt-1 block text-[10px] text-[#756B80]">
                                    Katering, dekor, peralatan
                                </span>
                            </span>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="venue"
                                   class="peer sr-only">
                            <span class="block h-full rounded-xl border border-[#D9D1E2] bg-white p-3 peer-checked:border-2 peer-checked:border-[#5B2887] peer-checked:bg-[#F5EFFB]">
                                <span class="block text-xs font-bold">Pemilik venue</span>
                                <span class="mt-1 block text-[10px] text-[#756B80]">
                                    Penyewaan tempat
                                </span>
                            </span>
                        </label>

                    </div>

                    <p class="mt-2 text-xs text-[#756B80]">
                        Akun vendor dan pemilik venue aktif setelah diverifikasi Admin.
                    </p>
                </fieldset>

                <!-- DATA PRIBADI -->
                <div>
                    <label for="name" class="mb-1 block text-sm font-semibold">Nama lengkap</label>
                    <input id="name" name="name" type="text" required
                           placeholder="Nama lengkap"
                           autocomplete="name"
                           class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
                    <input id="email" name="email" type="email" required
                           placeholder="email@contoh.id"
                           autocomplete="email"
                           class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-semibold">
                        Nomor telepon (opsional)
                    </label>
                    <input id="phone" name="phone" type="tel"
                           placeholder="08xx-xxxx-xxxx"
                           autocomplete="tel"
                           class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                </div>

                <!-- INFORMASI USAHA -->
                <div class="space-y-4 rounded-2xl border border-[#E7DCEF] bg-[#F6F1FA] p-4">

                    <h3 class="text-sm font-bold text-[#5B2887]">
                        Khusus vendor &amp; pemilik venue
                    </h3>

                    <div>
                        <label for="business_name" class="mb-1 block text-sm font-semibold">
                            Nama usaha
                        </label>
                        <input id="business_name" name="business_name" type="text"
                               required placeholder="Dapur Bu Rina"
                               class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                    </div>

                    <div>
                        <label for="business_category" class="mb-1 block text-sm font-semibold">
                            Kategori usaha
                        </label>
                        <select id="business_category" name="business_category" required
                                class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                            <option value="">Pilih kategori</option>
                            <option value="katering">Katering</option>
                            <option value="dekorasi">Dekorasi</option>
                            <option value="peralatan">Peralatan</option>
                            <option value="hiburan">Hiburan / MC</option>
                            <option value="venue">Venue</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label for="business_address" class="mb-1 block text-sm font-semibold">
                            Alamat usaha
                        </label>
                        <input id="business_address" name="business_address"
                               type="text" required
                               placeholder="Jl. Contoh No. 24, Kediri"
                               class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                    </div>

                </div>

                <!-- PASSWORD -->
                <div>
                    <label for="password" class="mb-1 block text-sm font-semibold">
                        Kata sandi
                    </label>
                    <input id="password" name="password" type="password"
                           minlength="8" required
                           placeholder="Minimal 8 karakter"
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                    <p class="mt-1 text-xs text-[#756B80]">Minimal 8 karakter.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-semibold">
                        Ulangi kata sandi
                    </label>
                    <input id="password_confirmation" name="password_confirmation"
                           type="password" minlength="8" required
                           placeholder="Ulangi kata sandi"
                           autocomplete="new-password"
                           class="w-full rounded-lg border border-[#CEC5D8] bg-white px-4 py-3 text-sm outline-none focus:border-[#5B2887]">
                </div>

                <p id="message" class="hidden text-sm text-red-600" role="alert"></p>

                <button type="submit"
                        class="w-full rounded-full bg-[#5B2887] px-6 py-3 text-sm font-bold text-white hover:bg-[#48206C]">
                    Daftar
                </button>

            </form>

            <p class="mt-5 text-center text-xs text-[#756B80]">
                Sudah punya akun?
                <a href="/login" class="font-bold text-[#5B2887]">Masuk</a>
            </p>

        </div>
    </section>
</main>

<script>
    const category = document.getElementById('business_category');
    const roles = document.querySelectorAll('input[name="role"]');

    function updateCategory() {
        const role = document.querySelector('input[name="role"]:checked').value;

        if (role === 'venue') {
            category.value = 'venue';
        } else if (category.value === 'venue') {
            category.value = '';
        }
    }

    roles.forEach(role => {
        role.addEventListener('change', updateCategory);
    });

    document.getElementById('vendorForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const password = document.getElementById('password').value;
        const confirmation = document.getElementById('password_confirmation').value;
        const message = document.getElementById('message');

        message.classList.remove('hidden');

        if (password !== confirmation) {
            message.textContent = 'Konfirmasi kata sandi tidak sama.';
            return;
        }

        message.classList.remove('text-red-600');
        message.classList.add('text-[#5B2887]');
        message.textContent = 'Tampilan pendaftaran siap. Backend belum dihubungkan.';
    });

    updateCategory();
</script>

</body>
</html>
