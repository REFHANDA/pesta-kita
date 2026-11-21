@extends('layouts.admin')

@section('title', 'Acara · Admin Pesta Kita')

@php
$statusLabel = fn ($status) => ucfirst(str_replace('_', ' ', $status->value));
$statuses = array_filter(\App\Enums\EventStatus::cases(), fn ($s) => $s !== \App\Enums\EventStatus::Draft);
$counts = $counts ?? collect();
$activeStatus = $activeStatus ?? null;
$activeTab = $activeTab ?? 'semua';

// G2: tab memakai komponen subnav yang sudah bergaya di admin.css.
$tabItems = [[
    'label' => 'Semua',
    'href' => route('admin.acara'),
    'active' => $activeStatus === null && $activeTab !== 'evaluasi',
    'count' => $totalSemua ?? 0,
]];
foreach ($statuses as $status) {
    $tabItems[] = [
        'label' => $statusLabel($status),
        'href' => route('admin.acara', ['status' => $status->value]),
        'active' => $activeStatus === $status,
        'count' => (int) ($counts[$status->value] ?? 0),
    ];
}
$tabItems[] = [
    'label' => 'Evaluasi',
    'href' => route('admin.acara', ['tab' => 'evaluasi']),
    'active' => $activeTab === 'evaluasi',
    'count' => (int) (($counts[\App\Enums\EventStatus::Selesai->value] ?? 0) + ($counts[\App\Enums\EventStatus::Dievaluasi->value] ?? 0)),
];
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Acara</div><h1>Acara</h1><p class="sub">Pantau acara klien yang sudah diajukan. Draft klien tidak ditampilkan.</p></div></div>
    <x-subnav label="Filter status acara" :items="$tabItems" />
    <section class="card">
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar acara">
            <table class="t-wide"><caption class="sr-only">Tabel: Daftar acara</caption>
                <thead><tr><th scope="col">Acara</th><th scope="col">Klien</th><th scope="col">Tanggal</th><th scope="col">Pesanan</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td><b><a class="link-plain" href="{{ route('admin.acara.detail', $event) }}">Acara #{{ $event->id }} · {{ $event->name }}</a></b></td>
                        <td>{{ $event->client?->name ?? '—' }}</td>
                        <td>{{ $event->event_date?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td>{{ $event->order_items_count }}</td>
                        <td><span class="badge b-mute">{{ $statusLabel($event->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($events->hasPages())<div class="card-b">{{ $events->links() }}</div>@endif
    </section>
</main>
@endsection
