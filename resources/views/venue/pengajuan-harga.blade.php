@extends('layouts.venue')

@section('title', 'Pengajuan harga · Panel Pemilik Venue Pesta Kita')

@php
$requests = $requests ?? collect();
$venuesList = $venues ?? collect();
$filter = $statusFilter ?? null;
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
$badge = [
    \App\Enums\PriceChangeStatus::Menunggu->value => 'b-warn',
    \App\Enums\PriceChangeStatus::Disetujui->value => 'b-ok',
    \App\Enums\PriceChangeStatus::Ditolak->value => 'b-bad',
];
$label = [
    \App\Enums\PriceChangeStatus::Menunggu->value => 'Menunggu keputusan',
    \App\Enums\PriceChangeStatus::Disetujui->value => 'Disetujui',
    \App\Enums\PriceChangeStatus::Ditolak->value => 'Ditolak',
];
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / Pengajuan harga</div>
            <h1>Pengajuan harga</h1>
            <p class="sub">Perubahan harga sewa harus ditinjau Admin. Selama pengajuan berstatus menunggu, harga lama tetap berlaku dan pesanan yang sudah disetujui tidak berubah.</p>
        </div>
    </div>

    @if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('venue_id')<div class="alert warn" role="alert">{{ $message }}</div>@enderror

    <section class="card">
        <div class="tabs">
            <a href="{{ url('/venue/pengajuan-harga') }}" @if($filter === null) aria-current="page" @endif>Semua pengajuan</a>
            <a href="{{ url('/venue/pengajuan-harga?status=menunggu') }}" @if($filter === 'menunggu') aria-current="page" @endif>Menunggu</a>
            <a href="{{ url('/venue/pengajuan-harga?status=disetujui') }}" @if($filter === 'disetujui') aria-current="page" @endif>Disetujui</a>
            <a href="{{ url('/venue/pengajuan-harga?status=ditolak') }}" @if($filter === 'ditolak') aria-current="page" @endif>Ditolak</a>
        </div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Riwayat pengajuan harga sewa venue">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Riwayat pengajuan harga</caption>
                <thead><tr><th scope="col">Venue</th><th scope="col" class="num">Harga lama</th><th scope="col" class="num">Harga diajukan</th><th scope="col">Tanggal</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse($requests as $row)
                    <tr>
                        <td><b>{{ $row->subject?->name ?? 'Venue telah dihapus' }}</b></td>
                        <td class="num">{{ $rupiah($row->old_price) }}</td>
                        <td class="num">{{ $rupiah($row->new_price) }}</td>
                        <td>{{ $row->created_at->translatedFormat('d M Y') }}</td>
                        <td><span class="badge {{ $badge[$row->status->value] }}">{{ $label[$row->status->value] }}</span>
                            @if($row->status === \App\Enums\PriceChangeStatus::Ditolak && $row->decision_note)<br><small class="hint">{{ $row->decision_note }}</small>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hint">Belum ada pengajuan harga berstatus {{ $filter ? str_replace('_', ' ', $filter) : 'ini' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-16">
        <div class="card-h"><h2>Ajukan perubahan harga sewa</h2></div>
        <div class="card-b">
            @if($venuesList->isEmpty())
                <p class="hint">Anda belum memiliki venue yang dapat diajukan perubahan harganya. <a href="{{ url('/venue/form') }}">Tambahkan venue</a> terlebih dahulu.</p>
            @else
            <form method="POST" action="{{ route('venue.pengajuan-harga.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="venue_id">Venue</label>
                    <select class="input" id="venue_id" name="venue_id" required>
                        <option value="" disabled selected>Pilih venue</option>
                        @foreach($venuesList as $venue)
                            <option value="{{ $venue->id }}" data-price="{{ $venue->rental_price }}">{{ $venue->name }}</option>
                        @endforeach
                    </select>
                    @error('venue_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="harga-saat-ini">Harga saat ini</label>
                    <input class="input locked" id="harga-saat-ini" value="—" readonly>
                </div>
                <div class="field">
                    <label for="new_price">Harga baru (rupiah)</label>
                    <input class="input" id="new_price" name="new_price" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 5500000" required>
                    <p class="hint">Isi bilangan bulat rupiah tanpa titik atau pemisah.</p>
                    @error('new_price')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field full">
                    <label for="reason">Alasan perubahan</label>
                    <textarea class="input" id="reason" name="reason" rows="3" placeholder="Jelaskan alasan perubahan harga" required></textarea>
                    @error('reason')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="full">
                    <button class="btn" type="submit">Kirim ke Admin</button>
                </div>
            </form>
            <div class="note mt-12"><b>Catatan:</b> Harga lama tetap berlaku sampai Admin memutuskan. Anda hanya dapat mengirim satu pengajuan per venue pada satu waktu.</div>
            @endif
        </div>
    </section>
</main>

@push('scripts')
<script>
/* Wiring BE-3b: tampilkan harga sewa aktif venue terpilih (sumber kebenaran tetap server). */
(function () {
    'use strict';
    var select = document.getElementById('venue_id');
    var view = document.getElementById('harga-saat-ini');
    if (!select || !view) return;
    function fmt(n) { return 'Rp ' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        view.value = opt && opt.getAttribute('data-price') ? fmt(opt.getAttribute('data-price')) : '—';
    });
})();
</script>
@endpush
@endsection
