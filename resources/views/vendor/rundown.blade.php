@extends('layouts.vendor')

@section('title', 'Rundown acara · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Rundown</div><h1>Rundown acara</h1><p class="sub">Rundown acara untuk pesanan Anda yang sudah terkonfirmasi atau selesai. Anda hanya dapat mengubah item yang Anda buat sendiri.</p></div></div>
    @if(session('success'))<div class="alert" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert warn" role="alert">{{ session('error') }}</div>@endif

    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar acara dengan rundown">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Acara dengan rundown</caption>
                <thead><tr><th scope="col">Acara</th><th scope="col">Tanggal</th><th scope="col" class="num">Tamu</th><th scope="col" class="num">Item rundown</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td><b>Acara #{{ $event->id }}</b></td>
                        <td>{{ $event->event_date?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="num">{{ $event->guest_count ?? '—' }}</td>
                        <td class="num">{{ $event->rundown_items_count }}</td>
                        <td><a class="btn btn-o btn-sm" href="{{ route('vendor.rundown.show', $event) }}">Lihat rundown</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada acara dengan pesanan terkonfirmasi. Rundown tampil setelah pembayaran acara terkonfirmasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
