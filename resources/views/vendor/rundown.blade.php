@extends('layouts.vendor')

@section('title', 'Rundown · '.$event->name.' · Panel Vendor Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('vendor.beranda') }}">Beranda</a> / <a href="{{ route('vendor.rundown.index') }}">Rundown acara</a> / {{ $event->name }}</div><h1>{{ $event->name }}</h1><p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') }} · Rundown bersama hanya menampilkan segmen layanan milik Anda.</p></div><a class="btn btn-o" href="{{ route('vendor.rundown.index') }}">Kembali</a></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('event')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    @error('order_item_id')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    @foreach ($orderItems as $orderItem)
        <section class="card mt-16">
            <div class="card-h"><div><h2>{{ $orderItem->subject?->name ?? 'Layanan' }}</h2><p class="meta">Pesanan #{{ $orderItem->id }}</p></div>@if (! $canEditRundown)<span class="badge b-mute">Hanya lihat</span>@endif</div>
            @forelse ($orderItem->rundownItems as $segment)
                <div class="card-b">
                    <h3>{{ $segment->starts_at?->format('H:i') ?? 'Waktu belum ditentukan' }} · {{ $segment->title }}</h3>
                    @if ($segment->duration_minutes)<p class="meta">Durasi {{ $segment->duration_minutes }} menit</p>@endif
                    @if ($segment->notes)<p>{{ $segment->notes }}</p>@endif
                    @if ($canEditRundown)
                        <details class="mt-8">
                            <summary>Ubah segmen</summary>
                            <form class="stack mt-8" method="POST" action="{{ route('vendor.rundown.update', [$event, $segment]) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="order_item_id" value="{{ $orderItem->id }}">
                                <div class="field"><label for="title-{{ $segment->id }}">Nama segmen</label><input class="input" id="title-{{ $segment->id }}" name="title" value="{{ old('title', $segment->title) }}" maxlength="255" required></div>
                                <div class="field"><label for="starts-{{ $segment->id }}">Waktu mulai</label><input class="input" id="starts-{{ $segment->id }}" name="starts_at" type="time" value="{{ old('starts_at', $segment->starts_at?->format('H:i')) }}"></div>
                                <div class="field"><label for="duration-{{ $segment->id }}">Durasi (menit)</label><input class="input" id="duration-{{ $segment->id }}" name="duration_minutes" type="number" min="5" max="720" value="{{ old('duration_minutes', $segment->duration_minutes) }}"></div>
                                <div class="field"><label for="notes-{{ $segment->id }}">Catatan</label><textarea class="input" id="notes-{{ $segment->id }}" name="notes" rows="3" maxlength="2000">{{ old('notes', $segment->notes) }}</textarea></div>
                                <button class="btn" type="submit">Simpan perubahan</button>
                            </form>
                            <form class="mt-8" method="POST" action="{{ route('vendor.rundown.destroy', [$event, $segment]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-o" type="submit">Hapus segmen</button>
                            </form>
                        </details>
                    @endif
                </div>
            @empty
                <p class="hint" style="padding:16px">Anda belum menambahkan segmen untuk layanan ini.</p>
            @endforelse
        </section>
    @endforeach
    @if ($canEditRundown)
        <section class="card mt-16">
            <div class="card-h"><h2>Tambah segmen layanan</h2></div>
            <div class="card-b">
                <form class="stack" method="POST" action="{{ route('vendor.rundown.store', $event) }}">
                    @csrf
                    <div class="field"><label for="order_item_id">Pesanan layanan</label><select class="input" id="order_item_id" name="order_item_id" required><option value="">Pilih pesanan</option>@foreach ($orderItems as $orderItem)<option value="{{ $orderItem->id }}" @selected(old('order_item_id') == $orderItem->id)>{{ $orderItem->subject?->name ?? 'Layanan' }} · #{{ $orderItem->id }}</option>@endforeach</select></div>
                    <div class="field"><label for="title">Nama segmen</label><input class="input" id="title" name="title" value="{{ old('title') }}" maxlength="255" required>@error('title')<p class="hint">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="starts_at">Waktu mulai</label><input class="input" id="starts_at" name="starts_at" type="time" value="{{ old('starts_at') }}">@error('starts_at')<p class="hint">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="duration_minutes">Durasi (menit)</label><input class="input" id="duration_minutes" name="duration_minutes" type="number" min="5" max="720" value="{{ old('duration_minutes') }}">@error('duration_minutes')<p class="hint">{{ $message }}</p>@enderror</div>
                    <div class="field"><label for="notes">Catatan</label><textarea class="input" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>@error('notes')<p class="hint">{{ $message }}</p>@enderror</div>
                    <button class="btn" type="submit">Tambah segmen</button>
                </form>
            </div>
        </section>
    @else
        <div class="alert mt-16" role="note">Rundown dikunci setelah acara selesai.</div>
    @endif
</main>
@endsection
