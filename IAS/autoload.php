<?php

/**
 * Simple PSR-4 autoloader for the CipherLab namespace.
 * Maps  CipherLab\Foo\Bar  →  src/Foo/Bar.php
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'CipherLab\\';
    $base   = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
