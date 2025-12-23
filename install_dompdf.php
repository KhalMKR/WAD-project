<?php
// Simple installer to download and extract Dompdf into vendor/dompdf
// Usage (CLI or browser): php install_dompdf.php

set_time_limit(0);
$targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dompdf';
$zipFile = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dompdf.zip';
$downloadUrl = 'https://github.com/dompdf/dompdf/releases/latest/download/dompdf.zip';

if (is_dir($targetDir)) {
    echo "Dompdf already installed in vendor/dompdf\n";
    exit;
}

if (!is_dir(dirname($zipFile))) {
    mkdir(dirname($zipFile), 0777, true);
}

echo "Downloading Dompdf...\n";
$data = @file_get_contents($downloadUrl);
if ($data === false) {
    echo "Failed to download from $downloadUrl.\n";
    echo "If this fails, please download the release manually from https://github.com/dompdf/dompdf/releases and extract to vendor/dompdf\n";
    exit(1);
}
file_put_contents($zipFile, $data);

if (!class_exists('ZipArchive')) {
    echo "ZipArchive not available in PHP. Please enable the zip extension.\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zipFile) === TRUE) {
    echo "Extracting...\n";
    // extraction will create a top-level folder like dompdf-<version>/
    $zip->extractTo(dirname($zipFile));
    $zip->close();

    // find extracted folder
    $files = scandir(dirname($zipFile));
    $found = null;
    foreach ($files as $f) {
        if (is_dir(dirname($zipFile) . DIRECTORY_SEPARATOR . $f) && preg_match('/^dompdf/i', $f)) {
            $found = dirname($zipFile) . DIRECTORY_SEPARATOR . $f;
            break;
        }
    }
    if (!$found) {
        echo "Could not locate extracted dompdf folder.\n";
        exit(1);
    }

    // Move to vendor/dompdf
    if (!rename($found, $targetDir)) {
        echo "Failed to move extracted folder to vendor/dompdf.\n";
        exit(1);
    }

    // cleanup zip
    unlink($zipFile);

    // Create a simple autoload wrapper
    $autoload = dirname($zipFile) . DIRECTORY_SEPARATOR . 'autoload.php';
    $autoloadContent = "<?php\n// Autoloader wrapper for bundled Dompdf\nif (file_exists(__DIR__ . '/dompdf/autoload.inc.php')) {\n    require_once __DIR__ . '/dompdf/autoload.inc.php';\n} else {\n    throw new Exception('Dompdf autoload not found.');\n}\n";
    file_put_contents(dirname($zipFile) . DIRECTORY_SEPARATOR . 'autoload.php', $autoloadContent);

    echo "Dompdf installed to vendor/dompdf. Autoload available at vendor/autoload.php (wrapper).\n";
    echo "You can now use Dompdf in your scripts by including vendor/autoload.php.\n";
    exit(0);
} else {
    echo "Failed to open downloaded zip file.\n";
    exit(1);
}

?>
