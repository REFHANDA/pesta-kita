@extends('layouts.klien')

@section('title', $event->name.' · Pesta Kita')

@php
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
    $rupiah = fn ($amount) => 'Rp '.number_format((int) $amount, 0, ',', '.');
    $orderStatus = fn ($status) => match ($status) {
        \App\Enums\OrderItemStatus::MenungguAdmin => ['b-warn', 'Menunggu review Admin'],
        \App\Enums\OrderItemStatus::Disetujui => ['b-ok', 'Disetujui'],
        \App\Enums\OrderItemStatus::MasaKeberatan => ['b-warn', 'Masa keberatan'],
        \App\Enums\OrderItemStatus::Mengikat => ['b-ok', 'Mengikat'],
        \App\Enums\OrderItemStatus::Ditolak => ['b-bad', 'Ditolak'],
        default => ['b-mute', '-'],
    };
    $quoteStatus = fn ($status) => match ($status) {
        \App\Enums\QuoteRequestStatus::Diajukan => 'Menunggu penawaran',
        \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => 'Ditinjau Admin',
        \App\Enums\QuoteRequestStatus::DisetujuiAdmin => 'Disetujui Admin',
        \App\Enums\QuoteRequestStatus::DitolakAdmin => 'Ditolak Admin',
        default => '-',
    };
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Rancang acara</a> / Detail</div>
            <h1>{{ $event->name }}</h1>
            <p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') }} · {{ $event->guest_count }} tamu</p>
        </div>
        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </div>

    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @error('event')
        <div class="alert warn" role="alert">{{ $message }}</div>
    @enderror
    @error('subject')
        <div class="alert warn" role="alert">{{ $message }}</div>
    @enderror

    <div class="grid two">
        <div class="stack">
            <section class="card">
                <div class="card-h">
                    <div><h2>Item pesanan</h2><p class="meta">Harga final ditetapkan Admin dan tidak berasal dari input Klien.</p></div>
                </div>
                @if ($event->orderItems->isEmpty())
                    <p class="sub" style="padding:16px">Belum ada item di draft acara ini.</p>
                @else
                    <div class="table-wrap" role="region" tabindex="0" aria-label="Item pesanan">
                        <table class="t-wide">
                            <caption class="sr-only">Item pesanan acara</caption>
                            <thead><tr><th scope="col">Item</th><th scope="col" class="num">Harga katalog</th><th scope="col" class="num">Harga final</th><th scope="col">Status</th>@if ($event->status === \App\Enums\EventStatus::Draft)<th scope="col"></th>@endif</tr></thead>
                            <tbody>
                            @foreach ($event->orderItems as $orderItem)
                                @php [$itemBadge, $itemLabel] = $orderStatus($orderItem->status); @endphp
                                <tr>
                                    <td>{{ $orderItem->subject?->name ?? 'Item sudah tidak tersedia' }}</td>
                                    <td class="num">{{ $rupiah($orderItem->catalog_price_snapshot) }}</td>
                                    <td class="num">{{ $orderItem->final_price === null ? 'Belum ditetapkan' : $rupiah($orderItem->final_price) }}</td>
                                    <td><span class="badge {{ $itemBadge }}">{{ $itemLabel }}</span></td>
                                    @if ($event->status === \App\Enums\EventStatus::Draft)
                                        <td>
                                            <form method="POST" action="{{ route('klien.acara.items.destroy', [$event, $orderItem]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-o btn-sm" type="submit">Hapus dari draft</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($event->status === \App\Enums\EventStatus::Draft)
                    <div class="card-b">
                        <form class="form-grid" method="POST" action="{{ route('klien.acara.items.store', $event) }}">
                            @csrf
                            <div class="field full">
                                <label for="subject">Tambah layanan harga tetap atau ruangan venue</label>
                                <select class="input" id="subject" name="subject" required>
                                    <option value="">Pilih item</option>
                                    @foreach ($catalogItems as $item)
                                        <option value="catalog_item:{{ $item->id }}">{{ $item->name }} · {{ $rupiah($item->catalog_price) }}</option>
                                    @endforeach
                                    @foreach ($venueRooms as $room)
                                        <option value="venue_room:{{ $room->id }}">{{ $room->venue->name }} — {{ $room->name }} · {{ $rupiah($room->venue->rental_price) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="full"><button class="btn" type="submit">Tambahkan ke draft</button></div>
                        </form>
                        <p class="hint mt-16">Layanan yang memerlukan penawaran ditambahkan melalui <a href="{{ route('klien.penawaran', ['event_id' => $event->id, 'event_date' => $event->event_date?->toDateString()]) }}">permintaan penawaran</a>.</p>
                    </div>
                @endif
            </section>

            <section class="card">
                <div class="card-h"><h2>Permintaan penawaran tertaut</h2></div>
                @forelse ($event->quoteRequests as $quoteRequest)
                    <div class="card-b">
                        <b>{{ $quoteRequest->subject?->name ?? 'Item katalog (tidak tersedia)' }}</b>
                        <span class="sub-t">Status: {{ $quoteStatus($quoteRequest->status) }}</span>
                        <span class="sub-t">Harga katalog: {{ $rupiah($quoteRequest->catalog_price_snapshot) }}</span>
                        @php($latestProposal = $quoteRequest->proposals->sortByDesc('submitted_at')->first())
                        @if ($latestProposal)
                            <span class="sub-t">Usulan penyedia: {{ $rupiah($latestProposal->proposed_amount) }}</span>
                        @endif
                        @if ($quoteRequest->decision?->approved_amount)
                            <span class="sub-t">Harga disetujui Admin: {{ $rupiah($quoteRequest->decision->approved_amount) }}</span>
                        @endif
                        <a class="btn btn-o btn-sm mt-8" href="{{ route('klien.penawaran.detail', $quoteRequest) }}">Lihat penawaran</a>
                    </div>
                @empty
                    <p class="sub" style="padding:16px">Belum ada permintaan penawaran yang ditautkan.</p>
                @endforelse
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Ringkasan acara</h2></div>
                <div class="card-b">
                    <dl class="dl">
                        <div><dt>Nama acara</dt><dd>{{ $event->name }}</dd></div>
                        <div><dt>Tanggal</dt><dd>{{ $event->event_date?->translatedFormat('l, d M Y') }}</dd></div>
                        <div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count }}</dd></div>
                        <div><dt>Tema</dt><dd>{{ $event->theme ?: 'Belum diisi' }}</dd></div>
                    </dl>
                    @if ($event->description)<p class="mt-8">{{ $event->description }}</p>@endif
                </div>
            </section>

            @if ($event->status === \App\Enums\EventStatus::Draft)
                <a class="btn btn-o full" href="{{ route('klien.acara.edit', $event) }}">Ubah draft</a>
                <form method="POST" action="{{ route('klien.acara.submit', $event) }}">
                    @csrf
                    <button class="btn full" type="submit" @disabled($event->orderItems->isEmpty() && $event->quoteRequests->where('status', \App\Enums\QuoteRequestStatus::DisetujuiAdmin)->isEmpty())>Ajukan acara ke Admin</button>
                </form>
            @elseif ($event->status === \App\Enums\EventStatus::Diajukan)
                <div class="data-note" role="note">Item sedang ditinjau Admin. Tagihan baru dapat diterbitkan setelah seluruh keputusan dan masa keberatan selesai.</div>
            @endif
            @if ($event->latestInvoice)
                <a class="btn btn-o full" href="{{ route('klien.tagihan', $event->latestInvoice) }}">
                    Tagihan v{{ $event->latestInvoice->version }} · {{ ucfirst(str_replace('_', ' ', $event->latestInvoice->status)) }}
                </a>
            @endif
            @if (in_array($event->status, [\App\Enums\EventStatus::Terkonfirmasi, \App\Enums\EventStatus::Selesai, \App\Enums\EventStatus::Dinilai], true))
                <a class="btn btn-o full" href="{{ route('klien.rundown', $event) }}">Lihat rundown</a>
            @endif
            @if ($event->latestInvoice?->status === 'lunas' && in_array($event->status, [\App\Enums\EventStatus::Terkonfirmasi, \App\Enums\EventStatus::Selesai], true))
                @if ($event->refundRequest)
                    <p class="hint">Permohonan refund tercatat dan menunggu tinjauan. Keputusan serta nominal final belum tersedia.</p>
                @else
                    <a class="btn btn-o full" href="{{ route('klien.refund.create', $event) }}">Ajukan permohonan refund</a>
                @endif
            @endif
            <a class="btn btn-o full" href="{{ route('klien.acara') }}">Kembali ke daftar</a>
        </div>
    </div>

    @if (in_array($event->status, [\App\Enums\EventStatus::Selesai, \App\Enums\EventStatus::Dinilai], true)
        && $event->latestInvoice?->status === 'lunas')
        <section class="card mt-16">
            <div class="card-h"><div><h2>Ulasan penyedia</h2><p class="meta">Ulasan hanya dapat diberikan setelah acara selesai dan tagihan lunas.</p></div></div>
            @foreach ($event->orderItems->where('status', \App\Enums\OrderItemStatus::Mengikat) as $orderItem)
                <div class="card-b">
                    <h3>{{ $orderItem->subject?->name ?? 'Penyedia' }}</h3>
                    @if ($orderItem->review)
                        <p>Ulasan Anda: {{ $orderItem->review->rating }}/5</p>
                        @if ($orderItem->review->comment)<p>{{ $orderItem->review->comment }}</p>@endif
                    @else
                        <form class="stack" method="POST" action="{{ route('klien.ulasan.store', [$event, $orderItem]) }}">
                            @csrf
                            <div class="field">
                                <label for="rating-{{ $orderItem->id }}">Nilai</label>
                                <select class="input" id="rating-{{ $orderItem->id }}" name="rating" required>
                                    <option value="">Pilih nilai</option>
                                    @for ($rating = 1; $rating <= 5; $rating++)
                                        <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} / 5</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="field">
                                <label for="comment-{{ $orderItem->id }}">Komentar (opsional)</label>
                                <textarea class="input" id="comment-{{ $orderItem->id }}" name="comment" rows="3" maxlength="2000">{{ old('comment') }}</textarea>
                            </div>
                            @error('rating')<p class="hint">{{ $message }}</p>@enderror
                            @error('comment')<p class="hint">{{ $message }}</p>@enderror
                            <button class="btn" type="submit">Kirim ulasan</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </section>
    @endif
</main>
@endsection
