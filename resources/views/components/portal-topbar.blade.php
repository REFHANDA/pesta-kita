@props(['notifUrl', 'profileUrl', 'name', 'email'])
<header class="topbar">
    <button type="button" class="burger" id="nav-toggle" aria-label="Buka menu navigasi" aria-controls="main-navigation" aria-expanded="false">
        <span class="ic i-menu" aria-hidden="true"></span>
    </button>
    <div class="top-right">
        @php($unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0)
        <a class="icon-btn notif-bell" href="{{ $notifUrl }}" aria-label="Notifikasi{{ $unreadCount > 0 ? ", {$unreadCount} belum dibaca" : '' }}">
            <span class="ic i-bell" aria-hidden="true"></span>
            @if($unreadCount > 0)<span class="notif-badge" data-unread-count="{{ $unreadCount }}" aria-hidden="true">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
        </a>
        <details class="user-menu">
            <summary class="user">
                <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                <span class="t"><b>{{ $name }}</b><small>{{ $email }}</small></span>
            </summary>
            <div class="user-menu-pop">
                <a href="{{ $profileUrl }}"><span class="ic i-users" aria-hidden="true"></span>Profil</a>
                <form method="POST" action="{{ url('/logout') }}">@csrf
                    <button type="submit"><span class="ic i-out" aria-hidden="true"></span>Keluar</button>
                </form>
            </div>
        </details>
    </div>
</header>
