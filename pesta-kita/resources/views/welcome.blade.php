
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesta Kita | Rencanakan Pesta Ulang Tahun</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white font-sans text-[#211631]">

    <!-- NAVBAR -->
    <header class="border-b border-[#EEE9F3] bg-white">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">

            <a href="/" class="text-lg font-extrabold text-[#5B2887]">
                Pesta Kita
            </a>

            <div class="flex items-center gap-5 text-sm">
                <a href="/login"
                   class="font-medium text-[#211631] hover:text-[#5B2887]">
                    Masuk
                </a>

                <a href="/register"
                   class="rounded-full bg-[#5B2887] px-5 py-2.5 font-semibold text-white hover:bg-[#48206C]">
                    Daftar
                </a>
            </div>

        </nav>
    </header>

    <main>

        <!-- HERO SECTION -->
        <section class="bg-[#FCFAFE]">
            <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 py-20 lg:grid-cols-2">

                <!-- TEKS KIRI -->
                <div>

                    <span class="inline-block rounded-full bg-[#F8B936] px-4 py-1.5 text-xs font-semibold">
                        Untuk pesta ulang tahun
                    </span>

                    <h1 class="mt-5 max-w-xl text-4xl font-extrabold leading-[1.12] tracking-tight md:text-5xl">
                        Rencanakan pesta ulang tahun dari satu tempat.
                    </h1>

                    <p class="mt-6 max-w-lg text-sm leading-7 text-[#756B80]">
                        Susun konsep, pesan katering sampai sound system,
                        dan bagikan rundown ke semua penyedia.
                        Jadwal bentrok dicek otomatis, tanpa grup chat
                        yang berantakan.
                    </p>

                    <div class="mt-7 flex flex-wrap items-center gap-4">

                        <a href="/register"
                           class="rounded-full bg-[#5B2887] px-7 py-3 text-sm font-bold text-white hover:bg-[#48206C]">
                            Mulai rencanakan acara
                        </a>

                        <p class="text-xs text-[#756B80]">
                            Sudah punya akun?
                            <a href="/login"
                               class="font-bold text-[#5B2887] underline">
                                Masuk
                            </a>
                        </p>

                    </div>
                </div>

                <!-- CONTOH RUNDOWN KANAN -->
                <div class="rounded-2xl border border-[#EEE9F3] bg-white p-6 shadow-xl shadow-[#5B2887]/10">

                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">

                        <div>
                            <p class="text-xs font-medium text-[#756B80]">
                                Contoh tampilan rundown acara
                            </p>

                            <h2 class="mt-1 text-lg font-extrabold">
                                Contoh: Ulang Tahun Nadia
                            </h2>
                        </div>

                        <span class="rounded-full bg-[#D9F8E8] px-3 py-1.5 text-xs font-semibold text-[#16804A]">
                            ✓ Tidak ada jadwal bentrok
                        </span>

                    </div>

                    <div class="space-y-3">

                        <div class="flex gap-5 rounded-xl bg-[#F3EEF9] p-4">
                            <span class="text-sm font-bold">15.00</span>

                            <div>
                                <h3 class="text-sm font-bold">
                                    Dekorasi tiba
                                </h3>

                                <p class="mt-1 text-xs text-[#756B80]">
                                    Vendor dekor, aula utama
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-5 rounded-xl bg-[#F3EEF9] p-4">
                            <span class="text-sm font-bold">16.30</span>

                            <div>
                                <h3 class="text-sm font-bold">
                                    Katering dan sound system siap
                                </h3>

                                <p class="mt-1 text-xs text-[#756B80]">
                                    2 vendor, sudah dibayar
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-5 rounded-xl bg-[#F3EEF9] p-4">
                            <span class="text-sm font-bold">17.30</span>

                            <div>
                                <h3 class="text-sm font-bold">
                                    Tiup lilin
                                </h3>

                                <p class="mt-1 text-xs text-[#756B80]">
                                    Dibagikan ke semua penyedia
                                </p>
                            </div>
                        </div>

                    </div>

                    <p class="mt-4 text-xs text-[#756B80]">
                        * Ilustrasi fitur, bukan data pengguna yang sedang login.
                    </p>

                </div>

            </div>
        </section>

        <!-- DARI IDE SAMPAI HARI-H -->
        <section class="mx-auto max-w-6xl px-6 py-20">

            <h2 class="mb-9 text-3xl font-extrabold">
                Dari ide sampai hari-H
            </h2>

            <div class="grid gap-5 md:grid-cols-3">

                <!-- LANGKAH 1 -->
                <article class="rounded-2xl border border-[#EEE9F3] bg-[#FCFAFE] p-6">

                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#5B2887] font-bold text-white">
                        1
                    </span>

                    <h3 class="mt-5 font-bold">
                        Rancang &amp; pesan
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-[#756B80]">
                        Tulis konsep acara, pilih vendor, venue beserta
                        layout ruangan, dan semua peralatan.
                        Sistem mengecek ketersediaan otomatis.
                    </p>

                </article>

                <!-- LANGKAH 2 -->
                <article class="rounded-2xl border border-[#EEE9F3] bg-[#FCFAFE] p-6">

                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#5B2887] font-bold text-white">
                        2
                    </span>

                    <h3 class="mt-5 font-bold">
                        Bayar &amp; susun rundown
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-[#756B80]">
                        Tagihan terbit begitu pesanan diterima.
                        Unggah bukti bayar, lalu atur segmen acara
                        yang bisa dibaca penyedia.
                    </p>

                </article>

                <!-- LANGKAH 3 -->
                <article class="rounded-2xl border border-[#EEE9F3] bg-[#FCFAFE] p-6">

                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#5B2887] font-bold text-white">
                        3
                    </span>

                    <h3 class="mt-5 font-bold">
                        Evaluasi &amp; riwayat
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-[#756B80]">
                        Setelah hari-H, beri penilaian dan lihat
                        riwayat semua acara Anda.
                    </p>

                </article>

            </div>
        </section>

        <!-- VENDOR DAN VENUE -->
        <section class="bg-[#FCFAFE]">

            <div class="mx-auto max-w-6xl px-6 py-20">

                <h2 class="text-3xl font-extrabold">
                    Vendor dan venue siap pesan
                </h2>

                <p class="mt-3 text-sm text-[#756B80]">
                    Contoh tampilan. Ketersediaan dicek otomatis untuk tanggal acara Anda.
                </p>

                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    <!-- VENUE -->
                    <article class="rounded-2xl border border-[#EEE9F3] bg-white p-5">

                        <span class="rounded-full bg-[#F3EEF9] px-3 py-1 text-xs font-semibold text-[#5B2887]">
                            Venue
                        </span>

                        <h3 class="mt-4 font-bold">
                            Aula serbaguna
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-[#756B80]">
                            Layout ruangan bisa diatur sesuai jumlah tamu.
                        </p>

                        <span class="mt-4 inline-block rounded-full bg-[#D9F8E8] px-3 py-1 text-xs font-semibold text-[#16804A]">
                            Tersedia di tanggal acara
                        </span>

                    </article>

                    <!-- KATERING -->
                    <article class="rounded-2xl border border-[#EEE9F3] bg-white p-5">

                        <span class="rounded-full bg-[#F3EEF9] px-3 py-1 text-xs font-semibold text-[#5B2887]">
                            Katering
                        </span>

                        <h3 class="mt-4 font-bold">
                            Katering prasmanan
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-[#756B80]">
                            Paket menu sesuai jumlah tamu.
                        </p>

                        <span class="mt-4 inline-block rounded-full bg-[#D9F8E8] px-3 py-1 text-xs font-semibold text-[#16804A]">
                            Tersedia di tanggal acara
                        </span>

                    </article>

                    <!-- DEKORASI -->
                    <article class="rounded-2xl border border-[#EEE9F3] bg-white p-5">

                        <span class="rounded-full bg-[#F3EEF9] px-3 py-1 text-xs font-semibold text-[#5B2887]">
                            Dekorasi
                        </span>

                        <h3 class="mt-4 font-bold">
                            Dekorasi balon dan backdrop
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-[#756B80]">
                            Tema mengikuti konsep acara.
                        </p>

                        <span class="mt-4 inline-block rounded-full bg-[#D9F8E8] px-3 py-1 text-xs font-semibold text-[#16804A]">
                            Tersedia di tanggal acara
                        </span>

                    </article>

                    <!-- PERALATAN -->
                    <article class="rounded-2xl border border-[#EEE9F3] bg-white p-5">

                        <span class="rounded-full bg-[#F3EEF9] px-3 py-1 text-xs font-semibold text-[#5B2887]">
                            Peralatan
                        </span>

                        <h3 class="mt-4 font-bold">
                            Sound system dan MC
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-[#756B80]">
                            Sewa per jam, jadwal dicek otomatis.
                        </p>

                        <span class="mt-4 inline-block rounded-full bg-[#D9F8E8] px-3 py-1 text-xs font-semibold text-[#16804A]">
                            Tersedia di tanggal acara
                        </span>

                    </article>

                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-6xl px-6 py-14">

            <div class="rounded-3xl bg-[#5B2887] px-6 py-14 text-center">

                <h2 class="text-2xl font-extrabold text-white md:text-3xl">
                    Mulai dari konsep pesta Anda.
                </h2>

                <a href="/register"
                   class="mt-6 inline-block rounded-full bg-[#F8B936] px-7 py-3 text-sm font-bold text-[#211631] hover:bg-[#F5AD17]">
                    Buat akun gratis
                </a>

            </div>

        </section>

    </main>

    <!-- FOOTER -->
    <footer class="border-t border-[#EEE9F3]">

        <div class="mx-auto max-w-6xl px-6 py-6 text-xs text-[#756B80]">
            © 2026 Pesta Kita
        </div>

    </footer>

</body>
</html>
