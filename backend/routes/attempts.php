<?php

require_once __DIR__ . '/../services/AttemptService.php';

Flight::route('GET /api/attempts', fn() => Api::run(fn() => (new AttemptService())->getAll()));
Flight::route('GET /api/attempts/@id', fn($id) => Api::run(fn() => (new AttemptService())->getById(Api::id($id))));
Flight::route('POST /api/attempts', fn() => Api::run(fn() => (new AttemptService())->create(Api::body()), 201));
Flight::route('PATCH /api/attempts/@id', fn($id) => Api::run(fn() => (new AttemptService())->update(Api::id($id), Api::body())));
Flight::route('DELETE /api/attempts/@id', fn($id) => Api::run(fn() => (new AttemptService())->delete(Api::id($id))));
