@props(['context' => null])
@php
    $adminWhatsapp = (string) \App\Models\Setting::get('admin_whatsapp', '');
    $clientName = auth()->user()?->name ?? 'Klien';
    $waMessage = trim(implode(' ', array_filter([
        "Halo Admin Pesta Kita, saya {$clientName}. Saya ingin bertanya.",
        $context ?: null,
        'Keputusan resmi (pesanan, pembayaran, pembatalan) hanya melalui aplikasi.',
    ])));
@endphp
@if($adminWhatsapp !== '')
<div class="wa-help" data-whatsapp-help>
    <a class="wa-help-btn"
       href="https://wa.me/{{ $adminWhatsapp }}?text={{ rawurlencode($waMessage) }}"
       target="_blank" rel="noopener noreferrer"
       aria-label="Tanya Admin lewat WhatsApp"
       aria-describedby="wa-help-tip">
        <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.9-.9L3 20.5l1.6-4.8A8.4 8.4 0 1 1 21 11.5z"/></svg>
        <span class="wa-help-label">Tanya Admin</span>
    </a>
    <span class="wa-help-tip" id="wa-help-tip" role="tooltip">Tanya Admin Pesta Kita. Semua pertanyaan tentang vendor dan venue lewat Admin.</span>
</div>
@endif
