<?php
/**
 * Removes mPDF fonts and files the plugin does not ship (keeps the DejaVu set used since 1.0).
 * Run by composer after Strauss; extra fonts can be added at runtime via the document_engine_pdf_font_dir filter.
 */
$root = dirname(__DIR__) . '/vendor-prefixed/mpdf/mpdf';
$keep = array_filter(array_map('trim', file(__DIR__ . '/keep-fonts.txt')));
foreach (glob($root . '/ttfonts/*') as $font) {
    if (!in_array(basename($font), $keep, true)) {
        unlink($font);
    }
}
foreach (array('phpstan-baseline.neon', 'phpunit.xml', '.github', 'utils', 'CHANGELOG.md', 'CREDITS.txt', 'README.md', 'SECURITY.md') as $junk) {
    $path = $root . '/' . $junk;
    if (is_dir($path)) {
        exec('rm -rf ' . escapeshellarg($path));
    } elseif (file_exists($path)) {
        unlink($path);
    }
}

// Strauss cannot rewrite namespaces built from strings; mPDF resolves tag handlers that way.
$prefix = 'MatrixAddons\\DocumentEngine\\Vendor\\';
foreach (array('src/Tag.php', 'src/Tag/Tag.php') as $relative) {
    $file = $root . '/' . $relative;
    $code = file_get_contents($file);
    $code = str_replace("'Mpdf\\Tag\\\\'", "'" . $prefix . "Mpdf\\Tag\\\\'", $code);
    file_put_contents($file, $code);
}
