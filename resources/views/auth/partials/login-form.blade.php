{{-- Form login bersama: POST /login nyata (BE-1), redirect sesuai role DB (T1). --}}
<form class="auth-form" method="POST" action="{{ url('/login') }}">
    @csrf
    <div class="auth-field">
        <label for="email">Email</label>
        <input class="auth-input" id="email" name="email" type="email" placeholder="nama@email.com" autocomplete="email" required value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
        @error('email')
            <p class="field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password">Kata sandi</label>
        <input class="auth-input" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
        @error('password')
            <p class="field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <label class="auth-check">
        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
        Ingat saya di perangkat ini
    </label>

    <button type="submit" class="auth-submit">Masuk</button>
</form>