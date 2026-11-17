@extends('layouts.admin')

@section('title', 'Rekening perusahaan · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Penyedia &amp; katalog / Rekening perusahaan</div><h1>Rekening perusahaan</h1><p class="sub">Hanya rekening aktif yang muncul untuk Klien. Pembayaran terdahulu menyimpan snapshot rekening.</p></div><a class="btn" href="{{ route('admin.rekening.create') }}">Tambah rekening</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('account')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two account-grid">
        @forelse ($bankAccounts as $account)
            <section class="card account-card">
                <div class="card-h"><h2>{{ $account->bank_name }}</h2><span class="badge {{ $account->is_active ? 'b-ok' : 'b-mute' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
                <div class="card-b">
                    <p class="big-num">{{ $account->account_number }}</p>
                    <p>{{ $account->account_name }}</p>
                    <div class="toolbar">
                        <a class="btn btn-o" href="{{ route('admin.rekening.edit', $account) }}">Ubah</a>
                        <form method="POST" action="{{ route('admin.rekening.status', $account) }}">
                            @csrf
                            <button class="btn {{ $account->is_active ? 'btn-danger' : '' }}" type="submit">{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </div>
                </div>
            </section>
        @empty
            <p class="hint">Belum ada rekening tujuan aktif. Tambahkan rekening sebelum Klien dapat mengirim pembayaran.</p>
        @endforelse
    </div>
    <div class="alert">Rekening tidak dapat dihapus. Menonaktifkan rekening tidak mengubah snapshot pembayaran yang sudah tercatat.</div>
</main>
@endsection
