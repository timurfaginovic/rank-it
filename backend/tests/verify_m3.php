<?php

if (PHP_SAPI !== 'cli') exit('Run from the command line.');
foreach (['User', 'Challenge', 'ChallengeItem', 'Attempt', 'AttemptItem', 'WorldBank'] as $name) {
    require_once __DIR__ . '/../services/' . $name . 'Service.php';
}

function checkM3(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function rejectsM3(callable $operation, int $status = 400): void
{
    try { $operation(); }
    catch (ServiceException $error) {
        checkM3($error->status === $status, 'Wrong service error status.');
        return;
    }
    throw new RuntimeException('Expected a service error.');
}

$connection = Database::getConnection();
$daos = [new UserDao($connection), new ChallengeDao($connection), new ChallengeItemDao($connection), new AttemptDao($connection), new AttemptItemDao($connection)];
$before = array_map(fn($dao) => $dao->getAll(), $daos);
try {
    $connection->beginTransaction();
    $users = new UserService($connection);
    $challenges = new ChallengeService($connection);
    $items = new ChallengeItemService($connection);
    $attempts = new AttemptService($connection);
    $answers = new AttemptItemService($connection);
    $user = $users->create(['name' => 'M3 check', 'email' => 'm3-' . bin2hex(random_bytes(6)) . '@example.com', 'password' => 'sample-test-pass']);
    checkM3(!isset($user['password_hash']) && password_verify('sample-test-pass', $daos[0]->getById($user['id'])['password_hash']), 'Password handling failed.');
    rejectsM3(fn() => $users->create(['name' => 'X', 'email' => $user['email'], 'password' => 'sample-test-pass']));
    rejectsM3(fn() => $users->update($user['id'], ['password_hash' => 'arbitrary']));
    rejectsM3(fn() => $users->update($user['id'], ['email' => 'invalid']));
    rejectsM3(fn() => $users->getById(4294967295), 404);
    $challenge = $challenges->create(['title' => 'M3 check', 'description' => 'Smallest first.', 'challenge_date' => '2090-01-01', 'items' => ['A', 'B', 'C', 'D', 'E']]);
    $challengeId = $challenge['id'];
    $rows = $daos[2]->getByChallengeId($challengeId);
    $item = $rows[0];
    rejectsM3(fn() => $items->create(['challenge_id' => $challengeId, 'item_text' => 'Other', 'correct_position' => 1]));
    $daos[2]->update($item['id'], ['item_text' => 'B']);
    rejectsM3(fn() => $challenges->update($challengeId, ['status' => 'Published']));
    $daos[1]->update($challengeId, ['status' => 'Draft']);
    $daos[2]->update($item['id'], ['item_text' => 'A']);
    $order = array_column($rows, 'id');
    foreach (['abc', null, [], true, 0, -1, 1.5, '1', 4294967296] as $invalidId) {
        rejectsM3(fn() => $answers->create(['attempt_id' => $invalidId, 'challenge_item_id' => $order[0], 'selected_position' => 1]));
    }
    rejectsM3(fn() => $answers->create(['attempt_id' => 4294967295, 'challenge_item_id' => $order[0], 'selected_position' => 1]), 404);
    foreach (['Draft', 'Pending'] as $status) {
        $challenges->update($challengeId, ['status' => $status]);
        $partial = $daos[3]->create(['user_id' => $user['id'], 'challenge_id' => $challengeId, 'score' => 0, 'completed_at' => '2090-01-01 00:00:00']);
        $original = $daos[3]->getById($partial);
        rejectsM3(fn() => $attempts->update($partial, ['ranking' => $order]));
        checkM3($daos[3]->getById($partial) === $original && $daos[4]->getByAttemptId($partial) === [], 'Rejected ranking update changed an unpublished attempt.');
        $daos[3]->delete($partial);
    }
    $challenges->update($challengeId, ['status' => 'Published']);
    rejectsM3(fn() => $items->update($item['id'], ['item_text' => 'Changed']));
    checkM3(AttemptService::calculateScore($rows, $order) === 100, 'Perfect score failed.');
    $swap = $order; [$swap[0], $swap[1]] = [$swap[1], $swap[0]];
    checkM3(AttemptService::calculateScore($rows, $swap) === 60, 'Swap score failed.');
    $rotation = [...array_slice($order, 1), $order[0]];
    checkM3(AttemptService::calculateScore($rows, $rotation) === 0, 'Rotation score failed.');
    rejectsM3(fn() => $attempts->create(['user_id' => $user['id'], 'challenge_id' => $challengeId, 'ranking' => [$order[0],$order[0],$order[2],$order[3],$order[4]]]));
    $attempt = $attempts->create(['user_id' => $user['id'], 'challenge_id' => $challengeId, 'ranking' => $order]);
    checkM3($attempt['score'] === 100 && count($daos[4]->getByAttemptId($attempt['id'])) === 5, 'Atomic attempt failed.');
    rejectsM3(fn() => $attempts->create(['user_id' => $user['id'], 'challenge_id' => $challengeId, 'ranking' => $order]));
    rejectsM3(fn() => $challenges->update($challengeId, ['title' => 'Change history']));
    $answer = $daos[4]->getByAttemptId($attempt['id'])[0];
    rejectsM3(fn() => $answers->delete($answer['id']));
    $attempts->update($attempt['id'], ['ranking' => $swap]);
    checkM3($attempts->getById($attempt['id'])['score'] === 60, 'Ranking correction failed.');
    $attempts->delete($attempt['id']);
    $partial = $daos[3]->create(['user_id' => $user['id'], 'challenge_id' => $challengeId, 'score' => 0, 'completed_at' => '2090-01-01 00:00:00']);
    $answer = $answers->create(['attempt_id' => $partial, 'challenge_item_id' => $order[0], 'selected_position' => 1]);
    $answers->update($answer['id'], ['selected_position' => 2]);
    checkM3($attempts->getById($partial)['score'] === 0, 'Incomplete attempt must be unscored.');
    $answers->delete($answer['id']);
    foreach ($order as $i => $id) $answers->create(['attempt_id' => $partial, 'challenge_item_id' => $id, 'selected_position' => $i + 1]);
    checkM3($attempts->getById($partial)['score'] === 100, 'Fifth answer did not complete scoring.');
    $connection->rollBack();
    checkM3(array_map(fn($dao) => $dao->getAll(), $daos) === $before, 'Service tests changed stored data.');
    echo "PASS: entity/ID validation, unpublished ranking rejection, history protection, 0/60/100 scoring, atomic submission, answer maintenance and rollback\n";

    $records = [];
    foreach (['BIH', 'HRV', 'SRB', 'MNE', 'SVN'] as $i => $code) {
        $records[] = ['countryiso3code' => $code, 'country' => ['value' => $code], 'date' => '2023', 'value' => (string) (($i + 1) * 100)];
    }
    $transport = fn($rows) => fn($url) => ['status' => 200, 'body' => json_encode([['pages' => 1], $rows])];
    $draft = (new WorldBankService($transport($records)))->createDraft(['year' => 2023]);
    checkM3($draft['items'][0]['item_text'] === 'SVN' && is_numeric($draft['items'][0]['population']) && !is_string($draft['items'][0]['population']), 'Draft must sort and normalize numeric values.');
    $log = __DIR__ . '/../logs/error.log';
    $size = is_file($log) ? filesize($log) : 0;
    $missing = array_slice($records, 1);
    rejectsM3(fn() => (new WorldBankService($transport($missing)))->createDraft(['year' => 2023]), 502);
    $wrongYear = $records; $wrongYear[0]['date'] = '2022';
    rejectsM3(fn() => (new WorldBankService($transport($wrongYear)))->createDraft(['year' => 2023]), 502);
    $tied = $records; $tied[0]['value'] = $tied[1]['value'];
    rejectsM3(fn() => (new WorldBankService($transport($tied)))->createDraft(['year' => 2023]), 502);
    rejectsM3(fn() => (new WorldBankService(fn($url) => ['status' => 500, 'body' => '']))->createDraft(['year' => 2023]), 502);
    rejectsM3(fn() => (new WorldBankService(fn($url) => ['status' => 200, 'body' => '{bad']))->createDraft(['year' => 2023]), 502);
    rejectsM3(fn() => (new WorldBankService(fn($url) => throw new ServiceException('Population request timed out.', 503)))->createDraft(['year' => 2023]), 503);
    clearstatcache(); checkM3(filesize($log) > $size, 'External failures were not logged.');
    echo "PASS: deterministic provider sorting, same-year/missing/tied/invalid data, timeout errors and failure logging\n";
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../routes/Api.php';
    Api::error(new RuntimeException('Private SQL and filesystem details.'));
    checkM3(Flight::response()->status() === 500 && !str_contains(Flight::response()->getBody(), 'Private'), 'Unexpected errors exposed details.');
    Api::error(new ServiceException('Population data is unavailable.', 503));
    checkM3(Flight::response()->status() === 503, 'External error status mapping failed.');
    echo "PASS: safe 500 JSON and external 503 response mapping\n";
} catch (Throwable $error) {
    if ($connection->inTransaction()) $connection->rollBack();
    fwrite(STDERR, 'FAIL: ' . ($error instanceof PDOException ? 'Database test failed.' : $error->getMessage()) . PHP_EOL);
    exit(1);
}
