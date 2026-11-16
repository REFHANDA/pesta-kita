@extends('layouts.klien')

@section('title', 'Permintaan penawaran · Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@php
$requests = $requests ?? collect();
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');

$statusBadge = fn ($status) => match ($status) {
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Menunggu penawaran'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Ditinjau Admin'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui Admin'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak Admin'],
    default => ['b-mute', '-'],
};

// Nama penyedia berdasarkan subjek permintaan (katalog item / ruangan venue).
$penyediaNama = fn ($row) => match (true) {
    $row->subject instanceof \App\Models\CatalogItem => $row->subject->vendorProfile?->business_name,
    $row->subject instanceof \App\Models\VenueRoom => $row->subject->venue?->venueProfile?->business_name,
    default => null,
};
$tabAktif = (string) request('status', '');
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/katalog-vendor') }}">Vendor &amp; venue</a> / Permintaan penawaran</div>
            <h1>Permintaan penawaran</h1>
            <p class="sub">Permintaan penawaran Anda yang tersimpan. Harga katalog, harga yang diajukan penyedia, dan harga yang disetujui Admin adalah tiga hal yang berbeda.</p>
        </div>
    </div>

    <div class="data-note" role="note">
        <span class="ic i-info" aria-hidden="true"></span>
        <p><b>Sebagian alur belum terhubung.</b> Daftar di bawah dibaca dari basis data permintaan milik akun Anda. Pengajuan permintaan baru dari katalog serta keputusan Admin atas usulan penyedia menyusul pada backend BE-4/BE-5, karena itu tidak ada form pengajuan di halaman ini.</p>
    </div>

    @if(request()->filled('tanggal'))
    <div class="alert mt-16" role="status">
        <span class="ic i-cal" aria-hidden="true"></span>
        <p>Tanggal pilihan dari kalender ketersediaan: <b>{{ request('tanggal') }}</b>. Tanggal ini hanya usulan tampilan dan belum tersimpan; ini bukan tanggal yang sudah dipesan dan status ketersediaannya tetap perlu dikonfirmasi.</p>
    </div>
    @endif

    <section class="card mt-16">
        <div class="tabs">
            @php($tabs = ['' => 'Semua', 'menunggu' => 'Menunggu penawaran', 'ditinjau' => 'Ditinjau Admin', 'disetujui' => 'Penawaran disetujui', 'ditolak' => 'Ditolak Admin'])
            @foreach($tabs as $key => $label)
            <a href="{{ url('/klien/permintaan-penawaran').($key !== '' ? '?status='.$key : '') }}"@if($tabAktif === $key) aria-current="page"@endif>{{ $label }}</a>
            @endforeach
        </div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar permintaan penawaran. Gulir horizontal untuk melihat seluruh kolom">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Daftar permintaan penawaran</caption>
                <thead>
                    <tr>
                        <th scope="col">Layanan / venue</th>
                        <th scope="col">Penyedia</th>
                        <th scope="col">Acara</th>
                        <th scope="col">Tanggal acara</th>
                        <th scope="col" class="num">Harga katalog (referensi)</th>
                        <th scope="col" class="num">Usulan terakhir</th>
                        <th scope="col">Status penawaran</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($requests as $row)
                    @php
                    $usulan = $row->proposals->sortByDesc('submitted_at')->first();
                    [$qClass, $qLabel] = $statusBadge($row->status);
                    @endphp
                    <tr>
                        <td><b>{{ $row->subject?->name ?? 'Item katalog (sudah dihapus)' }}</b></td>
                        <td>{{ $penyediaNama($row) ?? '—' }}</td>
                        <td>{{ $row->event?->name ?? 'Tanpa tertaut acara' }}</td>
                        <td>{{ $row->event_date?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="num">{{ $rupiah($row->catalog_price_snapshot) }}</td>
                        <td class="num">{{ $usulan ? $rupiah($usulan->proposed_amount) : 'Belum ada usulan' }}</td>
                        <td><span class="badge {{ $qClass }}">{{ $qLabel }}</span></td>
                        <td><a class="btn btn-o btn-sm" href="{{ url('/klien/permintaan-penawaran-detail/'.$row->id) }}">Lihat detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><p class="hint" style="padding:16px;margin:0">Belum ada permintaan penawaran tersimpan untuk akun Anda. Alur pengajuan penawaran dari katalog akan terhubung pada backend BE-4; sementara itu Anda dapat menjelajahi katalog penyedia dan peralatan.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Harga yang diajukan penyedia belum mengikat sampai Admin menyetujuinya. Komisi layanan Pesta Kita 10% ditambahkan ke tagihan Klien dan tidak dipotong dari harga penyedia.</p></div>
    </section>
</main>
@endsection
