<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__ . rawurldecode($path));
$docs = __DIR__ . '/docs';
if ($file !== false && ($file === $docs || str_starts_with($file, $docs . DIRECTORY_SEPARATOR)) && (is_file($file) || is_dir($file))) {
    return false;
}

require __DIR__ . '/index.php';
