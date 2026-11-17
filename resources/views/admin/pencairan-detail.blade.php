@extends('layouts.admin')

@section('title', 'Detail pencairan · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / <a href="{{ route('admin.pencairan') }}">Pencairan</a> / Detail</div><h1>Detail pencairan</h1></div><a class="btn btn-o" href="{{ route('admin.pencairan') }}">Kembali</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('order_item')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    @error('provider_bank_account_id')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two">
        <section class="card">
            <div class="card-h"><h2>Hak penyedia</h2>@if ($orderItem->payout)<span class="badge b-ok">Transfer tercatat</span>@else<span class="badge b-warn">Belum dicairkan</span>@endif</div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Acara</dt><dd>{{ $orderItem->event->name }} · {{ $orderItem->event->event_date?->translatedFormat('d M Y') }}</dd></div>
                    <div><dt>Item</dt><dd>{{ $orderItem->subject?->name ?? 'Item tidak tersedia' }}</dd></div>
                    <div><dt>Penyedia</dt><dd>{{ $orderItem->provider?->business_name ?? 'Penyedia tidak tersedia' }}</dd></div>
                    <div><dt>Harga final / hak penyedia</dt><dd>Rp {{ number_format($orderItem->final_price, 0, ',', '.') }}</dd></div>
                    <div><dt>Komisi platform</dt><dd>Ditagihkan kepada Klien</dd></div>
                </dl>
            </div>
        </section>
        <section class="card">
            <div class="card-h"><h2>Rekening terverifikasi</h2></div>
            <div class="card-b">
                @forelse ($bankAccounts as $account)
                    <p><b>{{ $account->bank_name }}</b> · •••• {{ substr($account->account_number, -4) }} · {{ $account->account_name }}</p>
                @empty
                    <p class="hint">Penyedia belum memiliki rekening terverifikasi; pencairan belum dapat dicatat.</p>
                @endforelse
            </div>
        </section>
    </div>
    @if ($orderItem->payout)
        <section class="card mt-16">
            <div class="card-h"><h2>Transfer tercatat</h2></div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Nominal</dt><dd>Rp {{ number_format($orderItem->payout->amount, 0, ',', '.') }}</dd></div>
                    <div><dt>Tanggal transfer</dt><dd>{{ $orderItem->payout->transferred_at->translatedFormat('d M Y') }}</dd></div>
                    <div><dt>Rekening tujuan</dt><dd>{{ $orderItem->payout->bank_name_snapshot }} · •••• {{ substr($orderItem->payout->account_number_snapshot, -4) }} · {{ $orderItem->payout->account_name_snapshot }}</dd></div>
                </dl>
                @if ($orderItem->payout->proof_path)<a class="btn btn-o mt-8" href="{{ route('admin.pencairan.bukti', $orderItem->payout) }}">Unduh bukti privat</a>@endif
                @if ($orderItem->payout->notes)<p class="hint">{{ $orderItem->payout->notes }}</p>@endif
            </div>
        </section>
    @elseif ($bankAccounts->isNotEmpty())
        <form class="card mt-16" method="POST" action="{{ route('admin.pencairan.store', $orderItem) }}" enctype="multipart/form-data">
            @csrf
            <div class="card-h"><h2>Catat transfer yang sudah dilakukan</h2></div>
            <div class="card-b stack">
                <div class="field">
                    <label for="provider_bank_account_id">Rekening tujuan</label>
                    <select class="input" id="provider_bank_account_id" name="provider_bank_account_id" required>
                        <option value="">Pilih rekening</option>
                        @foreach ($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected(old('provider_bank_account_id') == $account->id)>{{ $account->bank_name }} · •••• {{ substr($account->account_number, -4) }} · {{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="transferred_at">Tanggal transfer</label><input class="input" id="transferred_at" name="transferred_at" type="date" max="{{ now()->toDateString() }}" value="{{ old('transferred_at', now()->toDateString()) }}" required>@error('transferred_at')<p class="hint">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="proof">Bukti transfer (opsional)</label><input class="input" id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf">@error('proof')<p class="hint">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="notes">Catatan Admin</label><textarea class="input" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>@error('notes')<p class="hint">{{ $message }}</p>@enderror</div>
                <button class="btn" type="submit">Catat transfer</button>
            </div>
        </form>
    @endif
</main>
@endsection
