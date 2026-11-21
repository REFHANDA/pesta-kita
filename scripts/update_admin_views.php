<?php

$src = __DIR__.'/../_updates/admin/admin-preview';
$dest = __DIR__.'/../resources/views/admin';

if (! is_dir($dest)) {
    mkdir($dest, 0777, true);
}

$files = glob($src.'/*.html');
echo 'Found '.count($files)." admin HTML files in update package.\n";

$count = 0;
foreach ($files as $file) {
    $filename = basename($file, '.html');
    $raw = file_get_contents($file);

    // Extract title
    $title = 'Admin · Pesta Kita';
    if (preg_match('/<title>(.*?)<\/title>/is', $raw, $tMatches)) {
        $title = trim($tMatches[1]);
    }

    // Extract main tag
    if (preg_match('/<main\b[^>]*>[\s\S]*?<\/main>/i', $raw, $mMatches)) {
        $mainContent = $mMatches[0];
    } else {
        echo "  [WARN] No <main> tag in $filename\n";

        continue;
    }

    $useLayout = ($filename === 'login') ? 'layouts.guest' : 'layouts.admin';

    // Replace internal links, preserving query parameters e.g. ?mode=tambah
    $mainContent = preg_replace_callback('/\bhref="([a-zA-Z0-9_-]+)\.html(\?[^"]*)?"/i', function ($m) {
        $page = $m[1];
        $query = isset($m[2]) ? $m[2] : '';
        if ($page === 'index') {
            return 'href="{{ url(\'/admin\') }}'.$query.'"';
        }
        if ($page === 'login') {
            return 'href="{{ url(\'/login\') }}'.$query.'"';
        }
        if ($page === 'register') {
            return 'href="{{ url(\'/register\') }}'.$query.'"';
        }

        return 'href="{{ url(\'/admin/'.$page.'\') }}'.$query.'"';
    }, $mainContent);

    // Format Blade
    $blade = "@extends('$useLayout')\n\n";
    $blade .= "@section('title', ".var_export($title, true).")\n\n";
    $blade .= "@section('content')\n";
    $blade .= $mainContent."\n";
    $blade .= "@endsection\n";

    $destPath = "$dest/$filename.blade.php";
    file_put_contents($destPath, $blade);
    $count++;
    echo "  [OK] Converted: $filename.html -> $filename.blade.php\n";
}

echo "\nSuccessfully updated $count admin blade views!\n";
