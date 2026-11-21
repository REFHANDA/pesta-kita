@extends('layouts.klien')

@section('title', ($event->name ?? 'Detail acara').' · Pesta Kita')
@section('wa_context', 'Acara #'.$event->id.' ('.$event->name.').')

@php
$event = $event ?? new \App\Models\Event();
$orderItems = $event->orderItems ?? collect();

// Label badge status — sama dengan daftar acara dan beranda.
$badgeFor = fn ($status) => match ($status) {
    \App\Enums\EventStatus::Draft => ['b-mute', 'Draft'],
    \App\Enums\EventStatus::Diajukan => ['b-warn', 'Diajukan'],
    \App\Enums\EventStatus::MenungguPembayaran => ['b-warn', 'Menunggu pembayaran'],
    \App\Enums\EventStatus::Terkonfirmasi => ['b-ok', 'Terkonfirmasi'],
    \App\Enums\EventStatus::Selesai => ['b-ok', 'Selesai'],
    \App\Enums\EventStatus::Dievaluasi => ['b-ok', 'Dievaluasi'],
    \App\Enums\EventStatus::Dibatalkan => ['b-bad', 'Dibatalkan'],
    default => ['b-mute', '-'],
};
[$badgeClass, $badgeLabel] = $badgeFor($event->status);

// Tahap alur normal; acara dibatalkan tidak menampilkan langkah aktif.
$flow = [
    \App\Enums\EventStatus::Draft->value => 'Draft',
    \App\Enums\EventStatus::Diajukan->value => 'Diajukan',
    \App\Enums\EventStatus::MenungguPembayaran->value => 'Menunggu pembayaran',
    \App\Enums\EventStatus::Terkonfirmasi->value => 'Terkonfirmasi',
    \App\Enums\EventStatus::Selesai->value => 'Selesai',
    \App\Enums\EventStatus::Dievaluasi->value => 'Dievaluasi',
];
$flowKeys = array_keys($flow);
$currentStep = null;
foreach ($flowKeys as $i => $stepStatus) {
    if ($stepStatus === $event->status?->value) {
        $currentStep = $i;
    }
}
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
$estimatedTotal = $costSummary['amount'] ?? 0;
$estimatedInvoiceNumber = $costSummary['invoice_number'] ?? null;
@endphp

@section('content')
<main id="konten" class="content" tabindex="-1">
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ url('/klien') }}">Beranda</a> / <a href="{{ route('klien.acara') }}">Acara saya</a> / Detail</div>
        <h1>{{ $event->name ?: 'Detail acara' }}</h1>
        <p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count ? $event->guest_count.' tamu' : 'Jumlah tamu belum diisi' }}</p>
    </div>
    <div style="display:flex;align-items:center;gap:12px;">
        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-16" style="background:#dcfce7;border:1px solid #22c55e;color:#15803d;padding:12px;border-radius:6px;margin-bottom:16px;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger mb-16" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;">
    {{ session('error') }}
</div>
@endif

@if ($currentStep !== null)
<section class="card mb-16"><ol class="steps" aria-label="Tahap acara">
@foreach ($flow as $stepStatus => $stepLabel)
@php
$pos = $loop->index;
$stepClass = $pos === $currentStep ? 'now' : ($pos < $currentStep ? 'done' : '');
@endphp
<li class="{{ $stepClass }}"@if($pos === $currentStep) aria-current="step"@endif>{{ $stepLabel }}</li>
@endforeach
</ol></section>
@endif

@php
    $activeInvoice = $event->activeInvoice;
@endphp

@if($event->status === \App\Enums\EventStatus::MenungguPembayaran && $activeInvoice)
<div style="background:#eff6ff;border:1px solid #93c5fd;color:#1e40af;padding:16px;border-radius:8px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <b style="font-size:15px;">Tagihan resmi telah diterbitkan: {{ $activeInvoice->invoice_number }}</b>
        <p style="margin-top:4px;font-size:13px;">Total transfer: <b>Rp {{ number_format($activeInvoice->transfer_total, 0, ',', '.') }}</b> · Batas bayar: {{ $activeInvoice->due_at?->translatedFormat('d M Y, H:i') }} WIB</p>
    </div>
    <a class="btn btn-sm" href="{{ route('klien.tagihan.show', $activeInvoice) }}">Lihat &amp; Bayar Tagihan</a>
