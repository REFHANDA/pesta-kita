@extends('layouts.klien')

@section('title', ($event->name ?? 'Detail acara').' · Pesta Kita')

@php
$event = $event ?? new \App\Models\Event();
$orderItems = $event->orderItems ?? collect();

// Label badge status — sama dengan daftar acara dan beranda.
$badgeFor = fn ($status) => match ($status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dievaluasi => ['b-ok', 'Dievaluasi'],
    \App\Enums\EventStatus::Dibatalkan => ['b-bad', 'Dibatalkan'],
    default => ['b-mute', '-'],
};
[$badgeClass, $badgeLabel] = $badgeFor($event->status);

// Tahap alur normal; acara dibatalkan tidak menampilkan langkah aktif.
$flow = [
    \App\Enums\EventStatus::Draft->value => 'Draft',
    \App\Enums\EventStatus::Diajukan->value => 'Diajukan',
    \App\Enums\EventStatus::MenungguPembayaran->value => 'Menunggu pembayaran',
    \App\Enums\EventStatus::Terkonfirmasi->value => 'Terkonfirmasi',
    \App\Enums\EventStatus::Selesai->value => 'Selesai',
    \App\Enums\EventStatus::Dievaluasi->value => 'Dievaluasi',
];
$flowKeys = array_keys($flow);
$currentStep = null;
foreach ($flowKeys as $i => $stepStatus) {
    if ($stepStatus === $event->status?->value) {
        $currentStep = $i;
    }
}
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
$estimatedTotal = $orderItems->sum(fn ($item) => $item->lineTotal());
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Rancang acara</a> / Detail</div>
        <h1>{{ $event->name ?: 'Detail acara' }}</h1>
        <p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count ? $event->guest_count.' tamu' : 'Jumlah tamu belum diisi' }}</p>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16" style="background:#dcfce7;border:1px solid #22c55e;color:#15803d;padding:12px;border-radius:6px;margin-bottom:16px;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-16" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;">
    {{ session('error') }}
</div>
@endif

@if ($currentStep !== null)
<section class="card mb-16"><ol class="steps" aria-label="Tahap acara">
@foreach ($flow as $stepStatus => $stepLabel)
@php
$pos = $loop->index;
$stepClass = $pos === $currentStep ? 'now' : ($pos < $currentStep ? 'done' : '');
@endphp
<li class="{{ $stepClass }}"@if($pos === $currentStep) aria-current="step"@endif>{{ $stepLabel }}</li>
@endforeach
</ol></section>
@endif

@php
    $activeInvoice = $event->activeInvoice;
@endphp

@if($event->status === \App\Enums\EventStatus::MenungguPembayaran && $activeInvoice)
<div style="background:#eff6ff;border:1px solid #93c5fd;color:#1e40af;padding:16px;border-radius:8px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <b style="font-size:15px;">Tagihan resmi telah diterbitkan: {{ $activeInvoice->invoice_number }}</b>
        <p style="margin-top:4px;font-size:13px;">Total transfer: <b>Rp {{ number_format($activeInvoice->transfer_total, 0, ',', '.') }}</b> · Batas bayar: {{ $activeInvoice->due_at?->translatedFormat('d M Y, H:i') }} WIB</p>
    </div>
    <a class="btn btn-sm" href="{{ route('klien.tagihan.show', $activeInvoice) }}">Lihat &amp; Bayar Tagihan</a>
</div>
@elseif($event->status === \App\Enums\EventStatus::Terkonfirmasi && $activeInvoice)
<div style="background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:16px;border-radius:8px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <b style="font-size:15px;">Pembayaran Terverifikasi &amp; Acara Terkonfirmasi</b>
        <p style="margin-top:4px;font-size:13px;">Tagihan {{ $activeInvoice->invoice_number }} telah lunas. Nota kuitansi resmi telah tersedia.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="btn btn-sm" href="{{ route('klien.tagihan.show', $activeInvoice) }}">Lihat Tagihan &amp; Nota</a>
        <a class="btn btn-o btn-sm" href="{{ route('klien.tagihan.receipt', $activeInvoice) }}" target="_blank">Cetak Nota Lunas (PDF)</a>
    </div>
</div>
@endif

