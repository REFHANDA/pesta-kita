{{--
    Tahap G1: peringatan kondisional di halaman Pencairan dan beranda
    penyedia. Hilang begitu Admin menyetujui rekening terverifikasi.
--}}
@php($portal ??= auth()->user()->role->value)
@if(($verifiedAccount ?? null) === null)
<div class="alert warn" role="alert">
    <span class="ic i-info" aria-hidden="true"></span>
    <span>Rekening pencairan belum diverifikasi. Dana belum dapat dicairkan. <a href="{{ route($portal.'.profil') }}">Lengkapi rekening</a></span>
</div>
@endif
