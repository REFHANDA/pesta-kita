@extends('layouts.admin')

@section('title', 'Review rekening penyedia · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Rekening payout</div><h1>Review rekening payout</h1><p class="sub">Rekening baru harus disetujui sebelum dapat dipilih untuk pencairan.</p></div></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @forelse ($accounts as $account)
        <section class="card sec-gap">
            <div class="card-h"><div><h2>{{ $account->owner->business_name }}</h2><p class="meta">{{ $account->owner->user->name }} · {{ $account->owner->user->email }}</p></div></div>
            <div class="card-b">
                <p><b>{{ $account->bank_name }}</b> · {{ $account->account_number }} · {{ $account->account_name }}</p>
                <div class="actions">
                    <form method="POST" action="{{ route('admin.provider-bank-accounts.decide', $account) }}">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <button class="btn" type="submit">Setujui rekening</button>
                    </form>
                    <form class="actions" method="POST" action="{{ route('admin.provider-bank-accounts.decide', $account) }}">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <label class="sr-only" for="note-{{ $account->id }}">Catatan penolakan</label>
                        <input class="input" id="note-{{ $account->id }}" name="verification_note" maxlength="2000" placeholder="Alasan penolakan" required>
                        <button class="btn btn-o" type="submit">Tolak</button>
                    </form>
                </div>
            </div>
        </section>
    @empty
        <section class="card"><p class="hint" style="padding:16px">Tidak ada rekening yang menunggu review.</p></section>
    @endforelse
    <div class="card-b">{{ $accounts->links() }}</div>
</main>
@endsection
