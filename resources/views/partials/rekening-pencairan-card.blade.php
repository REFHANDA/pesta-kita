{{--
    Tahap G1: kartu "Rekening pencairan" pada profil Vendor dan Venue.
    Form terpisah dari "Data usaha dan kontak" (POST /profil) karena nomor
    rekening harus diverifikasi Admin. Pemilik melihat nomor penuh di sini
    satu-satunya (aturan penyamaran A7).
--}}
@php
    $portal ??= auth()->user()->role->value;
    $banks = ['BCA', 'BRI', 'BNI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Permata', 'Danamon', 'BTN'];
    $verifiedAccount = $verifiedAccount ?? null;
    $pendingAccount = $pendingAccount ?? null;
    $rejectedAccount = $rejectedAccount ?? null;
    [$badgeClass, $badgeLabel] = match (true) {
        $pendingAccount !== null => ['b-warn', 'Menunggu verifikasi'],
        $verifiedAccount !== null => ['b-ok', 'Terverifikasi'],
        $rejectedAccount !== null => ['b-bad', 'Ditolak'],
        default => ['b-mute', 'Belum diisi'],
    };
@endphp
<section class="card mt-20" aria-labelledby="rekening-pencairan-title">
    <div class="card-h">
        <h2 id="rekening-pencairan-title">Rekening pencairan</h2>
        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
    </div>
    <div class="card-b">
        <p class="hint">Dana hak Anda dicairkan Admin ke rekening terverifikasi. Atas nama sebaiknya sesuai nama usaha atau penanggung jawab. Perubahan rekening perlu diverifikasi ulang.</p>

        @if($verifiedAccount)
        <div class="detail-list mt-12">
            <div class="dl-row"><span>Rekening aktif</span><strong>{{ $verifiedAccount->bank_name }} · {{ $verifiedAccount->account_number }}</strong></div>
            <div class="dl-row"><span>Atas nama</span><strong>{{ $verifiedAccount->account_name }}</strong></div>
        </div>
        @endif

        @if($pendingAccount)
        <div class="detail-list mt-12">
            <div class="dl-row"><span>{{ $verifiedAccount ? 'Perubahan menunggu verifikasi' : 'Pengajuan menunggu verifikasi' }}</span><strong>{{ $pendingAccount->bank_name }} · {{ $pendingAccount->account_number }}</strong></div>
            <div class="dl-row"><span>Atas nama</span><strong>{{ $pendingAccount->account_name }}</strong></div>
        </div>
        @endif

        @if($rejectedAccount && ! $pendingAccount)
        <div class="alert warn mt-12" role="alert">
            <span class="ic i-info" aria-hidden="true"></span>
            <span>Pengajuan rekening terakhir ditolak{{ $rejectedAccount->rejection_reason ? ': '.$rejectedAccount->rejection_reason : '.' }} Silakan ajukan rekening yang benar.</span>
        </div>
        @endif

        <form class="mt-16" method="POST" action="{{ route($portal.'.rekening.simpan') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="rekening-bank">Bank</label>
                    <input class="input" id="rekening-bank" name="bank_name" type="text" list="rekening-bank-list" autocomplete="off" value="{{ old('bank_name', $pendingAccount?->bank_name ?? $verifiedAccount?->bank_name) }}" @error('bank_name') aria-invalid="true" @enderror>
                    <datalist id="rekening-bank-list">
                        @foreach($banks as $bank)<option value="{{ $bank }}"></option>@endforeach
                    </datalist>
                    <p class="hint">Pilih bank umum atau ketik nama bank lain.</p>
                    @error('bank_name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="rekening-nomor">Nomor rekening</label>
                    <input class="input" id="rekening-nomor" name="account_number" type="text" inputmode="numeric" autocomplete="off" value="{{ old('account_number', $pendingAccount?->account_number ?? $verifiedAccount?->account_number) }}" @error('account_number') aria-invalid="true" @enderror>
                    <p class="hint">Hanya digit, 6–20 digit. Spasi dan tanda hubung diabaikan.</p>
                    @error('account_number')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="field full">
                    <label for="rekening-nama">Atas nama</label>
                    <input class="input" id="rekening-nama" name="account_name" type="text" autocomplete="off" value="{{ old('account_name', $pendingAccount?->account_name ?? $verifiedAccount?->account_name) }}" @error('account_name') aria-invalid="true" @enderror>
                    @error('account_name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-actions">
                <button class="btn" type="submit">{{ $pendingAccount ? 'Perbarui pengajuan' : 'Ajukan verifikasi rekening' }}</button>
            </div>
        </form>
    </div>
</section>
