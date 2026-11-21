@extends('layouts.venue')

@section('title', 'Rundown Acara #'.$event->id.' · Panel Pemilik Venue Pesta Kita')

@section('content')
@php($lockedMessage = $lockedMessage ?? null)
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / <a href="{{ route('venue.rundown') }}">Rundown</a> / Acara #{{ $event->id }}</div>
            <h1>Rundown Acara #{{ $event->id }}</h1>
            <p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count ?? '—' }} tamu</p>
        </div>
        <a class="btn btn-o" href="{{ route('venue.rundown') }}">Kembali</a>
    </div>

    <section class="card">
        <div class="card-h"><div><h2>Susunan acara</h2><p class="meta">Hanya lihat. Perubahan rundown dilakukan klien atau vendor.</p></div></div>
        <div class="card-b">
            @if($rundownItems->isEmpty())
            <p class="hint" style="margin:0">Belum ada item rundown untuk acara ini.</p>
            @else
            <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar rundown acara">
                <table class="t-wide">
                    <caption class="sr-only">Tabel: Rundown Acara #{{ $event->id }}</caption>
                    <thead><tr><th scope="col">Jam</th><th scope="col">Kegiatan</th><th scope="col">Keterangan</th><th scope="col">Sumber</th></tr></thead>
                    <tbody>
                    @foreach($rundownItems as $rundownItem)
                    <tr>
                        <td><b>{{ $rundownItem->start_time }}</b>{{ $rundownItem->end_time ? '–'.$rundownItem->end_time : '' }}</td>
                        <td>{{ $rundownItem->activity }}</td>
                        <td>{{ $rundownItem->description ?: '—' }}</td>
                        <td>@if($rundownItem->creator?->role === \App\Enums\Role::Vendor)<span class="badge b-info">Vendor</span>@else<span class="badge b-mute">Klien</span>@endif</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @if($lockedMessage)<p class="hint mt-12">{{ $lockedMessage }}</p>@endif

            @if($event->room_layout)
            <dl class="dl mt-16">
                <div><dt>Susunan ruangan</dt><dd>{{ $event->room_layout->label() }}</dd></div>
                @if($event->room_layout_notes)<div><dt>Catatan susunan</dt><dd>{{ $event->room_layout_notes }}</dd></div>@endif
            </dl>
            @endif
        </div>
    </section>
</main>
@endsection
