<?php
// a crude autoloader for tests.
$dir = dirname(__FILE__);
require $dir . '/../vendor/autoload.php';

spl_autoload_register(function( $class ) use ( $dir ) {
    $pathPieces = explode('\\', $class);
    $filePath = implode('/', $pathPieces) . '.php';
    if (file_exists($dir . '/' . $filePath)) {
        /** @noinspection PhpIncludeInspection */
        include $filePath;
    }
});
