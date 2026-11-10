<div style="background:#1e1633;color:#ffffff;font-size:12.5px;padding:6px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;z-index:9999;position:relative;border-bottom:1px solid rgba(255,255,255,0.1)">
    <div style="display:flex;align-items:center;gap:12px;font-weight:600">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981"></span>
        <span>Laravel Pesta Kita — Pratinjau UI/UX</span>
        <span style="opacity:0.6;font-weight:400">|</span>
        <span style="color:#f5b83d;font-weight:700">{{ $portalTitle ?? 'Pratinjau' }}</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <a href="{{ url('/') }}" style="color:#ffffff;text-decoration:none;padding:3px 10px;border-radius:6px;background:{{ request()->is('/') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Landing Page</a>
        <a href="{{ url('/klien') }}" style="color:#ffffff;text-decoration:none;padding:3px 10px;border-radius:6px;background:{{ request()->is('klien*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Portal Klien</a>
        <a href="{{ url('/penyedia') }}" style="color:#ffffff;text-decoration:none;padding:3px 10px;border-radius:6px;background:{{ request()->is('penyedia*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Portal Penyedia</a>
        <a href="{{ url('/admin') }}" style="color:#ffffff;text-decoration:none;padding:3px 10px;border-radius:6px;background:{{ request()->is('admin*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Panel Admin</a>
        <a href="{{ url('/login') }}" style="color:#ffffff;text-decoration:none;padding:3px 10px;border-radius:6px;background:{{ request()->is('login*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Masuk</a>
        <a href="{{ url('/register') }}" style="color:#ffffff;text-decoration:none;padding:3px 10px;border-radius:6px;background:{{ request()->is('register*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Daftar</a>
    </div>
</div>
