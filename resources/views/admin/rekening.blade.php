@extends('layouts.admin')

@section('title', 'Rekening perusahaan · Admin Pesta Kita')

@section('content')
<main class="content page-refined page-bank-accounts" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.pengaturan') }}">Pengaturan</a> / Rekening perusahaan</div>
            <h1>Rekening perusahaan</h1>
            <p class="sub">Kelola rekening tujuan pembayaran klien. Hanya rekening aktif yang ditampilkan saat klien membayar.</p>
        </div>
        <a class="btn" href="{{ route('admin.rekening.create') }}">＋ Tambah rekening</a>
    </div>

    <x-subnav label="Pengaturan" :items="[
    ['label' => 'Parameter', 'href' => route('admin.pengaturan'), 'active' => request()->routeIs('admin.pengaturan')],
    ['label' => 'Rekening perusahaan', 'href' => route('admin.rekening.index'), 'active' => request()->routeIs('admin.rekening.index')],
]" />
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid two account-grid">
        @forelse($accounts as $account)
        <section class="card account-card">
            <div class="card-h">
                <h2>{{ $account->bank_name }}</h2>
                @if($account->is_active)
                <span class="badge b-ok">Aktif · Urutan {{ $account->sort_order }}</span>
                @else
                <span class="badge b-bad">Nonaktif · Urutan {{ $account->sort_order }}</span>
                @endif
            </div>
            <p class="big-num">{{ $account->account_number }}</p>
            <p>{{ $account->account_name }}</p>
            <div class="toolbar">
                <a class="btn btn-o btn-sm" href="{{ route('admin.rekening.edit', $account) }}">Edit</a>
                <form action="{{ route('admin.rekening.toggle', $account) }}" method="POST" style="display:inline;">
                    @csrf
                    @if($account->is_active)
                    <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Nonaktifkan rekening ini? Rekening tidak lagi tampil pada pilihan pembayaran klien.');">
                        Nonaktifkan
                    </button>
                    @else
                    <button class="btn btn-sm" type="submit" onclick="return confirm('Aktifkan rekening ini? Rekening akan tampil kembali pada pilihan pembayaran klien.');">
                        Aktifkan
                    </button>
                    @endif
                </form>
            </div>
        </section>
        @empty
        <div class="card card-b" style="grid-column:1/-1;">
            <p class="hint">Belum ada rekening perusahaan yang terdaftar. Tambahkan rekening pertama Anda.</p>
        </div>
        @endforelse
    </div>

    <div class="alert mt-16">
        Rekening yang pernah dipakai transaksi sebaiknya dinonaktifkan, bukan dihapus, agar riwayat bukti pembayaran tetap utuh dan terhubung.
    </div>
</main>
@endsection
