@extends('layouts.admin')

@section('title', 'Pembatalan & refund · Admin Pesta Kita')

@php
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Beranda</a> / Pembatalan &amp; refund</div><h1>Pembatalan &amp; refund</h1><p class="sub">Pengajuan pembatalan acara dan pengembalian dana klien.</p></div></div>

    <section class="card">
        <div aria-label="Navigasi status" class="tabs" role="navigation">
            @foreach($tabs as $key => $definition)
            <a href="{{ route('admin.pembatalan', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif>
                {{ $definition['label'] }}@if($counts[$key] > 0) <span class="n">{{ $counts[$key] }}</span>@endif
            </a>
            @endforeach
        </div>

        @if($cancellations->isEmpty())
        <div class="card-b"><p class="hint" style="margin:0">Belum ada data.</p></div>
        @else
        <div aria-label="Daftar pembatalan. Gulir horizontal untuk melihat seluruh kolom" class="table-wrap" role="region" tabindex="0">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Pengajuan pembatalan</caption>
                <thead>
                    <tr>
                        <th scope="col">Acara</th>
                        <th scope="col">Klien</th>
                        <th scope="col">Tanggal acara</th>
                        <th scope="col" class="num">Sisa hari</th>
                        <th scope="col" class="num">Persen</th>
                        <th scope="col" class="num">Refund + kode unik</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($cancellations as $cancellation)
                    <tr>
                        <td><b>Acara #{{ $cancellation->event_id }}</b><small class="sub-t">{{ $cancellation->event?->name }}</small></td>
                        <td>{{ $cancellation->event?->client?->name ?? '-' }}</td>
                        <td>{{ $cancellation->event?->event_date?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="num">{{ $cancellation->policy_snapshot['remaining_days'] ?? '-' }}</td>
                        <td class="num">{{ $cancellation->refund_percent }}%</td>
                        <td class="num"><b>{{ $rupiah($cancellation->refundTotal()) }}</b></td>
                        <td><span class="badge {{ $cancellation->status->badgeClass() }}">{{ $cancellation->status->label() }}</span></td>
                        <td class="col-aksi"><a class="btn btn-o btn-sm" href="{{ route('admin.pembatalan.show', $cancellation) }}">Detail</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($cancellations->hasPages())
        <div style="padding:16px;">{{ $cancellations->links() }}</div>
        @endif
        @endif
    </section>
</main>
@endsection
