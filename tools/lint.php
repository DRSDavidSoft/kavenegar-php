<?php

$root = dirname(__DIR__);
$count = 0;
foreach (['src', 'tests', 'tools'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory));
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $status);
            if ($status !== 0) {
                exit($status);
            }
            ++$count;
        }
    }
}
echo "Syntax checked $count PHP files.\n";
