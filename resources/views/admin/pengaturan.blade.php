@extends('layouts.admin')

@section('title', 'Pengaturan sistem · Admin Pesta Kita')

@php
$settings = $settings ?? \App\Http\Controllers\Admin\SettingController::DEFAULTS;
$tiers = old('refund_tiers', $settings['refund_tiers']);
$tiers = array_values(array_merge($tiers, [['min_days' => '', 'percent' => '']]));
$budget = old('budget_allocation', $settings['budget_allocation']);
$budgetLabels = ['venue' => 'Venue', 'catering' => 'Catering', 'dekorasi' => 'Dekorasi', 'hiburan' => 'Hiburan', 'peralatan' => 'Peralatan', 'cadangan' => 'Cadangan'];
$err = fn (string $key) => $errors->has($key) ? 'aria-invalid=true' : '';
@endphp

@section('content')
<main class="content page-refined page-settings" id="konten" tabindex="-1">
    <div class="page-head"><div><div class="crumbs"><a href="{{ url('/admin') }}">Admin</a> / Pengaturan</div><h1>Pengaturan sistem</h1><p class="sub">Parameter berlaku segera setelah disimpan. Setiap perubahan tercatat di log aktivitas.</p></div></div>

    <x-subnav label="Pengaturan" :items="[
        ['label' => 'Parameter', 'href' => route('admin.pengaturan'), 'active' => true],
        ['label' => 'Rekening perusahaan', 'href' => route('admin.rekening.index'), 'active' => false],
    ]" />

    @if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="alert warn" role="alert"><b>Pengaturan belum disimpan.</b><ul class="clean-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" novalidate>
        @csrf
        @method('PUT')
        <div class="grid two settings-grid">
            <section class="card settings-card">
                <div class="settings-card-head"><h2>Parameter operasional</h2></div>
                <div class="stack gap-16">
                    <div class="field"><label for="commission_percent">Komisi platform (%)</label><input class="input" id="commission_percent" name="commission_percent" type="number" min="0" max="50" value="{{ old('commission_percent', $settings['commission_percent']) }}" {{ $err('commission_percent') }}><p class="hint">Perubahan komisi hanya berlaku untuk pesanan yang disetujui setelah disimpan; pesanan lama tetap memakai komisi yang sudah dikunci.</p></div>
                    <div class="field"><label for="payment_deadline_hours">Batas pembayaran (jam)</label><input class="input" id="payment_deadline_hours" name="payment_deadline_hours" type="number" min="1" max="720" value="{{ old('payment_deadline_hours', $settings['payment_deadline_hours']) }}" {{ $err('payment_deadline_hours') }}></div>
                    <div class="field"><label for="objection_window_hours">Masa keberatan penyedia (jam)</label><input class="input" id="objection_window_hours" name="objection_window_hours" type="number" min="1" max="168" value="{{ old('objection_window_hours', $settings['objection_window_hours']) }}" {{ $err('objection_window_hours') }}></div>
                    <div class="field"><label for="admin_review_sla_hours">SLA review Admin (jam)</label><input class="input" id="admin_review_sla_hours" name="admin_review_sla_hours" type="number" min="1" max="168" value="{{ old('admin_review_sla_hours', $settings['admin_review_sla_hours']) }}" {{ $err('admin_review_sla_hours') }}></div>
                    <label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="unique_code_enabled" value="1" @checked(old('unique_code_enabled', $settings['unique_code_enabled']))> Aktifkan kode unik 3 digit pada tagihan</label>
                </div>
            </section>

            <section class="card settings-card">
                <div class="settings-card-head"><h2>Kontak bantuan Klien</h2></div>
                <div class="field"><label for="admin_whatsapp">Nomor WhatsApp Admin</label><input class="input" id="admin_whatsapp" name="admin_whatsapp" type="tel" inputmode="tel" placeholder="08xx atau +62xx" value="{{ old('admin_whatsapp', $settings['admin_whatsapp']) }}" {{ $err('admin_whatsapp') }}><p class="hint">Dipakai tombol “Tanya Admin” di portal Klien. Disimpan dalam format 62xxxxxxxxxx. Kosongkan untuk menyembunyikan tombol.</p></div>
            </section>

            <section class="card settings-card">
                <div class="settings-card-head"><h2>Tier refund</h2></div>
                <p class="meta">Urutkan dari sisa hari terbesar. Baris terakhir wajib 0 hari. Persentase tidak boleh naik saat sisa hari berkurang. Kosongkan kedua kolom untuk menghapus baris.</p>
                <div class="table-wrap" role="region" tabindex="0" aria-label="Tier refund">
                    <table><caption class="sr-only">Tabel: Tier refund</caption>
                        <thead><tr><th scope="col">Minimal sisa hari ke acara</th><th scope="col">Refund (%)</th></tr></thead>
                        <tbody>
                        @foreach($tiers as $i => $tier)
                            <tr>
                                <td><label class="sr-only" for="tier-days-{{ $i }}">Minimal sisa hari baris {{ $i + 1 }}</label><input class="input" id="tier-days-{{ $i }}" name="refund_tiers[{{ $i }}][min_days]" type="number" min="0" value="{{ $tier['min_days'] }}"></td>
                                <td><label class="sr-only" for="tier-pct-{{ $i }}">Refund baris {{ $i + 1 }}</label><input class="input" id="tier-pct-{{ $i }}" name="refund_tiers[{{ $i }}][percent]" type="number" min="0" max="100" value="{{ $tier['percent'] }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="hint">Baris kosong terakhir dapat diisi untuk menambah tier.</p>
            </section>

            <section class="card settings-card">
                <div class="settings-card-head"><h2>Alokasi budget rekomendasi</h2></div>
                <p class="meta">Total keenam kategori wajib tepat 100%.</p>
                <div class="grid two" style="gap:12px">
                    @foreach($budgetLabels as $key => $label)
                        <div class="field"><label for="budget-{{ $key }}">{{ $label }} (%)</label><input class="input" id="budget-{{ $key }}" name="budget_allocation[{{ $key }}]" type="number" min="0" max="100" value="{{ $budget[$key] ?? 0 }}" {{ $err('budget_allocation') }}></div>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="form-actions mt-16"><button class="btn" type="submit">Simpan pengaturan</button></div>
    </form>
</main>
@endsection
