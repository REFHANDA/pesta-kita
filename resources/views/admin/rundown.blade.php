@extends('layouts.admin')

@section('title', 'Monitoring rundown · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Pemantauan / Rundown</div><h1>Monitoring rundown</h1><p class="sub">Admin hanya dapat melihat jadwal acara.</p></div></div>
    @forelse ($events as $event)
        <section class="card mt-16">
            <div class="card-h"><div><h2><a href="{{ route('admin.acara.detail', $event) }}">{{ $event->name }}</a></h2><p class="meta">{{ $event->event_date?->translatedFormat('l, d F Y') }}</p></div><span class="badge b-ok">{{ ucfirst(str_replace('_', ' ', $event->status->value)) }}</span></div>
            <div class="table-wrap" role="region" tabindex="0" aria-label="Rundown {{ $event->name }}">
                <table class="t-wide">
                    <caption class="sr-only">Segmen rundown {{ $event->name }}</caption>
                    <thead><tr><th scope="col">Waktu</th><th scope="col">Agenda</th><th scope="col">Item pesanan</th><th scope="col">Durasi</th><th scope="col">Catatan</th></tr></thead>
                    <tbody>
                    @forelse ($event->rundownItems->sortBy('starts_at') as $segment)
                        <tr>
                            <td>{{ $segment->starts_at?->format('H:i') ?? '—' }}</td>
                            <td>{{ $segment->title }}</td>
                            <td>{{ $segment->orderItem?->subject?->name ?? 'Dikelola Klien' }}</td>
                            <td>{{ $segment->duration_minutes ? $segment->duration_minutes.' menit' : '—' }}</td>
                            <td>{{ $segment->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="hint" colspan="5">Belum ada segmen rundown.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <section class="card"><p class="hint" style="padding:16px">Belum ada acara untuk dipantau.</p></section>
    @endforelse
    <div class="card-b">{{ $events->links() }}</div>
</main>
@endsection
