@extends('layouts.admin')

@section('title', 'Pembatalan Acara #'.$cancellation->event_id.' · Admin Pesta Kita')

@php
use App\Enums\CancellationCause;
use App\Enums\CancellationStatus;

$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
$snapshot = $cancellation->policy_snapshot ?? [];
$lines = $snapshot['lines'] ?? [];
$event = $cancellation->event;
$canDecide = in_array($cancellation->status, [CancellationStatus::Diajukan, CancellationStatus::Ditinjau], true);
$causeLabels = [CancellationCause::Klien->value => 'Klien', CancellationCause::Vendor->value => 'Vendor/penyedia', CancellationCause::ForceMajeure->value => 'Force majeure'];
$sourceLabels = ['tier' => 'Tabel tier', 'override_admin' => 'Diubah Admin', 'penyebab' => 'Penyebab non-klien (100%)'];
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Beranda</a> / <a href="{{ route('admin.pembatalan') }}">Pembatalan &amp; refund</a> / Acara #{{ $cancellation->event_id }}</div>
            <h1>Pembatalan Acara #{{ $cancellation->event_id }}</h1>
            <p class="sub">{{ $event?->name }} · {{ $event?->event_date?->translatedFormat('l, d F Y') }}</p>
        </div>
        <span class="badge {{ $cancellation->status->badgeClass() }}">{{ $cancellation->status->label() }}</span>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:16px;border:1px solid #bbf7d0;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif

    <div class="grid two">
        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Perhitungan per pesanan</h2><p class="meta">Dihitung server dari harga penyedia (snapshot). Kompensasi = harga penyedia − bagian refund.</p></div>
                <div class="table-wrap" role="region" tabindex="0" aria-label="Perhitungan per pesanan">
                    <table class="t-wide">
                        <thead><tr><th scope="col">Pesanan</th><th scope="col" class="num">Harga penyedia</th><th scope="col" class="num">Kompensasi</th></tr></thead>
                        <tbody>
                        @forelse($lines as $line)
                            <tr><td>#{{ $line['order_item_id'] }} · {{ $line['label'] }}</td><td class="num">{{ $rupiah($line['provider_amount']) }}</td><td class="num">{{ $rupiah($line['compensation']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="hint">Belum ada data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-b">
                    <dl class="dl">
                        <div><dt>Grand total (tanpa kode unik)</dt><dd>{{ $rupiah($snapshot['grand_total'] ?? $cancellation->invoice?->grand_total) }}</dd></div>
                        <div><dt>Persen refund</dt><dd><b>{{ $cancellation->refund_percent }}%</b></dd></div>
                        <div><dt>Refund</dt><dd>{{ $rupiah($cancellation->refund_amount) }}</dd></div>
                        <div><dt>Kode unik (kembali penuh)</dt><dd>{{ $rupiah($cancellation->unique_code_refund) }}</dd></div>
                        @if($cancellation->status === CancellationStatus::Ditolak)
                        <div><dt>Refund ke klien</dt><dd><b>Tidak ada refund (pengajuan ditolak)</b></dd></div>
                        @else
                        <div><dt>{{ match ($cancellation->status) { CancellationStatus::RefundSelesai => 'Total ditransfer ke klien', CancellationStatus::Diajukan, CancellationStatus::Ditinjau => 'Perkiraan total refund ke klien', default => 'Total refund ke klien' } }}</dt><dd><b>{{ $rupiah($cancellation->refundTotal()) }}</b></dd></div>
                        @endif
                        <div><dt>Kompensasi penyedia</dt><dd>{{ $rupiah($cancellation->provider_compensation_total) }}</dd></div>
                        <div><dt>Pendapatan perusahaan</dt><dd>{{ $rupiah($cancellation->company_income) }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="card">
                <div class="card-h"><h2>Snapshot kebijakan</h2></div>
                <div class="card-b">
                    <dl class="dl">
                        <div><dt>Tanggal pengajuan</dt><dd>{{ $snapshot['requested_date'] ?? '-' }}</dd></div>
                        <div><dt>Tanggal acara</dt><dd>{{ $snapshot['event_date'] ?? '-' }}</dd></div>
                        <div><dt>Sisa hari</dt><dd>{{ $snapshot['remaining_days'] ?? '-' }} hari</dd></div>
                        <div><dt>Tier terpilih</dt><dd>&ge; {{ $snapshot['selected_tier']['min_days'] ?? '-' }} hari → {{ $snapshot['tier_percent'] ?? '-' }}%</dd></div>
                        <div><dt>Penyebab</dt><dd>{{ $causeLabels[$cancellation->cause->value] ?? $cancellation->cause->value }}</dd></div>
                        <div><dt>Sumber persen</dt><dd>{{ $sourceLabels[$snapshot['percent_source'] ?? 'tier'] ?? '-' }}</dd></div>
                    </dl>
                    @if(! empty($snapshot['tiers']))
                    <p class="hint mt-8">Tabel tier saat pengajuan:
                        @foreach($snapshot['tiers'] as $tierRow)&ge; {{ $tierRow['min_days'] }} hari: {{ $tierRow['percent'] }}%@if(! $loop->last) · @endif @endforeach
                    </p>
                    @endif
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Pengajuan klien</h2></div>
                <div class="card-b">
                    <dl class="dl">
                        <div><dt>Klien</dt><dd>{{ $event?->client?->name }} · {{ $event?->client?->email }}</dd></div>
                        <div><dt>Tagihan</dt><dd>{{ $cancellation->invoice?->invoice_number }} ({{ $rupiah($cancellation->invoice?->transfer_total) }})</dd></div>
                        <div><dt>Diajukan</dt><dd>{{ $cancellation->created_at?->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                        <div><dt>Alasan</dt><dd>{{ $cancellation->reason }}</dd></div>
                        <div><dt>Rekening tujuan refund</dt><dd>{{ $cancellation->refund_bank_name }} · {{ $cancellation->refund_account_number }} · a.n. {{ $cancellation->refund_account_name }}</dd></div>
                        @if($cancellation->reviewer)<div><dt>Ditangani</dt><dd>{{ $cancellation->reviewer->name }}</dd></div>@endif
                        @if($cancellation->decision_reason)<div><dt>Alasan keputusan</dt><dd>{{ $cancellation->decision_reason }}</dd></div>@endif
                        @if($cancellation->refund_paid_at)<div><dt>Tanggal transfer refund</dt><dd>{{ $cancellation->refund_paid_at->translatedFormat('d M Y') }}</dd></div>@endif
                    </dl>
                    @if($cancellation->refundProof)
                    <a class="btn btn-o btn-sm mt-12" href="{{ route('media.show', $cancellation->refundProof) }}" target="_blank" rel="noopener">Lihat bukti refund</a>
                    @endif
                </div>
            </section>

            @if($cancellation->status === CancellationStatus::Diajukan)
            <section class="card"><div class="card-b">
                <form method="POST" action="{{ route('admin.pembatalan.review', $cancellation) }}">@csrf<button class="btn btn-o" type="submit">Tandai sedang ditinjau</button></form>
            </div></section>
            @endif

            @if($canDecide)
            <section class="card">
                <div class="card-h"><h2>Setujui pembatalan</h2><p class="meta">Penyebab vendor/force majeure → refund 100% dan alasan wajib. Persen berbeda dari tier → alasan wajib.</p></div>
                <div class="card-b">
                    <form method="POST" action="{{ route('admin.pembatalan.approve', $cancellation) }}">
                        @csrf
                        <div class="field">
                            <label for="cause">Penyebab</label>
                            <select class="input" id="cause" name="cause" required>
                                @foreach($causeLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('cause', $cancellation->cause->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="refund_percent">Persen refund (khusus penyebab klien; kosongkan untuk mengikuti tier {{ $snapshot['tier_percent'] ?? $cancellation->refund_percent }}%)</label>
                            <input class="input" id="refund_percent" name="refund_percent" type="number" min="0" max="100" value="{{ old('refund_percent') }}">
                        </div>
                        <div class="field">
                            <label for="approve_reason">Alasan keputusan</label>
                            <textarea class="input" id="approve_reason" name="decision_reason" rows="2">{{ old('decision_reason') }}</textarea>
                        </div>
                        <button class="btn mt-8" type="submit">Setujui &amp; hitung ulang</button>
                    </form>
                </div>
            </section>
            <section class="card">
                <div class="card-h"><h2>Tolak pengajuan</h2></div>
                <div class="card-b">
                    <form method="POST" action="{{ route('admin.pembatalan.reject', $cancellation) }}">
                        @csrf
                        <div class="field">
                            <label for="reject_reason">Alasan penolakan <span class="req">*</span></label>
                            <textarea class="input" id="reject_reason" name="decision_reason" rows="2" required></textarea>
                        </div>
                        <button class="btn btn-o mt-8" type="submit" style="color:#b91c1c;border-color:#fca5a5;">Tolak pengajuan</button>
                    </form>
                </div>
            </section>
            @endif

            @if($cancellation->status === CancellationStatus::Disetujui)
            <section class="card"><div class="card-b">
                <p>Transfer <b>{{ $rupiah($cancellation->refundTotal()) }}</b> (refund + kode unik) ke {{ $cancellation->refund_bank_name }} {{ $cancellation->refund_account_number }} a.n. {{ $cancellation->refund_account_name }}.</p>
                <form method="POST" action="{{ route('admin.pembatalan.start-refund', $cancellation) }}" class="mt-8">@csrf<button class="btn" type="submit">Mulai proses refund</button></form>
            </div></section>
            @endif

            @if($cancellation->status === CancellationStatus::RefundDiproses)
            <section class="card">
                <div class="card-h"><h2>Catat refund selesai</h2><p class="meta">Nominal: <b>{{ $rupiah($cancellation->refundTotal()) }}</b> ke {{ $cancellation->refund_bank_name }} {{ $cancellation->refund_account_number }} a.n. {{ $cancellation->refund_account_name }}.</p></div>
                <div class="card-b">
                    <form method="POST" action="{{ route('admin.pembatalan.complete-refund', $cancellation) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="field">
                            <label for="refund_paid_at">Tanggal transfer <span class="req">*</span></label>
                            <input class="input" id="refund_paid_at" name="refund_paid_at" type="date" max="{{ now()->toDateString() }}" value="{{ old('refund_paid_at', now()->toDateString()) }}" required>
                        </div>
                        <div class="field">
                            <label for="proof">Bukti transfer (JPG/PNG/PDF, maks. 5 MB) <span class="req">*</span></label>
                            <input class="input" id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                        <button class="btn mt-8" type="submit">Simpan refund selesai</button>
                    </form>
                </div>
            </section>
            @endif
        </div>
    </div>
</main>
@endsection
