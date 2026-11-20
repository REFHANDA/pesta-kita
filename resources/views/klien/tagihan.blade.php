@extends('layouts.klien')

@section('title', 'Tagihan · Pesta Kita')

@section('content')
@php
    $invoice = $invoice ?? null;
    $activeBankAccounts = $activeBankAccounts ?? collect();
@endphp
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs">
                <a href="{{ url('/klien') }}">Beranda</a> /
                @if($invoice && $invoice->event)
                <a href="{{ route('klien.acara.detail', $invoice->event) }}">{{ $invoice->event->name }}</a> /
                @endif
                Tagihan
            </div>
            <h1>Tagihan {{ $invoice?->invoice_number ?? 'Acara' }}</h1>
        </div>
    </div>

    @if(!$invoice)
    <div class="card card-b">
        <p>Pilih acara dari <a href="{{ route('klien.acara') }}">daftar acara Anda</a> untuk melihat tagihan aktif.</p>
    </div>
    @else

    @if(session('success'))
    <div class="alert alert-success" style="background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:20px;border:1px solid #bbf7d0;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger" style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:20px;border:1px solid #fecaca;">
        <ul style="margin:0;padding-left:20px;">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($invoice->latestPayment && $invoice->latestPayment->status === \App\Enums\PaymentStatus::Ditolak)
    <div class="bad-box" style="background:#fee2e2;color:#991b1b;padding:16px;border-radius:8px;margin-bottom:20px;border:1px solid #f87171;">
        <b>Bukti pembayaran sebelumnya ditolak Admin.</b>
        <p style="margin-top:4px;">Catatan Admin: <i>{{ $invoice->latestPayment->admin_note ?? 'Nominal atau bukti transfer tidak sesuai.' }}</i></p>
        <p style="margin-top:6px;font-size:13px;">Silakan periksa kembali dan unggah ulang bukti transfer yang benar di bawah ini.</p>
    </div>
    @elseif($invoice->status === \App\Enums\InvoiceStatus::MenungguValidasi)
    <div class="warn-box" style="background:#eff6ff;color:#1e40af;padding:16px;border-radius:8px;margin-bottom:20px;border:1px solid #93c5fd;">
        <b>Bukti pembayaran telah dikirim dan sedang menunggu validasi Admin.</b>
        <p style="margin-top:4px;font-size:13px;">Admin Pesta Kita akan memverifikasi transfer Anda. Jika ada kesalahan, Anda tetap dapat mengirimkan bukti perbaikan melalui formulir di bawah.</p>
    </div>
    @endif

    <div class="grid two">
        <section class="card">
            <div class="card-h">
                <div>
                    <h2>Rincian tagihan</h2>
                    <p class="meta">Terbit {{ $invoice->issued_at?->format('d/m/Y H:i') }} · Versi {{ $invoice->version }}</p>
                </div>
                @php
                    $badgeClass = match($invoice->status) {
                        \App\Enums\InvoiceStatus::Lunas => 'b-ok',
                        \App\Enums\InvoiceStatus::MenungguValidasi => 'b-warn',
                        \App\Enums\InvoiceStatus::BelumDibayar => 'b-warn',
                        \App\Enums\InvoiceStatus::Kedaluwarsa, \App\Enums\InvoiceStatus::Dibatalkan => 'b-bad',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $invoice->status->label() }}</span>
            </div>

            <div class="table-wrap" role="region" tabindex="0" aria-label="Rincian tagihan. Gulir horizontal untuk melihat seluruh kolom">
                <table class="t-wide">
                    <caption class="sr-only">Tabel: Rincian tagihan</caption>
                    <thead>
                        <tr>
                            <th scope="col">Rincian Komponen</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="num">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td><b>{{ $item->description }}</b></td>
                            <td>{{ $item->type->label() }}</td>
                            <td class="num">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <table class="sum">
                <tr>
                    <td>Subtotal penyedia</td>
                    <td class="num">Rp {{ number_format($invoice->subtotal_provider, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Komisi platform (10%)</td>
                    <td class="num">Rp {{ number_format($invoice->total_commission, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->cleaning_fee > 0)
                <tr>
                    <td>Biaya kebersihan venue</td>
                    <td class="num">Rp {{ number_format($invoice->cleaning_fee, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="border-top:1px solid var(--line, #e2e8f0);">
                    <td>Total tagihan</td>
                    <td class="num">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->unique_code > 0)
                <tr>
                    <td><b>Kode unik transfer</b> <span class="sub-t">membantu verifikasi otomatis</span></td>
                    <td class="num">Rp {{ number_format($invoice->unique_code, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="t">
                    <td>Total yang harus ditransfer</td>
                    <td class="num"><b>Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}</b></td>
                </tr>
            </table>

            <div class="card-b">
                @if($invoice->unique_code > 0)
                <div class="actions">
                    <button class="btn btn-o btn-sm" type="button" data-salin="{{ $invoice->unique_code }}" data-salin-target="salin-kode" data-salin-done="Kode unik transfer {{ $invoice->unique_code }} disalin.">Salin kode unik transfer</button>
                    <button class="btn btn-o btn-sm" type="button" data-salin="{{ $invoice->transfer_total }}" data-salin-target="salin-kode" data-salin-done="Total transfer Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }} disalin.">Salin total transfer</button>
                </div>
                <p class="hint" id="salin-kode" role="status"></p>
                @endif

                <p class="deadline" style="margin-top:12px;">
                    @php
                        $now = now();
                        $hoursLeft = $invoice->due_at ? max(0, (int) ceil($now->diffInRealHours($invoice->due_at, false))) : 0;
                    @endphp
                    @if($invoice->due_at && $now->lt($invoice->due_at))
                    <span class="chip">Sisa {{ $hoursLeft }} jam</span>
                    @endif
                    Bayar sebelum <time datetime="{{ $invoice->due_at?->toIso8601String() }}">{{ $invoice->due_at?->translatedFormat('d/m/Y H:i') }} WIB</time>
                </p>
                <p class="hint">Transfer tepat sesuai nominal hingga 3 digit terakhir. Lewat batas waktu, tagihan kedaluwarsa, pesanan dibatalkan otomatis, serta tanggal venue dan stok dilepas.</p>

                <div class="actions" style="margin-top:16px;">
                    <a class="btn btn-o" href="{{ route('klien.tagihan.print', $invoice) }}" target="_blank">
                        Cetak / Unduh Tagihan (PDF)
                    </a>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-h">
                <h2>Bayar tagihan</h2>
            </div>
            <div class="card-b">
                <form class="stack mt-16" action="{{ route('klien.tagihan.pay', $invoice) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <fieldset class="choice-group">
                        <legend>Pilih rekening tujuan <span class="req" aria-hidden="true">*</span></legend>
                        @forelse($activeBankAccounts as $idx => $account)
                        <label class="choice" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <input type="radio" name="company_bank_account_id" value="{{ $account->id }}" {{ (old('company_bank_account_id') == $account->id || $idx === 0) ? 'checked' : '' }} required>
                            <span class="rek">
                                <b>{{ $account->bank_name }} {{ $account->account_number }}</b>
                                a.n. {{ $account->account_name }}
                            </span>
                        </label>
                        <button class="btn btn-o btn-sm" type="button" style="margin-bottom:12px;" data-salin="{{ $account->account_number }}" data-salin-target="salin" data-salin-done="Nomor rekening {{ $account->bank_name }} {{ $account->account_number }} disalin ke clipboard.">
                            Salin nomor {{ $account->bank_name }}
                        </button>
                        @empty
                        <p class="hint">Belum ada rekening aktif yang terdaftar.</p>
                        @endforelse
                    </fieldset>

                    <div class="field">
                        <label for="n">Nama pemilik rekening pengirim <span class="req">*</span></label>
                        <input class="input" id="n" name="sender_name" type="text" value="{{ old('sender_name', auth()->user()->name) }}" required>
                    </div>

                    <div class="field">
                        <label for="d">Tanggal transfer <span class="req">*</span></label>
                        <input class="input" id="d" name="transfer_date" type="date" value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="field">
                        <label for="j">Jumlah ditransfer (Rp) <span class="req">*</span></label>
                        <input class="input" id="j" name="amount" type="number" value="{{ old('amount', $invoice->transfer_total) }}" required>
                        <p class="hint">Wajib tepat Rp {{ number_format($invoice->transfer_total, 0, ',', '.') }}. Perbedaan nominal akan menyebabkan penolakan oleh Admin.</p>
                    </div>

                    <div class="field">
                        <label for="b">Bukti transfer <span class="req">*</span></label>
                        <input class="input" id="b" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf" style="padding-top:9px" required>
                        <p class="hint">Format JPG, PNG, atau PDF, ukuran file maksimal 5 MB.</p>
                    </div>

                    <button class="btn" id="kirim" type="submit">Kirim bukti pembayaran</button>
                    <p class="hint" id="salin" role="status"></p>
                </form>
            </div>
        </section>
    </div>
    @endif
</main>
@endsection
