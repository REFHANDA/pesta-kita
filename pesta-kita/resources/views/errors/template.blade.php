
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Galat {{ $code }} | Pesta Kita</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Arial, sans-serif;
    background: white;
    color: #241a36;
    min-height: 100vh;
    padding: 12px;
}

.error-container {
    min-height: 90vh;
    background: #fbf9fd;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 25px;
}

.error-content { max-width: 520px; }

.brand {
    color: #632e8b;
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 28px;
}

.error-code {
    color: #632e8b;
    font-size: 76px;
    font-weight: bold;
    margin-bottom: 12px;
}

h1 {
    font-size: 27px;
    margin-bottom: 13px;
}

.description {
    color: #756b80;
    font-size: 14px;
    line-height: 1.7;
    margin-bottom: 25px;
}

.actions {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 22px;
    border-radius: 25px;
    background: #632e8b;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: bold;
}

.btn:hover { background: #512370; }

.btn-secondary {
    background: white;
    color: #30243e;
    border: 1px solid #a797ba;
}

.error-note {
    margin-top: 25px;
    font-size: 12px;
    color: #756b80;
}

@media(max-width:600px) {
    .error-code { font-size: 60px; }
    h1 { font-size: 22px; }
}
</style>
</head>
<body>

<main class="error-container">
    <div class="error-content">

        <div class="brand">Pesta Kita</div>

        <div class="error-code">{{ $code }}</div>

        <h1>{{ $title }}</h1>

        <p class="description">{{ $description }}</p>

        <div class="actions">
            @if($code == 419)
                <a href="{{ url()->previous() }}" class="btn">
                    Kembali ke halaman sebelumnya
                </a>
                <a href="{{ route('klien.dashboard') }}" class="btn btn-secondary">
                    Beranda
                </a>
            @elseif($code == 500 || $code == 503)
                <a href="javascript:location.reload()" class="btn">
                    Muat ulang
                </a>
                <a href="{{ route('klien.dashboard') }}" class="btn btn-secondary">
                    Beranda
                </a>
            @else
                <a href="{{ route('klien.dashboard') }}" class="btn">
                    Kembali ke beranda
                </a>
            @endif
        </div>

        <p class="error-note">Kode galat {{ $code }}</p>

    </div>
</main>

</body>
</html>
