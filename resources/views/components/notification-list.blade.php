@props(['notifications', 'portal'])
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<section class="card narrow" aria-label="Daftar notifikasi">
    @forelse($notifications as $notification)
        <a class="notif {{ $notification->read_at ? '' : 'new' }}" href="{{ route($portal.'.notifikasi.buka', $notification->id) }}">
            <b>{{ \App\Http\Controllers\NotificationController::titleFor($notification) }}@unless($notification->read_at)<span class="sr-only"> (belum dibaca)</span>@endunless</b>
            <span>{{ $notification->data['message'] ?? 'Ada pembaruan untuk akun Anda.' }}</span>
            <small>{{ $notification->created_at?->diffForHumans() }}</small>
        </a>
    @empty
        <p class="notif-empty">Belum ada notifikasi.</p>
    @endforelse
    @if($notifications->hasPages())<div class="card-b">{{ $notifications->links() }}</div>@endif
</section>
