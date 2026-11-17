@extends('layouts.venue')

@section('title', 'Ulasan venue · Panel Pemilik Venue Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('venue.beranda') }}">Beranda</a> / Ulasan</div><h1>Ulasan venue</h1></div></div>
    <section class="grid two">
        <div class="card">
            <div class="card-h"><h2>Ringkasan penilaian</h2></div>
            <div class="card-b">
                <p class="val">{{ $ratingAverage === null ? '—' : number_format((float) $ratingAverage, 1, ',', '.') }} / 5</p>
                <p class="meta">{{ $reviewCount }} ulasan untuk venue Anda</p>
                <p class="hint">Identitas Klien dan komentar tidak ditampilkan kepada penyedia.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-h"><h2>Distribusi nilai</h2></div>
            @for ($rating = 5; $rating >= 1; $rating--)
                @php $count = $ratingDistribution->firstWhere('rating', $rating)?->review_count ?? 0; @endphp
                <div class="card-b"><b>{{ $rating }} / 5</b><span class="spacer"></span>{{ $count }} ulasan</div>
            @endfor
        </div>
    </section>
</main>
@endsection
