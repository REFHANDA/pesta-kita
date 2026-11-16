@extends('layouts.guest')

@section('title', 'Pesta Kita — Rencanakan Pesta Ulang Tahun dari Satu Tempat')

@section('content')
<div class="landing-page">
    <header class="landing-nav">
        <a class="lk-brand" href="{{ url('/') }}">
            <span class="lk-logo" aria-hidden="true">P</span>
            <b>Pesta Kita</b>
        </a>
        <div class="landing-nav-actions">
            <a href="{{ url('/login') }}" class="lk-btn lk-btn-o">Masuk</a>
            <a href="{{ url('/register') }}" class="lk-btn">Daftar Akun</a>
        </div>
    </header>

    <main id="konten" tabindex="-1">
        {{-- Hero: ajakan utama + contoh rundown nyata --}}
        <section class="landing-hero">
            <span class="hero-shape s1" aria-hidden="true"></span>
            <span class="hero-shape s2" aria-hidden="true"></span>
            <span class="hero-shape s3" aria-hidden="true"></span>
            <span class="hero-shape s4" aria-hidden="true"></span>

            <div>
                <span class="landing-badge lp-rise"><span class="dot" aria-hidden="true"></span>Platform Rancang Pesta Terintegrasi</span>
                <h1 class="hero-title lp-rise lp-d1">Rencanakan pesta ulang tahun <em>dari satu tempat.</em></h1>
                <p class="hero-desc lp-rise lp-d2">Susun konsep, pesan katering sampai sound system, dan bagikan rundown ke semua penyedia. Jadwal bentrok dicek otomatis, tanpa grup chat yang berantakan.</p>
                <div class="hero-actions lp-rise lp-d3">
                    <a href="{{ url('/register') }}" class="lk-btn lk-btn-lg">Mulai Sekarang</a>
                    <span class="hero-login-hint">Sudah punya akun? <a href="{{ url('/login') }}">Masuk</a></span>
                </div>
            </div>

            <div class="hero-rundown-card lp-rise lp-d4">
                <div class="rundown-header">
                    <div>
                        <span class="eyebrow">Contoh rundown</span>
                        <h3>Ulang tahun Nadia, Sabtu</h3>
                    </div>
                    <span class="rundown-ok">Terkonfirmasi</span>
                </div>

                <div class="rundown-item">
                    <span class="time-pill">15.00</span>
                    <div>
                        <b>Dekorasi tiba</b>
                        <small>Vendor dekor, aula utama</small>
                    </div>
                </div>
                <div class="rundown-item">
                    <span class="time-pill">16.30</span>
                    <div>
                        <b>Katering dan sound system siap</b>
                        <small>2 vendor, sudah dibayar</small>
                    </div>
                </div>
                <div class="rundown-item">
                    <span class="time-pill">17.30</span>
                    <div>
                        <b>Tiup lilin</b>
                        <small>Dibagikan ke semua penyedia</small>
                    </div>
                </div>
            </div>
        </section>

        {{-- Tiga langkah --}}
        <section class="section-wrap section-white">
            <div class="section-head reveal">
                <span class="eyebrow">Cara Kerja</span>
                <h2>Dari ide sampai hari-H</h2>
                <p>Tiga langkah praktis menyelenggarakan pesta ulang tahun tanpa pusing.</p>
            </div>

            <div class="steps-grid">
                <div class="step-card reveal">
                    <div class="step-num" aria-hidden="true">1</div>
                    <h3>Rancang &amp; pesan</h3>
                    <p>Tulis konsep acara, pilih vendor, venue beserta layout ruangan, dan sewa peralatan. Sistem mengecek ketersediaan otomatis.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-num" aria-hidden="true">2</div>
                    <h3>Bayar &amp; susun rundown</h3>
                    <p>Tagihan terbit begitu pesanan diterima. Unggah bukti bayar, lalu atur segmen acara yang bisa dibaca penyedia.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-num" aria-hidden="true">3</div>
                    <h3>Evaluasi &amp; riwayat</h3>
                    <p>Setelah hari-H, beri penilaian dan lihat riwayat semua acara Anda. Rekam jejak transparan untuk pesta berikutnya.</p>
                </div>
            </div>
        </section>

        {{-- Contoh katalog --}}
        <section class="section-wrap">
            <div class="section-head reveal">
                <span class="eyebrow">Layanan Siap Pakai</span>
                <h2>Vendor dan venue siap pesan</h2>
                <p>Contoh tampilan. Ketersediaan dicek otomatis untuk tanggal acara Anda.</p>
            </div>

            <div class="catalog-grid">
                <div class="catalog-card reveal">
                    <div class="cat-img" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6"/><path d="M9 11h.01M15 11h.01"/></svg>
                    </div>
                    <div class="cat-body">
                        <h3>Aula serbaguna</h3>
                        <p>Layout ruangan bisa diatur sesuai jumlah tamu.</p>
                        <span class="cat-tag">Venue</span>
                    </div>
                </div>
                <div class="catalog-card reveal">
                    <div class="cat-img" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18h18"/><path d="M5 18a7 7 0 0 1 14 0"/><path d="M12 11V9"/><circle cx="12" cy="7.5" r="1.2"/><path d="M4 21h16"/></svg>
                    </div>
                    <div class="cat-body">
                        <h3>Katering prasmanan</h3>
                        <p>Paket menu sesuai jumlah tamu.</p>
                        <span class="cat-tag">Katering</span>
                    </div>
                </div>
                <div class="catalog-card reveal">
                    <div class="cat-img" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c2.5 0 4.5 2 4.5 4.7 0 2.6-2 5.3-4.5 5.3S7.5 10.3 7.5 7.7C7.5 5 9.5 3 12 3Z"/><path d="M12 13v2"/><path d="M10.5 17.5 12 15l1.5 2.5-1.5 3.5-1.5-3.5Z"/></svg>
                    </div>
                    <div class="cat-body">
                        <h3>Dekorasi balon &amp; backdrop</h3>
                        <p>Tema mengikuti konsep acara.</p>
                        <span class="cat-tag">Dekorasi</span>
                    </div>
                </div>
                <div class="catalog-card reveal">
                    <div class="cat-img" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="3" width="12" height="18" rx="2"/><circle cx="12" cy="14.5" r="3.2"/><circle cx="12" cy="7.5" r="1.4"/></svg>
                    </div>
                    <div class="cat-body">
                        <h3>Sound system &amp; MC</h3>
                        <p>Sewa per jam, jadwal dicek otomatis.</p>
                        <span class="cat-tag">Hiburan</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Empat portal --}}
        <section class="section-wrap" style="padding-top:0">
            <div class="portal-banner reveal">
                <h2>Eksplorasi 4 portal pengguna Pesta Kita</h2>
                <p>Dirancang terpisah dan presisi sesuai perancangan sistem: Klien, Vendor Layanan, Pemilik Venue, dan Admin Perusahaan.</p>
                <div class="portal-grid">
                    <a href="{{ url('/klien') }}" class="portal-card">
                        <h3>Portal Klien</h3>
                        <p>Susun konsep acara, denah ruangan, pilih vendor &amp; venue, bayar 1 tagihan gabungan, dan susun rundown.</p>
                        <span>Buka Portal Klien &rarr;</span>
                    </a>
                    <a href="{{ url('/vendor') }}" class="portal-card">
                        <h3>Panel Vendor</h3>
                        <p>Kelola paket menu katering, dekorasi, sewa peralatan, dan isi rundown miliknya.</p>
                        <span>Buka Panel Vendor &rarr;</span>
                    </a>
                    <a href="{{ url('/venue') }}" class="portal-card">
                        <h3>Pemilik Venue</h3>
                        <p>Kelola spesifikasi gedung, kapasitas tamu, tinjau pesanan venue, dan pantau rundown.</p>
                        <span>Buka Panel Venue &rarr;</span>
                    </a>
                    <a href="{{ url('/admin') }}" class="portal-card">
                        <h3>Panel Admin</h3>
                        <p>Verifikasi mitra, tinjau transaksi, mediasi keberatan, dan pantau operasional platform.</p>
                        <span>Buka Panel Admin &rarr;</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- CTA bawah --}}
        <section class="section-wrap" style="padding-top:0">
            <div class="cta-banner reveal">
                <h2>Mulai dari konsep pesta Anda.</h2>
                <p>Wujudkan pesta ulang tahun impian yang tertata rapi, transparan, dan berkesan.</p>
                <div class="cta-actions">
                    <a href="{{ url('/register') }}" class="lk-btn lk-btn-lg lk-btn-white">Daftar Akun Gratis</a>
                    <a href="{{ url('/login') }}" class="lk-btn lk-btn-lg lk-btn-ow">Masuk</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <span>&copy; 2026 Pesta Kita. Seluruh hak cipta dilindungi.</span>
        <nav aria-label="Tautan portal">
            <a href="{{ url('/klien') }}">Klien</a>
            <a href="{{ url('/vendor') }}">Vendor</a>
            <a href="{{ url('/venue') }}">Venue</a>
            <a href="{{ url('/admin') }}">Admin</a>
        </nav>
    </footer>
</div>
@endsection

@push('scripts')
<script>
// Reveal bertahap saat scroll; tanpa JS seluruh konten tetap terlihat.
(function () {
    if (!('IntersectionObserver' in window)) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.documentElement.classList.add('js-reveal');
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.reveal').forEach((el, i) => {
        el.style.transitionDelay = (Math.min(i % 4, 3) * 0.08) + 's';
        io.observe(el);
    });
})();
</script>
@endpush