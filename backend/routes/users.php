<?php

require_once __DIR__ . '/../services/UserService.php';

Flight::route('GET /api/users', fn() => Api::run(fn() => (new UserService())->getAll()));
Flight::route('GET /api/users/@id', fn($id) => Api::run(fn() => (new UserService())->getById(Api::id($id))));
Flight::route('POST /api/users', fn() => Api::run(fn() => (new UserService())->create(Api::body()), 201));
Flight::route('PATCH /api/users/@id', fn($id) => Api::run(fn() => (new UserService())->update(Api::id($id), Api::body())));
Flight::route('DELETE /api/users/@id', fn($id) => Api::run(fn() => (new UserService())->delete(Api::id($id))));
