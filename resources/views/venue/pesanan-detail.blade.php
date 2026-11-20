@extends('layouts.venue')

@section('title', 'Detail Pesanan Venue · Panel Pemilik Venue Pesta Kita')

@section('content')
@php
    $orderItem = $orderItem ?? null;
    $event = $event ?? ($orderItem?->event ?? null);
    $roomName = $orderItem?->subject?->name ?? ($orderItem ? 'Ruangan #'.$orderItem->id : '-');
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
                <a href="{{ url('/venue') }}">Beranda</a> /
                <a href="{{ route('venue.pesanan') }}">Pesanan</a> /
                {{ $eventLabel }}
            </div>
            <h1>Detail pesanan venue</h1>
            <p class="sub">Rincian reservasi, kebutuhan ruangan, nilai hak penyedia, dan status operasional.</p>
        </div>
        <a class="btn btn-o" href="{{ route('venue.pesanan') }}">Kembali</a>
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
            <p>Pilih pesanan dari <a href="{{ route('venue.pesanan') }}">daftar pesanan venue</a> untuk melihat detail.</p>
        </div>
    @else
    <div class="order-banner">
        <div>
            <span class="eyebrow">Pesanan venue · {{ $eventLabel }}</span>
            <h2>{{ $roomName }}</h2>
            <p>{{ $dateStr }}</p>
        </div>
        <div class="order-banner-side">
            <span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($orderItem->status->value)) }}</span>
            <b class="amount">Rp {{ number_format($orderItem->lineTotal(), 0, ',', '.') }}</b>
            <small class="sub">Hak penyedia (harga penuh)</small>
        </div>
    </div>

    <div class="grid order-layout">
        <div class="stack">
            <section class="card">
                <div class="card-h">
                    <h2>Informasi pemesanan</h2>
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
                        <div><dt>Tanggal acara</dt><dd>{{ $dateStr }}</dd></div>
                        <div><dt>Ruangan</dt><dd>{{ $roomName }}</dd></div>
                        <div><dt>Jumlah tamu</dt><dd>{{ $event->guest_count }} orang</dd></div>
                        <div><dt>Tema acara</dt><dd>{{ $event->theme ?? '-' }}</dd></div>
                    </dl>

                    @if($event->description)
                        <div class="note mt-16">
                            <b>Catatan dari Pesta Kita:</b> {{ $event->description }}
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
                            <p class="sub mb-16 text-danger"><b>Keberatan darurat:</b> Wajib melampirkan bukti kendala (kerusakan fasilitas/force majeure) untuk ditinjau Admin.</p>
                        @else
                            <p class="sub mb-16">Masa keberatan untuk meninjau ketersediaan venue. Keberatan menjadi catatan untuk Admin dan tidak mengubah status pesanan sampai diputuskan.</p>
                        @endif

                        <form class="form-grid" method="POST" action="{{ route('venue.pesanan.keberatan.store', $orderItem) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="is_emergency" value="{{ $isEmergency ? 1 : 0 }}"/>

                            <div class="field">
                                <label for="kategori">Kategori keberatan</label>
                                <select class="input" id="kategori" name="category" required>
                                    <option value="jadwal_bentrok">Jadwal bentrok</option>
                                    <option value="kapasitas">Kapasitas / tata letak</option>
                                    <option value="alat_rusak">Kondisi fasilitas / alat rusak</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="bukti-order">Bukti pendukung @if($isEmergency) <span class="text-danger">(wajib)</span> @else (opsional) @endif</label>
                                <input class="input" id="bukti-order" name="evidence" type="file" accept=".jpg,.jpeg,.png,.pdf" @if($isEmergency) required @endif/>
                            </div>

                            <div class="field full">
                                <label for="alasan">Alasan keberatan (wajib)</label>
                                <textarea class="input" id="alasan" name="reason" rows="3" placeholder="Jelaskan kendala venue yang perlu ditinjau Admin…" required></textarea>
                            </div>

                            <div class="field full">
                                <button class="btn btn-bad" type="submit" onclick="return confirm('Kirim keberatan ke Admin? Status pesanan tetap aktif sampai Admin memutuskan.');">
                                    Kirim keberatan ke Admin
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <aside class="stack">
            <section class="card">
                <div class="card-h"><h2>Status pesanan</h2></div>
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
                            <b>Pencairan hak venue</b>
                            <small>Setelah acara dinyatakan selesai oleh Admin</small>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="card">
                <div class="card-h"><h2>Informasi acara</h2></div>
                <div class="card-b">
                    <div class="profile-mini">
                        <span class="avatar">#</span>
                        <div>
                            <b>{{ $eventLabel }}</b>
                            <small>Identitas klien dirahasiakan</small>
                        </div>
                    </div>
                    <dl class="side-dl mt-16">
                        <div><dt>Kontak</dt><dd>Melalui Pesta Kita</dd></div>
                    </dl>
                    <p class="hint">Komunikasi dan koordinasi dilakukan melalui Admin Pesta Kita.</p>
                </div>
            </section>

            <section class="card">
                <div class="card-h"><h2>Pencairan</h2></div>
                <div class="card-b">
                    <span class="lbl">Hak penyedia venue (harga sewa)</span>
                    <div class="big-num">Rp {{ number_format($orderItem->lineTotal(), 0, ',', '.') }}</div>
                    <p class="hint">Dicairkan setelah acara selesai dan divalidasi oleh Admin Pesta Kita.</p>
                </div>
            </section>

            <section class="card">
                <div class="card-h"><h2>Butuh bantuan?</h2></div>
                <div class="card-b">
                    <p class="sub">Hubungi Admin untuk perubahan jadwal atau kebutuhan teknis.</p>
                </div>
            </section>
        </aside>
    </div>
    @endif
</main>
@endsection
