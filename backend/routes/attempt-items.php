<?php

require_once __DIR__ . '/../services/AttemptItemService.php';

Flight::route('GET /api/attempt-items', fn() => Api::run(fn() => (new AttemptItemService())->getAll()));
Flight::route('GET /api/attempt-items/@id', fn($id) => Api::run(fn() => (new AttemptItemService())->getById(Api::id($id))));
Flight::route('POST /api/attempt-items', fn() => Api::run(fn() => (new AttemptItemService())->create(Api::body()), 201));
Flight::route('PATCH /api/attempt-items/@id', fn($id) => Api::run(fn() => (new AttemptItemService())->update(Api::id($id), Api::body())));
Flight::route('DELETE /api/attempt-items/@id', fn($id) => Api::run(fn() => (new AttemptItemService())->delete(Api::id($id))));
