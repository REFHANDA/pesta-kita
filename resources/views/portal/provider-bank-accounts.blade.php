@extends('layouts.'.$role)

@section('title', 'Rekening pencairan · Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/'.$role) }}">Beranda</a> / Rekening pencairan</div><h1>Rekening pencairan</h1><p class="sub">Rekening baru hanya digunakan setelah disetujui Admin. Rekening yang sudah terverifikasi tetap dapat digunakan.</p></div></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    <section class="card sec-gap">
        <div class="card-h"><h2>Tambahkan rekening</h2></div>
        <div class="card-b">
            <form class="form-grid" method="POST" action="{{ route($role.'.bank-accounts.store') }}">
                @csrf
                <div class="field"><label for="bank_name">Nama bank</label><input class="input" id="bank_name" name="bank_name" maxlength="100" value="{{ old('bank_name') }}" required></div>
                <div class="field"><label for="account_number">Nomor rekening</label><input class="input" id="account_number" name="account_number" inputmode="numeric" pattern="[0-9]+" maxlength="50" value="{{ old('account_number') }}" required></div>
                <div class="field"><label for="account_name">Nama pemilik rekening</label><input class="input" id="account_name" name="account_name" maxlength="255" value="{{ old('account_name') }}" required></div>
                <div class="field"><label>&nbsp;</label><button class="btn" type="submit">Ajukan rekening</button></div>
            </form>
            @foreach (['bank_name', 'account_number', 'account_name'] as $field)
                @error($field)<p class="field-error" role="alert">{{ $message }}</p>@enderror
            @endforeach
        </div>
    </section>
    <section class="card">
        <div class="card-h"><h2>Daftar rekening</h2></div>
        <div class="table-wrap">
            <table class="t-wide">
                <thead><tr><th scope="col">Bank</th><th scope="col">Nomor rekening</th><th scope="col">Atas nama</th><th scope="col">Status</th><th scope="col">Catatan Admin</th></tr></thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ $account->verification_status === \App\Enums\VerificationStatus::Terverifikasi ? $account->account_number : str_repeat('•', max(strlen($account->account_number) - 4, 0)).substr($account->account_number, -4) }}</td>
                            <td>{{ $account->account_name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $account->verification_status->value)) }}</td>
                            <td>{{ $account->verification_note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada rekening pencairan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
