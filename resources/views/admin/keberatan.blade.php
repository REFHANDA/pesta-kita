@extends('layouts.admin')

@section('title', 'Keberatan penyedia · Admin Pesta Kita')

@php
    $objections = $objections ?? collect();
    $statusFilter = $statusFilter ?? 'diajukan';
    $filters = ['diajukan' => 'Terbuka', 'diputuskan' => 'Diputuskan', 'semua' => 'Semua'];
@endphp

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Keberatan</div><h1>Keberatan penyedia</h1><p class="sub">Persetujuan keberatan mengeluarkan item dari pesanan; tidak menetapkan refund.</p></div></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    <section class="card">
        <nav class="tabs" aria-label="Filter keberatan">
            @foreach ($filters as $value => $label)
                <a href="{{ route('admin.keberatan', ['status' => $value]) }}" @if ($statusFilter === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar keberatan penyedia">
            <table class="t-wide">
                <caption class="sr-only">Keberatan yang diajukan penyedia</caption>
                <thead><tr><th scope="col">Pesanan</th><th scope="col">Penyedia</th><th scope="col">Acara</th><th scope="col">Diajukan</th><th scope="col">Status</th></tr></thead>
                <tbody>
                @forelse ($objections as $objection)
                    @php
                        $item = $objection->orderItem;
                        $subject = $item?->subject;
                        $subjectName = $subject?->name ?? 'Item tidak tersedia';
                        if ($subject instanceof \App\Models\VenueRoom) {
                            $subjectName = ($subject->venue?->name ? $subject->venue->name.' — ' : '').$subjectName;
                        }
                    @endphp
                    <tr>
                        <td><a href="{{ route('admin.keberatan.detail', $objection) }}">#{{ $item?->id }} · {{ $subjectName }}</a></td>
                        <td>{{ $objection->providerUser?->name ?? 'Akun penyedia' }}</td>
                        <td>{{ $item?->event?->name }}</td>
                        <td>{{ $objection->created_at?->translatedFormat('d M Y H:i') }}</td>
                        <td>{{ ucfirst($objection->admin_decision ?? $objection->status) }}</td>
                    </tr>
                @empty
                    <tr><td class="hint" colspan="5">Tidak ada keberatan untuk filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($objections, 'links'))<div class="card-b">{{ $objections->links() }}</div>@endif
    </section>
</main>
@endsection
