@extends('layouts.vendor')

@section('title', 'Rundown Acara #'.$event->id.' · Panel Vendor Pesta Kita')

@section('content')
@php
    $canWrite = $canWrite ?? false;
    $lockedMessage = $lockedMessage ?? null;
    $linkableOrderItems = $linkableOrderItems ?? collect();
@endphp
<main id="konten" class="content" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/vendor') }}">Beranda</a> / <a href="{{ route('vendor.rundown') }}">Rundown</a> / Acara #{{ $event->id }}</div>
            <h1>Rundown Acara #{{ $event->id }}</h1>
            <p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') ?? 'Tanggal belum diisi' }} · {{ $event->guest_count ?? '—' }} tamu</p>
        </div>
        <a class="btn btn-o" href="{{ route('vendor.rundown') }}">Kembali</a>
    </div>
    @if(session('success'))<div class="alert" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert warn" role="alert">{{ session('error') }}</div>@endif

    <section class="card">
        <div class="card-h"><div><h2>Susunan acara</h2><p class="meta">Anda dapat menambah item dan hanya mengubah item yang Anda buat sendiri.</p></div></div>
        <div class="card-b">
            @if($rundownItems->isEmpty())
            <p class="hint" style="margin:0">Belum ada item rundown untuk acara ini.</p>
            @else
            <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar rundown acara">
                <table class="t-wide">
                    <caption class="sr-only">Tabel: Rundown Acara #{{ $event->id }}</caption>
                    <thead><tr><th scope="col">Jam</th><th scope="col">Kegiatan</th><th scope="col">Keterangan</th><th scope="col">Sumber</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                    @foreach($rundownItems as $rundownItem)
                    @php($isOwn = (int) $rundownItem->created_by === (int) auth()->id())
                    <tr>
                        <td><b>{{ $rundownItem->start_time }}</b>{{ $rundownItem->end_time ? '–'.$rundownItem->end_time : '' }}</td>
                        <td>{{ $rundownItem->activity }}</td>
                        <td>{{ $rundownItem->description ?: '—' }}</td>
                        <td>@if($isOwn)<span class="badge b-info">Dibuat vendor</span>@else<span class="badge b-mute">Klien</span>@endif</td>
                        <td>
                            @if($canWrite && $isOwn)
                            <details>
                                <summary class="btn btn-o btn-sm">Ubah</summary>
                                <form method="POST" action="{{ route('vendor.rundown.update', [$event, $rundownItem]) }}" class="mt-8" style="min-width:240px">
                                    @csrf
                                    @method('PUT')
                                    <div class="field"><label for="v-start-{{ $rundownItem->id }}">Jam mulai</label><input class="input" id="v-start-{{ $rundownItem->id }}" type="time" name="start_time" value="{{ $rundownItem->start_time }}" required></div>
                                    <div class="field"><label for="v-end-{{ $rundownItem->id }}">Jam selesai</label><input class="input" id="v-end-{{ $rundownItem->id }}" type="time" name="end_time" value="{{ $rundownItem->end_time }}"></div>
                                    <div class="field"><label for="v-activity-{{ $rundownItem->id }}">Kegiatan</label><input class="input" id="v-activity-{{ $rundownItem->id }}" name="activity" value="{{ $rundownItem->activity }}" maxlength="150" required></div>
                                    <div class="field"><label for="v-description-{{ $rundownItem->id }}">Keterangan</label><textarea class="input" id="v-description-{{ $rundownItem->id }}" name="description" rows="2" maxlength="1000">{{ $rundownItem->description }}</textarea></div>
                                    <div class="field"><label for="v-order-{{ $rundownItem->id }}">Pesanan terkait</label>
                                        <select class="input" id="v-order-{{ $rundownItem->id }}" name="order_item_id" required>
                                            @foreach($linkableOrderItems as $orderOption)
                                            <option value="{{ $orderOption->id }}" @selected((int) $rundownItem->order_item_id === (int) $orderOption->id)>{{ $orderOption->subject?->name ?? 'Pesanan #'.$orderOption->id }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-sm mt-8">Simpan</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('vendor.rundown.destroy', [$event, $rundownItem]) }}" onsubmit="return confirm('Hapus item rundown ini?');" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-o btn-sm" style="color:#ef4444;border-color:#fca5a5;">Hapus</button>
                            </form>
                            @else
                            <span class="hint">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($lockedMessage)
            <p class="hint mt-12">{{ $lockedMessage }}</p>
            @elseif($canWrite)
                @if($linkableOrderItems->isEmpty())
                <p class="hint mt-12">Belum ada pesanan Anda yang terkonfirmasi pada acara ini, sehingga item rundown belum dapat ditambahkan.</p>
                @else
                <form method="POST" action="{{ route('vendor.rundown.store', $event) }}" class="mt-16">
                    @csrf
                    <div class="form-grid">
                        <div class="field"><label for="v-new-start">Jam mulai <span style="color:#ef4444">*</span></label><input class="input" id="v-new-start" type="time" name="start_time" value="{{ old('start_time') }}" required>@error('start_time')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="v-new-end">Jam selesai</label><input class="input" id="v-new-end" type="time" name="end_time" value="{{ old('end_time') }}">@error('end_time')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                        <div class="field full"><label for="v-new-activity">Kegiatan <span style="color:#ef4444">*</span></label><input class="input" id="v-new-activity" name="activity" value="{{ old('activity') }}" maxlength="150" required>@error('activity')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                        <div class="field full"><label for="v-new-description">Keterangan</label><textarea class="input" id="v-new-description" name="description" rows="2" maxlength="1000">{{ old('description') }}</textarea>@error('description')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                        <div class="field full"><label for="v-new-order">Pesanan terkait <span style="color:#ef4444">*</span></label>
                            <select class="input" id="v-new-order" name="order_item_id" required>
                                @foreach($linkableOrderItems as $orderOption)
                                <option value="{{ $orderOption->id }}" @selected((int) old('order_item_id') === (int) $orderOption->id)>{{ $orderOption->subject?->name ?? 'Pesanan #'.$orderOption->id }}</option>
                                @endforeach
                            </select>
                            @error('order_item_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm mt-12">Tambah item rundown</button>
                </form>
                @endif
            @else
            <p class="hint mt-12">Rundown hanya dapat diubah saat acara terkonfirmasi.</p>
            @endif
        </div>
    </section>
</main>
@endsection
