@php
$rupiah = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
@endphp
<div class="grid stats two">
    <div class="card stat"><div><div class="lbl">Total belum cair</div><div class="val">{{ $rupiah($totalUnpaid) }}</div></div></div>
    <div class="card stat"><div><div class="lbl">Total sudah cair</div><div class="val">{{ $rupiah($totalPaid) }}</div></div></div>
</div>
<section class="card">
    @if($disbursements->isEmpty())
    <div class="card-b"><p class="hint" style="margin:0">Belum ada data.</p></div>
    @else
    <div class="table-wrap" role="region" tabindex="0" aria-label="Daftar pencairan">
        <table class="t-wide">
            <caption class="sr-only">Tabel: Pencairan Anda</caption>
            <thead><tr><th scope="col">Acara</th><th scope="col">Layanan</th><th scope="col">Jenis</th><th scope="col" class="num">Jumlah</th><th scope="col">Status</th><th scope="col">Tanggal cair</th><th scope="col" class="col-aksi">Bukti</th></tr></thead>
            <tbody>
            @foreach($disbursements as $disbursement)
            <tr>
                <td>Acara #{{ $disbursement->orderItem?->event_id }}</td>
                <td>{{ $disbursement->orderItem?->subject?->name ?? '-' }}</td>
                <td>{{ $disbursement->type === \App\Enums\DisbursementType::Kompensasi ? 'Kompensasi pembatalan' : 'Hak pesanan' }}</td>
                <td class="num"><b>{{ $rupiah($disbursement->amount) }}</b></td>
                <td>@if($disbursement->status === \App\Enums\DisbursementStatus::Cair)<span class="badge b-ok">Cair</span>@else<span class="badge b-warn">Belum cair</span>@endif</td>
                <td>{{ $disbursement->paid_at?->translatedFormat('d M Y') ?? '-' }}</td>
                <td class="col-aksi">@if($disbursement->status === \App\Enums\DisbursementStatus::Cair && $disbursement->proof)<a class="btn btn-o btn-sm" href="{{ route('media.show', $disbursement->proof) }}" download>Unduh bukti</a>@else - @endif</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($disbursements->hasPages())<div style="padding:16px;">{{ $disbursements->links() }}</div>@endif
    @endif
</section>
