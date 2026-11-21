@extends('layouts.vendor')

@section('title', 'Pencairan · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Pencairan</div><h1>Pencairan</h1><p class="sub">Hak Anda dicairkan setelah acara selesai; kompensasi dicairkan bila klien membatalkan setelah lunas.</p></div></div>
    @include('partials.rekening-pencairan-banner', ['portal' => 'vendor'])
    @include('partials.pencairan-penyedia')
</main>
@endsection
