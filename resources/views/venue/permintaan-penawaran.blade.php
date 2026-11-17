@extends('layouts.venue')

@section('title', 'Permintaan penawaran · Panel Pemilik Venue Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@php
$requests = $requests ?? collect();
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');

$statusBadge = fn ($status) => match ($status) {
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Perlu diusulkan'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Ditinjau Admin'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak Admin'],
    default => ['b-mute', '-'],
};
$tabAktif = (string) request('status', '');
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / Permintaan penawaran</div>
            <h1>Permintaan penawaran</h1>
            <p class="sub">Permintaan Klien yang masuk ke ruangan venue Anda melalui Pesta Kita. Ajukan harga dari halaman detail; Admin akan meninjau proposal tersebut.</p>
        </div>
    </div>

    <div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Daftar ini hanya memuat permintaan atas ruangan venue milik Anda. Identitas dan kontak Klien tidak ditampilkan; proposal harga disampaikan melalui Pesta Kita.</p></div>

    <section class="card">
        <div class="tabs">
            @php
            $tabs = ['' => 'Semua', 'baru' => 'Perlu diusulkan', 'ditinjau' => 'Ditinjau Admin', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
            @endphp
            @foreach($tabs as $key => $label)
            <a href="{{ url('/venue/permintaan-penawaran').($key !== '' ? '?status='.$key : '') }}"@if($tabAktif === $key) aria-current="page"@endif>{{ $label }}</a>
            @endforeach
        </div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar permintaan penawaran Klien. Gulir horizontal untuk melihat seluruh kolom">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Daftar permintaan penawaran Klien</caption>
                <thead>
                    <tr>
                        <th scope="col">Ruangan Anda</th>
                        <th scope="col">Venue</th>
                        <th scope="col">Tanggal acara</th>
                        <th scope="col">Tamu</th>
                        <th scope="col" class="num">Harga referensi</th>
                        <th scope="col" class="num">Usulan Anda terakhir</th>
                        <th scope="col">Status</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($requests as $row)
                    @php
                    $usulan = $row->proposals->first();
                    [$qClass, $qLabel] = $statusBadge($row->status);
                    @endphp
                    <tr>
                        <td><b>{{ $row->subject?->name ?? 'Ruangan (sudah dihapus)' }}</b></td>
                        <td>{{ $row->subject?->venue?->name ?? '—' }}</td>
                        <td>{{ $row->event_date?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td>{{ $row->guest_count ? $row->guest_count.' tamu' : '—' }}</td>
                        <td class="num">{{ $rupiah($row->catalog_price_snapshot) }}</td>
                        <td class="num">{{ $usulan ? $rupiah($usulan->proposed_amount) : 'Belum ada usulan' }}</td>
                        <td><span class="badge {{ $qClass }}">{{ $qLabel }}</span></td>
                        <td><a class="btn btn-o btn-sm" href="{{ url('/venue/permintaan-penawaran-detail/'.$row->id) }}">Lihat detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><p class="hint" style="padding:16px;margin:0">Belum ada permintaan penawaran yang masuk ke ruangan venue Anda.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Harga yang Anda usulkan belum mengikat sampai Admin menyetujuinya. Komisi layanan Pesta Kita 10% ditambahkan ke tagihan Klien dan tidak dipotong dari harga Anda.</p></div>
    </section>
</main>
@endsection
