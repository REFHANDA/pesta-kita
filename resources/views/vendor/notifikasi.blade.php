@extends('layouts.vendor')

@section('title', 'Notifikasi · Panel Vendor Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div><div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / Notifikasi</div><h1>Notifikasi</h1><p class="sub">Perubahan status pesanan, tagihan, dan pembayaran untuk akun Anda.</p></div>
        @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('vendor.notifikasi.baca-semua') }}">@csrf<button class="btn btn-o" type="submit">Tandai semua sudah dibaca</button></form>
        @endif
    </div>
    <x-notification-list :notifications="$notifications" portal="vendor" />
</main>
@endsection
