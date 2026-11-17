@extends('layouts.admin')

@section('title', ($bankAccount ? 'Ubah rekening' : 'Tambah rekening').' · Panel Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / <a href="{{ route('admin.rekening') }}">Rekening perusahaan</a> / {{ $bankAccount ? 'Ubah' : 'Tambah' }}</div><h1>{{ $bankAccount ? 'Ubah rekening' : 'Tambah rekening' }}</h1></div></div>
    <form class="card" method="POST" action="{{ $bankAccount ? route('admin.rekening.update', $bankAccount) : route('admin.rekening.store') }}">
        @csrf
        @if ($bankAccount)@method('PUT')@endif
        <div class="card-h"><h2>Data rekening</h2></div>
        <div class="card-b">
            <div class="form-grid">
                <div class="field"><label for="bank">Nama bank</label><input class="input" id="bank" name="bank_name" value="{{ old('bank_name', $bankAccount?->bank_name) }}" maxlength="100" required>@error('bank_name')<p class="hint">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="number">Nomor rekening</label><input class="input" id="number" name="account_number" value="{{ old('account_number', $bankAccount?->account_number) }}" maxlength="64" required>@error('account_number')<p class="hint">{{ $message }}</p>@enderror</div>
                <div class="field full"><label for="account-name">Atas nama</label><input class="input" id="account-name" name="account_name" value="{{ old('account_name', $bankAccount?->account_name) }}" maxlength="255" required>@error('account_name')<p class="hint">{{ $message }}</p>@enderror</div>
                @if ($bankAccount)
                    <div class="field full"><input type="hidden" name="is_active" value="0"><label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bankAccount->is_active))> Aktif</label></div>
                @endif
            </div>
            <div class="form-actions mt-20"><button class="btn" type="submit">Simpan</button><a class="btn btn-o" href="{{ route('admin.rekening') }}">Batal</a></div>
        </div>
    </form>
</main>
@endsection
