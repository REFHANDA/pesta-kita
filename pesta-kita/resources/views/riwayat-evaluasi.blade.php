
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Acara | Pesta Kita</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --purple: #5B2887;
            --dark: #211733;
            --yellow: #F8B936;
            --background: #FAF8FD;
            --border: #E9E1F0;
            --muted: #777087;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--background);
            color: var(--dark);
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            background: var(--dark);
            color: white;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            padding: 25px 12px 15px;
            z-index: 10;
        }

        .brand {
            padding: 0 15px 25px;
        }

        .brand h2 {
            font-size: 21px;
            font-weight: 800;
        }

        .brand span {
            font-size: 11px;
            color: #c9bfd3;
            font-style: italic;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            text-decoration: none;
            color: #ddd4e5;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-style: italic;
            transition: .2s;
        }

        .menu a:hover {
            background: #49345e;
            color: white;
        }

        .menu a.active {
            background: var(--yellow);
            color: var(--dark);
        }

        .profile {
            margin-top: auto;
            border-top: 1px solid #443650;
            padding: 17px 5px 0;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #493957;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .profile-info strong {
            font-size: 12px;
        }

        .profile-info small {
            display: block;
            font-size: 10px;
            color: #c9bfd3;
        }

        .logout {
            display: block;
            text-align: center;
            color: white;
            border: 1px solid #9b8ba9;
            padding: 9px;
            border-radius: 25px;
            font-size: 12px;
            text-decoration: none;
        }

        /* MAIN */
        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            min-height: 100vh;
        }

        .topbar {
            height: 75px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar h3 {
            font-size: 19px;
            font-weight: 800;
        }

        .notification {
            border: 1px solid #9278a7;
            border-radius: 30px;
            padding: 9px 17px;
            font-size: 12px;
            font-weight: 700;
            background: white;
        }

        .notification span {
            background: var(--yellow);
            padding: 4px 9px;
            border-radius: 20px;
            margin-left: 8px;
        }

        /* CONTENT */
        .content {
            padding: 20px 30px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        /* TABS */
        .tabs {
            display: inline-flex;
            align-items: center;
            padding: 3px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: white;
        }

        .tab {
            text-decoration: none;
            color: var(--dark);
            padding: 9px 18px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
        }

        .tab.active {
            background: var(--purple);
            color: white;
        }

        /* BUTTON */
        .create-button {
            background: var(--purple);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 11px 20px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .create-button:hover {
            background: #472068;
        }

        /* EVENT GRID */
        .event-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .event-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 17px;
            padding: 17px;
            min-height: 150px;
        }

        .status {
            display: inline-block;
            background: #D5F6E7;
            color: #16754D;
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .event-card h4 {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .event-card p {
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .event-action {
            display: inline-block;
            text-decoration: none;
            color: var(--dark);
            border: 1px solid #9278a7;
            padding: 7px 13px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
        }

        .event-action:hover {
            background: var(--purple);
            color: white;
        }

        .empty-state {
            grid-column: 1 / -1;
            background: white;
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        /* RESPONSIVE */
        @media(max-width: 900px) {
            .event-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {
            .sidebar {
                width: 75px;
                padding: 20px 7px;
            }

            .brand {
                padding: 0 0 25px;
                text-align: center;
            }

            .brand h2 {
                font-size: 11px;
            }

            .brand span,
            .menu a span,
            .profile-info div,
            .logout {
                display: none;
            }

            .menu a {
                text-align: center;
                font-size: 0;
                padding: 13px 5px;
            }

            .menu a::first-letter {
                font-size: 17px;
            }

            .profile-info {
                justify-content: center;
            }

            .main {
                margin-left: 75px;
                width: calc(100% - 75px);
            }

            .topbar {
                height: 65px;
                padding: 0 12px;
            }

            .topbar h3 {
                font-size: 13px;
            }

            .notification {
                font-size: 10px;
                padding: 7px;
            }

            .content {
                padding: 15px 12px;
            }

            .toolbar {
                flex-wrap: wrap;
            }

            .event-card h4 {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

@php
    $user = auth()->user();

    $nama = $user->name ?? 'Nama Klien';
    $email = $user->email ?? 'Email belum tersedia';
    $inisial = strtoupper(substr($nama, 0, 1));

    // Data contoh untuk preview.
    // Nantinya ganti dengan data riwayat dari database.
    $riwayatAcara = $riwayatAcara ?? [
        [
            'nama' => 'Syukuran Ulang Tahun',
            'tanggal' => '3 Oktober 2026',
            'jumlah_tamu' => 50,
            'tema' => 'Rustic',
            'pesanan' => 4,
            'evaluasi' => '4/5',
        ],
        [
            'nama' => 'Ulang Tahun ke-6',
            'tanggal' => '11 Oktober 2025',
            'jumlah_tamu' => 35,
            'tema' => 'Unicorn',
            'pesanan' => 3,
            'evaluasi' => null,
        ],
    ];
@endphp

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <h2>Pesta Kita</h2>
            <span>Klien</span>
        </div>

        <nav class="menu">
            <a href="{{ route('klien.dashboard') }}">⌂ <span>Beranda</span></a>
            <a href="{{ route('klien.rancang') }}">✎ <span>Rancang acara</span></a>
            <a href="#">♙ <span>Vendor & venue</span></a>
            <a href="#">▣ <span>Sewa peralatan</span></a>
            <a href="#">▤ <span>Pesanan & tagihan</span></a>
            <a href="#" class="active">◷ <span>Riwayat & evaluasi</span></a>
            <a href="#">⚙ <span>Profil saya</span></a>
        </nav>

        <div class="profile">

            <div class="profile-info">
                <div class="avatar">{{ $inisial }}</div>

                <div>
                    <strong>{{ $nama }}</strong>
                    <small>{{ $email }}</small>
                </div>
            </div>

            <a href="{{ route('logout') }}"
               class="logout"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                Keluar
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                @csrf
            </form>

        </div>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <header class="topbar">

            <h3>Riwayat acara saya</h3>

            <button class="notification">
                ♧ Notifikasi <span>3</span>
            </button>

        </header>

        <div class="content">

            <!-- TOOLBAR -->
            <div class="toolbar">

                <div class="tabs">
                    <a href="{{ route('klien.rancang') }}" class="tab">
                        Mendatang
                    </a>

                    <a href="{{ route('klien.riwayat') }}" class="tab active">
                        Riwayat
                    </a>
                </div>

                <a href="{{ route('klien.rancang') }}" class="create-button">
                    Buat konsep acara
                </a>

            </div>

            <!-- RIWAYAT ACARA -->
            <div class="event-grid">

                @forelse($riwayatAcara as $acara)

                    <article class="event-card">

                        <span class="status">Selesai</span>

                        <h4>{{ $acara['nama'] }}</h4>

                        <p>{{ $acara['tanggal'] }}</p>

                        <p>
                            {{ $acara['jumlah_tamu'] }} tamu
                            · {{ $acara['tema'] }}
                            · {{ $acara['pesanan'] }} pesanan
                        </p>

                        @if($acara['evaluasi'])
                            <a href="#" class="event-action">
                                Lihat evaluasi ({{ $acara['evaluasi'] }})
                            </a>
                        @else
                            <a href="#" class="event-action">
                                Isi evaluasi
                            </a>
                        @endif

                    </article>

                @empty

                    <div class="empty-state">
                        Belum ada riwayat acara.
                    </div>

                @endforelse

            </div>

        </div>

    </main>

</div>

</body>
</html>
