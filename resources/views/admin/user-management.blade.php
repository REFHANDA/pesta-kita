@extends('layouts.admin')

@section('title', 'Pengguna · Admin Pesta Kita')

@section('content')
<main class="content" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ route('admin.beranda') }}">Admin</a> / Pengguna</div><h1>Manajemen pengguna</h1><p class="sub">Admin hanya dapat melihat akun dan mengubah status aktif/nonaktif. Peran tidak dapat diubah di sini.</p></div></div>
    <section class="card">
        <div class="card-b">
            <form class="form-grid" method="GET" action="{{ route('admin.users') }}">
                <div class="field"><label for="q">Nama atau email</label><input class="input" id="q" name="q" value="{{ $filters['q'] ?? '' }}"></div>
                <div class="field"><label for="role">Peran</label><select class="input" id="role" name="role"><option value="">Semua</option>@foreach(['admin', 'klien', 'vendor', 'venue'] as $role)<option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucfirst($role) }}</option>@endforeach</select></div>
                <div class="field"><label for="status">Status</label><select class="input" id="status" name="status"><option value="">Semua</option>@foreach(['aktif', 'nonaktif'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                <div class="field"><label>&nbsp;</label><button class="btn" type="submit">Filter</button></div>
            </form>
        </div>
        <div class="table-wrap">
            <table class="t-wide">
                <thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Peran</th><th scope="col">Status</th><th scope="col">Terdaftar</th><th scope="col">Detail</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ ucfirst($user->role->value) }}</td><td>{{ ucfirst($user->status->value) }}</td><td>{{ $user->created_at->translatedFormat('d M Y') }}</td><td><a class="btn btn-o btn-sm" href="{{ route('admin.users.detail', $user) }}">Lihat</a></td></tr>
                    @empty
                        <tr><td colspan="6">Tidak ada pengguna yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-b">{{ $users->links() }}</div>
    </section>
</main>
@endsection
