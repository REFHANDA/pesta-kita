<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$files = glob(__DIR__.'/../resources/views/admin/*.blade.php');
$ok = 0;
$fail = 0;

foreach ($files as $file) {
    $viewName = 'admin.'.basename($file, '.blade.php');
    try {
        $out = view($viewName)->render();
        if (strlen($out) > 100) {
            $ok++;
        } else {
            echo "Warning: $viewName is suspiciously short\n";
        }
    } catch (Throwable $e) {
        $fail++;
        echo "Error in $viewName: ".$e->getMessage()."\n";
    }
}

echo "Admin views test: $ok rendered successfully, $fail failed.\n";
