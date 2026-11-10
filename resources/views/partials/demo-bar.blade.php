<div style="background:#1e1633;color:#ffffff;font-size:12.5px;padding:6px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;z-index:9999;position:relative;border-bottom:1px solid rgba(255,255,255,0.1)">
    <div style="display:flex;align-items:center;gap:10px;font-weight:600">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981"></span>
        <span>Pesta Kita (EMS)</span>
        <span style="opacity:0.4">|</span>
        <span style="color:#f5b83d;font-weight:700">{{ $portalTitle ?? 'Pratinjau UI/UX' }}</span>
    </div>
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        <a href="{{ url('/') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('/') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">Landing</a>
        <a href="{{ url('/klien') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('klien*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">👤 Klien</a>
        <a href="{{ url('/vendor') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('vendor*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">🍴 Vendor</a>
        <a href="{{ url('/venue') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('venue*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">🏛️ Pemilik Venue</a>
        <a href="{{ url('/admin') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('admin*') ? '#5b2a86' : 'rgba(255,255,255,0.1)' }};font-weight:600;font-size:12px">🛡️ Admin</a>
        <span style="opacity:0.3;margin:0 2px">|</span>
        <a href="{{ url('/login') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('login*') ? '#5b2a86' : 'rgba(255,255,255,0.08)' }};font-weight:600;font-size:12px">Masuk</a>
        <a href="{{ url('/register') }}" style="color:#ffffff;text-decoration:none;padding:3px 9px;border-radius:6px;background:{{ request()->is('register*') ? '#5b2a86' : 'rgba(255,255,255,0.08)' }};font-weight:600;font-size:12px">Daftar</a>
    </div>
</div>
