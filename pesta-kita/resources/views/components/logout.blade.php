<form action="{{ route('logout') }}" method="POST" class="logout-form">
    @csrf
    <button type="submit" class="logout" style="width: 100%; cursor: pointer; background: transparent; font: inherit;">
        Keluar
    </button>
</form>
