@extends('layouts.admin')

@section('title', 'Detail keberatan · Admin Pesta Kita')

@php
    $item = $objection->orderItem;
    $subject = $item->subject;
    $subjectName = $subject?->name ?? 'Item tidak tersedia';
    if ($subject instanceof \App\Models\VenueRoom) {
        $subjectName = ($subject->venue?->name ? $subject->venue->name.' — ' : '').$subjectName;
    }
@endphp

@section('content')
<main class="content page-refined detail-page" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / <a href="{{ route('admin.keberatan') }}">Keberatan</a> / Detail</div><h1>Detail keberatan</h1></div><a class="btn btn-o" href="{{ route('admin.keberatan') }}">Kembali</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('objection')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two">
        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Pesanan #{{ $item->id }}</h2><span class="badge {{ $objection->status === 'diajukan' ? 'b-warn' : 'b-mute' }}">{{ ucfirst($objection->admin_decision ?? $objection->status) }}</span></div>
                <div class="card-b">
                    <dl class="dl">
                        <div><dt>Item</dt><dd>{{ $subjectName }}</dd></div>
                        <div><dt>Acara</dt><dd>{{ $item->event->name }}</dd></div>
                        <div><dt>Tanggal acara</dt><dd>{{ $item->event->event_date?->translatedFormat('d M Y') }}</dd></div>
                        <div><dt>Penyedia</dt><dd>{{ $objection->providerUser->name }}</dd></div>
                        <div><dt>Harga final</dt><dd>{{ $item->final_price === null ? 'Belum ditetapkan' : 'Rp '.number_format($item->final_price, 0, ',', '.') }}</dd></div>
                        <div><dt>Batas keberatan</dt><dd>{{ $item->objection_deadline?->translatedFormat('d M Y H:i') }} WIB</dd></div>
                    </dl>
                </div>
            </section>
            <section class="card"><div class="card-h"><h2>Alasan penyedia</h2></div><div class="card-b"><p>{{ $objection->reason }}</p></div></section>
            @if ($objection->admin_decision)
                <section class="card"><div class="card-h"><h2>Keputusan Admin</h2></div><div class="card-b"><p>{{ ucfirst($objection->admin_decision) }}</p><p>{{ $item->rejection_reason }}</p><p class="meta">Diputuskan {{ $objection->decided_at?->translatedFormat('d M Y H:i') }}</p></div></section>
            @endif
        </div>
        <aside class="stack">
            @if ($objection->status === 'diajukan')
                <section class="card">
                    <div class="card-h"><h2>Putuskan keberatan</h2></div>
                    <div class="card-b">
                        <p>Menerima keberatan akan mengeluarkan item ini dari acara. Penolakan mempertahankan harga final yang sudah dikunci. Keputusan ini tidak menentukan refund.</p>
                        <form method="POST" action="{{ route('admin.keberatan.decide', [$objection, 'approve']) }}">
                            @csrf
                            <div class="field"><label for="reason-approve">Alasan menerima keberatan</label><textarea class="input" id="reason-approve" name="reason" minlength="5" maxlength="2000" rows="3" required>{{ old('reason') }}</textarea>@error('reason')<p class="hint">{{ $message }}</p>@enderror</div>
                            <button class="btn full" type="submit">Terima keberatan</button>
                        </form>
                        <form class="mt-16" method="POST" action="{{ route('admin.keberatan.decide', [$objection, 'reject']) }}">
                            @csrf
                            <div class="field"><label for="reason-reject">Alasan menolak keberatan</label><textarea class="input" id="reason-reject" name="reason" minlength="5" maxlength="2000" rows="3" required>{{ old('reason') }}</textarea>@error('reason')<p class="hint">{{ $message }}</p>@enderror</div>
                            <button class="btn btn-o full" type="submit">Tolak keberatan</button>
                        </form>
                    </div>
                </section>
            @endif
            <a class="btn btn-o" href="{{ route('admin.pesanan.detail', $item) }}">Lihat item pesanan</a>
        </aside>
    </div>
</main>
@endsection
