@extends('layouts.vendor')

@section('title', 'Detail pesanan · Panel Vendor Pesta Kita')

@php
    $item = $orderItem;
    $badge = match ($item->status) {
        \App\Enums\OrderItemStatus::MenungguAdmin => ['b-warn', 'Menunggu review Admin'],
        \App\Enums\OrderItemStatus::MasaKeberatan => ['b-warn', 'Masa keberatan'],
        \App\Enums\OrderItemStatus::Mengikat => ['b-ok', 'Mengikat'],
        \App\Enums\OrderItemStatus::Disetujui => ['b-ok', 'Disetujui'],
        \App\Enums\OrderItemStatus::Ditolak => ['b-bad', 'Ditolak'],
        default => ['b-mute', $item->status->value],
    };
    [$badgeClass, $badgeLabel] = $badge;
    $mayObject = $item->status === \App\Enums\OrderItemStatus::MasaKeberatan
        && $item->objection_deadline?->isFuture()
        && $item->objections->isEmpty();
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('vendor.beranda') }}">Beranda</a> / <a href="{{ route('vendor.pesanan') }}">Pesanan</a> / Detail</div><h1>Pesanan #{{ $item->id }}</h1></div><a class="btn btn-o" href="{{ route('vendor.pesanan') }}">Kembali</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('order_item')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two">
        <section class="card">
            <div class="card-h"><h2>{{ $item->subject?->name ?? 'Item tidak tersedia' }}</h2><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Tanggal acara</dt><dd>{{ $item->event->event_date?->translatedFormat('l, d F Y') }}</dd></div>
                    <div><dt>Jumlah tamu</dt><dd>{{ number_format($item->event->guest_count) }}</dd></div>
                    <div><dt>Harga final</dt><dd>{{ $item->final_price === null ? 'Belum ditetapkan' : 'Rp '.number_format($item->final_price, 0, ',', '.') }}</dd></div>
                    @if ($item->objection_deadline)<div><dt>Batas keberatan</dt><dd>{{ $item->objection_deadline->translatedFormat('d M Y H:i') }} WIB</dd></div>@endif
                    @if ($item->rejection_reason)<div><dt>Catatan Admin</dt><dd>{{ $item->rejection_reason }}</dd></div>@endif
                </dl>
                <p class="hint mt-16">Nama dan kontak Klien tidak tersedia di tampilan penyedia.</p>
                <a class="btn btn-o" href="{{ route('vendor.checklist', $item->event) }}">Lihat checklist acara</a>
            </div>
        </section>
        <section class="card">
            <div class="card-h"><h2>Keberatan</h2></div>
            <div class="card-b">
                @if ($item->objections->isNotEmpty())
                    <p>Keberatan Anda sudah dikirim dan sedang diproses Admin.</p>
                    <p>{{ $item->objections->first()->reason }}</p>
                @elseif ($mayObject)
                    <p>Anda dapat mengajukan satu keberatan sebelum batas waktu.</p>
                    <form method="POST" action="{{ route('vendor.keberatan.store', $item) }}">
                        @csrf
                        <div class="field"><label for="reason">Alasan keberatan</label><textarea class="input" id="reason" name="reason" rows="4" minlength="5" maxlength="2000" required>{{ old('reason') }}</textarea>@error('reason')<p class="hint">{{ $message }}</p>@enderror</div>
                        <button class="btn mt-8" type="submit">Kirim keberatan</button>
                    </form>
                @else
                    <p class="sub">Tidak ada keberatan yang dapat diajukan saat ini.</p>
                @endif
            </div>
        </section>
    </div>
</main>
@endsection
