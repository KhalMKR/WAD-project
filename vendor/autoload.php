<?php
// Simple autoload wrapper to locate a bundled Dompdf installation.
// Place this file at vendor/autoload.php and it will try common dompdf locations.

$candidates = [
    __DIR__ . '/autoload.inc.php',
    __DIR__ . '/../dompdf/dompdf/autoload.inc.php',
    __DIR__ . '/../dompdf/autoload.inc.php',
    __DIR__ . '/../dompdf/src/autoload.php',
];

$loaded = false;
foreach ($candidates as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    // Friendly runtime message instead of silent failure
    header('Content-Type: text/plain');
    echo "Dompdf autoload not found.\n";
    echo "Searched paths:\n" . implode("\n", $candidates) . "\n";
    echo "If you installed Dompdf to 'dompdf/dompdf', ensure that file exists: vendor/../dompdf/dompdf/autoload.inc.php\n";
    exit(1);
}
