@extends('layouts.vendor')

@section('title', 'Detail Pesanan · Panel Vendor Pesta Kita')

@section('content')
@php
    $orderItem = $orderItem ?? null;
    $event = $event ?? ($orderItem?->event ?? null);
    $subjectName = $orderItem?->subject?->name ?? ($orderItem ? 'Layanan #'.$orderItem->id : '-');
    $eventLabel = $orderItem ? 'Acara #'.$orderItem->event_id : '-';
    $dateStr = $orderItem?->usage_date ? $orderItem->usage_date->format('l, d F Y') : ($event?->event_date?->format('l, d F Y') ?? '-');
    $badgeClass = match($orderItem?->status?->value) {
        'menunggu_review' => 'badge b-warn',
        'disetujui', 'terkonfirmasi', 'selesai' => 'badge b-ok',
        'ditolak', 'dibatalkan' => 'badge b-bad',
        'dialihkan' => 'badge b-info',
        default => 'badge',
    };
@endphp

<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs">
                <a href="{{ url('/vendor') }}">Beranda</a> /
                <a href="{{ route('vendor.pesanan') }}">Pesanan</a> /
                {{ $eventLabel }}
            </div>
            <h1>{{ $subjectName }}</h1>
        </div>
        <a class="btn btn-o" href="{{ route('vendor.pesanan') }}">Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-16"><span class="ic i-check" aria-hidden="true"></span><span>{{ session('success') }}</span></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-16"><span class="ic i-info" aria-hidden="true"></span><span>{{ session('error') }}</span></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-16">
            <span class="ic i-info" aria-hidden="true"></span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if(!$orderItem)
        <div class="card card-b">
            <p>Pilih pesanan dari <a href="{{ route('vendor.pesanan') }}">daftar pesanan masuk</a> untuk melihat detail.</p>
        </div>
    @else
    <div class="grid two">
        <div class="stack">
            <section class="card">
                <div class="card-h">
                    <h2>Detail pesanan</h2>
                    <span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($orderItem->status->value)) }}</span>
                </div>
                <div class="card-b">
                    @if($orderItem->status === \App\Enums\OrderItemStatus::Disetujui && $deadline)
                        @php
                            $diffHours = (int) now()->diffInHours($deadline, false);
                        @endphp
                        @if($diffHours > 0)
                            <p class="deadline">
                                <span class="chip">Sisa {{ $diffHours }} jam</span>
                                Masa keberatan berakhir <time datetime="{{ $deadline->toIso8601String() }}">{{ $deadline->format('d/m/Y H:i') }} WIB</time>
                            </p>
                        @else
                            <p class="deadline">
                                <span class="chip">Masa keberatan selesai</span>
                                Berakhir pada {{ $deadline->format('d/m/Y H:i') }} WIB
                            </p>
                        @endif
                    @endif

                    <dl class="dl">
                        <div><dt>Acara</dt><dd>{{ $eventLabel }}</dd></div>
                        <div><dt>Tanggal penggunaan</dt><dd>{{ $dateStr }}</dd></div>
                        <div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count }} orang</dd></div>
                        <div><dt>Tema acara</dt><dd>{{ $event->theme ?? '-' }}</dd></div>
                        <div><dt>Layanan</dt><dd>{{ $subjectName }}</dd></div>
                        @if($orderItem->quantity > 1)
                            <div><dt>Kuantitas &amp; harga satuan</dt><dd>{{ $orderItem->quantity }} unit (@ Rp {{ number_format($orderItem->provider_price ?? $orderItem->catalog_price_snapshot ?? 0, 0, ',', '.') }})</dd></div>
                        @endif
                        <div><dt>Hak Anda (total harga penuh)</dt><dd>Rp {{ number_format($orderItem->lineTotal(), 0, ',', '.') }}</dd></div>
                    </dl>

                    @if($event->description)
                        <div class="note mt-16">
                            <b>Kebutuhan acara (diteruskan Pesta Kita):</b> {{ $event->description }}
                        </div>
                    @endif
                </div>
            </section>

            @if($latestObjection)
                <section class="card">
                    <div class="card-h">
                        <h2>Riwayat keberatan Anda</h2>
                        <span class="badge {{ $latestObjection->status->value === 'diterima' ? 'b-ok' : ($latestObjection->status->value === 'ditolak' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst($latestObjection->status->value) }}
                        </span>
                    </div>
                    <div class="card-b">
                        <dl class="dl">
                            <div><dt>Kategori</dt><dd>{{ ucfirst(str_replace('_', ' ', $latestObjection->category?->value ?? 'lainnya')) }}</dd></div>
                            <div><dt>Alasan</dt><dd>{{ $latestObjection->reason }}</dd></div>
                            <div><dt>Waktu diajukan</dt><dd>{{ $latestObjection->created_at->format('d/m/Y H:i') }} WIB</dd></div>
                        </dl>
                        @if($latestObjection->decision_reason)
                            <div class="note-box mt-16">
                                <b>Keputusan Admin:</b> {{ $latestObjection->decision_reason }}
                            </div>
                        @else
                            <p class="hint mt-8">Keberatan sedang ditinjau oleh Admin Pesta Kita. Status pesanan tetap aktif sampai ada keputusan.</p>
                        @endif
                    </div>
                </section>
            @endif

            @if($canFileObjection && (!$latestObjection || in_array($latestObjection->status->value, ['ditolak'])))
                <section class="card">
                    <div class="card-h">
                        <h2>Ajukan keberatan</h2>
                    </div>
                    <div class="card-b">
                        @if($isEmergency)
                            <p class="sub mb-16 text-danger"><b>Keberatan darurat:</b> Wajib melampirkan bukti kendala (alat rusak/force majeure) untuk ditinjau oleh Admin.</p>
                        @else
                            <p class="sub mb-16">Keberatan hanya catatan untuk Admin; status pesanan tidak berubah sampai Admin memutuskan.</p>
                        @endif

                        <form class="form-grid" method="POST" action="{{ route('vendor.pesanan.keberatan.store', $orderItem) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="is_emergency" value="{{ $isEmergency ? 1 : 0 }}"/>

                            <div class="field">
                                <label for="kategori">Kategori keberatan</label>
                                <select class="input" id="kategori" name="category" required>
                                    <option value="alat_rusak">Alat rusak</option>
                                    <option value="jadwal_bentrok">Jadwal bentrok</option>
                                    <option value="kapasitas">Kapasitas</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="bukti">Bukti pendukung @if($isEmergency) <span class="text-danger">(wajib)</span> @else (opsional) @endif</label>
                                <input class="input" id="bukti" name="evidence" type="file" accept=".jpg,.jpeg,.png,.pdf" @if($isEmergency) required @endif/>
                            </div>

                            <div class="field full">
                                <label for="alasan">Alasan, jelaskan masalahnya secara rinci (wajib)</label>
                                <textarea class="input" id="alasan" name="reason" rows="3" placeholder="Jelaskan kendala operasional yang dihadapi…" required></textarea>
                            </div>

                            <div class="full">
                                <button class="btn btn-bad" type="submit" onclick="return confirm('Kirim keberatan ke Admin? Status pesanan tetap aktif sampai Admin memutuskan.');">
                                    Kirim keberatan ke Admin
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <div class="stack">
            <section class="card">
                <div class="card-h"><h2>Status operasional pesanan</h2></div>
                <div class="card-b">
                    <ul class="timeline">
                        <li class="done">
                            <b>Pesanan diajukan klien</b>
                            <small>{{ $orderItem->created_at->format('d M Y · H:i') }}</small>
                        </li>
                        <li class="{{ $orderItem->approved_at ? 'done' : 'pending' }}">
                            <b>Disetujui Pesta Kita</b>
                            <small>{{ $orderItem->approved_at ? $orderItem->approved_at->format('d M Y · H:i') : 'Menunggu review Admin' }}</small>
                        </li>
                        <li class="{{ $orderItem->status === \App\Enums\OrderItemStatus::Terkonfirmasi || $orderItem->status === \App\Enums\OrderItemStatus::Selesai ? 'done' : 'pending' }}">
                            <b>Pembayaran klien &amp; Konfirmasi</b>
                            <small>{{ $orderItem->status === \App\Enums\OrderItemStatus::Terkonfirmasi ? 'Terkonfirmasi lunas' : 'Menunggu pembayaran' }}</small>
                        </li>
                        <li class="{{ $orderItem->status === \App\Enums\OrderItemStatus::Selesai ? 'done' : '' }}">
                            <b>Acara berlangsung</b>
                            <small>{{ $dateStr }}</small>
                        </li>
                        <li>
                            <b>Pencairan hak penyedia</b>
                            <small>Setelah acara dinyatakan selesai oleh Admin</small>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="card">
                <div class="card-h"><h2>Hak penyedia</h2></div>
                <div class="card-b">
                    <p class="sub m-0">Hak penuh penyedia tanpa potongan komisi. Dicairkan setelah acara selesai.</p>
                    <div class="big-num mt-8">Rp {{ number_format($orderItem->lineTotal(), 0, ',', '.') }}</div>
                    @if($orderItem->quantity > 1)
                        <p class="hint mt-4">Rp {{ number_format($orderItem->provider_price ?? $orderItem->catalog_price_snapshot ?? 0, 0, ',', '.') }} × {{ $orderItem->quantity }} unit</p>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-h"><h2>Butuh bantuan?</h2></div>
                <div class="card-b">
                    <p class="m-0">Seluruh komunikasi dengan klien dilakukan melalui perantara Pesta Kita.</p>
                    <p class="mt-8 fw-7">Hubungi Admin Pesta Kita</p>
                </div>
            </section>
        </div>
    </div>
    @endif
</main>
@endsection
