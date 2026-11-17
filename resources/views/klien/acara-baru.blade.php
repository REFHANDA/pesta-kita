@extends('layouts.klien')

@section('title', 'Buat konsep acara · Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Rancang acara</a> / Baru</div>
            <h1>Buat konsep acara</h1>
        </div>
    </div>
    <div class="grid two">
        <form class="card" method="POST" action="{{ route('klien.acara.store') }}">
            @csrf
            <div class="card-b">
                <div class="form-grid">
                    <div class="field full">
                        <label for="nama">Nama acara</label>
                        <input class="input" id="nama" name="name" type="text" value="{{ old('name') }}" maxlength="255" required>
                        @error('name')<p class="hint">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="tgl">Tanggal acara</label>
                        <input class="input" id="tgl" name="event_date" type="date" value="{{ old('event_date') }}" min="{{ now()->toDateString() }}" required>
                        @error('event_date')<p class="hint">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="tamu">Jumlah tamu</label>
                        <input class="input" id="tamu" name="guest_count" type="number" value="{{ old('guest_count') }}" min="1" max="100000" required>
                        @error('guest_count')<p class="hint">{{ $message }}</p>@enderror
                    </div>
                    <div class="field full">
                        <label for="tema">Tema</label>
                        <input class="input" id="tema" name="theme" type="text" value="{{ old('theme') }}" maxlength="255">
                    </div>
                    <div class="field full">
                        <label for="desk">Deskripsi konsep</label>
                        <textarea class="input" id="desk" name="description" rows="4" maxlength="5000">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn" type="submit">Simpan konsep</button>
                    <a class="btn btn-o" href="{{ route('klien.acara') }}">Batal</a>
                </div>
            </div>
        </form>
        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Mulai dari konsep</h2></div>
                <div class="card-b"><p class="m-0">Isi nama, tanggal, dan jumlah tamu. Anda dapat memilih layanan dan venue selama acara masih draft.</p></div>
            </section>
        </div>
    </div>
</main>
@endsection
