@extends('layouts.admin')

@section('title', 'Pencairan · Admin Pesta Kita')

@php
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Beranda</a> / Pencairan</div><h1>Pencairan penyedia</h1><p class="sub">Pencairan hak penyedia setelah acara selesai dan kompensasi pembatalan.</p></div></div>

    @if(session('success'))
    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:16px;border:1px solid #bbf7d0;">{{ session('success') }}</div>
    @endif
    @if(session('error') || $errors->any())
    <div class="alert alert-danger" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;">{{ session('error') }}@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <div class="grid stats two">
        <div class="card stat"><div><div class="lbl">Total belum cair</div><div class="val">{{ $rupiah($totalUnpaid) }}</div></div></div>
        <div class="card stat"><div><div class="lbl">Total sudah cair</div><div class="val">{{ $rupiah($totalPaid) }}</div></div></div>
    </div>

    <section class="card">
        <div aria-label="Navigasi status" class="tabs" role="navigation">
            <a href="{{ route('admin.pencairan', ['tab' => 'belum']) }}" class="{{ $tab === 'belum' ? 'active' : '' }}" @if($tab === 'belum') aria-current="page" @endif>Belum cair</a>
            <a href="{{ route('admin.pencairan', ['tab' => 'cair']) }}" class="{{ $tab === 'cair' ? 'active' : '' }}" @if($tab === 'cair') aria-current="page" @endif>Sudah cair</a>
        </div>
        <form class="toolbar" method="GET" action="{{ route('admin.pencairan') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select class="input" name="penyedia" aria-label="Filter penyedia">
                <option value="">Semua penyedia</option>
                @foreach($providers as $key => $name)<option value="{{ $key }}" @selected($provider === $key)>{{ $name }}</option>@endforeach
            </select>
            <select class="input" name="jenis" aria-label="Filter jenis">
                <option value="">Semua jenis</option>
                <option value="hak_pesanan" @selected($type?->value === 'hak_pesanan')>Hak pesanan</option>
                <option value="kompensasi" @selected($type?->value === 'kompensasi')>Kompensasi</option>
            </select>
            <button class="btn btn-o" type="submit">Terapkan</button>
        </form>

        @if($disbursements->isEmpty())
        <div class="card-b"><p class="hint" style="margin:0">Belum ada data.</p></div>
        @else
        <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pencairan">
            <table class="t-wide">
                <caption class="sr-only">Tabel: Pencairan penyedia</caption>
                <thead><tr><th scope="col">Penyedia</th><th scope="col">Acara</th><th scope="col" class="col-layanan">Layanan</th><th scope="col">Jenis</th><th scope="col" class="num">Jumlah</th><th scope="col" class="col-rekening">Rekening penyedia</th><th scope="col">Status</th><th scope="col" class="col-aksi">Aksi</th></tr></thead>
                <tbody>
                @foreach($disbursements as $disbursement)
                @php
                $account = $disbursement->verifiedBankAccount();
                $pendingAccount = $disbursement->provider?->bankAccounts
                    ?->first(fn ($bankAccount) => $bankAccount->verification_status === \App\Enums\VerificationStatus::Menunggu);
                @endphp
                <tr>
                    <td><b>{{ $disbursement->provider?->business_name ?? '-' }}</b><small class="sub-t">{{ $disbursement->provider_type === 'venue_profile' ? 'Venue' : 'Vendor' }}</small></td>
                    <td>Acara #{{ $disbursement->orderItem?->event_id }}</td>
                    <td class="col-layanan">{{ $disbursement->orderItem?->subject?->name ?? '-' }}</td>
                    <td>{{ $disbursement->type === \App\Enums\DisbursementType::Kompensasi ? 'Kompensasi' : 'Hak pesanan' }}</td>
                    <td class="num"><b>{{ $rupiah($disbursement->amount) }}</b></td>
                    <td class="col-rekening">
                        @if($account)
                        <div>{{ $account->bank_name }} · {{ $account->account_number }}</div><small class="sub-t">a.n. {{ $account->account_name }}</small>
                        @elseif($pendingAccount)
                        <span class="badge b-warn">Rekening menunggu verifikasi</span>
                        <a class="link-plain" href="{{ route('admin.penyedia.detail', ['type' => $disbursement->provider_type, 'id' => $disbursement->provider_id]) }}">Verifikasi rekening</a>
                        @else
                        <span class="badge b-mute">Penyedia belum mengisi rekening</span>
                        @endif
                    </td>
                    <td>
                        @if($disbursement->status === \App\Enums\DisbursementStatus::Cair)
                        <span class="badge b-ok">Cair</span><small class="sub-t">{{ $disbursement->paid_at?->translatedFormat('d M Y') }}</small>
                        @else
                        <span class="badge b-warn">Belum cair</span>
                        @endif
                    </td>
                    <td class="col-aksi">
                        @if($disbursement->status === \App\Enums\DisbursementStatus::Cair)
                            @if($disbursement->proof)<a class="btn btn-o btn-sm" href="{{ route('media.show', $disbursement->proof) }}" target="_blank" rel="noopener">Bukti</a>@endif
                        @elseif(! $account)
                            <button class="btn btn-o btn-sm" type="button" disabled title="{{ $pendingAccount ? 'Rekening penyedia menunggu verifikasi' : 'Penyedia belum mengisi rekening' }}">Catat transfer</button>
                        @else
                        <details>
                            <summary class="btn btn-sm">Catat transfer</summary>
                            <form method="POST" action="{{ route('admin.pencairan.record', $disbursement) }}" enctype="multipart/form-data" class="mt-8">
                                @csrf
                                <label>Tanggal transfer <input class="input" type="date" name="paid_at" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></label>
                                <label>Bukti (JPG/PNG/PDF) <input class="input" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required></label>
                                <button class="btn btn-sm mt-8" type="submit">Simpan</button>
                            </form>
                        </details>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($disbursements->hasPages())<div style="padding:16px;">{{ $disbursements->links() }}</div>@endif
        @endif
    </section>
</main>
@endsection
