@extends('layouts.klien')

@section('title', 'Buat konsep acara · Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Rancang acara</a> / Baru</div>
            <h1>Buat konsep acara</h1>
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
        <form class="card" method="POST" action="{{ route('klien.acara.store') }}">
            @csrf
            <div class="card-b">
                <div class="form-grid">
                    <div class="field full">
                        <label for="nama">Nama acara <span style="color:#ef4444">*</span></label>
                        <input class="input" id="nama" name="name" type="text" value="{{ old('name') }}" placeholder="Contoh: Ulang Tahun Raka ke-5" required>
                        <p class="hint">Gunakan nama yang mudah dikenali, misalnya nama anak dan usianya.</p>
                    </div>
                    <div class="field">
                        <label for="tgl">Tanggal acara <span style="color:#ef4444">*</span></label>
                        <input class="input" id="tgl" name="event_date" type="date" value="{{ old('event_date') }}" min="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="field">
                        <label for="tamu">Jumlah tamu <span style="color:#ef4444">*</span></label>
                        <input class="input" id="tamu" name="guest_count" type="number" value="{{ old('guest_count', 30) }}" min="1" required>
                    </div>
                    <div class="field full">
                        <label for="tema">Tema acara</label>
                        <input class="input" id="tema" name="theme" type="text" value="{{ old('theme') }}" placeholder="Contoh: Pesta Kebun, Superhero, Princess">
                    </div>
                    <div class="field full">
                        <label for="ang">Anggaran (Rp) <span style="color:#ef4444">*</span></label>
                        <input class="input" id="ang" name="budget" type="number" value="{{ old('budget') }}" min="0" inputmode="numeric" placeholder="Contoh: 15000000" required>
                        <p class="hint">Wajib diisi; dipakai untuk rekomendasi paket dan kontrol pengeluaran.</p>
                    </div>
                    <div class="field full">
                        <label for="desk">Deskripsi konsep</label>
                        <textarea class="input" id="desk" name="description" rows="4" placeholder="Suasana, susunan acara khusus, atau harapan Anda">{{ old('description') }}</textarea>
                        <p class="hint">Suasana, rangkaian acara, atau hal yang ingin dicapai.</p>
                    </div>
                </div>
                <div class="form-actions mt-16">
                    <button type="submit" class="btn">Simpan konsep</button>
                    <a class="btn btn-o" href="{{ route('klien.acara') }}">Batal</a>
                </div>
            </div>
        </form>

        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Mulai dari konsep</h2></div>
                <div class="card-b">
                    <p class="m-0">Isi konsep dasar acara Anda terlebih dahulu. Setelah konsep tersimpan, Anda dapat menambahkan layanan katering, dekorasi, hiburan, peralatan, dan venue dari katalog penyedia terverifikasi.</p>
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
