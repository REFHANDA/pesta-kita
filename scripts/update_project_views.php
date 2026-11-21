<?php

$root = dirname(__DIR__);
$path = static fn (string $relative): string => $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

$resolvePageUrl = static function (string $page, string $prefix, string $role): string {
    if ($page === 'index') {
        return $prefix;
    }

    if ($page === 'venue') {
        return '/venue';
    }

    if ($page === 'venue-data') {
        return '/venue/data';
    }

    if ($page === 'venue-form') {
        return '/venue/form';
    }

    if ($page === 'pesanan-venue-detail') {
        return '/venue/pesanan-detail';
    }

    if ($page === 'verifikasi-penyedia') {
        return $prefix.'/verifikasi';
    }

    if ($page === 'login') {
        return in_array($role, ['klien', 'vendor'], true) ? $prefix.'/login' : '/login';
    }

    if ($page === 'register') {
        return $role === 'klien' ? $prefix.'/register' : '/register';
    }

    if ($page === 'registrasi-penyedia') {
        return $role === 'vendor' ? '/vendor/registrasi-penyedia' : '/register';
    }

    return $prefix.'/'.$page;
};

$convert = static function (string $sourceName, string $destinationName, string $prefix, array $pages) use ($path, $resolvePageUrl): int {
    $sourceDirectory = $path('_updates/'.$sourceName.'/'.$sourceName.'-preview');
    $destinationDirectory = $path('resources/views/'.$destinationName);

    if (! is_dir($destinationDirectory) && ! mkdir($destinationDirectory, 0777, true) && ! is_dir($destinationDirectory)) {
        throw new RuntimeException("Unable to create {$destinationDirectory}");
    }

    foreach ($pages as $sourcePage => $destinationPage) {
        $source = $sourceDirectory.DIRECTORY_SEPARATOR.$sourcePage.'.html';
        $raw = is_file($source) ? file_get_contents($source) : false;

        if ($raw === false || ! preg_match('/<title>(.*?)<\/title>/is', $raw, $titleMatch)) {
            throw new RuntimeException("Unable to read page title from {$source}");
        }

        if (! preg_match('/<main\b[^>]*>[\s\S]*?<\/main>/i', $raw, $mainMatch)) {
            throw new RuntimeException("Missing main content in {$source}");
        }

        $title = trim($titleMatch[1]);
        $main = $mainMatch[0];
        $role = trim($prefix, '/');

        if ($role === 'venue') {
            $title = str_ireplace('Vendor', 'Pemilik Venue', $title);
        }

        $main = preg_replace_callback(
            '/\bhref="([a-zA-Z0-9_-]+)\.html(\?[^"]*)?"/i',
            static function (array $match) use ($prefix, $role, $resolvePageUrl): string {
                $url = $resolvePageUrl($match[1], $prefix, $role);
                $query = $match[2] ?? '';

                return 'href="{{ url('.var_export($url, true).') }}'.$query.'"';
            },
            $main
        );

        $main = preg_replace_callback(
            '/\b(data-after|data-go)="([a-zA-Z0-9_-]+)\.html(\?[^"]*)?"/i',
            static function (array $match) use ($prefix, $role, $resolvePageUrl): string {
                $url = $resolvePageUrl($match[2], $prefix, $role);
                $query = $match[3] ?? '';

                return $match[1].'="{{ url('.var_export($url, true).') }}'.$query.'"';
            },
            $main
        );

        $main = preg_replace_callback(
            '/(location\.href\s*=\s*)([\'"])([a-zA-Z0-9_-]+)\.html(\?[^\'"]*)?\2/i',
            static function (array $match) use ($prefix, $role, $resolvePageUrl): string {
                $url = $resolvePageUrl($match[3], $prefix, $role);
                $query = $match[4] ?? '';

                return $match[1]."'{{ url(".var_export($url, true).") }}{$query}'";
            },
            $main
        );

        if ($main === null) {
            throw new RuntimeException("Unable to rewrite internal navigation in {$source}");
        }

        $layout = in_array($sourcePage, ['login', 'register', 'registrasi-penyedia'], true)
            ? 'layouts.guest'
            : 'layouts.'.$destinationName;
        $blade = "@extends('{$layout}')\n\n";
        $blade .= "@section('title', ".var_export($title, true).")\n\n";
        $blade .= "@section('content')\n{$main}\n@endsection\n";
        $target = $destinationDirectory.DIRECTORY_SEPARATOR.$destinationPage.'.blade.php';

        if (file_put_contents($target, $blade) === false) {
            throw new RuntimeException("Unable to write {$target}");
        }
    }

    return count($pages);
};

$clientPages = [];
foreach (glob($path('_updates/klien/klien-preview/*.html')) as $file) {
    $page = pathinfo($file, PATHINFO_FILENAME);
    $clientPages[$page] = $page;
}

$vendorPages = [];
foreach (glob($path('_updates/penyedia/penyedia-preview/*.html')) as $file) {
    $page = pathinfo($file, PATHINFO_FILENAME);

    if (in_array($page, ['venue', 'venue-data', 'venue-form', 'pesanan-venue-detail'], true)) {
        continue;
    }

    $vendorPages[$page] = $page === 'verifikasi-penyedia' ? 'verifikasi' : $page;
}

$venuePages = [
    'venue' => 'index',
    'venue-data' => 'data',
    'venue-form' => 'form',
    'pesanan-venue-detail' => 'pesanan-detail',
    'keberatan' => 'keberatan',
    'notifikasi' => 'notifikasi',
    'pencairan-detail' => 'pencairan-detail',
    'pencairan' => 'pencairan',
    'pengajuan-harga' => 'pengajuan-harga',
    'pesanan' => 'pesanan',
    'profil' => 'profil',
    'rundown' => 'rundown',
    'verifikasi-penyedia' => 'verifikasi',
];

printf("Klien: %d halaman diperbarui.\n", $convert('klien', 'klien', '/klien', $clientPages));
printf("Vendor: %d halaman diperbarui.\n", $convert('penyedia', 'vendor', '/vendor', $vendorPages));
printf("Venue: %d halaman diperbarui.\n", $convert('penyedia', 'venue', '/venue', $venuePages));

$stylesheets = [
    '_updates/klien/klien-preview/klien.css' => 'public/assets/css/klien.css',
    '_updates/penyedia/penyedia-preview/penyedia-lanjutan.css' => 'public/assets/css/penyedia-lanjutan.css',
];

foreach ($stylesheets as $source => $target) {
    if (! copy($path($source), $path($target))) {
        throw new RuntimeException("Unable to copy stylesheet {$source}");
    }
}

echo "Stylesheet klien.css and penyedia-lanjutan.css updated.\n";
