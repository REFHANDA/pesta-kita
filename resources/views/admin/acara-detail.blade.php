@extends('layouts.admin')

@section('title', 'Acara #'.$event->id.' · Admin Pesta Kita')

@php
$label = fn ($status) => ucfirst(str_replace('_', ' ', $status->value));
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.acara') }}">Acara</a> / Acara #{{ $event->id }}</div><h1>{{ $event->name }}</h1><p class="sub">Acara #{{ $event->id }} · {{ $event->event_date?->translatedFormat('l, d M Y') ?? 'Tanggal belum diisi' }}</p></div><span class="badge b-mute">{{ $label($event->status) }}</span></div>
    <x-subnav label="Detail acara" :items="[
        ['label' => 'Ringkasan', 'href' => '#ringkasan'],
        ['label' => 'Rundown', 'href' => '#rundown'],
        ['label' => 'Evaluasi', 'href' => '#evaluasi'],
    ]" />
    <div class="grid cols2" id="ringkasan">
        <section class="card"><div class="card-h"><h2>Data acara</h2></div><div class="card-b"><dl class="dl">
            <div><dt>Klien</dt><dd>{{ $event->client?->name ?? '—' }}</dd></div>
            <div><dt>Email klien</dt><dd>{{ $event->client?->email ?? '—' }}</dd></div>
            <div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count ?? '—' }}</dd></div>
            <div><dt>Lokasi</dt><dd>{{ $event->location ?? '—' }}</dd></div>
        </dl></div></section>
        <section class="card"><div class="card-h"><h2>Tagihan</h2></div><div class="card-b">
            @forelse($event->invoices as $invoice)
                <p><a href="{{ route('admin.pembayaran.show', $invoice) }}">{{ $invoice->invoice_number }}</a> · {{ $invoice->status->label() }}</p>
            @empty
                <p class="hint" style="margin:0">Belum ada tagihan.</p>
            @endforelse
        </div></section>
    </div>
    @if(session('success'))
    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin:16px 0;border:1px solid #bbf7d0;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin:16px 0;">{{ session('error') }}</div>
    @endif
    @if($event->status === \App\Enums\EventStatus::Terkonfirmasi)
    <section class="card mt-20" id="tandai-selesai"><div class="card-h"><h2>Tandai selesai</h2><p class="meta">Membuat pencairan hak pesanan (harga penyedia × jumlah) untuk tiap penyedia. Komisi dan biaya kebersihan tidak dicairkan.</p></div>
        <div class="card-b">
            @if($completionBlocker ?? null)
            <p class="hint">{{ $completionBlocker }}</p>
            @else
            <form method="POST" action="{{ route('admin.acara.selesai', $event) }}" onsubmit="return confirm('Tandai Acara #{{ $event->id }} selesai dan buat pencairan hak pesanan?');">
                @csrf
                <button class="btn" type="submit">Tandai selesai</button>
            </form>
            @endif
        </div>
    </section>
    @endif
    <section class="card mt-20"><div class="card-h"><h2>Pesanan</h2></div>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Pesanan acara">
            <table class="t-wide"><caption class="sr-only">Tabel: Pesanan acara</caption>
                <thead><tr><th scope="col">Item</th><th scope="col">Penyedia</th><th scope="col" class="num">Harga penyedia</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse($event->orderItems as $item)
                    <tr>
                        <td><a class="link-plain" href="{{ route('admin.pesanan.show', $item) }}">{{ $item->subject?->name ?? 'Item' }}</a></td>
                        <td>{{ $item->provider?->business_name ?? '—' }}</td>
                        <td class="num">{{ $rupiah($item->provider_price) }}</td>
                        <td><span class="badge b-mute">{{ $label($item->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card mt-20" id="rundown"><div class="card-h"><h2>Rundown</h2><p class="meta">Hanya lihat. Penyusunan dilakukan klien dan vendor.</p></div>
        <div class="card-b">
            @if($rundownItems->isEmpty())
            <p class="hint" style="margin:0">{{ $event->status === \App\Enums\EventStatus::Terkonfirmasi ? 'Belum ada item rundown.' : 'Belum ada item rundown untuk acara ini.' }}</p>
            @else
            <div class="table-wrap" role="region" tabindex="0" aria-label="Rundown acara">
                <table class="t-wide"><caption class="sr-only">Tabel: Rundown acara</caption>
                    <thead><tr><th scope="col">Jam</th><th scope="col">Kegiatan</th><th scope="col">Keterangan</th><th scope="col">Penyedia terkait</th><th scope="col">Dibuat oleh</th></tr></thead>
                    <tbody>
                    @foreach($rundownItems as $rundownItem)
                        <tr>
                            <td><b>{{ $rundownItem->start_time }}</b>{{ $rundownItem->end_time ? '–'.$rundownItem->end_time : '' }}</td>
                            <td>{{ $rundownItem->activity }}</td>
                            <td>{{ $rundownItem->description ?: '—' }}</td>
                            <td>{{ $rundownItem->orderItem?->subject?->name ?? '—' }}</td>
                            <td>{{ $rundownItem->creator?->name ?? '—' }} <span class="badge b-mute">{{ $rundownItem->creator?->role?->label() ?? '—' }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </section>

    <section class="card mt-20" id="evaluasi"><div class="card-h"><h2>Evaluasi</h2><p class="meta">Hasil penilaian klien untuk venue, vendor, dan Admin. Admin dapat membaca masukan sistem.</p></div>
        <div class="card-b">
            @php($evaluation = $evaluation ?? null)
            @if(! $evaluation)
            <p class="hint" style="margin:0">{{ $event->status === \App\Enums\EventStatus::Selesai ? 'Acara sudah selesai dan belum dievaluasi klien.' : 'Belum ada evaluasi untuk acara ini.' }}</p>
            @else
            <div class="table-wrap" role="region" tabindex="0" aria-label="Hasil evaluasi acara">
                <table class="t-wide"><caption class="sr-only">Tabel: Hasil evaluasi acara</caption>
                    <thead><tr><th scope="col">Subjek</th><th scope="col">Rating</th><th scope="col">Komentar</th></tr></thead>
                    <tbody>
                    @foreach($evaluation->items as $evaluationItem)
                        <tr>
                            <td><b>{{ $evaluationNames[$evaluationItem->target_type.':'.($evaluationItem->target_id ?? 0)] ?? ucfirst($evaluationItem->target_type) }}</b></td>
                            <td><span aria-hidden="true">{{ str_repeat('★', (int) $evaluationItem->rating).str_repeat('☆', max(0, 5 - (int) $evaluationItem->rating)) }}</span> <span class="hint">{{ $evaluationItem->rating }}/5</span></td>
                            <td>{{ $evaluationItem->comment ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($evaluation->system_feedback)
            <dl class="dl mt-12"><div><dt>Masukan untuk sistem</dt><dd>{{ $evaluation->system_feedback }}</dd></div></dl>
            @endif
            <p class="hint mt-8">Dikirim {{ $evaluation->created_at?->translatedFormat('d M Y, H:i') }} WIB oleh {{ $event->client?->name ?? 'klien' }}.</p>
            @endif
            <dl class="dl mt-16">
                <div><dt>Susunan ruangan</dt><dd>{{ $event->room_layout?->label() ?? 'Belum ditentukan' }}</dd></div>
                @if($event->room_layout_notes)<div><dt>Catatan susunan</dt><dd>{{ $event->room_layout_notes }}</dd></div>@endif
            </dl>
        </div>
    </section>
</main>
@endsection
