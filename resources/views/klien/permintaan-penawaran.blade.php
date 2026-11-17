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
        <p>Pengajuan, usulan penyedia, dan keputusan Admin tersimpan di Pesta Kita. Keputusan Admin menetapkan harga final untuk permintaan ini; proses pemesanan dan tagihan mengikuti tahap backend berikutnya.</p>
    </div>

    @if(request()->filled('tanggal'))
    <div class="alert mt-16" role="status">
        <span class="ic i-cal" aria-hidden="true"></span>
        <p>Tanggal pilihan dari kalender ketersediaan: <b>{{ request('tanggal') }}</b>. Tanggal ini baru tersimpan sebagai permintaan penawaran setelah formulir di bawah dikirim; bukan tanggal yang sudah dipesan.</p>
    </div>
    @endif

    <section class="card mt-16">
        <div class="card-h"><h2>Ajukan permintaan penawaran</h2><p class="meta">Pilih layanan atau ruangan venue yang tersedia, lalu jelaskan kebutuhan acara Anda.</p></div>
        <div class="card-body">
            @if(session('status'))
            <div class="alert" role="status"><span class="ic i-check" aria-hidden="true"></span><p>{{ session('status') }}</p></div>
            @endif
            @if($errors->has('quote_request'))
            <div class="alert" role="alert"><span class="ic i-info" aria-hidden="true"></span><p>{{ $errors->first('quote_request') }}</p></div>
            @endif
            <form method="post" action="{{ route('klien.penawaran.store') }}">
                @csrf
                <div class="field">
                    <label for="quote-subject">Layanan / ruangan venue</label>
                    <select class="input" id="quote-subject" name="subject" required>
                        <option value="">Pilih layanan atau venue</option>
                        @foreach($catalogItems as $item)
                        <option value="catalog_item:{{ $item->id }}" @selected(old('subject', request('subject')) === 'catalog_item:'.$item->id)>
                            {{ $item->name }} · {{ $item->vendorProfile?->business_name }} · harga referensi {{ $rupiah($item->catalog_price) }}
                        </option>
                        @endforeach
                        @foreach($venueRooms as $room)
                        <option value="venue_room:{{ $room->id }}" @selected(old('subject', request('subject')) === 'venue_room:'.$room->id)>
                            {{ $room->name }} · {{ $room->venue?->name }} · harga referensi {{ $rupiah($room->venue?->rental_price) }}
                        </option>
                        @endforeach
                    </select>
                    @error('subject')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="quote-event">Tautkan acara draft (opsional)</label>
                    <select class="input" id="quote-event" name="event_id">
                        <option value="">Tanpa acara tertaut</option>
                        @foreach($draftEvents as $event)
                        <option value="{{ $event->id }}" @selected((string) old('event_id') === (string) $event->id)>{{ $event->name }}</option>
                        @endforeach
                    </select>
                    @error('event_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="form-grid mt-12">
                    <div class="field">
                        <label for="quote-event-date">Tanggal acara</label>
                        <input class="input" type="date" id="quote-event-date" name="event_date" min="{{ now()->toDateString() }}" value="{{ old('event_date', request('tanggal')) }}" required>
                        @error('event_date')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="quote-guest-count">Perkiraan tamu</label>
                        <input class="input" type="number" min="1" step="1" id="quote-guest-count" name="guest_count" value="{{ old('guest_count') }}">
                        @error('guest_count')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="quote-budget">Anggaran acuan (Rp)</label>
                        <input class="input" type="number" min="0" step="1" id="quote-budget" name="budget" value="{{ old('budget') }}">
                        @error('budget')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="field">
                    <label for="quote-notes">Kebutuhan khusus</label>
                    <textarea class="input" id="quote-notes" name="notes" rows="4" maxlength="2000">{{ old('notes') }}</textarea>
                    @error('notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="toolbar mt-16">
                    <button class="btn" type="submit" @disabled($catalogItems->isEmpty() && $venueRooms->isEmpty())>Kirim permintaan penawaran</button>
                </div>
                @if($catalogItems->isEmpty() && $venueRooms->isEmpty())
                <p class="hint mt-12">Belum ada layanan yang memerlukan penawaran atau ruangan venue terverifikasi yang tersedia.</p>
                @endif
            </form>
        </div>
    </section>

    <section class="card mt-16">
        <div class="tabs">
            @php
            $tabs = ['' => 'Semua', 'menunggu' => 'Menunggu penawaran', 'ditinjau' => 'Ditinjau Admin', 'disetujui' => 'Penawaran disetujui', 'ditolak' => 'Ditolak Admin'];
            @endphp
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
                    $usulan = $row->proposals->first();
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
                    <tr><td colspan="8"><p class="hint" style="padding:16px;margin:0">Belum ada permintaan penawaran tersimpan untuk akun Anda. Ajukan melalui formulir di atas.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Harga yang diajukan penyedia belum mengikat sampai Admin menyetujuinya. Komisi layanan Pesta Kita 10% ditambahkan ke tagihan Klien dan tidak dipotong dari harga penyedia.</p></div>
    </section>
</main>
@endsection
