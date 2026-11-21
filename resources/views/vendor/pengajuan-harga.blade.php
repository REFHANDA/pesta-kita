@extends('layouts.vendor')

@section('title', 'Pengajuan harga · Panel Vendor Pesta Kita')

@php
$requests = $requests ?? collect();
$items = $items ?? collect();
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
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Pengajuan harga</div>
            <h1>Pengajuan harga</h1>
            <p class="sub">Perubahan harga harus ditinjau Admin. Selama pengajuan berstatus menunggu, harga lama tetap berlaku dan pesanan yang sudah disetujui tidak berubah.</p>
        </div>
    </div>

    <x-subnav label="Katalog saya" :items="[
    ['label' => 'Layanan', 'href' => route('vendor.layanan'), 'active' => request()->routeIs('vendor.layanan')],
    ['label' => 'Peralatan', 'href' => route('vendor.peralatan'), 'active' => request()->routeIs('vendor.peralatan')],
    ['label' => 'Pengajuan harga', 'href' => route('vendor.pengajuan-harga'), 'active' => request()->routeIs('vendor.pengajuan-harga')],
]" />
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('item_id')<div class="alert warn" role="alert">{{ $message }}</div>@enderror

    <section class="card">
        <div class="tabs">
            <a href="{{ url('/vendor/pengajuan-harga') }}" @if($filter === null) aria-current="page" @endif>Semua pengajuan</a>
            <a href="{{ url('/vendor/pengajuan-harga?status=menunggu') }}" @if($filter === 'menunggu') aria-current="page" @endif>Menunggu</a>
            <a href="{{ url('/vendor/pengajuan-harga?status=disetujui') }}" @if($filter === 'disetujui') aria-current="page" @endif>Disetujui</a>
            <a href="{{ url('/vendor/pengajuan-harga?status=ditolak') }}" @if($filter === 'ditolak') aria-current="page" @endif>Ditolak</a>
        </div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Riwayat pengajuan harga">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Riwayat pengajuan harga</caption>
                <thead><tr><th scope="col">Item</th><th scope="col">Jenis</th><th scope="col" class="num">Harga lama</th><th scope="col" class="num">Harga diajukan</th><th scope="col">Tanggal</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse($requests as $row)
                    @php($subject = $row->subject)
                    <tr>
                        <td><b>{{ $subject?->name ?? 'Item telah dihapus' }}</b></td>
                        <td>{{ $subject && $subject->kind ? ($subject->kind->value === 'layanan' ? 'Layanan' : 'Peralatan') : 'Layanan' }}</td>
                        <td class="num">{{ $rupiah($row->old_price) }}</td>
                        <td class="num">{{ $rupiah($row->new_price) }}</td>
                        <td>{{ $row->created_at->translatedFormat('d M Y') }}</td>
                        <td><span class="badge {{ $badge[$row->status->value] }}">{{ $label[$row->status->value] }}</span>
                            @if($row->status === \App\Enums\PriceChangeStatus::Ditolak && $row->decision_note)<br><small class="hint">{{ $row->decision_note }}</small>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hint">Belum ada pengajuan harga berstatus {{ $filter ? str_replace('_', ' ', $filter) : 'ini' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-16">
        <div class="card-h"><h2>Ajukan perubahan harga</h2></div>
        <div class="card-b">
            @if($items->isEmpty())
                <p class="hint">Anda belum memiliki item katalog aktif yang dapat diajukan perubahan harganya. <a href="{{ url('/vendor/item-form') }}">Tambahkan item</a> terlebih dahulu.</p>
            @else
            <form method="POST" action="{{ route('vendor.pengajuan-harga.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="item_id">Item</label>
                    <select class="input" id="item_id" name="item_id" required data-price-sync>
                        <option value="" disabled selected>Pilih item</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" data-price="{{ $item->catalog_price }}">{{ $item->name }} ({{ $item->kind->value === 'layanan' ? 'Layanan' : 'Peralatan' }})</option>
                        @endforeach
                    </select>
                    @error('item_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="harga-saat-ini">Harga saat ini</label>
                    <input class="input locked" id="harga-saat-ini" value="—" readonly>
                </div>
                <div class="field">
                    <label for="new_price">Harga baru (rupiah)</label>
                    <input class="input" id="new_price" name="new_price" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 2900000" required>
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
            <div class="note mt-12"><b>Catatan:</b> Harga lama tetap berlaku sampai Admin memutuskan. Anda hanya dapat mengirim satu pengajuan per item pada satu waktu.</div>
            @endif
        </div>
    </section>
</main>

@push('scripts')
<script>
/* tampilkan harga aktif item terpilih (sumber kebenaran tetap server). */
(function () {
    'use strict';
    var select = document.getElementById('item_id');
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
