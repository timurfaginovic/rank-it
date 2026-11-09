<?php

require_once __DIR__ . '/../services/WorldBankService.php';

Flight::route('POST /api/external/population-draft', fn() => Api::run(fn() => (new WorldBankService())->createDraft(Api::body())));
