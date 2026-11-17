@extends('layouts.admin')

@section('title', 'Detail pengguna · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.users') }}">Pengguna</a> / Detail</div><h1>{{ $managedUser->name }}</h1><p class="sub">Detail akun dan status akses.</p></div></div>
    @if (session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @error('status')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
    <section class="card">
        <div class="card-b">
            <dl class="dl">
                <div><dt>Email</dt><dd>{{ $managedUser->email }}</dd></div>
                <div><dt>Peran</dt><dd>{{ ucfirst($managedUser->role->value) }}</dd></div>
                <div><dt>Status</dt><dd>{{ ucfirst($managedUser->status->value) }}</dd></div>
                <div><dt>Terdaftar</dt><dd>{{ $managedUser->created_at->translatedFormat('d M Y H:i') }}</dd></div>
            </dl>
            @if ($managedUser->id !== auth()->id())
                <form class="actions mt-16" method="POST" action="{{ route('admin.users.status', $managedUser) }}">
                    @csrf
                    <label class="sr-only" for="account-status">Status akun</label>
                    <select class="input" id="account-status" name="status" required>
                        <option value="aktif" @selected($managedUser->status === \App\Enums\UserStatus::Aktif)>Aktif</option>
                        <option value="nonaktif" @selected($managedUser->status === \App\Enums\UserStatus::Nonaktif)>Nonaktif</option>
                    </select>
                    <button class="btn" type="submit">Simpan status</button>
                </form>
            @else
                <p class="hint mt-16">Admin tidak dapat mengubah status akunnya sendiri.</p>
            @endif
        </div>
    </section>
</main>
@endsection
