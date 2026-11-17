@extends('layouts.klien')

@section('title', 'Rundown · '.$event->name.' · Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div><div class="crumbs"><a href="{{ route('klien.beranda') }}">Beranda</a> / <a href="{{ route('klien.acara.detail', $event) }}">{{ $event->name }}</a> / Rundown</div><h1>Rundown</h1><p class="sub">{{ $event->event_date?->translatedFormat('l, d F Y') }} · Dikelola Klien dan Vendor terkait.</p></div>
        <a class="btn btn-o" href="{{ route('klien.acara.detail', $event) }}">Kembali ke acara</a>
    </div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('event')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    @error('order_item_id')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <div class="grid two">
        <section class="card">
            <div class="card-h"><h2>Susunan acara</h2>@if (! $canEditRundown)<span class="badge b-mute">Hanya lihat</span>@endif</div>
            @forelse ($event->rundownItems->sortBy('starts_at') as $segment)
                <article class="card-b">
                    <h3>{{ $segment->starts_at?->format('H:i') ?? 'Waktu belum ditentukan' }} · {{ $segment->title }}</h3>
                    @if ($segment->duration_minutes)<p class="meta">Durasi {{ $segment->duration_minutes }} menit</p>@endif
                    @if ($segment->notes)<p>{{ $segment->notes }}</p>@endif
                    @if ($canEditRundown)
                        <details class="mt-8">
                            <summary>Ubah segmen</summary>
                            <form class="stack mt-8" method="POST" action="{{ route('klien.rundown.update', [$event, $segment]) }}">
                                @csrf
                                @method('PUT')
                                <div class="field"><label for="title-{{ $segment->id }}">Nama segmen</label><input class="input" id="title-{{ $segment->id }}" name="title" value="{{ old('title', $segment->title) }}" maxlength="255" required></div>
                                <div class="field"><label for="starts-{{ $segment->id }}">Waktu mulai</label><input class="input" id="starts-{{ $segment->id }}" name="starts_at" type="time" value="{{ old('starts_at', $segment->starts_at?->format('H:i')) }}"></div>
                                <div class="field"><label for="duration-{{ $segment->id }}">Durasi (menit)</label><input class="input" id="duration-{{ $segment->id }}" name="duration_minutes" type="number" min="5" max="720" value="{{ old('duration_minutes', $segment->duration_minutes) }}"></div>
                                <div class="field"><label for="notes-{{ $segment->id }}">Catatan</label><textarea class="input" id="notes-{{ $segment->id }}" name="notes" rows="3" maxlength="2000">{{ old('notes', $segment->notes) }}</textarea></div>
                                <button class="btn" type="submit">Simpan perubahan</button>
                            </form>
                            <form class="mt-8" method="POST" action="{{ route('klien.rundown.destroy', [$event, $segment]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-o" type="submit">Hapus segmen</button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <p class="hint" style="padding:16px">Belum ada segmen rundown.</p>
            @endforelse
        </section>
        @if ($canEditRundown)
            <section class="card">
                <div class="card-h"><h2>Tambah segmen</h2></div>
                <div class="card-b">
                    <form class="stack" method="POST" action="{{ route('klien.rundown.store', $event) }}">
                        @csrf
                        <div class="field"><label for="title">Nama segmen</label><input class="input" id="title" name="title" value="{{ old('title') }}" maxlength="255" required>@error('title')<p class="hint">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="starts_at">Waktu mulai</label><input class="input" id="starts_at" name="starts_at" type="time" value="{{ old('starts_at') }}">@error('starts_at')<p class="hint">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="duration_minutes">Durasi (menit)</label><input class="input" id="duration_minutes" name="duration_minutes" type="number" min="5" max="720" value="{{ old('duration_minutes') }}">@error('duration_minutes')<p class="hint">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="notes">Catatan</label><textarea class="input" id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>@error('notes')<p class="hint">{{ $message }}</p>@enderror</div>
                        <button class="btn" type="submit">Tambah ke rundown</button>
                    </form>
                </div>
            </section>
        @else
            <div class="alert" role="note">Rundown dikunci setelah acara selesai.</div>
        @endif
    </div>
</main>
@endsection
