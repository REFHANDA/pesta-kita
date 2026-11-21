{{-- Panel brand bersama untuk halaman auth split (masuk & daftar). --}}
<aside class="auth-brand">
    <span class="auth-orb o1" aria-hidden="true"></span>
    <span class="auth-orb o2" aria-hidden="true"></span>
    <span class="auth-orb o3" aria-hidden="true"></span>
    <span class="auth-orb o4" aria-hidden="true"></span>
    <span class="auth-orb o5" aria-hidden="true"></span>

    <a class="auth-brand-line" href="{{ url('/') }}">
        <span class="auth-logo" aria-hidden="true">P</span>
        <b>Pesta Kita</b>
    </a>

    <div class="auth-brand-copy">
        <h2>{{ $heading }}</h2>
        <p>{{ $desc }}</p>
        @if (! empty($points))
            <ul class="auth-brand-points">
                @foreach ($points as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <span class="auth-brand-foot">&copy; 2026 Pesta Kita</span>
</aside>