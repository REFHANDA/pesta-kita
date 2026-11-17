@extends('layouts.'.$role)

@section('title', 'Checklist acara · Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/'.$role) }}">Beranda</a> / {{ $event->name }} / Checklist</div>
            <h1>Checklist acara</h1>
            <p class="sub">{{ $event->name }} · {{ $event->event_date?->translatedFormat('d M Y') }}</p>
        </div>
    </div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @if ($canManage)
        <section class="card sec-gap">
            <div class="card-h"><h2>Tambahkan tugas</h2></div>
            <div class="card-b">
                <form class="actions" method="POST" action="{{ route('klien.checklist.store', $event) }}">
                    @csrf
                    <label class="sr-only" for="checklist-title">Nama tugas</label>
                    <input class="input" id="checklist-title" name="title" maxlength="255" value="{{ old('title') }}" required>
                    <button class="btn" type="submit">Tambah</button>
                </form>
                @error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </section>
    @endif
    <section class="card">
        <div class="card-h"><h2>Daftar tugas</h2><span class="chip">{{ $event->checklistItems->where('is_completed', true)->count() }} / {{ $event->checklistItems->count() }} selesai</span></div>
        <div class="card-b">
            @forelse ($event->checklistItems as $item)
                <div class="row-link">
                    @if ($canManage)
                        <form class="actions grow" method="POST" action="{{ route('klien.checklist.update', [$event, $item]) }}">
                            @csrf @method('PUT')
                            <input class="input" name="title" value="{{ $item->title }}" maxlength="255" required aria-label="Nama tugas">
                            <input type="hidden" name="is_completed" value="0">
                            <label class="check"><input type="checkbox" name="is_completed" value="1" @checked($item->is_completed)> Selesai</label>
                            <button class="btn btn-o btn-sm" type="submit">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('klien.checklist.destroy', [$event, $item]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-o btn-sm" type="submit">Hapus</button>
                        </form>
                    @else
                        <span class="grow">{{ $item->title }}</span>
                        <span class="badge {{ $item->is_completed ? 'b-ok' : 'b-mute' }}">{{ $item->is_completed ? 'Selesai' : 'Belum selesai' }}</span>
                    @endif
                </div>
            @empty
                <p class="hint">Belum ada item checklist.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
