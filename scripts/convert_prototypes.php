<?php

$panels = [
    'klien' => [
        'src' => __DIR__.'/../_prototypes/pesta-kita-klien-selaras/klien-preview',
        'dest' => __DIR__.'/../resources/views/klien',
        'layout' => 'layouts.klien',
        'url_prefix' => '/klien',
    ],
    'penyedia' => [
        'src' => __DIR__.'/../_prototypes/pesta-kita-penyedia-selaras/penyedia-preview',
        'dest' => __DIR__.'/../resources/views/penyedia',
        'layout' => 'layouts.penyedia',
        'url_prefix' => '/penyedia',
    ],
    'admin' => [
        'src' => __DIR__.'/../_prototypes/pesta-kita-admin-selaras/admin-preview',
        'dest' => __DIR__.'/../resources/views/admin',
        'layout' => 'layouts.admin',
        'url_prefix' => '/admin',
    ],
];

foreach ($panels as $panelKey => $panel) {
    if (! is_dir($panel['dest'])) {
        mkdir($panel['dest'], 0777, true);
    }

    $files = glob($panel['src'].'/*.html');
    echo "Processing panel: $panelKey (".count($files)." files)\n";

    foreach ($files as $file) {
        $filename = basename($file, '.html');
        $raw = file_get_contents($file);

        // Extract title
        $title = 'Pesta Kita';
        if (preg_match('/<title>(.*?)<\/title>/is', $raw, $tMatches)) {
            $title = trim($tMatches[1]);
        }

        // Extract main tag
        if (preg_match('/<main\b[^>]*>[\s\S]*?<\/main>/i', $raw, $mMatches)) {
            $mainContent = $mMatches[0];
        } else {
            echo "  [WARNING] No <main> found in $file\n";

            continue;
        }

        // Check if this is a guest page (e.g. login)
        $useLayout = $panel['layout'];
        if (str_contains($mainContent, 'class="guest"') || $filename === 'login') {
            $useLayout = 'layouts.guest';
        }

        // Replace internal hrefs
        // 1. index.html -> url prefix
        $mainContent = preg_replace('/\bhref="index\.html"/i', 'href="{{ url(\''.$panel['url_prefix'].'\') }}"', $mainContent);
        // 2. login.html -> /login
        $mainContent = preg_replace('/\bhref="login\.html"/i', 'href="{{ url(\'/login\') }}"', $mainContent);
        // 3. register.html -> /register
        $mainContent = preg_replace('/\bhref="register\.html"/i', 'href="{{ url(\'/register\') }}"', $mainContent);
        // 4. other html links -> prefix/name
        $prefix = $panel['url_prefix'];
        $mainContent = preg_replace_callback('/\bhref="([a-zA-Z0-9_-]+)\.html"/i', function ($m) use ($prefix) {
            $name = $m[1];
            if ($name === 'index') {
                return 'href="{{ url(\''.$prefix.'\') }}"';
            }
            if ($name === 'login') {
                return 'href="{{ url(\'/login\') }}"';
            }
            if ($name === 'register') {
                return 'href="{{ url(\'/register\') }}"';
            }

            return 'href="{{ url(\''.$prefix.'/'.$name.'\') }}"';
        }, $mainContent);

        // Escape any raw blade-like braces if needed, but in our static HTML there are standard brackets
        $blade = "@extends('$useLayout')\n\n";
        $blade .= "@section('title', ".var_export($title, true).")\n\n";
        $blade .= "@section('content')\n";
        $blade .= $mainContent."\n";
        $blade .= "@endsection\n";

        $destFile = $panel['dest'].'/'.$filename.'.blade.php';
        file_put_contents($destFile, $blade);
        echo "  Converted: $filename.html -> $destFile\n";
    }
}

echo "All prototype conversions completed successfully!\n";