<div class="grid two">
    <div class="stack">
        <section class="card">
            <div class="card-h" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2>Pesanan layanan &amp; venue terkait</h2>
                    <p class="meta">Layanan dan venue yang Anda tambahkan ke acara ini.</p>
                </div>
                @if($event->status === \App\Enums\EventStatus::Draft)
                <a class="btn btn-o btn-sm" href="{{ route('klien.katalog.vendor') }}">+ Tambah dari katalog</a>
                @endif
            </div>

            @if ($orderItems->isEmpty())
            <div style="padding:24px;text-align:center;">
                <p class="hint">Belum ada layanan atau venue yang ditambahkan ke acara ini.</p>
                <a class="btn btn-o mt-12" href="{{ route('klien.katalog.vendor') }}">Jelajahi Katalog</a>
            </div>
            @else
            <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pesanan">
                <table class="t-wide">
                    <thead>
                        <tr>
                            <th scope="col">Layanan / Venue</th>
                            <th scope="col">Penyedia</th>
                            <th scope="col" class="num">Jumlah</th>
                            <th scope="col" class="num">Harga Satuan</th>
                            <th scope="col" class="num">Total</th>
                            <th scope="col">Status</th>
                            @if($event->status === \App\Enums\EventStatus::Draft)
                            <th scope="col" class="text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($orderItems as $item)
                    <tr>
                        <td><b>{{ $item->subject?->name ?? 'Item katalog' }}</b></td>
                        <td>{{ $item->provider?->business_name ?? '-' }}</td>
                        <td class="num">{{ $item->quantity ?? 1 }}</td>
                        <td class="num">{{ $rupiah($item->provider_price ?? $item->catalog_price_snapshot) }}</td>
                        <td class="num">{{ $rupiah($item->lineTotal()) }}</td>
                        <td><span class="badge b-info">{{ $item->status?->value ?? '-' }}</span></td>
                        @if($event->status === \App\Enums\EventStatus::Draft)
                        <td class="text-right">
                            <form method="POST" action="{{ route('klien.acara.pesanan.destroy', [$event, $item]) }}" onsubmit="return confirm('Hapus item ini dari acara?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-o btn-sm" style="color:#ef4444;border-color:#fca5a5;">Hapus</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>

        @if($event->status === \App\Enums\EventStatus::Draft)
        <section class="card" style="border:2px solid #3b82f6;background:#eff6ff;">
            <div class="card-h">
                <h2 style="color:#1d4ed8;">Ajukan Acara ke Admin</h2>
            </div>
            <div class="card-b">
                <p style="margin-bottom:12px;">Ketika Anda menekan <b>Ajukan Acara</b>, sistem akan mengunci ketersediaan slot tanggal, mencatat harga resmi, dan mengirimkan notifikasi kepada Admin untuk peninjauan.</p>
                <form method="POST" action="{{ route('klien.acara.ajukan', $event) }}" onsubmit="return confirm('Yakin ingin mengajukan acara ini? Ketersediaan slot akan dikunci.');">
                    @csrf
                    <button type="submit" class="btn" style="background:#2563eb;" @if($orderItems->isEmpty()) disabled title="Tambahkan minimal 1 pesanan layanan/venue terlebih dahulu" @endif>
                        Ajukan Acara Sekarang
                    </button>
                    @if($orderItems->isEmpty())
                    <span class="hint" style="margin-left:12px;color:#dc2626;">(Tambahkan minimal 1 layanan/venue sebelum mengajukan)</span>
                    @endif
                </form>
            </div>
        </section>
        @endif

        <section class="card">
            <div class="card-h"><h2>Alur Pemesanan</h2></div>
            <div class="card-b">
                <ul class="policy">
                    <li><span>Pesanan disetujui langsung oleh Admin Pesta Kita tanpa proses tawar-menawar harga.</span></li>
                    <li><span>Setelah semua pesanan disetujui, tagihan resmi akan diterbitkan dengan batas bayar 48 jam.</span></li>
                    <li><span>Pembayaran dilakukan melalui transfer 100% ke rekening resmi perusahaan Pesta Kita.</span></li>
                </ul>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-h"><h2>Ringkasan acara</h2></div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Nama acara</dt><dd>{{ $event->name ?: '—' }}</dd></div>
                    <div><dt>Tanggal</dt><dd>{{ $event->event_date?->translatedFormat('l, d M Y') ?? 'Belum diisi' }}</dd></div>
                    <div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count ? $event->guest_count.' orang' : 'Belum diisi' }}</dd></div>
                    <div><dt>Tema</dt><dd>{{ $event->theme ?: 'Belum diisi' }}</dd></div>
                    <div><dt>Anggaran (Budget)</dt><dd><b>{{ $event->budget !== null ? $rupiah($event->budget) : 'Belum diisi' }}</b></dd></div>
                    <div><dt>Estimasi Biaya</dt><dd><b>{{ $rupiah($estimatedTotal) }}</b></dd></div>
                </dl>
                @if ($event->description)
                <p class="mt-8 hint">{{ $event->description }}</p>
                @endif

                @if($event->status === \App\Enums\EventStatus::Draft)
                <div class="form-actions mt-16" style="display:flex;gap:8px;">
                    <a class="btn btn-o full" href="{{ route('klien.acara.edit', $event) }}">Ubah Konsep</a>
                    <form method="POST" action="{{ route('klien.acara.destroy', $event) }}" onsubmit="return confirm('Hapus konsep acara ini secara permanen?');" style="flex:1;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-o full" style="color:#ef4444;border-color:#fca5a5;">Hapus</button>
                    </form>
                </div>
                @endif
            </div>
        </section>

        <div class="stack"><a class="btn btn-o full" href="{{ route('klien.acara') }}">Kembali ke daftar acara</a></div>
    </div>
</div>
</main>
@endsection
