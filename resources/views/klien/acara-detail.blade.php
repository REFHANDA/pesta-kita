@extends('layouts.klien')

@section('title', ($event->name ?? 'Detail acara').' · Pesta Kita')

@php
$event = $event ?? new \App\Models\Event();
$requests = $event->quoteRequests ?? collect();

// Label badge status — sama dengan daftar acara dan beranda.
$badgeFor = fn ($status) => match ($status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dinilai => ['b-ok', 'Dinilai'],
    \App\Enums\EventStatus::Dibatalkan => ['b-bad', 'Dibatalkan'],
    default => ['b-mute', '-'],
};
[$badgeClass, $badgeLabel] = $badgeFor($event->status);

// Tahap alur normal; acara dibatalkan tidak menampilkan langkah aktif.
$flow = [
    \App\Enums\EventStatus::Draft => 'Draft',
    \App\Enums\EventStatus::Diajukan => 'Diajukan',
    \App\Enums\EventStatus::MenungguPembayaran => 'Menunggu pembayaran',
    \App\Enums\EventStatus::Terkonfirmasi => 'Terkonfirmasi',
    \App\Enums\EventStatus::Selesai => 'Selesai',
    \App\Enums\EventStatus::Dinilai => 'Dinilai',
];
$flowKeys = array_keys($flow);
$currentStep = null;
foreach ($flowKeys as $i => $stepStatus) {
    if ($stepStatus === $event->status) {
        $currentStep = $i;
    }
}
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');

$statusBadgeForQuote = fn ($status) => match ($status) {
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Menunggu penawaran'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Ditinjau Admin'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui Admin'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak Admin'],
    default => ['b-mute', '-'],
};
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/acara') }}">Rancang acara</a> / Detail</div><h1>{{ $event->name ?: 'Detail acara' }}</h1><p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count ? $event->guest_count.' tamu' : 'Jumlah tamu belum diisi' }}</p></div><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></div>

<div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p><b>Sebagian alur belum terhubung.</b> Halaman ini membaca data acara Anda yang tersimpan di basis data. Pesanan final, tagihan, refund, rundown, dan penilaian menyusul pada backend BE-4–BE-7, sehingga tidak ada tombol aksi di halaman ini yang memproses data.</p></div>

@if ($currentStep !== null)
<section class="card"><ol class="steps" aria-label="Tahap acara">
@foreach ($flow as $stepStatus => $stepLabel)
@php
$pos = $loop->index;
$stepClass = $pos === $currentStep ? 'now' : ($pos < $currentStep ? 'done' : '');
@endphp
<li class="{{ $stepClass }}"@if($pos === $currentStep) aria-current="step"@endif>{{ $stepLabel }}</li>
@endforeach
</ol></section>
@endif

<div class="grid two"><div class="stack">
<section class="card"><div class="card-h"><h2>Permintaan penawaran terkait</h2><p class="meta">Permintaan yang Anda catatkan untuk acara ini (tabel penawaran nyata).</p></div>
@if ($requests->isEmpty())
<p class="sub" style="padding:16px">Belum ada permintaan penawaran yang tertaut ke acara ini. Pengajuan penawaran dari katalog akan terhubung pada backend alur acara (BE-4/BE-5).</p>
@else
<div class="table-wrap" role="region" tabindex="0" aria-label="Permintaan penawaran terkait acara ini. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Permintaan penawaran terkait</caption><thead><tr><th scope="col">Layanan / venue</th><th scope="col" class="num">Harga katalog (referensi)</th><th scope="col" class="num">Usulan terakhir</th><th scope="col">Status</th><th scope="col"></th></tr></thead><tbody>
@foreach ($requests as $row)
@php
$usulan = $row->proposals->sortByDesc('submitted_at')->first();
[$qClass, $qLabel] = $statusBadgeForQuote($row->status);
@endphp
<tr><td><b>{{ $row->subject?->name ?? 'Item katalog (sudah dihapus)' }}</b><span class="sub-t">{{ $row->event_date?->translatedFormat('d M Y') ?? 'Tanggal tidak diketahui' }}</span></td><td class="num">{{ $rupiah($row->catalog_price_snapshot) }}</td><td class="num">{{ $usulan ? $rupiah($usulan->proposed_amount) : 'Belum ada usulan' }}</td><td><span class="badge {{ $qClass }}">{{ $qLabel }}</span></td><td><a class="btn btn-o btn-sm" href="{{ url('/klien/permintaan-penawaran-detail/'.$row->id) }}">Lihat detail</a></td></tr>
@endforeach
</tbody></table></div>
@endif
</section>

<section class="card"><div class="card-h"><h2>Yang menyusul</h2></div><div class="card-b"><ul class="policy"><li><span>Pesanan final dan tagihan gabungan terhubung pada backend BE-5/BE-6.</span></li><li><span>Rundown acara dan evaluasi pasca-acara terhubung pada backend BE-7.</span></li><li><span>Pembatalan dan refund dihitung oleh sistem pada BE-6, bukan oleh halaman ini.</span></li></ul></div></section>
</div>

<div class="stack">
<section class="card"><div class="card-h"><h2>Ringkasan acara</h2></div><div class="card-b"><dl class="dl"><div><dt>Nama acara</dt><dd>{{ $event->name ?: '—' }}</dd></div><div><dt>Tanggal</dt><dd>{{ $event->event_date?->translatedFormat('l, d M Y') ?? 'Belum diisi' }}</dd></div><div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count ? $event->guest_count.' orang' : 'Belum diisi' }}</dd></div><div><dt>Tema</dt><dd>{{ $event->theme ?: 'Belum diisi' }}</dd></div></dl>@if ($event->description)<p class="mt-8">{{ $event->description }}</p>@endif<div class="note"><b>Catatan:</b> Budget dan komposisi pesanan belum tersimpan pada acara; kolom tersebut menyusul bersama backend BE-4/BE-5.</div></div></section>
<div class="stack"><a class="btn btn-o full" href="{{ url('/klien/acara') }}">Kembali ke daftar acara</a></div>
</div>
</div>
</main>
@endsection
