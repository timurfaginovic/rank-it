<?php

ini_set('display_errors', '0');
date_default_timezone_set('UTC');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../routes/Api.php';

Flight::map('error', fn(Throwable $error) => Api::error($error));
Flight::map('notFound', fn() => Flight::json(['error' => 'Endpoint not found.'], 404));

foreach (['users', 'challenges', 'challenge-items', 'attempts', 'attempt-items', 'external'] as $route) {
    require __DIR__ . '/../routes/' . $route . '.php';
}

Flight::start();
