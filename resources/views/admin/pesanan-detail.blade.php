@extends('layouts.admin')

@section('title', 'Detail pesanan · Admin Pesta Kita')

@php
    $item = $orderItem;
    $provider = $item->provider;
    $subject = $item->subject;
    $subjectName = $subject?->name ?? 'Item tidak tersedia';
    if ($subject instanceof \App\Models\VenueRoom) {
        $subjectName = ($subject->venue?->name ? $subject->venue->name.' — ' : '').$subjectName;
    }
    $status = match ($item->status) {
        \App\Enums\OrderItemStatus::MenungguAdmin => ['b-warn', 'Menunggu review Admin'],
        \App\Enums\OrderItemStatus::MasaKeberatan => ['b-warn', 'Masa keberatan'],
        \App\Enums\OrderItemStatus::Mengikat => ['b-ok', 'Mengikat'],
        \App\Enums\OrderItemStatus::Disetujui => ['b-ok', 'Disetujui'],
        \App\Enums\OrderItemStatus::Ditolak => ['b-bad', 'Ditolak'],
        default => ['b-mute', $item->status->value],
    };
    [$badgeClass, $badgeLabel] = $status;
    $amount = $item->final_price ?? $item->proposed_price_snapshot ?? $item->catalog_price_snapshot;
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
@endphp

@section('content')
<main class="content page-refined detail-page" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / <a href="{{ route('admin.pesanan') }}">Review pesanan</a> / Detail</div>
            <h1>Detail item pesanan</h1>
            <p class="sub">{{ $subjectName }}</p>
        </div>
        <a class="btn btn-o" href="{{ route('admin.pesanan') }}">Kembali ke daftar</a>
    </div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('order_item')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h">
                    <div class="split-head"><div><h2>{{ $subjectName }}</h2><p class="meta">Item #{{ $item->id }}</p></div><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></div>
                </div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Acara</span><span class="v">{{ $item->event->name }}</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">{{ $item->event->event_date?->translatedFormat('d F Y') }}</span></div>
                        <div class="kv"><span class="k">Jumlah tamu</span><span class="v">{{ number_format($item->event->guest_count) }}</span></div>
                        <div class="kv"><span class="k">Klien</span><span class="v">{{ $item->event->client->name }}</span></div>
                        <div class="kv"><span class="k">Penyedia</span><span class="v">{{ $provider?->business_name ?? 'Penyedia tidak tersedia' }}</span></div>
                    </div>
                </div>
            </section>
            <section class="card detail-card">
                <div class="card-h"><h2>Rincian harga</h2><p class="meta">Harga final ditentukan Admin saat review dan tidak dapat diubah dari browser.</p></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga katalog snapshot</span><strong>{{ $rupiah($item->catalog_price_snapshot) }}</strong></div>
                        @if ($item->proposed_price_snapshot !== null)<div class="dl-row"><span>Harga usulan yang disetujui</span><strong>{{ $rupiah($item->proposed_price_snapshot) }}</strong></div>@endif
                        <div class="dl-row"><span>Harga final</span><strong>{{ $item->final_price === null ? 'Belum ditetapkan' : $rupiah($item->final_price) }}</strong></div>
                        @if ($item->rejection_reason)<div class="dl-row"><span>Alasan penolakan</span><strong>{{ $item->rejection_reason }}</strong></div>@endif
                        @if ($item->objection_deadline)<div class="dl-row"><span>Batas keberatan</span><strong>{{ $item->objection_deadline->translatedFormat('d M Y H:i') }} WIB</strong></div>@endif
                    </div>
                </div>
            </section>
            @if ($item->objections->isNotEmpty())
                <section class="card detail-card">
                    <div class="card-h"><h2>Keberatan penyedia</h2></div>
                    @foreach ($item->objections as $objection)
                        <div class="card-body">
                            <p><b>{{ $objection->providerUser->name }}</b> · {{ $objection->created_at?->translatedFormat('d M Y H:i') }}</p>
                            <p>{{ $objection->reason }}</p>
                            <p class="meta">Status: {{ $objection->status }}</p>
                            @if ($objection->status === 'diajukan')
                                <a class="btn btn-o" href="{{ route('admin.keberatan.detail', $objection) }}">Periksa keberatan</a>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif
        </div>
        <aside class="detail-side">
            @if ($item->status === \App\Enums\OrderItemStatus::MenungguAdmin && $item->event->status === \App\Enums\EventStatus::Diajukan)
                <section class="card detail-card">
                    <div class="card-h"><h2>Tindakan Admin</h2><p class="meta">Keputusan berlaku untuk item ini saja.</p></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.pesanan.decide', [$item, 'approve']) }}">
                            @csrf
                            <button class="btn full" type="submit">Setujui · {{ $rupiah($amount) }}</button>
                        </form>
                        <form class="mt-16" method="POST" action="{{ route('admin.pesanan.decide', [$item, 'reject']) }}">
                            @csrf
                            <div class="field">
                                <label for="reason">Alasan penolakan</label>
                                <textarea class="input" id="reason" name="reason" rows="3" minlength="5" maxlength="2000" required>{{ old('reason') }}</textarea>
                                @error('reason')<p class="hint">{{ $message }}</p>@enderror
                            </div>
                            <button class="btn btn-danger full" type="submit">Tolak item</button>
                        </form>
                    </div>
                </section>
            @elseif ($item->status === \App\Enums\OrderItemStatus::MasaKeberatan)
                <div class="data-note" role="note">Masa keberatan penyedia berakhir pada {{ $item->objection_deadline?->translatedFormat('d M Y H:i') }} WIB.</div>
            @endif
            <a class="btn btn-o full" href="{{ route('admin.pesanan') }}">Kembali ke review pesanan</a>
        </aside>
    </div>
</main>
@endsection
