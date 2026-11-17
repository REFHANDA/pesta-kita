@extends('layouts.vendor')

@section('title', 'Detail permintaan penawaran · Panel Vendor Pesta Kita')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/katalog.css') }}">
@endpush

@php
$quoteRequest = $quoteRequest ?? new \App\Models\QuoteRequest();
$subject = $quoteRequest->subject;
$proposals = $quoteRequest->proposals ?? collect();
$usulan = $proposals->sortByDesc('submitted_at')->first();
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');

$statusBadge = fn ($status) => match ($status) {
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Perlu diusulkan'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Ditinjau Admin'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak Admin'],
    default => ['b-mute', '-'],
};
[$qClass, $qLabel] = $statusBadge($quoteRequest->status);
@endphp

@section('content')
<main id="konten" class="content detail-page" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / <a href="{{ url('/vendor/permintaan-penawaran') }}">Permintaan penawaran</a> / #{{ $quoteRequest->id }}</div>
            <h1>Permintaan: {{ $subject?->name ?? 'Item katalog (sudah dihapus)' }}</h1>
            <p class="meta">Permintaan #{{ $quoteRequest->id }} · {{ $quoteRequest->event_date?->translatedFormat('d F Y') ?? 'tanggal belum diisi' }} · masuk {{ $quoteRequest->created_at?->translatedFormat('d F Y') }}</p>
        </div>
        <a class="btn btn-o" href="{{ url('/vendor/permintaan-penawaran') }}">Kembali ke daftar</a>
    </div>

    <div class="data-note" role="note"><span class="ic i-info" aria-hidden="true"></span><p>Identitas dan kontak Klien tidak ditampilkan. Sampaikan usulan harga melalui Pesta Kita; Admin akan meninjau proposal tersebut.</p></div>
    @if(session('status'))
    <div class="alert mt-16" role="status"><span class="ic i-check" aria-hidden="true"></span><p>{{ session('status') }}</p></div>
    @endif
    @if($errors->has('quote_request'))
    <div class="alert mt-16" role="alert"><span class="ic i-info" aria-hidden="true"></span><p>{{ $errors->first('quote_request') }}</p></div>
    @endif

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h"><div class="split-head"><div><h2>Permintaan Klien</h2><p class="meta">Kebutuhan acara yang tercatat melalui Pesta Kita.</p></div><span class="badge {{ $qClass }}">{{ $qLabel }}</span></div></div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Layanan</span><span class="v">{{ $subject?->name ?? 'Item katalog (sudah dihapus)' }}</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">{{ $quoteRequest->event_date?->translatedFormat('d F Y') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">{{ $quoteRequest->guest_count ? $quoteRequest->guest_count.' orang' : '—' }}</span></div>
                        <div class="kv"><span class="k">Anggaran acuan Klien</span><span class="v">{{ $quoteRequest->budget ? $rupiah($quoteRequest->budget) : '—' }}</span></div>
                        <div class="kv"><span class="k">Harga katalog saat permintaan dibuat</span><span class="v">{{ $rupiah($quoteRequest->catalog_price_snapshot) }}</span></div>
                    </div>
                    @if ($quoteRequest->notes)
                    <p class="field-label">Kebutuhan khusus dari Klien</p>
                    <div class="quote-panel">{{ $quoteRequest->notes }}</div>
                    @endif
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Usulan Anda</h2><p class="meta">Usulan harga yang sudah tercatat untuk permintaan ini.</p></div>
                <div class="card-body">
                    @if ($usulan)
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga usulan terakhir</span><strong>{{ $rupiah($usulan->proposed_amount) }}</strong></div>
                        <div class="dl-row"><span>Dikirim</span><strong>{{ $usulan->submitted_at?->translatedFormat('d M Y H.i') ?? 'waktu tidak tercatat' }}</strong></div>
                        @if ($usulan->notes)
                        <div class="dl-row"><span>Catatan</span><strong>{{ $usulan->notes }}</strong></div>
                        @endif
                    </div>
                    @else
                    <p class="m-0">Belum ada usulan harga yang tercatat untuk permintaan ini.</p>
                    @endif
                    @if($quoteRequest->status === \App\Enums\QuoteRequestStatus::Diajukan)
                    <form method="post" action="{{ route('vendor.penawaran.store', $quoteRequest) }}" class="mt-16">
                        @csrf
                        <div class="field">
                            <label for="vendor-proposed-amount">Harga usulan (Rp)</label>
                            <input class="input" type="number" id="vendor-proposed-amount" name="proposed_amount" min="1" step="1" value="{{ old('proposed_amount') }}" required>
                            @error('proposed_amount')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="vendor-proposal-notes">Catatan untuk Admin dan Klien</label>
                            <textarea class="input" id="vendor-proposal-notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                            @error('notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <button class="btn mt-12" type="submit">Kirim usulan harga</button>
                    </form>
                    @endif
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Harga yang Anda usulkan belum menjadi harga final. Harga final ditetapkan setelah Admin menyetujui usulan; komisi 10% ditambahkan ke tagihan Klien oleh sistem, bukan dipotong dari harga Anda.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat permintaan</h2><p class="meta">Dibaca dari waktu pencatatan di basis data.</p></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item"><b>Permintaan Klien masuk</b><small>{{ $quoteRequest->created_at?->translatedFormat('d M Y H.i') ?? '—' }}</small></div>
                        @foreach ($proposals->sortBy('submitted_at') as $proposal)
                        <div class="timeline-item"><b>Usulan harga tercatat {{ $rupiah($proposal->proposed_amount) }}</b><small>{{ $proposal->submitted_at?->translatedFormat('d M Y H.i') ?? 'waktu usulan tidak tercatat' }}</small></div>
                        @endforeach
                        <div class="timeline-item"><b>{{ $qLabel }}</b><small>Status saat ini</small></div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Ketentuan penawaran</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Peninjau keputusan</span><strong>Admin Pesta Kita</strong></div>
                        <div class="dl-row"><span>Harga berlaku</span><strong>Setelah disetujui Admin</strong></div>
                        <div class="dl-row"><span>Komisi layanan</span><strong>10% ditambahkan ke Klien</strong></div>
                    </div>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Identitas dan kontak Klien tidak ditampilkan. Seluruh komunikasi dan pembayaran dilakukan melalui Pesta Kita.</p></div>
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
