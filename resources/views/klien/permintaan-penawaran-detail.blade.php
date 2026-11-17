@extends('layouts.klien')

@section('title', 'Status penawaran · Pesta Kita')

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
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Menunggu penawaran'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Ditinjau Admin'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui Admin'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak Admin'],
    default => ['b-mute', '-'],
};
[$qClass, $qLabel] = $statusBadge($quoteRequest->status);

// Penyedia + tautan profil publik berdasarkan jenis subjek permintaan.
if ($subject instanceof \App\Models\CatalogItem) {
    $profil = $subject->vendorProfile;
    $tautanProfil = $profil ? url('/klien/penyedia-vendor/'.$profil->id) : null;
    $jenisPenyedia = 'Vendor';
} elseif ($subject instanceof \App\Models\VenueRoom) {
    $profil = $subject->venue?->venueProfile;
    $tautanProfil = $profil ? url('/klien/penyedia-venue/'.$profil->id) : null;
    $jenisPenyedia = 'Pemilik venue';
} else {
    $profil = null;
    $tautanProfil = null;
    $jenisPenyedia = null;
}
@endphp

@section('content')
<main id="konten" class="content detail-page" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ url('/klien/permintaan-penawaran') }}">Permintaan penawaran</a> / #{{ $quoteRequest->id }}</div>
            <h1>Penawaran: {{ $subject?->name ?? 'Item katalog (sudah dihapus)' }}</h1>
            <p class="meta">Permintaan #{{ $quoteRequest->id }} · {{ $quoteRequest->event?->name ?? 'Tanpa tertaut acara' }} · {{ $quoteRequest->event_date?->translatedFormat('d F Y') ?? 'tanggal belum diisi' }} · tercatat {{ $quoteRequest->created_at?->translatedFormat('d F Y') }}</p>
        </div>
        <a class="btn btn-o" href="{{ url('/klien/permintaan-penawaran') }}">Kembali ke daftar</a>
    </div>

    <div class="data-note" role="note">
        <span class="ic i-info" aria-hidden="true"></span>
        <p>Harga katalog, usulan penyedia, dan harga yang disetujui Admin dicatat terpisah. Pemesanan dan tagihan hanya diproses pada tahap backend berikutnya.</p>
    </div>
    @if(session('status'))
    <div class="alert mt-16" role="status"><span class="ic i-check" aria-hidden="true"></span><p>{{ session('status') }}</p></div>
    @endif

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h"><div class="split-head"><div><h2>Ringkasan permintaan</h2><p class="meta">Yang Anda ajukan ke penyedia melalui Pesta Kita.</p></div><span class="badge {{ $qClass }}">{{ $qLabel }}</span></div></div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Layanan / venue</span><span class="v">{{ $subject?->name ?? 'Item katalog (sudah dihapus)' }}</span></div>
                        <div class="kv"><span class="k">Penyedia</span><span class="v">{{ $profil?->business_name ?? '—' }}</span></div>
                        <div class="kv"><span class="k">Acara</span><span class="v">{{ $quoteRequest->event?->name ?? 'Tanpa tertaut acara' }}</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">{{ $quoteRequest->event_date?->translatedFormat('d F Y') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">{{ $quoteRequest->guest_count ? $quoteRequest->guest_count.' orang' : '—' }}</span></div>
                        <div class="kv"><span class="k">Anggaran acuan</span><span class="v">{{ $quoteRequest->budget ? $rupiah($quoteRequest->budget) : '—' }}</span></div>
                    </div>
                    @if ($quoteRequest->notes)
                    <p class="field-label">Kebutuhan khusus</p>
                    <p class="body-copy">{{ $quoteRequest->notes }}</p>
                    @endif
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Perbandingan harga</h2><p class="meta">Harga katalog, harga usulan penyedia, dan harga yang disetujui Admin dibedakan secara terpisah (aturan terkunci A5).</p></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Harga katalog (referensi)</span><strong>{{ $rupiah($quoteRequest->catalog_price_snapshot) }} <span class="badge b-mute">Referensi</span></strong></div>
                        <div class="dl-row"><span>Harga diajukan penyedia</span><strong>@if($usulan){{ $rupiah($usulan->proposed_amount) }} <span class="badge b-warn">Usulan</span>@else Belum ada usulan @endif</strong></div>
                        <div class="dl-row">
                            <span>Harga disetujui Admin</span>
                            <strong>
                                @if($quoteRequest->decision?->decision === \App\Enums\QuoteDecisionResult::Disetujui)
                                {{ $rupiah($quoteRequest->decision->approved_amount) }}
                                @elseif($quoteRequest->decision?->decision === \App\Enums\QuoteDecisionResult::Ditolak)
                                Ditolak Admin
                                @else
                                Belum diputuskan
                                @endif
                            </strong>
                        </div>
                    </div>
                    @if($quoteRequest->decision?->reason)
                    <p class="hint mt-12">Catatan keputusan Admin: {{ $quoteRequest->decision->reason }}</p>
                    @endif
                    @if ($usulan?->notes)
                    <p class="hint mt-12">Catatan penyedia atas usulan: {{ $usulan->notes }}</p>
                    @endif
                    <div class="warn-box mt-12">Usulan penyedia belum mengikat. Harga final ditetapkan setelah Admin memutuskan, dan komisi layanan Pesta Kita 10% ditambahkan ke tagihan Klien — bukan dipotong dari harga penyedia.</div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat penawaran</h2><p class="meta">Dibaca dari waktu pencatatan di basis data.</p></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item"><b>Permintaan tercatat</b><small>{{ $quoteRequest->created_at?->translatedFormat('d M Y H.i') ?? '—' }}</small></div>
                        @foreach ($proposals->sortBy('submitted_at') as $proposal)
                        <div class="timeline-item"><b>Penyedia mengusulkan harga {{ $rupiah($proposal->proposed_amount) }}</b><small>{{ $proposal->submitted_at?->translatedFormat('d M Y H.i') ?? 'waktu usulan tidak tercatat' }}</small></div>
                        @endforeach
                        @if ($proposals->isEmpty())
                        <div class="timeline-item"><b>Belum ada usulan penyedia</b><small>Usulan akan tampil di sini setelah penyedia merespons.</small></div>
                        @endif
                        @if($quoteRequest->decision)
                        <div class="timeline-item"><b>Keputusan Admin: {{ $quoteRequest->decision->decision->value }}</b><small>{{ $quoteRequest->decision->decided_at?->translatedFormat('d M Y H.i') ?? '—' }}@if($quoteRequest->decision->reason) · {{ $quoteRequest->decision->reason }}@endif</small></div>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Status alur</h2><p class="meta">Kelanjutan permintaan ini.</p></div>
                <div class="card-body">
                    <p class="body-copy">Status saat ini: <span class="badge {{ $qClass }}">{{ $qLabel }}</span>. Pemesanan lanjutan dan tagihan mengikuti tahap backend berikutnya.</p>
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Kontak pribadi penyedia tidak ditampilkan. Seluruh komunikasi dan pembayaran dilakukan melalui Pesta Kita.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Informasi penyedia</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Nama penyedia</span><strong>{{ $profil?->business_name ?? '—' }}</strong></div>
                        <div class="dl-row"><span>Jenis</span><strong>{{ $jenisPenyedia ?? '—' }}</strong></div>
                        <div class="dl-row"><span>Status verifikasi</span><strong>{{ $profil?->verification_status?->name ?? '—' }}</strong></div>
                        <div class="dl-row"><span>Lokasi</span><strong>{{ $profil?->address ?? '—' }}</strong></div>
                    </div>
                    @if ($tautanProfil)
                    <a class="btn btn-o btn-block" href="{{ $tautanProfil }}">Lihat profil penyedia</a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
