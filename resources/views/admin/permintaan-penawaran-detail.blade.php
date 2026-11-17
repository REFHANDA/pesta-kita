@extends('layouts.admin')

@section('title', 'Detail penawaran · Admin Pesta Kita')

@php
$rupiah = fn ($amount) => 'Rp '.number_format((int) $amount, 0, ',', '.');
$statusBadge = fn ($status) => match ($status) {
    \App\Enums\QuoteRequestStatus::Diajukan => ['b-warn', 'Menunggu usulan'],
    \App\Enums\QuoteRequestStatus::DiusulkanPenyedia => ['b-info', 'Menunggu keputusan'],
    \App\Enums\QuoteRequestStatus::DisetujuiAdmin => ['b-ok', 'Disetujui'],
    \App\Enums\QuoteRequestStatus::DitolakAdmin => ['b-bad', 'Ditolak'],
};
$subject = $quoteRequest?->subject;
$proposal = $quoteRequest?->proposals?->first();
$decision = $quoteRequest?->decision;
$providerName = match (true) {
    $subject instanceof \App\Models\CatalogItem => $subject->vendorProfile?->business_name,
    $subject instanceof \App\Models\VenueRoom => $subject->venue?->venueProfile?->business_name,
    default => null,
};
@endphp

@section('content')
<main class="content page-refined detail-page" id="konten" tabindex="-1">
    @if(!$quoteRequest)
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.penawaran') }}">Peninjauan penawaran</a></div>
            <h1>Detail penawaran</h1>
            <p class="meta">Pilih permintaan dari antrean peninjauan untuk melihat detailnya.</p>
        </div>
        <a class="btn btn-o" href="{{ route('admin.penawaran') }}">Kembali ke daftar</a>
    </div>
    <section class="card detail-card"><div class="card-body"><p class="m-0">Tidak ada permintaan yang dipilih.</p></div></section>
    @else
    @php
    [$badgeClass, $badgeLabel] = $statusBadge($quoteRequest->status);
    @endphp
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.penawaran') }}">Peninjauan penawaran</a> / #{{ $quoteRequest->id }}</div>
            <h1>Detail penawaran: {{ $subject?->name ?? 'Subjek sudah tidak tersedia' }}</h1>
            <p class="meta">Permintaan #{{ $quoteRequest->id }} · {{ $quoteRequest->event?->name ?? 'Tanpa acara tertaut' }} · {{ $quoteRequest->event_date?->translatedFormat('d F Y') ?? 'Tanggal tidak tersedia' }}</p>
        </div>
        <a class="btn btn-o" href="{{ route('admin.penawaran') }}">Kembali ke daftar</a>
    </div>

    @if(session('status'))
    <div class="alert" role="status"><span class="ic i-check" aria-hidden="true"></span><p>{{ session('status') }}</p></div>
    @endif
    @if($errors->has('quote_request'))
    <div class="alert" role="alert"><span class="ic i-info" aria-hidden="true"></span><p>{{ $errors->first('quote_request') }}</p></div>
    @endif

    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h">
                    <div class="split-head">
                        <div><h2>Perbandingan harga</h2><p class="meta">{{ $providerName ?? 'Penyedia tidak tersedia' }} · Permintaan #{{ $quoteRequest->id }}</p></div>
                        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Klien</span><span class="v">{{ $quoteRequest->client?->name ?? 'Akun tidak tersedia' }}</span></div>
                        <div class="kv"><span class="k">Acara</span><span class="v">{{ $quoteRequest->event?->name ?? 'Tanpa acara tertaut' }}</span></div>
                        <div class="kv"><span class="k">Tanggal acara</span><span class="v">{{ $quoteRequest->event_date?->translatedFormat('d F Y') ?? '—' }}</span></div>
                        <div class="kv"><span class="k">Perkiraan tamu</span><span class="v">{{ $quoteRequest->guest_count ? $quoteRequest->guest_count.' orang' : '—' }}</span></div>
                        <div class="kv"><span class="k">Waktu permintaan</span><span class="v">{{ $quoteRequest->created_at?->translatedFormat('d M Y H.i') ?? '—' }}</span></div>
                    </div>
                    <div class="price-compare">
                        <div><small>Harga katalog (referensi)</small><b>{{ $rupiah($quoteRequest->catalog_price_snapshot) }}</b></div>
                        <span>→</span>
                        <div><small>Harga diajukan penyedia</small><b>{{ $proposal ? $rupiah($proposal->proposed_amount) : 'Belum ada usulan' }}</b></div>
                    </div>
                    <div class="detail-list">
                        <div class="dl-row">
                            <span>Harga disetujui Admin</span>
                            <strong>
                                @if($decision?->decision === \App\Enums\QuoteDecisionResult::Disetujui)
                                {{ $rupiah($decision->approved_amount) }}
                                @elseif($decision?->decision === \App\Enums\QuoteDecisionResult::Ditolak)
                                Ditolak
                                @else
                                Belum ditetapkan
                                @endif
                            </strong>
                        </div>
                    </div>
                    @if($decision?->reason)
                    <p class="hint mt-12">Alasan keputusan: {{ $decision->reason }}</p>
                    @endif
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Harga persetujuan dicatat terpisah dari harga katalog dan harga usulan. Nominal persetujuan berasal dari proposal terakhir di basis data.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Kebutuhan Klien</h2><p class="meta">Keterangan permintaan yang diteruskan ke penyedia.</p></div>
                <div class="card-body">
                    <p class="quote-panel">{{ $quoteRequest->notes ?: 'Tidak ada catatan kebutuhan khusus.' }}</p>
                    @if($proposal?->notes)
                    <p class="field-label">Catatan penyedia</p>
                    <p class="quote-panel">{{ $proposal->notes }}</p>
                    @endif
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat penawaran</h2></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item"><b>Permintaan Klien masuk</b><small>{{ $quoteRequest->created_at?->translatedFormat('d M Y H.i') ?? '—' }}</small></div>
                        @foreach($quoteRequest->proposals->sortBy('submitted_at') as $historyProposal)
                        <div class="timeline-item"><b>Penyedia mengusulkan harga {{ $rupiah($historyProposal->proposed_amount) }}</b><small>{{ $historyProposal->submitted_at?->translatedFormat('d M Y H.i') ?? '—' }}</small></div>
                        @endforeach
                        @if($decision)
                        <div class="timeline-item"><b>Keputusan Admin: {{ $decision->decision->value }}</b><small>{{ $decision->decided_at?->translatedFormat('d M Y H.i') ?? '—' }} · {{ $decision->admin?->name ?? 'Admin' }}</small></div>
                        @elseif($quoteRequest->status === \App\Enums\QuoteRequestStatus::Diajukan)
                        <div class="timeline-item"><b>Menunggu usulan penyedia</b><small>Status saat ini</small></div>
                        @else
                        <div class="timeline-item"><b>Menunggu keputusan Admin</b><small>Status saat ini</small></div>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Tindakan Admin</h2><p class="meta">Catat keputusan final untuk permintaan ini.</p></div>
                <div class="card-body">
                    @if($quoteRequest->status === \App\Enums\QuoteRequestStatus::DiusulkanPenyedia && $proposal)
                    <form method="post" action="{{ route('admin.penawaran.decide', [$quoteRequest, 'approve']) }}">
                        @csrf
                        <label class="field-label" for="approval-reason">Catatan persetujuan (opsional)</label>
                        <textarea class="field-control" id="approval-reason" name="reason" rows="3" maxlength="2000">{{ old('reason') }}</textarea>
                        @error('reason')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        <button class="btn btn-block mt-12" type="submit">Setujui proposal {{ $rupiah($proposal->proposed_amount) }}</button>
                    </form>

                    <form method="post" action="{{ route('admin.penawaran.decide', [$quoteRequest, 'reject']) }}" class="mt-16">
                        @csrf
                        <label class="field-label" for="rejection-reason">Alasan penolakan</label>
                        <textarea class="field-control" id="rejection-reason" name="reason" rows="3" minlength="5" maxlength="2000" required>{{ old('reason') }}</textarea>
                        @error('reason')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        <button class="btn btn-danger btn-block mt-12" type="submit">Tolak proposal</button>
                    </form>
                    @elseif($decision)
                    <p class="body-copy">Keputusan final sudah tercatat: <b>{{ $decision->decision->value }}</b>. Skema BE-2 menjaga satu keputusan per permintaan; pengusulan ulang tidak tersedia dalam alur ini.</p>
                    @else
                    <p class="body-copy">Keputusan tersedia setelah penyedia mengajukan proposal harga.</p>
                    @endif
                    <div class="alert mt-16"><span class="ic i-info" aria-hidden="true"></span><p>Keputusan final dicatat dalam riwayat audit. Harga persetujuan tidak mengubah harga katalog.</p></div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h"><h2>Informasi penyedia</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row"><span>Nama penyedia</span><strong>{{ $providerName ?? '—' }}</strong></div>
                        @if($subject instanceof \App\Models\CatalogItem)
                        <div class="dl-row"><span>Jenis</span><strong>Vendor</strong></div>
                        <div class="dl-row"><span>Status verifikasi</span><strong>{{ $subject->vendorProfile?->verification_status?->name ?? '—' }}</strong></div>
                        @elseif($subject instanceof \App\Models\VenueRoom)
                        <div class="dl-row"><span>Jenis</span><strong>Pemilik venue</strong></div>
                        <div class="dl-row"><span>Status verifikasi</span><strong>{{ $subject->venue?->venueProfile?->verification_status?->name ?? '—' }}</strong></div>
                        @endif
                    </div>
                </div>
            </section>
        </aside>
    </div>
    @endif
</main>
@endsection
