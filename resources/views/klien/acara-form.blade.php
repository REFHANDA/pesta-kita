@extends('layouts.klien')

@section('title', 'Ubah konsep acara · Pesta Kita')

@php
$event = $event ?? new \App\Models\Event();
$actionUrl = $event->exists ? route('klien.acara.update', $event) : '#';
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Acara saya</a> / Ubah konsep</div>
            <h1>Ubah konsep acara</h1>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-16" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;">
        <ul style="margin:0;padding-left:20px;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid two">
        <form class="card" method="POST" action="{{ $actionUrl }}">
            @csrf
            @method('PUT')
            <div class="card-b">
                <div class="form-grid">
                    <div class="field full">
                        <label for="nama">Nama acara <span style="color:#ef4444">*</span></label>
                        <input class="input" id="nama" name="name" type="text" value="{{ old('name', $event->name) }}" required>
                        <p class="hint">Gunakan nama yang mudah dikenali, misalnya nama anak dan usianya.</p>
                    </div>
                    <div class="field">
                        <label for="tgl">Tanggal acara <span style="color:#ef4444">*</span></label>
                        <input class="input" id="tgl" name="event_date" type="date" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="field">
                        <label for="tamu">Jumlah tamu <span style="color:#ef4444">*</span></label>
                        <input class="input" id="tamu" name="guest_count" type="number" value="{{ old('guest_count', $event->guest_count) }}" min="1" required>
                    </div>
                    <div class="field full">
                        <label for="tema">Tema</label>
                        <input class="input" id="tema" name="theme" type="text" value="{{ old('theme', $event->theme) }}">
                    </div>
                    <div class="field full">
                        <label for="ang">Anggaran (Rp) <span style="color:#ef4444">*</span></label>
                        <input class="input" id="ang" name="budget" type="number" value="{{ old('budget', $event->budget) }}" min="0" inputmode="numeric" required>
                        <p class="hint">Dipakai untuk rekomendasi paket. Bisa diubah kapan saja selama acara masih draft.</p>
                    </div>
                    <div class="field full">
                        <label for="desk">Deskripsi konsep</label>
                        <textarea class="input" id="desk" name="description" rows="4">{{ old('description', $event->description) }}</textarea>
                        <p class="hint">Suasana, rangkaian acara, atau hal yang ingin dicapai.</p>
                    </div>
                </div>
                <div class="form-actions mt-16">
                    <button type="submit" class="btn">Simpan konsep</button>
                    <a class="btn btn-o" href="{{ $event->exists ? route('klien.acara.detail', $event) : route('klien.acara') }}">Batal</a>
                </div>
            </div>
        </form>

        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Setelah konsep disimpan</h2></div>
                <div class="card-b">
                    <ol style="margin:0;padding-left:20px;display:grid;gap:10px">
                        <li>Pilih layanan vendor, venue, dan peralatan dari katalog terverifikasi.</li>
                        <li>Tekan <b>Ajukan Acara</b> agar Pesta Kita meninjau tiap pesanan.</li>
                        <li>Tagihan resmi diterbitkan setelah pesanan disetujui Admin.</li>
                    </ol>
                    <p class="hint mt-16">Tanggal dan jumlah tamu menentukan ketersediaan layanan dan venue, pastikan mengisi dengan tepat.</p>
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
