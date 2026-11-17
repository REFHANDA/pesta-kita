@extends('layouts.klien')

@section('title', 'Rekomendasi vendor dan venue · Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / Rekomendasi</div>
            <h1>Rekomendasi vendor dan venue</h1>
            <p class="sub">Pilihan diambil dari penyedia terverifikasi dan dapat difilter berdasarkan kebutuhan acara.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert warn" role="alert">Periksa kembali filter rekomendasi.</div>
    @endif

    <section class="card sec-gap">
        <div class="card-b">
            <form class="form-grid" method="GET" action="{{ route('klien.rekomendasi') }}">
                <div class="field">
                    <label for="category">Kategori</label>
                    <select class="input" id="category" name="category">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="area">Area</label>
                    <input class="input" id="area" name="area" value="{{ $filters['area'] ?? '' }}" maxlength="100">
                </div>
                <div class="field">
                    <label for="event_date">Tanggal acara</label>
                    <input class="input" id="event_date" name="event_date" type="date" value="{{ $filters['event_date'] ?? '' }}" min="{{ now()->toDateString() }}">
                </div>
                <div class="field">
                    <label for="budget">Batas anggaran (Rp)</label>
                    <input class="input" id="budget" name="budget" type="number" min="0" step="1" value="{{ $filters['budget'] ?? '' }}">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <div class="actions">
                        <button class="btn" type="submit">Tampilkan rekomendasi</button>
                        <a class="btn btn-o" href="{{ route('klien.rekomendasi') }}">Reset</a>
                    </div>
                </div>
            </form>
            <p class="hint mt-12">Estimasi mencakup komisi 10%; venue juga mencakup biaya kebersihan. Ketersediaan tanggal mengikuti kalender yang diisi penyedia.</p>
        </div>
    </section>

    <section class="card sec-gap">
        <div class="card-h"><h2>Vendor dan peralatan</h2><span class="chip">{{ $items->count() }} hasil</span></div>
        <div class="table-wrap">
            <table class="t-wide">
                <thead><tr><th scope="col">Item</th><th scope="col">Kategori</th><th scope="col">Penyedia</th><th scope="col" class="num">Estimasi</th><th scope="col">Detail</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><b>{{ $item->name }}</b></td>
                            <td>{{ $item->category }}</td>
                            <td>{{ $item->vendorProfile->business_name }}</td>
                            <td class="num">Rp {{ number_format($item->estimated_total, 0, ',', '.') }}</td>
                            <td><a class="btn btn-o btn-sm" href="{{ route('klien.katalog.vendor.detail', $item) }}">Lihat detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Tidak ada vendor atau peralatan yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Venue</h2><span class="chip">{{ $venues->count() }} hasil</span></div>
        <div class="table-wrap">
            <table class="t-wide">
                <thead><tr><th scope="col">Venue</th><th scope="col">Area</th><th scope="col">Kapasitas</th><th scope="col" class="num">Estimasi</th><th scope="col">Detail</th></tr></thead>
                <tbody>
                    @forelse ($venues as $venue)
                        <tr>
                            <td><b>{{ $venue->name }}</b><small class="sub-t">{{ $venue->kind }}</small></td>
                            <td>{{ $venue->venueProfile->service_area ?: $venue->address }}</td>
                            <td>{{ number_format($venue->capacity, 0, ',', '.') }} orang</td>
                            <td class="num">Rp {{ number_format($venue->estimated_total, 0, ',', '.') }}</td>
                            <td><a class="btn btn-o btn-sm" href="{{ route('klien.katalog.venue.detail', $venue) }}">Lihat detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Tidak ada venue yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
