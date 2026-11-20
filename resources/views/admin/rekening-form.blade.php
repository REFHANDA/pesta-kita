@extends('layouts.admin')

@php
    $mode = $mode ?? 'tambah';
    $isEdit = ($mode === 'edit');
    $title = $isEdit ? 'Ubah Rekening' : 'Tambah Rekening';
@endphp

@section('title', $title . ' · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs">
                <a href="{{ url('/admin') }}">Admin</a> /
                <a href="{{ route('admin.rekening.index') }}">Rekening perusahaan</a> /
                {{ $title }}
            </div>
            <h1>{{ $title }}</h1>
            <p class="sub">Rekening tujuan pembayaran klien. Rekening yang pernah dipakai tidak dapat dihapus; nonaktifkan agar tidak tampil.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-16" style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:8px;border:1px solid #fecaca;margin-bottom:16px;">
        <ul style="margin:0;padding-left:20px;">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form class="card" action="{{ $isEdit ? route('admin.rekening.update', $account) : route('admin.rekening.store') }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card-h">
            <h2>Data Rekening</h2>
        </div>

        <div class="card-b">
            <div class="form-grid">
                <div class="field">
                    <label for="bank_name">Nama Bank <span class="req" aria-hidden="true">*</span></label>
                    <input class="input" id="bank_name" name="bank_name" type="text" value="{{ old('bank_name', $account->bank_name) }}" required placeholder="Contoh: BCA, Mandiri, BNI, BRI">
                </div>

                <div class="field">
                    <label for="account_number">Nomor Rekening <span class="req" aria-hidden="true">*</span></label>
                    <input class="input" id="account_number" name="account_number" type="text" value="{{ old('account_number', $account->account_number) }}" required placeholder="Contoh: 8800123456">
                </div>

                <div class="field">
                    <label for="account_name">Atas Nama <span class="req" aria-hidden="true">*</span></label>
                    <input class="input" id="account_name" name="account_name" type="text" value="{{ old('account_name', $account->account_name) }}" required placeholder="Contoh: PT Pesta Kita Indonesia">
                    <p class="hint" id="account_name-h">Harus sesuai dengan nama resmi rekening perusahaan.</p>
                </div>

                <div class="field">
                    <label for="sort_order">Urutan Tampil <span class="req" aria-hidden="true">*</span></label>
                    <input class="input" id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $account->sort_order) }}" required min="1">
                    <p class="hint" id="sort_order-h">Rekening ditampilkan ke klien sesuai urutan terkecil (1, 2, dst).</p>
                </div>

                <div class="field full">
                    <label class="check" style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                        Aktif (tampil sebagai pilihan tujuan transfer bagi klien)
                    </label>
                </div>
            </div>

            <div class="form-actions mt-20" style="margin-top:20px;display:flex;gap:12px;">
                <button class="btn" type="submit">Simpan Rekening</button>
                <a class="btn btn-o" href="{{ route('admin.rekening.index') }}">Batal</a>
            </div>
        </div>
    </form>
</main>
@endsection
