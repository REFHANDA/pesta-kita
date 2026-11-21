@extends('layouts.admin')

@section('title', 'Pengguna · Admin Pesta Kita')

@php
$roles = \App\Enums\Role::cases();
@endphp

@section('content')
<main class="content" id="konten" tabindex="-1">
<div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Penyedia &amp; katalog / Pengguna</div><h1>Pengguna</h1><p class="sub">Daftar akun terdaftar. Akun penyedia dibuat lewat pendaftaran mandiri dan aktif setelah diverifikasi.</p></div></div>
<x-subnav label="Penyedia & katalog" :items="[
    ['label' => 'Verifikasi penyedia', 'href' => route('admin.penyedia.index'), 'active' => request()->routeIs('admin.penyedia.index')],
    ['label' => 'Katalog', 'href' => route('admin.katalog'), 'active' => request()->routeIs('admin.katalog')],
    ['label' => 'Pengajuan harga', 'href' => route('admin.pengajuan-harga'), 'active' => request()->routeIs('admin.pengajuan-harga')],
    ['label' => 'Pengguna', 'href' => route('admin.pengguna'), 'active' => request()->routeIs('admin.pengguna')],
]" />
<section class="card">
    <form class="toolbar" method="GET" action="{{ route('admin.pengguna') }}">
        <label class="sr-only" for="q">Cari nama atau email</label><input class="input min-260" id="q" name="q" value="{{ $search }}" placeholder="Cari nama atau email">
        <label class="sr-only" for="role">Peran</label><select class="input" id="role" name="role"><option value="">Semua peran</option>@foreach($roles as $role)<option value="{{ $role->value }}" @selected($activeRole === $role)>{{ $role->label() }}</option>@endforeach</select>
        <button class="btn btn-o" type="submit">Terapkan</button>
    </form>
    <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pengguna">
        <table class="t-wide"><caption class="sr-only">Tabel: Daftar pengguna</caption>
            <thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">Peran</th><th scope="col">Rekening</th><th scope="col">Terdaftar</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
            @forelse($users as $user)
                @php($profile = $user->vendorProfile ?? $user->venueProfile)
                <tr>
                    <td><b>{{ $user->name }}</b>@if($profile)<span class="sub-t">{{ $profile->business_name }}</span>@endif</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role->label() }}</td>
                    <td>@php($rekeningStatus = $profile ? \App\Models\ProviderBankAccount::currentStatus($profile->bankAccounts) : null)@if($profile)<span class="badge {{ $rekeningStatus?->badgeClass() ?? 'b-mute' }}">{{ $rekeningStatus?->label() ?? 'Belum diisi' }}</span>@else — @endif</td>
                    <td>{{ $user->created_at?->translatedFormat('d M Y') }}</td>
                    <td>@if($profile)<a class="btn btn-o btn-sm" href="{{ route('admin.penyedia.detail', ['type' => $user->vendorProfile ? 'vendor_profile' : 'venue_profile', 'id' => $profile->id]) }}">Detail penyedia</a>@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())<div class="card-b">{{ $users->links() }}</div>@endif
</section>
</main>
@endsection
