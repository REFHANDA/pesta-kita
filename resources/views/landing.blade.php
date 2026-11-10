@extends('layouts.guest')

@section('title', 'Pesta Kita — Rencanakan Pesta Ulang Tahun dari Satu Tempat')

@section('content')
<header class="landing-nav">
    <a class="brand" href="{{ url('/') }}" style="padding:0">
        <span class="logo" aria-hidden="true"></span>
        <span><b>Pesta Kita</b><small>Rencanakan pesta ulang tahun</small></span>
    </a>
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ url('/login') }}" class="btn btn-o" style="min-height:40px;padding:0 18px">Masuk</a>
        <a href="{{ url('/register') }}" class="btn" style="min-height:40px;padding:0 20px">Daftar Akun</a>
    </div>
</header>

<main id="konten" tabindex="-1">
    <!-- Hero Section -->
    <section class="landing-hero">
        <div>
            <div class="landing-badge">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--accent)"></span>
                Platform Rancang Pesta Terintegrasi
            </div>
            <h1 class="hero-title">Rencanakan pesta ulang tahun dari satu tempat.</h1>
            <p class="hero-desc">Susun konsep, pesan katering sampai sound system, dan bagikan rundown ke semua penyedia. Jadwal bentrok dicek otomatis, tanpa grup chat yang berantakan.</p>
            <div class="hero-actions">
                <a href="{{ url('/register') }}" class="btn btn-lg">Mulai Sekarang</a>
                <a href="{{ url('/klien') }}" class="btn btn-o btn-lg">Coba Pratinjau Klien</a>
            </div>
        </div>

        <div class="hero-rundown-card">
            <div class="rundown-header">
                <div>
                    <span style="font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:var(--muted);font-weight:700">Contoh rundown</span>
                    <h3 style="font-size:20px;font-weight:800;color:var(--ink);margin-top:2px">Ulang tahun Nadia, Sabtu</h3>
                </div>
                <span class="badge b-ok">Terkonfirmasi</span>
            </div>

            <div class="rundown-item">
                <div class="time-pill">15.00</div>
                <div>
                    <b style="font-size:16px;color:var(--ink);display:block">Dekorasi tiba</b>
                    <span style="color:var(--muted);font-size:14px">Vendor dekor · Aula utama</span>
                </div>
            </div>

            <div class="rundown-item">
                <div class="time-pill">16.30</div>
                <div>
                    <b style="font-size:16px;color:var(--ink);display:block">Katering dan sound system siap</b>
                    <span style="color:var(--muted);font-size:14px">2 vendor · Pembayaran lunas</span>
                </div>
            </div>

            <div class="rundown-item">
                <div class="time-pill">17.30</div>
                <div>
                    <b style="font-size:16px;color:var(--ink);display:block">Tiup lilin</b>
                    <span style="color:var(--muted);font-size:14px">Dibagikan ke semua penyedia</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Portal Switcher Section (4 Aktor Sesuai Rancangan) -->
    <section class="section-wrap">
        <div class="portal-switch-banner">
            <h2>Eksplorasi 4 Portal Pengguna Pesta Kita</h2>
            <p style="opacity:0.9;font-size:16px;max-width:760px;margin:0 auto">Dirancang terpisah dan presisi sesuai perancangan sistem: Klien, Vendor Layanan, Pemilik Venue, dan Admin Perusahaan.</p>
            
            <div class="portal-switch-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">
                <a href="{{ url('/klien') }}" class="portal-switch-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                        <span class="ic-box" style="background:rgba(255,255,255,0.2);color:#ffffff"><span class="ic i-users"></span></span>
                        <h3 style="font-size:18px;font-weight:800;margin:0">Portal Klien</h3>
                    </div>
                    <p style="font-size:13.5px;opacity:0.88;line-height:1.5;margin:0 0 14px">Susun konsep acara, denah ruangan, pilih vendor &amp; venue, bayar 1 tagihan gabungan, dan susun rundown.</p>
                    <span style="font-weight:700;font-size:13px;text-decoration:underline">Buka Portal Klien &rarr;</span>
                </a>

                <a href="{{ url('/vendor') }}" class="portal-switch-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                        <span class="ic-box" style="background:rgba(255,255,255,0.2);color:#ffffff"><span class="ic i-box"></span></span>
                        <h3 style="font-size:18px;font-weight:800;margin:0">Panel Vendor</h3>
                    </div>
                    <p style="font-size:13.5px;opacity:0.88;line-height:1.5;margin:0 0 14px">Kelola paket menu katering, dekorasi, sewa peralatan, masa keberatan 24 jam, dan isi rundown miliknya.</p>
                    <span style="font-weight:700;font-size:13px;text-decoration:underline">Buka Panel Vendor &rarr;</span>
                </a>

                <a href="{{ url('/venue') }}" class="portal-switch-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                        <span class="ic-box" style="background:rgba(255,255,255,0.2);color:#ffffff"><span class="ic i-bank"></span></span>
                        <h3 style="font-size:18px;font-weight:800;margin:0">Pemilik Venue</h3>
                    </div>
                    <p style="font-size:13.5px;opacity:0.88;line-height:1.5;margin:0 0 14px">Kelola spesifikasi gedung, kapasitas tamu, tinjau pesanan venue, pantau rundown &amp; pencairan dana.</p>
                    <span style="font-weight:700;font-size:13px;text-decoration:underline">Buka Panel Venue &rarr;</span>
                </a>

                <a href="{{ url('/admin') }}" class="portal-switch-card">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                        <span class="ic-box" style="background:rgba(255,255,255,0.2);color:#ffffff"><span class="ic i-cog"></span></span>
                        <h3 style="font-size:18px;font-weight:800;margin:0">Panel Admin</h3>
                    </div>
                    <p style="font-size:13.5px;opacity:0.88;line-height:1.5;margin:0 0 14px">Review pesanan, validasi bukti transfer klien, mediasi keberatan, kelola katalog, dan log aktivitas.</p>
                    <span style="font-weight:700;font-size:13px;text-decoration:underline">Buka Panel Admin &rarr;</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 3 Langkah Section -->
    <section class="section-wrap" style="background:#ffffff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
        <div class="section-center-head">
            <span style="color:var(--brand);font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:0.06em">Cara Kerja</span>
            <h2>Dari ide sampai hari-H</h2>
            <p>Tiga langkah praktis menyelenggarakan pesta ulang tahun tanpa pusing.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <h3 style="font-size:20px;font-weight:800;margin-bottom:10px">Rancang &amp; pesan</h3>
                <p style="color:var(--muted);line-height:1.6;font-size:15px;margin:0">Tulis konsep acara, pilih vendor, venue beserta layout ruangan, dan sewa peralatan. Sistem mengecek ketersediaan otomatis.</p>
            </div>

            <div class="step-card">
                <div class="step-num">2</div>
                <h3 style="font-size:20px;font-weight:800;margin-bottom:10px">Bayar &amp; susun rundown</h3>
                <p style="color:var(--muted);line-height:1.6;font-size:15px;margin:0">Tagihan terbit begitu pesanan diterima. Unggah bukti bayar, lalu atur segmen acara yang bisa dibaca penyedia.</p>
            </div>

            <div class="step-card">
                <div class="step-num">3</div>
                <h3 style="font-size:20px;font-weight:800;margin-bottom:10px">Evaluasi &amp; riwayat</h3>
                <p style="color:var(--muted);line-height:1.6;font-size:15px;margin:0">Setelah hari-H, beri penilaian dan lihat riwayat semua acara Anda. Rekam jejak transparan untuk pesta berikutnya.</p>
            </div>
        </div>
    </section>

    <!-- Catalog Showcase -->
    <section class="section-wrap">
        <div class="section-center-head">
            <span style="color:var(--brand);font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:0.06em">Layanan Siap Pakai</span>
            <h2>Vendor dan venue siap pesan</h2>
            <p>Contoh tampilan. Ketersediaan dicek otomatis untuk tanggal acara Anda.</p>
        </div>

        <div class="catalog-grid">
            <div class="catalog-card">
                <div class="cat-img">
                    <span class="ic i-bank" style="width:48px;height:48px"></span>
                </div>
                <div class="cat-body">
                    <h3>Aula serbaguna</h3>
                    <p>Layout ruangan bisa diatur sesuai jumlah tamu dan panggung pertunjukan.</p>
                    <div style="margin-top:auto;padding-top:14px">
                        <span class="badge b-brand">Venue</span>
                    </div>
                </div>
            </div>

            <div class="catalog-card">
                <div class="cat-img">
                    <span class="ic i-box" style="width:48px;height:48px"></span>
                </div>
                <div class="cat-body">
                    <h3>Katering prasmanan</h3>
                    <p>Paket menu variatif disesuaikan dengan kebutuhan dan selera tamu undangan.</p>
                    <div style="margin-top:auto;padding-top:14px">
                        <span class="badge b-brand">Katering</span>
                    </div>
                </div>
            </div>

            <div class="catalog-card">
                <div class="cat-img">
                    <span class="ic i-star" style="width:48px;height:48px"></span>
                </div>
                <div class="cat-body">
                    <h3>Dekorasi balon &amp; backdrop</h3>
                    <p>Tema dekorasi tematik mengikuti konsep impian perayaan ulang tahun.</p>
                    <div style="margin-top:auto;padding-top:14px">
                        <span class="badge b-brand">Dekorasi</span>
                    </div>
                </div>
            </div>

            <div class="catalog-card">
                <div class="cat-img">
                    <span class="ic i-clip" style="width:48px;height:48px"></span>
                </div>
                <div class="cat-body">
                    <h3>Sound system &amp; MC</h3>
                    <p>Sewa peralatan audio dan pembawa acara profesional per jam terverifikasi.</p>
                    <div style="margin-top:auto;padding-top:14px">
                        <span class="badge b-brand">Hiburan</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom CTA -->
    <section class="section-wrap" style="padding-top:0">
        <div style="background:#ffffff;border:1px solid var(--line);border-radius:24px;padding:60px 40px;text-align:center">
            <h2 style="font-size:36px;font-weight:800;color:var(--ink);margin-bottom:12px">Mulai dari konsep pesta Anda.</h2>
            <p style="color:var(--muted);font-size:18px;max-width:540px;margin:0 auto 30px">Wujudkan pesta ulang tahun impian yang tertata rapi, transparan, dan berkesan.</p>
            <div style="display:flex;justify-content:center;gap:14px;flex-wrap:wrap">
                <a href="{{ url('/register') }}" class="btn btn-lg">Daftar Akun Gratis</a>
                <a href="{{ url('/login') }}" class="btn btn-o btn-lg">Masuk</a>
            </div>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div style="display:flex;align-items:center;gap:10px">
        <span class="logo" aria-hidden="true" style="width:30px;height:30px"></span>
        <b style="font-family:'Bricolage Grotesque',sans-serif;font-size:18px;color:var(--brand)">Pesta Kita</b>
        <span style="color:var(--muted);font-size:14px;margin-left:10px">&copy; 2026 Pesta Kita. Seluruh hak cipta dilindungi.</span>
    </div>
    <div style="display:flex;gap:20px;font-size:14px">
        <a href="{{ url('/klien') }}" style="color:var(--muted);text-decoration:none">Klien</a>
        <a href="{{ url('/vendor') }}" style="color:var(--muted);text-decoration:none">Vendor</a>
        <a href="{{ url('/venue') }}" style="color:var(--muted);text-decoration:none">Venue</a>
        <a href="{{ url('/admin') }}" style="color:var(--muted);text-decoration:none">Admin</a>
    </div>
</footer>
@endsection
