@extends('layouts.venue')

@section('title', 'Data venue · Panel Pemilik Venue Pesta Kita')

@section('content')
<main id="konten" class="content" tabindex="-1"><div class="page-head"><div><div class="crumbs"><a href="{{ url('/venue') }}">Beranda</a> / Data venue</div><h1>Data venue</h1><p class="sub">Venue mengikuti status verifikasi profil penyedia: setelah profil disetujui Admin, venue tayang di katalog. Admin dapat menonaktifkan venue secara individual. Biaya kebersihan hanya dapat diubah Admin.</p></div><a class="btn" href="{{ route('venue.form.create') }}"><span class="ic i-plus" aria-hidden="true"></span>Tambah venue</a></div>
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
@error('room')<div class="alert warn" role="alert">{{ $message }}</div>@enderror
<section class="card"><div class="table-wrap" role="region" tabindex="0" aria-label="Daftar data venue. Gulir horizontal untuk melihat seluruh kolom"><table class="t-wide"><caption class="sr-only">Tabel: Daftar data venue</caption><thead><tr><th scope="col">Venue</th><th scope="col" class="num">Kapasitas</th><th scope="col" class="num">Ruangan</th><th scope="col" class="num">Harga sewa</th><th scope="col" class="num">Biaya kebersihan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody>
@forelse($venues as $venue)
<tr><td><b>{{ $venue->name }}</b><span class="sub-t">{{ $venue->address }}</span></td><td class="num">{{ $venue->capacity }}</td><td class="num">{{ $venue->rooms_count }}</td><td class="num">Rp {{ number_format($venue->rental_price, 0, ',', '.') }}</td><td class="num">{{ $venue->cleaning_fee !== null ? 'Rp '.number_format($venue->cleaning_fee, 0, ',', '.') : 'Belum ditetapkan' }}</td><td>@if($venue->verification_status === \App\Enums\VerificationStatus::Terverifikasi)<span class="badge b-ok">Tayang</span>@elseif($venue->verification_status === \App\Enums\VerificationStatus::Nonaktif)<span class="badge b-mute">Nonaktif (Admin)</span>@elseif($venue->verification_status === \App\Enums\VerificationStatus::Ditolak)<span class="badge b-bad">Ditolak</span>@else<span class="badge b-warn">Menunggu verifikasi</span>@endif</td><td><div class="actions"><a class="btn btn-o btn-sm" href="{{ route('venue.form.edit', $venue) }}">Ubah</a><form method="POST" action="{{ route('venue.data.destroy', $venue) }}" onsubmit="return confirm('Hapus venue ini? Venue yang sudah punya pesanan tetap tersimpan sebagai riwayat.')">@csrf @method('DELETE')<button class="btn btn-o btn-sm" type="submit">Hapus</button></form></div></td></tr>
@empty
<tr><td colspan="7">Belum ada venue. Tambahkan venue pertama Anda; venue tayang setelah profil penyedia terverifikasi.</td></tr>
@endforelse
</tbody></table></div><p class="hint mt-12">Menampilkan {{ $venues->count() }} venue. Penonaktifan individual venue dilakukan oleh Admin melalui panel Admin.</p></section></main>
@endsection