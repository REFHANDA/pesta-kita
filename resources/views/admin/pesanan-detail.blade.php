@extends('layouts.admin')

@section('title', 'Detail pesanan · Admin Pesta Kita')

@section('content')
<main class="content page-refined" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / <a href="{{ route('admin.pesanan') }}">Review pesanan</a> / Detail</div>
            <h1>Detail pesanan</h1>
        </div>
        <a class="btn btn-o" href="{{ route('admin.pesanan') }}">Kembali</a>
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

    @php
        $orderItem = $orderItem ?? null;
        $event = $event ?? ($orderItem?->event ?? null);
        $badgeClass = match($orderItem?->status?->value) {
            'menunggu_review' => 'badge b-warn',
            'disetujui', 'terkonfirmasi', 'selesai' => 'badge b-ok',
            'ditolak', 'dibatalkan' => 'badge b-bad',
            'dialihkan' => 'badge b-info',
            default => 'badge',
        };
        $subjectName = $orderItem?->subject?->name ?? ($orderItem ? 'Item #'.$orderItem->id : '-');
        $providerName = $orderItem?->provider?->business_name ?? $orderItem?->provider?->name ?? 'Penyedia';
    @endphp

    @if(!$orderItem)
        <div class="card card-b">
            <p>Pilih pesanan dari <a href="{{ route('admin.pesanan') }}">daftar review pesanan</a> untuk melihat detail.</p>
        </div>
    @else
    <div class="detail-grid">
        <div class="detail-stack">
            <section class="card detail-card">
                <div class="card-h">
                    <div class="row">
                        <div>
                            <h2>{{ $subjectName }} @if($orderItem->quantity > 1) ×{{ $orderItem->quantity }} @endif</h2>
                            <p class="meta">Penyedia: {{ $providerName }} · Pesanan #{{ $orderItem->id }}</p>
                        </div>
                        <span class="{{ $badgeClass }}">{{ str_replace('_', ' ', ucfirst($orderItem->status->value)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="detail-kv">
                        <div class="kv"><span class="k">Nama acara</span><span class="v">{{ $event->name }}</span></div>
                        <div class="kv"><span class="k">Tanggal penggunaan</span><span class="v">{{ $orderItem->usage_date ? $orderItem->usage_date->format('d F Y') : ($event->event_date?->format('d F Y') ?? '-') }}</span></div>
                        <div class="kv"><span class="k">Klien</span><span class="v">{{ $event->client?->name ?? 'Klien' }}</span></div>
                        <div class="kv"><span class="k">Jumlah tamu</span><span class="v">{{ $event->guest_count }} orang</span></div>
                        <div class="kv"><span class="k">Tema acara</span><span class="v">{{ $event->theme ?? '-' }}</span></div>
                        <div class="kv"><span class="k">Batas review SLA</span><span class="v">{{ $orderItem->review_due_at ? $orderItem->review_due_at->format('d M Y H:i').' WIB' : '-' }}</span></div>
                    </div>
                </div>
            </section>

            <section class="card detail-card">
                <div class="card-h">
                    <h2>Item dalam acara yang sama</h2>
                    <p class="meta">Seluruh pesanan yang diajukan dalam acara ini.</p>
                </div>
                <div aria-label="Item pesanan" class="table-wrap" role="region" tabindex="0">
                    <table class="detail-table t-wide">
                        <caption class="sr-only">Tabel: Item pesanan</caption>
                        <thead>
                            <tr>
                                <th scope="col">Item &amp; penyedia</th>
                                <th scope="col" class="num">Jumlah</th>
                                <th class="num" scope="col">Harga satuan</th>
                                <th class="num" scope="col">Total baris</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($event->orderItems as $item)
                                @php
                                    $itemSubj = $item->subject?->name ?? 'Item #'.$item->id;
                                    $itemProv = $item->provider?->business_name ?? $item->provider?->name ?? '-';
                                    $bCls = match($item->status->value) {
                                        'menunggu_review' => 'badge b-warn',
                                        'disetujui', 'terkonfirmasi', 'selesai' => 'badge b-ok',
                                        'ditolak', 'dibatalkan' => 'badge b-bad',
                                        'dialihkan' => 'badge b-info',
                                        default => 'badge',
                                    };
                                    $unitPrice = $item->provider_price ?? $item->catalog_price_snapshot ?? 0;
                                @endphp
                                <tr @if($item->id === $orderItem->id) style="background: rgba(var(--primary-rgb, 100, 100, 255), 0.05);" @endif>
                                    <td>
                                        <a href="{{ route('admin.pesanan.show', $item) }}" class="fw-7 no-ul">{{ $itemSubj }}</a>
                                        <span class="sub-t">{{ $itemProv }}</span>
                                    </td>
                                    <td class="num">{{ $item->quantity }}</td>
                                    <td class="num">Rp {{ number_format($unitPrice, 0, ',', '.') }}</td>
                                    <td class="num">Rp {{ number_format($item->lineTotal(), 0, ',', '.') }}</td>
                                    <td><span class="{{ $bCls }}">{{ str_replace('_', ' ', ucfirst($item->status->value)) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if($orderItem->objections->isNotEmpty())
                <section class="card detail-card">
                    <div class="card-h">
                        <h2>Keberatan penyedia</h2>
                        <p class="meta">Catatan keberatan dari penyedia jasa atas pesanan ini.</p>
                    </div>
                    <div class="card-body">
                        @foreach($orderItem->objections as $objection)
                            <div class="p-16 mb-16 border rounded" style="background: #fafafa;">
                                <div class="row mb-8">
                                    <div>
                                        <b>Kategori: {{ ucfirst(str_replace('_', ' ', $objection->category?->value ?? 'lainnya')) }}</b>
                                        @if($objection->is_emergency)
                                            <span class="badge b-bad ml-8">Keberatan Darurat</span>
                                        @endif
                                    </div>
                                    <span class="badge {{ $objection->status->value === 'diterima' ? 'b-ok' : ($objection->status->value === 'ditolak' ? 'b-bad' : 'b-warn') }}">
                                        {{ ucfirst($objection->status->value) }}
                                    </span>
                                </div>
                                <p class="mb-8"><strong>Alasan:</strong> {{ $objection->reason }}</p>
                                @if($objection->evidence)
                                    <p class="mb-8"><a href="{{ route('media.show', $objection->evidence) }}" target="_blank" class="btn btn-o btn-sm">Lihat bukti pendukung</a></p>
                                @endif
                                @if($objection->decision_reason)
                                    <div class="note-box mt-8">
                                        <b>Keputusan Admin:</b> {{ $objection->decision_reason }}
                                        <small class="meta d-block mt-4">Diputuskan pada {{ $objection->decided_at?->format('d/m/Y H:i') }} WIB</small>
                                    </div>
                                @endif

                                @if(in_array($objection->status->value, ['diajukan', 'ditinjau']))
                                    <form method="POST" action="{{ route('admin.keberatan.decide', $objection) }}" class="mt-16 pt-16 border-top">
                                        @csrf
                                        <div class="field mb-8">
                                            <label for="decision_{{ $objection->id }}">Tindakan keputusan</label>
                                            <select class="input" id="decision_{{ $objection->id }}" name="decision" required>
                                                <option value="terima">Terima keberatan</option>
                                                <option value="tolak">Tolak keberatan</option>
                                            </select>
                                        </div>
                                        <div class="field mb-8">
                                            <label for="action_{{ $objection->id }}">Tindakan pada pesanan (bila keberatan diterima)</label>
                                            <select class="input" id="action_{{ $objection->id }}" name="action_type">
                                                <option value="tolak">Tolak / batalkan pesanan ini</option>
                                                <option value="tetap">Pertahankan pesanan</option>
                                            </select>
                                        </div>
                                        <div class="field mb-8">
                                            <label for="reason_{{ $objection->id }}">Alasan keputusan tertulis (wajib)</label>
                                            <textarea class="input" id="reason_{{ $objection->id }}" name="decision_reason" rows="2" placeholder="Tuliskan pertimbangan keputusan Admin…" required></textarea>
                                        </div>
                                        <button class="btn btn-sm" type="submit">Simpan keputusan keberatan</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($orderItem->replacedBy)
                <section class="card detail-card">
                    <div class="card-h">
                        <h2>Pesanan pengganti</h2>
                    </div>
                    <div class="card-body">
                        <p>Pesanan ini telah dialihkan ke:
                            <a href="{{ route('admin.pesanan.show', $orderItem->replacedBy) }}" class="fw-7">
                                {{ $orderItem->replacedBy->subject?->name ?? 'Pesanan #'.$orderItem->replacedBy->id }}
                            </a>
                        </p>
                    </div>
                </section>
            @endif
        </div>

        <aside class="detail-side">
            <section class="card detail-card">
                <div class="card-h"><h2>Ringkasan biaya pesanan</h2></div>
                <div class="card-body">
                    <div class="detail-list">
                        <div class="dl-row">
                            <span>Harga satuan</span>
                            <strong>Rp {{ number_format($orderItem->provider_price ?? $orderItem->catalog_price_snapshot ?? 0, 0, ',', '.') }}</strong>
                        </div>
                        <div class="dl-row">
                            <span>Jumlah</span>
                            <strong>{{ $orderItem->quantity ?? 1 }} unit</strong>
                        </div>
                        <div class="dl-row">
                            <span>Total harga penyedia</span>
                            <strong>Rp {{ number_format($orderItem->lineTotal(), 0, ',', '.') }}</strong>
                        </div>
                        <div class="dl-row">
                            <span>Komisi platform (10%)</span>
                            <strong>Rp {{ number_format($orderItem->commission, 0, ',', '.') }}</strong>
                        </div>
                        <div class="dl-row">
                            <span>Total untuk klien</span>
                            <strong>Rp {{ number_format($orderItem->lineTotal() + $orderItem->commission, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            @if($activeInvoice)
                <section class="card detail-card">
                    <div class="card-h"><h2>Tagihan acara</h2></div>
                    <div class="card-body">
                        <div class="detail-list">
                            <div class="dl-row"><span>Nomor tagihan</span><strong>{{ $activeInvoice->invoice_number }}</strong></div>
                            <div class="dl-row"><span>Versi</span><strong>{{ $activeInvoice->version }}</strong></div>
                            <div class="dl-row"><span>Total transfer</span><strong>Rp {{ number_format($activeInvoice->transfer_total, 0, ',', '.') }}</strong></div>
                            <div class="dl-row"><span>Status</span><span class="badge {{ $activeInvoice->status->value === 'lunas' ? 'b-ok' : 'b-warn' }}">{{ ucfirst($activeInvoice->status->value) }}</span></div>
                        </div>

                        @if($activeInvoice->status->value === 'belum_dibayar')
                            <form method="POST" action="{{ route('admin.tagihan.reissue', $activeInvoice) }}" class="mt-16" onsubmit="return confirm('Buat ulang tagihan? Versi tagihan lama akan dibatalkan.');">
                                @csrf
                                <button class="btn btn-o w-full" type="submit">Buat ulang tagihan (versi baru)</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif

            <section class="card detail-card">
                <div class="card-h"><h2>Riwayat &amp; jejak audit</h2></div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($auditLogs as $log)
                            <div class="timeline-item">
                                <b>{{ str_replace('_', ' ', ucfirst($log->action)) }}</b>
                                <p class="m-0 text-sm">{{ $log->reason }}</p>
                                <small>{{ $log->actor?->name ?? 'Sistem' }} · {{ $log->created_at->format('d M Y · H:i') }}</small>
                            </div>
                        @empty
                            <p class="meta">Belum ada jejak audit untuk pesanan ini.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </aside>
    </div>

    @if($orderItem->status === \App\Enums\OrderItemStatus::MenungguReview)
        <section class="card detail-card mt-16">
            <div class="card-h">
                <h2>Tindakan Admin</h2>
                <p class="meta">Tentukan keputusan untuk pesanan yang menunggu review.</p>
            </div>
            <div class="card-body">
                <div class="grid two gap-16">
                    <div>
                        <h3>Setujui pesanan</h3>
                        <p class="meta mb-8">Harga penyedia dikunci dan masa keberatan 24 jam penyedia akan dimulai.</p>
                        <form method="POST" action="{{ route('admin.pesanan.approve', $orderItem) }}">
                            @csrf
                            <div class="field mb-8">
                                <label for="admin_note">Catatan keputusan (opsional)</label>
                                <textarea class="input" id="admin_note" name="admin_note" rows="2" placeholder="Catatan persetujuan…"></textarea>
                            </div>
                            <button class="btn" type="submit">Setujui pesanan</button>
                        </form>
                    </div>

                    <div>
                        <h3>Tolak pesanan</h3>
                        <p class="meta mb-8">Slot tanggal/stok dilepas dan klien diberi tahu untuk memilih alternatif.</p>
                        <form method="POST" action="{{ route('admin.pesanan.reject', $orderItem) }}">
                            @csrf
                            <div class="field mb-8">
                                <label for="rejection_reason">Alasan penolakan (wajib)</label>
                                <textarea class="input" id="rejection_reason" name="rejection_reason" rows="2" placeholder="Tuliskan alasan penolakan…" required></textarea>
                            </div>
                            <button class="btn btn-danger" type="submit">Tolak pesanan</button>
                        </form>
                    </div>
                </div>

                <div class="mt-24 pt-16 border-top">
                    <h3>Alihkan ke penyedia lain</h3>
                    <p class="meta mb-8">Pesanan ini akan ditandai dialihkan, dan pesanan baru akan dibuat untuk penyedia pengganti.</p>
                    <form method="POST" action="{{ route('admin.pesanan.reassign', $orderItem) }}">
                        @csrf
                        <div class="grid two gap-16 mb-8">
                            <div class="field">
                                <label for="replacement_subject_type">Tipe penyedia pengganti</label>
                                <select class="input" id="replacement_subject_type" name="replacement_subject_type" required>
                                    <option value="catalog_item" @selected($orderItem->subject_type === 'catalog_item')>Layanan / Peralatan Vendor</option>
                                    <option value="venue_room" @selected($orderItem->subject_type === 'venue_room')>Ruangan Venue</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="replacement_subject_id">Pilih item pengganti</label>
                                <select class="input" id="replacement_subject_id" name="replacement_subject_id" required>
                                    <optgroup label="Layanan / Peralatan Vendor">
                                        @foreach($replacementCatalogItems as $rItem)
                                            <option value="{{ $rItem->id }}">{{ $rItem->name }} (Rp {{ number_format($rItem->catalog_price, 0, ',', '.') }})</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Ruangan Venue">
                                        @foreach($replacementVenueRooms as $rRoom)
                                            <option value="{{ $rRoom->id }}">{{ $rRoom->name }} - {{ $rRoom->venue?->name }} (Rp {{ number_format($rRoom->venue?->rental_price ?? 0, 0, ',', '.') }})</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="field mb-8">
                            <label for="reassign_reason">Alasan pengalihan (wajib)</label>
                            <textarea class="input" id="reassign_reason" name="reason" rows="2" placeholder="Tuliskan alasan pengalihan…" required></textarea>
                        </div>
                        <button class="btn btn-o" type="submit">Alihkan pesanan</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
    @endif
</main>
@endsection
