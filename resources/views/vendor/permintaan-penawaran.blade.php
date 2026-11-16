@extends('layouts.vendor')

@section('title', 'Permintaan penawaran · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Permintaan penawaran</div>
            <h1>Permintaan penawaran</h1>
            <p class="sub">Klien mengajukan kebutuhan acara melalui Pesta Kita. Anda dapat mengusulkan harga; Admin meninjau usulan tersebut sebelum penawaran berlaku bagi Klien.</p>
        </div>
    </div>

    <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p><b>Fitur ini masih berupa prototipe frontend.</b> Pengajuan dan perubahan penawaran belum tersimpan ke server. Seluruh nama, harga, dan status di halaman ini adalah contoh statis.</p></div>

    <section class="card">
        <div class="tabs">
            <a href="{{ url('/vendor/permintaan-penawaran') }}" aria-current="page">Semua</a>
            <a href="{{ url('/vendor/permintaan-penawaran') }}?status=baru">Perlu diusulkan</a>
            <a href="{{ url('/vendor/permintaan-penawaran') }}?status=ditinjau">Ditinjau Admin</a>
            <a href="{{ url('/vendor/permintaan-penawaran') }}?status=disetujui">Disetujui</a>
        </div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar permintaan penawaran. Gulir horizontal untuk melihat seluruh kolom">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Daftar permintaan penawaran Klien</caption>
                <thead>
                    <tr>
                        <th scope="col">Permintaan</th>
                        <th scope="col">Acara</th>
                        <th scope="col">Tanggal acara</th>
                        <th scope="col" class="num">Harga katalog (referensi)</th>
                        <th scope="col">Status</th>
                        <th scope="col">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><b>Katering prasmanan</b><span class="sub-t">PW-0044 · 60 tamu · menu bebas kacang</span></td>
                        <td>Pesta Kebun Raka</td>
                        <td>12 Des 2026</td>
                        <td class="num">Rp 2.992.000</td>
                        <td><span class="badge b-warn">Perlu diusulkan</span></td>
                        <td><a class="btn btn-o btn-sm" href="{{ url('/vendor/permintaan-penawaran-detail') }}">Lihat detail</a></td>
                    </tr>
                    <tr>
                        <td><b>Paket nasi box</b><span class="sub-t">PW-0041 · 40 box</span></td>
                        <td>Rapat keluarga Wijaya</td>
                        <td>28 Nov 2026</td>
                        <td class="num">Rp 1.650.000</td>
                        <td><span class="badge b-info">Ditinjau Admin</span></td>
                        <td><a class="btn btn-o btn-sm" href="{{ url('/vendor/permintaan-penawaran-detail') }}">Lihat detail</a></td>
                    </tr>
                    <tr>
                        <td><b>Katering prasmanan</b><span class="sub-t">PW-0038 · 45 tamu</span></td>
                        <td>Ulang Tahun ke-7 Nadia</td>
                        <td>14 Nov 2026</td>
                        <td class="num">Rp 2.992.000</td>
                        <td><span class="badge b-ok">Disetujui</span></td>
                        <td><a class="btn btn-o btn-sm" href="{{ url('/vendor/permintaan-penawaran-detail') }}">Lihat detail</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="alert"><span class="ic i-info" aria-hidden="true"></span><p>Harga yang Anda usulkan belum mengikat sampai Admin menyetujuinya. Komisi layanan Pesta Kita 10% ditambahkan ke tagihan Klien dan tidak dipotong dari harga Anda.</p></div>
    </section>
</main>
@endsection