</div>
@elseif($event->status === \App\Enums\EventStatus::Terkonfirmasi && $activeInvoice)
<div style="background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:16px;border-radius:8px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <b style="font-size:15px;">Pembayaran Terverifikasi &amp; Acara Terkonfirmasi</b>
        <p style="margin-top:4px;font-size:13px;">Tagihan {{ $activeInvoice->invoice_number }} telah lunas. Nota kuitansi resmi telah tersedia.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="btn btn-sm" href="{{ route('klien.tagihan.show', $activeInvoice) }}">Lihat Tagihan &amp; Nota</a>
        <a class="btn btn-o btn-sm" href="{{ route('klien.tagihan.receipt', $activeInvoice) }}" target="_blank">Cetak Nota Lunas (PDF)</a>
    </div>
</div>
@endif

<div class="grid two">
    <div class="stack">
        <section class="card">
            <div class="card-h" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2>Pesanan layanan &amp; venue terkait</h2>
                    <p class="meta">Layanan dan venue yang Anda tambahkan ke acara ini.</p>
                </div>
                @if($event->status === \App\Enums\EventStatus::Draft)
                <a class="btn btn-o btn-sm" href="{{ route('klien.katalog.vendor') }}">+ Tambah dari katalog</a>
                @endif
            </div>

            @if ($orderItems->isEmpty())
            <div style="padding:24px;text-align:center;">
                <p class="hint">Belum ada layanan atau venue yang ditambahkan ke acara ini.</p>
                <a class="btn btn-o mt-12" href="{{ route('klien.katalog.vendor') }}">Jelajahi Katalog</a>
            </div>
            @else
            <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pesanan">
                <table class="t-wide">
                    <thead>
                        <tr>
                            <th scope="col">Layanan / Venue</th>
                            <th scope="col">Penyedia</th>
                            <th scope="col" class="num">Jumlah</th>
                            <th scope="col" class="num">Harga Satuan</th>
                            <th scope="col" class="num">Total</th>
                            <th scope="col">Status</th>
                            @if($event->status === \App\Enums\EventStatus::Draft)
                            <th scope="col" class="text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($orderItems as $item)
                    <tr>
                        <td><b>{{ $item->subject?->name ?? 'Item katalog' }}</b></td>
                        <td>{{ $item->provider?->business_name ?? '-' }}</td>
                        <td class="num">{{ $item->quantity ?? 1 }}</td>
                        <td class="num">{{ $rupiah($item->provider_price ?? $item->catalog_price_snapshot) }}</td>
                        <td class="num">{{ $rupiah($item->lineTotal()) }}</td>
                        <td><span class="badge b-info">{{ $item->status?->value ?? '-' }}</span></td>
                        @if($event->status === \App\Enums\EventStatus::Draft)
                        <td class="text-right">
                            <form method="POST" action="{{ route('klien.acara.pesanan.destroy', [$event, $item]) }}" onsubmit="return confirm('Hapus item ini dari acara?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-o btn-sm" style="color:#ef4444;border-color:#fca5a5;">Hapus</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </section>

        <section class="card" id="susunan-ruangan">
            <div class="card-h"><div><h2>Susunan ruangan</h2><p class="meta">Pilihan tata ruang untuk hari-H. Dapat diubah selama acara belum selesai.</p></div></div>
            <div class="card-b">
                @php
                    $roomLayoutOptions = $roomLayoutOptions ?? [];
                @endphp
                @if($roomLayoutEditable ?? false)
                <form method="POST" action="{{ route('klien.acara.layout.update', $event) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <div class="field full">
                            <label for="room-layout">Susunan ruangan</label>
                            <select class="input" id="room-layout" name="room_layout">
                                <option value="">Belum ditentukan</option>
                                @foreach($roomLayoutOptions as $layout)
                                <option value="{{ $layout->value }}" @selected(old('room_layout', $event->room_layout?->value) === $layout->value)>{{ $layout->label() }}</option>
                                @endforeach
                            </select>
                            @error('room_layout')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="field full">
                            <label for="room-layout-notes">Catatan susunan (opsional)</label>
                            <textarea class="input" id="room-layout-notes" name="room_layout_notes" rows="2" maxlength="500">{{ old('room_layout_notes', $event->room_layout_notes) }}</textarea>
                            @error('room_layout_notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm mt-12">Simpan susunan</button>
                </form>
                @else
                <dl class="dl">
                    <div><dt>Susunan</dt><dd>{{ $event->room_layout?->label() ?? 'Belum ditentukan' }}</dd></div>
                    @if($event->room_layout_notes)<div><dt>Catatan</dt><dd>{{ $event->room_layout_notes }}</dd></div>@endif
                </dl>
                @endif
            </div>
        </section>

        @php
            $rundownItems = $rundownItems ?? collect();
            $canWriteRundown = $canWriteRundown ?? false;
            $rundownNotice = $rundownNotice ?? null;
            $rundownOrderItems = $rundownOrderItems ?? collect();
            $rundownLocked = in_array($event->status, [\App\Enums\EventStatus::Selesai, \App\Enums\EventStatus::Dievaluasi], true);
        @endphp
        <section class="card" id="rundown">
            <div class="card-h" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div>
                    <h2>Rundown</h2>
                    <p class="meta">Susunan acara bersama penyedia. Item diurutkan otomatis berdasarkan jam mulai.</p>
                </div>
                @if($rundownItems->isNotEmpty())
                <span class="badge b-info">{{ $rundownItems->count() }} item</span>
                @endif
            </div>
            <div class="card-b">
                @if($rundownNotice)
                <p class="hint" style="margin:0">{{ $rundownNotice }}</p>
                @else
                @if($rundownItems->isEmpty())
                <p class="hint" style="margin:0">Belum ada item rundown. Tambahkan kegiatan pertama beserta jam mulainya.</p>
                @else
                <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar rundown acara">
                    <table class="t-wide">
                        <caption class="sr-only">Tabel: Rundown acara</caption>
                        <thead>
                            <tr>
                                <th scope="col">Jam</th>
                                <th scope="col">Kegiatan</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col">Penyedia terkait</th>
                                <th scope="col">Dibuat oleh</th>
                                @if($canWriteRundown)<th scope="col" class="text-right">Aksi</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rundownItems as $rundownItem)
                        <tr>
                            <td><b>{{ $rundownItem->start_time }}</b>{{ $rundownItem->end_time ? '–'.$rundownItem->end_time : '' }}</td>
                            <td>{{ $rundownItem->activity }}</td>
                            <td>{{ $rundownItem->description ?: '—' }}</td>
                            <td>{{ $rundownItem->orderItem?->subject?->name ?? '—' }}@if($rundownItem->orderItem?->provider?->business_name)<span class="sub-t">{{ $rundownItem->orderItem->provider->business_name }}</span>@endif</td>
                            <td>
                                @if((int) $rundownItem->created_by === (int) $event->client_id)
                                <span class="badge b-mute">Klien</span>
                                @else
                                <span class="badge b-info">Dibuat oleh vendor</span>
                                @endif
                            </td>
                            @if($canWriteRundown)
                            <td class="text-right">
                                <details>
                                    <summary class="btn btn-o btn-sm">Ubah</summary>
                                    <form method="POST" action="{{ route('klien.acara.rundown.update', [$event, $rundownItem]) }}" class="mt-8" style="min-width:240px;text-align:left;">
                                        @csrf
                                        @method('PUT')
                                        <div class="field"><label for="edit-start-{{ $rundownItem->id }}">Jam mulai</label><input class="input" id="edit-start-{{ $rundownItem->id }}" type="time" name="start_time" value="{{ $rundownItem->start_time }}" required></div>
                                        <div class="field"><label for="edit-end-{{ $rundownItem->id }}">Jam selesai</label><input class="input" id="edit-end-{{ $rundownItem->id }}" type="time" name="end_time" value="{{ $rundownItem->end_time }}"></div>
                                        <div class="field"><label for="edit-activity-{{ $rundownItem->id }}">Kegiatan</label><input class="input" id="edit-activity-{{ $rundownItem->id }}" name="activity" value="{{ $rundownItem->activity }}" maxlength="150" required></div>
                                        <div class="field"><label for="edit-description-{{ $rundownItem->id }}">Keterangan</label><textarea class="input" id="edit-description-{{ $rundownItem->id }}" name="description" rows="2" maxlength="1000">{{ $rundownItem->description }}</textarea></div>
                                        @if($rundownOrderItems->isNotEmpty())
                                        <div class="field"><label for="edit-order-{{ $rundownItem->id }}">Penyedia terkait</label>
                                            <select class="input" id="edit-order-{{ $rundownItem->id }}" name="order_item_id">
                                                <option value="">Tidak terkait pesanan</option>
                                                @foreach($rundownOrderItems as $orderOption)
                                                <option value="{{ $orderOption->id }}" @selected((int) $rundownItem->order_item_id === (int) $orderOption->id)>{{ $orderOption->subject?->name ?? 'Pesanan #'.$orderOption->id }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @endif
                                        <button type="submit" class="btn btn-sm mt-8">Simpan</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('klien.acara.rundown.destroy', [$event, $rundownItem]) }}" onsubmit="return confirm('Hapus item rundown ini?');" class="mt-4">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-o btn-sm" style="color:#ef4444;border-color:#fca5a5;">Hapus</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($canWriteRundown)
                <form method="POST" action="{{ route('klien.acara.rundown.store', $event) }}" class="mt-16">
                    @csrf
                    <div class="form-grid">
                        <div class="field">
                            <label for="rundown-start">Jam mulai <span style="color:#ef4444">*</span></label>
                            <input class="input" id="rundown-start" type="time" name="start_time" value="{{ old('start_time') }}" required>
                            @error('start_time')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="rundown-end">Jam selesai</label>
                            <input class="input" id="rundown-end" type="time" name="end_time" value="{{ old('end_time') }}">
                            @error('end_time')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="field full">
                            <label for="rundown-activity">Kegiatan <span style="color:#ef4444">*</span></label>
                            <input class="input" id="rundown-activity" name="activity" value="{{ old('activity') }}" maxlength="150" required>
                            @error('activity')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="field full">
                            <label for="rundown-description">Keterangan</label>
                            <textarea class="input" id="rundown-description" name="description" rows="2" maxlength="1000">{{ old('description') }}</textarea>
                            @error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        @if($rundownOrderItems->isNotEmpty())
                        <div class="field full">
                            <label for="rundown-order">Penyedia terkait (opsional)</label>
                            <select class="input" id="rundown-order" name="order_item_id">
                                <option value="">Tidak terkait pesanan</option>
                                @foreach($rundownOrderItems as $orderOption)
                                <option value="{{ $orderOption->id }}" @selected((int) old('order_item_id') === (int) $orderOption->id)>{{ $orderOption->subject?->name ?? 'Pesanan #'.$orderOption->id }}@if($orderOption->provider?->business_name) — {{ $orderOption->provider->business_name }}@endif</option>
                                @endforeach
                            </select>
                            @error('order_item_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-sm mt-12">Tambah item rundown</button>
                </form>
                @else
                <p class="hint mt-12">{{ $rundownLocked ? 'Rundown dikunci karena acara sudah selesai.' : 'Rundown hanya dapat diubah saat acara terkonfirmasi.' }}</p>
                @endif
                @endif
            </div>
        </section>

        @if(in_array($event->status, [\App\Enums\EventStatus::Selesai, \App\Enums\EventStatus::Dievaluasi], true))
        @php
            $evaluation = $evaluation ?? null;
            $evaluationSubjects = $evaluationSubjects ?? [];
            $canSubmitEvaluation = $canSubmitEvaluation ?? false;
            $evaluationNames = $evaluationNames ?? [];
            $stars = fn (int $n) => str_repeat('★', $n).str_repeat('☆', max(0, 5 - $n));
        @endphp
        <section class="card" id="evaluasi">
            <div class="card-h">
                <div>
                    <h2>Evaluasi</h2>
                    <p class="meta">Penilaian venue, vendor, dan Pesta Kita untuk acara ini. Evaluasi dikirim satu kali dan bersifat final.</p>
                </div>
            </div>
            <div class="card-b">
                @if($evaluation)
                <p class="hint" style="margin:0 0 12px;">Evaluasi terkirim {{ $evaluation->created_at?->translatedFormat('d M Y, H:i') }} WIB. Terima kasih atas penilaian Anda.</p>
                <div class="table-wrap" role="region" tabindex="0" aria-label="Hasil evaluasi acara">
                    <table class="t-wide">
                        <caption class="sr-only">Tabel: Hasil evaluasi acara</caption>
                        <thead><tr><th scope="col">Subjek</th><th scope="col">Rating</th><th scope="col">Komentar</th></tr></thead>
                        <tbody>
                        @foreach($evaluation->items as $evaluationItem)
                        <tr>
                            <td><b>{{ $evaluationNames[$evaluationItem->target_type.':'.($evaluationItem->target_id ?? 0)] ?? ucfirst($evaluationItem->target_type) }}</b></td>
                            <td><span aria-hidden="true">{{ $stars((int) $evaluationItem->rating) }}</span> <span class="hint">{{ $evaluationItem->rating }}/5</span></td>
                            <td>{{ $evaluationItem->comment ?: '—' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($evaluation->system_feedback)
                <dl class="dl mt-12"><div><dt>Masukan untuk sistem</dt><dd>{{ $evaluation->system_feedback }}</dd></div></dl>
                @endif
                @elseif($canSubmitEvaluation)
                <form method="POST" action="{{ route('klien.acara.evaluasi.store', $event) }}">
                    @csrf
                    @error('ratings')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    @foreach($evaluationSubjects as $subject)
                    <fieldset class="mt-12" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
                        <legend style="font-weight:600;padding:0 6px;">{{ $subject['name'] }}</legend>
                        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
                            @for($n = 1; $n <= 5; $n++)
                            <label style="display:inline-flex;align-items:center;gap:6px;">
                                <input type="radio" name="ratings[{{ $subject['key'] }}][rating]" value="{{ $n }}" required @checked((int) old('ratings.'.$subject['key'].'.rating') === $n)>
                                <span aria-hidden="true">{{ $n }}</span>
                                <span class="sr-only">bintang {{ $n }} dari 5 untuk {{ $subject['name'] }}</span>
                            </label>
                            @endfor
                        </div>
                        @error('ratings.'.$subject['key'].'.rating')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        <div class="field mt-8">
                            <label for="comment-{{ str_replace(':', '-', $subject['key']) }}">Komentar (opsional)</label>
                            <textarea class="input" id="comment-{{ str_replace(':', '-', $subject['key']) }}" name="ratings[{{ $subject['key'] }}][comment]" rows="2" maxlength="1000">{{ old('ratings.'.$subject['key'].'.comment') }}</textarea>
                            @error('ratings.'.$subject['key'].'.comment')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </fieldset>
                    @endforeach
                    <div class="field full mt-12">
                        <label for="system-feedback">Masukan untuk sistem Pesta Kita (opsional)</label>
                        <textarea class="input" id="system-feedback" name="system_feedback" rows="3" maxlength="2000">{{ old('system_feedback') }}</textarea>
                        @error('system_feedback')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn btn-sm mt-12" onclick="return confirm('Kirim evaluasi? Setelah dikirim, evaluasi tidak dapat diubah.');">Kirim evaluasi</button>
                </form>
                @else
                <p class="hint" style="margin:0">Evaluasi hanya dapat diisi setelah acara selesai.</p>
                @endif
            </div>
        </section>
        @endif

        @if($event->status === \App\Enums\EventStatus::Draft && ($recommendation ?? null))
        @php
            $rec = $recommendation;
            $starsFor = fn ($avg) => str_repeat('★', (int) round((float) $avg)).str_repeat('☆', max(0, 5 - (int) round((float) $avg)));
        @endphp
        <section class="card" id="rekomendasi">
            <div class="card-h">
                <div>
                    <h2>Rekomendasi</h2>
                    <p class="meta">Rekomendasi otomatis berdasarkan anggaran, jumlah tamu, tanggal, dan aturan alokasi. Estimasi sudah termasuk komisi layanan dan biaya kebersihan venue; angka final tertera pada tagihan dan dapat berbeda bila pengaturan komisi berubah.</p>
                </div>
            </div>
            <div class="card-b">
                @if(! $rec['ready'])
                <p class="hint" style="margin:0">Rekomendasi belum dapat dibuat karena {{ implode(', ', $rec['missing']) }} belum diisi. Lengkapi konsep acara terlebih dahulu.</p>
                @else
                <div class="table-wrap" role="region" tabindex="0" aria-label="Alokasi anggaran per kategori">
                    <table class="t-wide">
                        <caption class="sr-only">Tabel: Alokasi anggaran per kategori</caption>
                        <thead><tr><th scope="col">Kategori</th><th scope="col" class="num">Alokasi</th></tr></thead>
                        <tbody>
                        @foreach($rec['categories'] as $category)
                        <tr><td>{{ $category['label'] }}</td><td class="num">{{ $rupiah($category['allocation']) }}</td></tr>
                        @endforeach
                        <tr><td><b>Dana cadangan</b> <span class="hint">(termasuk selisih pembulatan)</span></td><td class="num"><b>{{ $rupiah($rec['allocations']['cadangan']) }}</b></td></tr>
                        </tbody>
                    </table>
                </div>

                @foreach($rec['categories'] as $category)
                <div class="mt-16">
                    <h3 style="font-size:15px;">{{ $category['label'] }} <span class="hint">· alokasi {{ $rupiah($category['allocation']) }}</span></h3>
                    @if(empty($category['candidates']))
                    <p class="hint">{{ $category['message'] }}.</p>
                    @else
                    <div class="table-wrap" role="region" tabindex="0" aria-label="Kandidat {{ $category['label'] }}">
                        <table class="t-wide">
                            <caption class="sr-only">Tabel: Kandidat {{ $category['label'] }}</caption>
                            <thead><tr><th scope="col">Pilihan</th><th scope="col">Penyedia</th><th scope="col" class="num">Estimasi biaya</th><th scope="col">Rating</th><th scope="col">Aksi</th></tr></thead>
                            <tbody>
                            @foreach($category['candidates'] as $candidate)
                            <tr>
                                <td><b>{{ $candidate['name'] }}</b>@if($candidate['meta'])<span class="sub-t">{{ $candidate['meta'] }}</span>@endif</td>
                                <td>{{ $candidate['provider'] ?? '—' }}</td>
                                <td class="num"><b>{{ $rupiah($candidate['estimate']) }}</b><span class="sub-t">Harga penyedia {{ $rupiah($candidate['price']) }} + komisi layanan {{ $rupiah($candidate['commission']) }}@if($candidate['cleaning_fee'] > 0) + kebersihan {{ $rupiah($candidate['cleaning_fee']) }}@endif</span></td>
                                <td>@if(($candidate['rating']['count'] ?? 0) > 0)<span aria-hidden="true">{{ $starsFor($candidate['rating']['average']) }}</span> <span class="hint">{{ number_format($candidate['rating']['average'], 1, ',', '.') }} ({{ $candidate['rating']['count'] }})</span>@else<span class="hint">Belum ada penilaian</span>@endif</td>
                                <td>
                                    <form method="POST" action="{{ route('klien.acara.pesanan.store', $event) }}">
                                        @csrf
                                        <input type="hidden" name="subject_type" value="{{ $candidate['subject_type'] }}">
                                        <input type="hidden" name="subject_id" value="{{ $candidate['subject_id'] }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-sm">Tambah ke acara</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endforeach

                <div class="mt-16" style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px;">
                    <b>Ringkasan paket (saran, tidak disimpan)</b>
                    @if(empty($rec['summary']['items']))
                    <p class="hint" style="margin:8px 0 0;">Belum ada kombinasi kandidat yang muat dalam anggaran.</p>
                    @else
                    <ul class="policy mt-8">
                        @foreach($rec['summary']['items'] as $summaryItem)
                        <li><span>{{ $summaryItem['category'] }}: {{ $summaryItem['name'] }}</span><span>{{ $rupiah($summaryItem['estimate']) }}</span></li>
                        @endforeach
                    </ul>
                    <p class="mt-8">Total estimasi: <b>{{ $rupiah($rec['summary']['total']) }}</b> · Sisa anggaran: <b>{{ $rupiah($rec['summary']['remaining']) }}</b></p>
                    @endif
                </div>
                @endif
            </div>
        </section>
        @endif

        @if($event->status === \App\Enums\EventStatus::Draft)
        <section class="card" style="border:2px solid #3b82f6;background:#eff6ff;">
            <div class="card-h">
                <h2 style="color:#1d4ed8;">Ajukan Acara ke Admin</h2>
            </div>
            <div class="card-b">
                <p style="margin-bottom:12px;">Ketika Anda menekan <b>Ajukan Acara</b>, sistem akan mengunci ketersediaan slot tanggal, mencatat harga resmi, dan mengirimkan notifikasi kepada Admin untuk peninjauan.</p>
                <form method="POST" action="{{ route('klien.acara.ajukan', $event) }}" onsubmit="return confirm('Yakin ingin mengajukan acara ini? Ketersediaan slot akan dikunci.');">
                    @csrf
                    <button type="submit" class="btn" style="background:#2563eb;" @if($orderItems->isEmpty()) disabled title="Tambahkan minimal 1 pesanan layanan/venue terlebih dahulu" @endif>
                        Ajukan Acara Sekarang
                    </button>
                    @if($orderItems->isEmpty())
                    <span class="hint" style="margin-left:12px;color:#dc2626;">(Tambahkan minimal 1 layanan/venue sebelum mengajukan)</span>
                    @endif
                </form>
            </div>
        </section>
        @endif

        @if(in_array($event->status, [\App\Enums\EventStatus::Diajukan, \App\Enums\EventStatus::MenungguPembayaran], true))
        <section class="card" id="batalkan-acara">
            <div class="card-h"><h2>Batalkan acara</h2></div>
            <div class="card-b">
                @if($activeInvoice?->status === \App\Enums\InvoiceStatus::MenungguValidasi)
                <p class="hint">{{ \App\Services\EventCancellationService::MESSAGE_PAYMENT_UNDER_VALIDATION }}</p>
                @else
                <p class="hint">Sebelum pembayaran divalidasi, pembatalan berjalan otomatis tanpa biaya. Semua pesanan dan slot tanggal akan dilepas{{ $activeInvoice ? ', dan tagihan '.$activeInvoice->invoice_number.' ikut dibatalkan' : '' }}.</p>
                <details class="mt-12">
                    <summary class="btn btn-o btn-sm" style="color:#b91c1c;border-color:#fca5a5;">Batalkan acara</summary>
                    <form method="POST" action="{{ route('klien.acara.batalkan', $event) }}" class="mt-12">
                        @csrf
                        <label style="display:flex;gap:8px;align-items:flex-start;">
                            <input type="checkbox" name="konfirmasi" value="1" required>
                            <span>Saya yakin membatalkan acara ini. Tindakan ini tidak dapat diurungkan.</span>
                        </label>
                        @error('konfirmasi')<p class="hint" style="color:#b91c1c;">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-sm mt-12" style="background:#b91c1c;">Ya, batalkan acara</button>
                    </form>
                </details>
                @endif
            </div>
        </section>
        @endif

        @php
            $cancellation = $cancellation ?? null;
            $cancellationPreview = $cancellationPreview ?? null;
            $cancellationSteps = [
                \App\Enums\CancellationStatus::Diajukan,
                \App\Enums\CancellationStatus::Ditinjau,
                \App\Enums\CancellationStatus::Disetujui,
                \App\Enums\CancellationStatus::RefundDiproses,
                \App\Enums\CancellationStatus::RefundSelesai,
            ];
        @endphp
        @if($cancellation || $cancellationPreview)
        <section class="card" id="pembatalan">
            <div class="card-h"><h2>Pembatalan</h2></div>
            <div class="card-b">
                @if($cancellation)
                @php
                    $isRejected = $cancellation->status === \App\Enums\CancellationStatus::Ditolak;
                    $stepIndex = array_search($cancellation->status, $cancellationSteps, true);
                @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <b>Pengajuan pembatalan #{{ $cancellation->id }}</b>
                    <span class="badge {{ $cancellation->status->badgeClass() }}">{{ $cancellation->status->label() }}</span>
                </div>
                @if(! $isRejected)
                <ol class="steps mt-12" aria-label="Tahap pembatalan">
                    @foreach($cancellationSteps as $i => $step)
                    <li class="{{ $i === $stepIndex ? 'now' : ($i < $stepIndex ? 'done' : '') }}"@if($i === $stepIndex) aria-current="step"@endif>{{ $step->label() }}</li>
                    @endforeach
                </ol>
                @endif
                <dl class="dl mt-12">
                    <div><dt>Diajukan</dt><dd>{{ $cancellation->created_at?->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                    <div><dt>Alasan</dt><dd>{{ $cancellation->reason }}</dd></div>
                    <div><dt>Persen refund</dt><dd>{{ $cancellation->refund_percent }}%</dd></div>
                    <div><dt>Refund + kode unik</dt><dd><b>{{ $rupiah($cancellation->refundTotal()) }}</b>@if(in_array($cancellation->status, [\App\Enums\CancellationStatus::Diajukan, \App\Enums\CancellationStatus::Ditinjau], true)) <span class="hint">(perkiraan; nominal final ditetapkan Admin)</span>@endif</dd></div>
                    <div><dt>Rekening tujuan</dt><dd>{{ $cancellation->refund_bank_name }} · {{ $cancellation->refund_account_number }} · a.n. {{ $cancellation->refund_account_name }}</dd></div>
                    @if($cancellation->refund_paid_at)
                    <div><dt>Ditransfer</dt><dd>{{ $cancellation->refund_paid_at->translatedFormat('d M Y') }}</dd></div>
                    @endif
                    @if($isRejected && $cancellation->decision_reason)
                    <div><dt>Alasan penolakan</dt><dd>{{ $cancellation->decision_reason }}</dd></div>
                    @endif
                </dl>
                @if($cancellation->status === \App\Enums\CancellationStatus::RefundSelesai && $cancellation->refundProof)
                <a class="btn btn-o btn-sm mt-12" href="{{ route('media.show', $cancellation->refundProof) }}" download>Unduh bukti refund</a>
                @endif
                @endif

                @if($cancellationPreview)
                @if($cancellation)<hr class="mt-16 mb-16">@endif
                <p class="hint">Pembatalan berlaku untuk seluruh acara. Perubahan sebagian ditangani Admin; hubungi Admin lewat tombol WhatsApp.</p>
                <div class="mt-12" style="background:#fffbeb;border:1px solid #fcd34d;padding:12px;border-radius:6px;">
                    <b>Perkiraan; nominal final ditetapkan Admin</b>
                    <dl class="dl mt-8">
                        <div><dt>Sisa hari ke acara</dt><dd>{{ $cancellationPreview['policy_snapshot']['remaining_days'] }} hari</dd></div>
                        <div><dt>Tier kebijakan</dt><dd>&ge; {{ $cancellationPreview['policy_snapshot']['selected_tier']['min_days'] }} hari → {{ $cancellationPreview['refund_percent'] }}%</dd></div>
                        <div><dt>Perkiraan refund</dt><dd>{{ $rupiah($cancellationPreview['refund_amount']) }} + kode unik {{ $rupiah($cancellationPreview['unique_code_refund']) }} = <b>{{ $rupiah($cancellationPreview['refund_total']) }}</b></dd></div>
                    </dl>
                </div>
                <details class="mt-12" @if($errors->hasAny(['reason', 'refund_bank_name', 'refund_account_number', 'refund_account_name', 'acknowledge'])) open @endif>
                    <summary class="btn btn-o btn-sm" style="color:#b91c1c;border-color:#fca5a5;">Ajukan pembatalan</summary>
                    <form method="POST" action="{{ route('klien.acara.pembatalan.store', $event) }}" class="mt-12">
                        @csrf
                        <div class="field full">
                            <label for="cancel-reason">Alasan pembatalan <span style="color:#ef4444">*</span></label>
                            <textarea class="input" id="cancel-reason" name="reason" rows="3" minlength="10" required>{{ old('reason') }}</textarea>
                            @error('reason')<p class="hint" style="color:#b91c1c;">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="refund-bank">Bank tujuan refund <span style="color:#ef4444">*</span></label>
                            <input class="input" id="refund-bank" name="refund_bank_name" value="{{ old('refund_bank_name') }}" required>
                            @error('refund_bank_name')<p class="hint" style="color:#b91c1c;">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="refund-number">Nomor rekening <span style="color:#ef4444">*</span></label>
                            <input class="input" id="refund-number" name="refund_account_number" inputmode="numeric" value="{{ old('refund_account_number') }}" required>
                            @error('refund_account_number')<p class="hint" style="color:#b91c1c;">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="refund-name">Nama pemilik rekening <span style="color:#ef4444">*</span></label>
                            <input class="input" id="refund-name" name="refund_account_name" value="{{ old('refund_account_name') }}" required>
                            @error('refund_account_name')<p class="hint" style="color:#b91c1c;">{{ $message }}</p>@enderror
                        </div>
                        <label class="mt-8" style="display:flex;gap:8px;align-items:flex-start;">
                            <input type="checkbox" name="acknowledge" value="1" required>
                            <span>Saya memahami nominal akhir ditetapkan Admin</span>
                        </label>
                        @error('acknowledge')<p class="hint" style="color:#b91c1c;">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-sm mt-12" style="background:#b91c1c;">Kirim pengajuan pembatalan</button>
                    </form>
                </details>
                @endif
            </div>
        </section>
        @endif

        <section class="card">
            <div class="card-h"><h2>Alur Pemesanan</h2></div>
            <div class="card-b">
                <ul class="policy">
                    <li><span>Pesanan disetujui langsung oleh Admin Pesta Kita tanpa proses tawar-menawar harga.</span></li>
                    <li><span>Setelah semua pesanan disetujui, tagihan resmi akan diterbitkan dengan batas bayar 48 jam.</span></li>
                    <li><span>Pembayaran dilakukan melalui transfer 100% ke rekening resmi perusahaan Pesta Kita.</span></li>
                </ul>
            </div>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-h"><h2>Ringkasan acara</h2></div>
            <div class="card-b">
                <dl class="dl">
                    <div><dt>Nama acara</dt><dd>{{ $event->name ?: '—' }}</dd></div>
                    <div><dt>Tanggal</dt><dd>{{ $event->event_date?->translatedFormat('l, d M Y') ?? 'Belum diisi' }}</dd></div>
                    <div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count ? $event->guest_count.' orang' : 'Belum diisi' }}</dd></div>
                    <div><dt>Tema</dt><dd>{{ $event->theme ?: 'Belum diisi' }}</dd></div>
                    <div><dt>Anggaran (Budget)</dt><dd><b>{{ $event->budget !== null ? $rupiah($event->budget) : 'Belum diisi' }}</b></dd></div>
                    <div><dt>Estimasi biaya</dt><dd><b>{{ $rupiah($estimatedTotal) }}</b><small class="hint" style="display:block;">@if ($estimatedInvoiceNumber)Sesuai tagihan {{ $estimatedInvoiceNumber }} (belum termasuk kode unik).@else Sudah termasuk komisi layanan dan biaya kebersihan venue; angka final tertera pada tagihan.@endif</small></dd></div>
                </dl>
                @if ($event->description)
                <p class="mt-8 hint">{{ $event->description }}</p>
                @endif

                @if($event->status === \App\Enums\EventStatus::Draft)
                <div class="form-actions mt-16" style="display:flex;gap:8px;">
                    <a class="btn btn-o full" href="{{ route('klien.acara.edit', $event) }}">Ubah Konsep</a>
                    <form method="POST" action="{{ route('klien.acara.destroy', $event) }}" onsubmit="return confirm('Hapus konsep acara ini secara permanen?');" style="flex:1;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-o full" style="color:#ef4444;border-color:#fca5a5;">Hapus</button>
                    </form>
                </div>
                @endif
            </div>
        </section>

        <div class="stack"><a class="btn btn-o full" href="{{ route('klien.acara') }}">Kembali ke daftar acara</a></div>
    </div>
</div>
</main>
@endsection
