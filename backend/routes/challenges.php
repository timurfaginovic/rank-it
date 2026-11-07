<?php

require_once __DIR__ . '/../services/ChallengeService.php';

Flight::route('GET /api/challenges', fn() => Api::run(fn() => (new ChallengeService())->getAll()));
Flight::route('GET /api/challenges/@id', fn($id) => Api::run(fn() => (new ChallengeService())->getById(Api::id($id))));
Flight::route('POST /api/challenges', fn() => Api::run(fn() => (new ChallengeService())->create(Api::body()), 201));
Flight::route('PATCH /api/challenges/@id', fn($id) => Api::run(fn() => (new ChallengeService())->update(Api::id($id), Api::body())));
Flight::route('DELETE /api/challenges/@id', fn($id) => Api::run(fn() => (new ChallengeService())->delete(Api::id($id))));
