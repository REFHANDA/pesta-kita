@extends('layouts.venue')

@section('title', 'Rundown acara · Panel Pemilik Venue Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / Rundown</div><h1>Rundown acara</h1><p class="sub">Pemilik venue hanya dapat melihat rundown acara yang memakai venue Anda.</p></div></div>
    @if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif

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
                        <td><a class="btn btn-o btn-sm" href="{{ route('venue.rundown.show', $event) }}">Lihat rundown</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada acara dengan pesanan venue terkonfirmasi. Rundown tampil setelah pembayaran acara terkonfirmasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
