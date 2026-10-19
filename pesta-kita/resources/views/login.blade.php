
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Masuk | Pesta Kita</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#FBF9FD] font-sans text-[#211631]">

    <main class="grid min-h-screen lg:grid-cols-2">

        <!-- BAGIAN KIRI -->
        <section class="flex min-h-[340px] flex-col bg-[#5B2887] px-8 py-8 text-white md:px-12 lg:min-h-screen lg:px-16">

            <a href="/" class="text-lg font-extrabold">
                Pesta Kita
            </a>

            <div class="my-auto py-12">

                <span class="inline-block rounded-full bg-[#F8B936] px-4 py-1.5 text-xs font-bold text-[#211631]">
                    Untuk pesta ulang tahun
                </span>

                <h1 class="mt-5 max-w-md text-4xl font-extrabold leading-tight tracking-tight md:text-5xl">
                    Pesta Anda, rapi dari awal.
                </h1>

                <p class="mt-5 max-w-md text-sm leading-7 text-white/75">
                    Konsep, vendor, venue, dan rundown dalam satu tempat.
                    Jadwal bentrok dicek otomatis.
                </p>

            </div>

            <p class="text-xs text-white/60">
                © 2026 Pesta Kita
            </p>

        </section>

        <!-- BAGIAN KANAN -->
        <section class="flex min-h-[550px] items-center justify-center px-6 py-14 md:px-12">

            <div class="w-full max-w-[420px]">

                <h2 class="text-2xl font-extrabold">
                    Masuk ke akun Anda
                </h2>

                <!-- ERROR LOGIN -->
                <div
                    id="loginError"
                    class="mt-5 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                    role="alert"
                    aria-live="polite"
                >
                    ✕ Email atau kata sandi tidak cocok.
                </div>

                <!-- FORM -->
                <form id="loginForm" class="mt-7 space-y-5" novalidate>

                    <!-- EMAIL -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold">
                            Email
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="nama@contoh.id"
                            class="w-full rounded-xl border border-[#CEC5D8] bg-white px-4 py-3.5 text-sm outline-none transition focus:border-[#5B2887] focus:ring-2 focus:ring-[#5B2887]/15"
                        >

                        <p
                            id="emailError"
                            class="mt-2 hidden text-xs text-red-600"
                        >
                            Masukkan alamat email yang valid.
                        </p>
                    </div>

                    <!-- PASSWORD -->
                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold">
                            Kata sandi
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            class="w-full rounded-xl border border-[#CEC5D8] bg-white px-4 py-3.5 text-sm outline-none transition focus:border-[#5B2887] focus:ring-2 focus:ring-[#5B2887]/15"
                        >

                        <p
                            id="passwordError"
                            class="mt-2 hidden text-xs text-red-600"
                        >
                            Kata sandi wajib diisi.
                        </p>
                    </div>

                    <!-- REMEMBER ME -->
                    <label class="flex cursor-pointer items-center gap-2 text-xs text-[#756B80]">
                        <input
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 accent-[#5B2887]"
                        >

                        Ingat saya di perangkat ini
                    </label>

                    <!-- TOMBOL LOGIN -->
                    <button
                        type="submit"
                        class="w-full rounded-full bg-[#5B2887] px-6 py-3.5 text-sm font-bold text-white transition hover:bg-[#48206C]"
                    >
                        Masuk
                    </button>

                </form>

                <!-- LINK DAFTAR -->
                <p class="mt-6 text-center text-xs text-[#756B80]">
                    Belum punya akun?

                    <a href="/register" class="font-bold text-[#5B2887] hover:underline">
                        Daftar
                    </a>
                </p>

                <!-- KEMBALI -->
                <div class="mt-8 text-center">
                    <a href="/" class="text-xs text-[#756B80] hover:text-[#5B2887]">
                        ← Kembali ke beranda
                    </a>
                </div>

            </div>

        </section>

    </main>

    <script>
        const loginForm = document.getElementById('loginForm');

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        const loginError = document.getElementById('loginError');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');

        const normalInputClass =
            'w-full rounded-xl border border-[#CEC5D8] bg-white px-4 py-3.5 text-sm outline-none transition focus:border-[#5B2887] focus:ring-2 focus:ring-[#5B2887]/15';

        const errorInputClass =
            'w-full rounded-xl border border-red-500 bg-white px-4 py-3.5 text-sm outline-none focus:ring-2 focus:ring-red-100';

        loginForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            // Reset tampilan error
            loginError.classList.add('hidden');
            emailError.classList.add('hidden');
            passwordError.classList.add('hidden');

            emailInput.className = normalInputClass;
            passwordInput.className = normalInputClass;

            let hasError = false;

            // Validasi email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email)) {
                emailInput.className = errorInputClass;
                emailError.classList.remove('hidden');
                hasError = true;
            }

            // Validasi password
            if (password.length === 0) {
                passwordInput.className = errorInputClass;
                passwordError.textContent = 'Kata sandi wajib diisi.';
                passwordError.classList.remove('hidden');
                hasError = true;
            }

            if (hasError) {
                return;
            }

            // SIMULASI LOGIN GAGAL SESUAI FIGMA
            // Belum terhubung ke database / autentikasi Laravel

            loginError.classList.remove('hidden');

            passwordInput.className = errorInputClass;

            passwordError.textContent = 'Kata sandi salah.';
            passwordError.classList.remove('hidden');
        });
    </script>

</body>
</html>
