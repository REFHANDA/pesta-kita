@extends('layouts.'.$role)

@section('title', 'Notifikasi · Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="{{ url('/'.$role) }}">Beranda</a> / Notifikasi</div>
            <h1>Notifikasi</h1>
            <p class="sub">Pemberitahuan aktivitas akun dan transaksi Anda.</p>
        </div>
        <form method="POST" action="{{ route($role.'.notifikasi.read-all') }}">
            @csrf
            <button class="btn btn-o" type="submit">Tandai semua dibaca</button>
        </form>
    </div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    <section class="card">
        <div class="card-h"><h2>Daftar notifikasi</h2><span class="chip">{{ $unreadCount }} belum dibaca</span></div>
        @forelse ($notifications as $notification)
            <article class="notif {{ $notification->read_at === null ? 'new' : '' }}">
                <b>{{ $notification->data['title'] ?? 'Pembaruan' }}</b>
                <span>{{ $notification->data['message'] ?? '' }}</span>
                <small>{{ $notification->created_at->translatedFormat('d M Y H:i') }}</small>
                <div class="actions mt-8">
                    @if (str_starts_with($notification->data['url'] ?? '', '/'))
                        <a class="btn btn-o btn-sm" href="{{ $notification->data['url'] }}">Lihat terkait</a>
                    @endif
                    @if ($notification->read_at === null)
                        <form method="POST" action="{{ route($role.'.notifikasi.read', $notification->id) }}">
                            @csrf
                            <button class="btn btn-o btn-sm" type="submit">Tandai dibaca</button>
                        </form>
                    @else
                        <span class="badge b-mute">Dibaca</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="card-b"><p class="hint">Belum ada notifikasi.</p></div>
        @endforelse
        <div class="card-b">{{ $notifications->links() }}</div>
    </section>
</main>
@endsection
