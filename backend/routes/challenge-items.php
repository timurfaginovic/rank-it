<?php

require_once __DIR__ . '/../services/ChallengeItemService.php';

Flight::route('GET /api/challenge-items', fn() => Api::run(fn() => (new ChallengeItemService())->getAll()));
Flight::route('GET /api/challenge-items/@id', fn($id) => Api::run(fn() => (new ChallengeItemService())->getById(Api::id($id))));
Flight::route('POST /api/challenge-items', fn() => Api::run(fn() => (new ChallengeItemService())->create(Api::body()), 201));
Flight::route('PATCH /api/challenge-items/@id', fn($id) => Api::run(fn() => (new ChallengeItemService())->update(Api::id($id), Api::body())));
Flight::route('DELETE /api/challenge-items/@id', fn($id) => Api::run(fn() => (new ChallengeItemService())->delete(Api::id($id))));
