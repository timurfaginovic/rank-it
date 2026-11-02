<?php

if (PHP_SAPI !== 'cli') {
    exit('Run this script from the command line.');
}

require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/ChallengeDao.php';
require_once __DIR__ . '/../dao/ChallengeItemDao.php';
require_once __DIR__ . '/../dao/AttemptDao.php';
require_once __DIR__ . '/../dao/AttemptItemDao.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectFailure(callable $operation, string $exceptionClass, ?int $mysqlCode = null): void
{
    try {
        $operation();
    } catch (Throwable $error) {
        if ($error instanceof $exceptionClass && ($mysqlCode === null || ($error->errorInfo[1] ?? null) === $mysqlCode)) {
            return;
        }
        throw $error;
    }
    throw new RuntimeException('An invalid operation was unexpectedly accepted.');
}

$connection = null;
$stage = 'connection';
try {
    $connection = Database::getConnection();
    check(!$connection->getAttribute(PDO::ATTR_EMULATE_PREPARES), 'Native prepared statements must be enabled.');
    echo "PASS: PDO connection and native prepared statements\n";
    $users = new UserDao($connection);
    $challenges = new ChallengeDao($connection);
    $items = new ChallengeItemDao($connection);
    $attempts = new AttemptDao($connection);
    $answers = new AttemptItemDao($connection);
    $daos = ['users' => $users, 'challenges' => $challenges, 'challenge_items' => $items, 'attempts' => $attempts, 'attempt_items' => $answers];

    $stage = 'schema and sample data';
    $snapshots = [];
    foreach ($daos as $table => $dao) {
        $snapshots[$table] = $dao->getAll();
        check(count($snapshots[$table]) > 0, "$table has no sample data.");
    }
    $statement = $connection->prepare("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('users', 'challenges', 'challenge_items', 'attempts', 'attempt_items')");
    $statement->execute();
    $tables = $statement->fetchAll();
    check(count($tables) === 5, 'All five tables must exist.');
    foreach ($tables as $table) {
        check($table['ENGINE'] === 'InnoDB' && str_starts_with($table['TABLE_COLLATION'], 'utf8mb4_'), 'Tables must use InnoDB and utf8mb4.');
    }
    $statement = $connection->prepare('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL');
    $statement->execute();
    $foreignKeys = [];
    foreach ($statement->fetchAll() as $key) {
        $foreignKeys[] = implode('.', array_values($key));
    }
    $expectedKeys = ['challenge_items.challenge_id.challenges.id', 'attempts.user_id.users.id', 'attempts.challenge_id.challenges.id', 'attempt_items.attempt_id.attempts.id', 'attempt_items.challenge_item_id.challenge_items.id'];
    sort($foreignKeys);
    sort($expectedKeys);
    check($foreignKeys === $expectedKeys, 'Foreign key relationships differ from the ERD.');
    $statement = $connection->prepare('SELECT a.id, a.score, COUNT(ai.id) AS item_count, COALESCE(SUM(ai.selected_position = ci.correct_position), 0) * 20 AS calculated FROM attempts a LEFT JOIN attempt_items ai ON ai.attempt_id = a.id LEFT JOIN challenge_items ci ON ci.id = ai.challenge_item_id GROUP BY a.id, a.score');
    $statement->execute();
    foreach ($statement->fetchAll() as $row) {
        check((int) $row['item_count'] === 5 && (int) $row['score'] === (int) $row['calculated'], 'A stored attempt has inconsistent items or score.');
    }
    echo "PASS: five InnoDB/utf8mb4 tables, five foreign keys and valid sample scores\n";

    $connection->beginTransaction();
    $stage = 'CRUD';
    $email = 'dao-check-' . bin2hex(random_bytes(6)) . '@example.com';
    $userData = ['name' => 'DAO check', 'email' => $email, 'password_hash' => password_hash('temporary-check', PASSWORD_DEFAULT), 'role' => 'user'];
    $userId = $users->create($userData);
    $challengeData = ['title' => 'DAO check', 'description' => 'Smallest number first.', 'challenge_date' => '2026-11-02', 'status' => 'Draft'];
    $challengeId = $challenges->create($challengeData);
    $itemId = $items->create(['challenge_id' => $challengeId, 'item_text' => 'One', 'correct_position' => 1]);
    $attemptId = $attempts->create(['user_id' => $userId, 'challenge_id' => $challengeId, 'score' => 0, 'completed_at' => '2026-11-02 10:00:00']);
    $answerId = $answers->create(['attempt_id' => $attemptId, 'challenge_item_id' => $itemId, 'selected_position' => 1]);
    $records = [
        ['users', $users, $userId, ['name' => "Ranker's check"]],
        ['challenges', $challenges, $challengeId, ['title' => 'Updated challenge']],
        ['challenge_items', $items, $itemId, ['item_text' => 'Updated item']],
        ['attempts', $attempts, $attemptId, ['score' => 20]],
        ['attempt_items', $answers, $answerId, ['selected_position' => 2]],
    ];
    foreach ($records as [$table, $dao, $id, $changes]) {
        check($dao->getById($id) !== null, "$table read by ID failed.");
        check($dao->update($id, $changes), "$table update failed.");
        $row = $dao->getById($id);
        foreach ($changes as $column => $value) {
            check((string) $row[$column] === (string) $value, "$table updated value was not stored.");
        }
        check($dao->update($id, $changes), "$table unchanged update failed.");
    }

    $stage = 'constraints and reference checks';
    expectFailure(fn() => $users->create($userData), PDOException::class, 1062);
    expectFailure(fn() => $items->create(['challenge_id' => $challengeId, 'item_text' => 'Duplicate position', 'correct_position' => 1]), PDOException::class, 1062);
    expectFailure(fn() => $attempts->create(['user_id' => $userId, 'challenge_id' => $challengeId, 'score' => 0, 'completed_at' => '2026-11-02 10:00:00']), PDOException::class, 1062);
    expectFailure(fn() => $items->create(['challenge_id' => $challengeId, 'item_text' => 'Invalid position', 'correct_position' => 6]), PDOException::class, 3819);
    expectFailure(fn() => $attempts->update($attemptId, ['score' => 80]), PDOException::class, 3819);
    expectFailure(fn() => $items->create(['challenge_id' => 0, 'item_text' => 'Orphan', 'correct_position' => 1]), PDOException::class, 1452);
    expectFailure(fn() => $users->delete($userId), PDOException::class, 1451);
    expectFailure(fn() => $challenges->delete($challengeId), PDOException::class, 1451);
    expectFailure(fn() => $items->delete($itemId), PDOException::class, 1451);
    expectFailure(fn() => $users->update($userId, ['name = NULL --' => 'bad']), InvalidArgumentException::class);
    expectFailure(fn() => $users->create([]), InvalidArgumentException::class);
    $secondItem = $items->create(['challenge_id' => $challengeId, 'item_text' => 'Two', 'correct_position' => 2]);
    expectFailure(fn() => $answers->create(['attempt_id' => $attemptId, 'challenge_item_id' => $secondItem, 'selected_position' => 2]), PDOException::class, 1062);
    expectFailure(fn() => $answers->create(['attempt_id' => $attemptId, 'challenge_item_id' => $itemId, 'selected_position' => 3]), PDOException::class, 1062);
    $items->delete($secondItem);

    $otherChallenge = $challenges->create($challengeData);
    $otherItem = $items->create(['challenge_id' => $otherChallenge, 'item_text' => 'Other', 'correct_position' => 1]);
    expectFailure(fn() => $answers->create(['attempt_id' => $attemptId, 'challenge_item_id' => $otherItem, 'selected_position' => 3]), InvalidArgumentException::class);
    expectFailure(fn() => $answers->update($answerId, ['challenge_item_id' => $otherItem]), InvalidArgumentException::class);
    expectFailure(fn() => $attempts->update($attemptId, ['challenge_id' => $otherChallenge]), InvalidArgumentException::class);
    expectFailure(fn() => $items->update($itemId, ['challenge_id' => $otherChallenge]), InvalidArgumentException::class);
    echo "PASS: foreign keys, unique keys, ranges, identifier allowlists and challenge consistency\n";

    $stage = 'delete and cascade';
    foreach (array_reverse($records) as [$table, $dao, $id, $changes]) {
        check($dao->delete($id), "$table delete failed.");
        check($dao->getById($id) === null, "$table deleted record still exists.");
        check(!$dao->delete($id) && !$dao->update($id, $changes), 'Missing record handling failed.');
        echo "PASS: $table create, read all/by ID, update and delete\n";
    }
    $cascadeAttempt = $attempts->create(['user_id' => 1, 'challenge_id' => $otherChallenge, 'score' => 0, 'completed_at' => '2026-11-02 10:00:00']);
    $cascadeAnswer = $answers->create(['attempt_id' => $cascadeAttempt, 'challenge_item_id' => $otherItem, 'selected_position' => 1]);
    $attempts->delete($cascadeAttempt);
    check($answers->getById($cascadeAnswer) === null, 'Attempt answer cascade failed.');
    $connection->rollBack();
    foreach ($daos as $table => $dao) {
        check($dao->getAll() === $snapshots[$table], "$table changed after rollback.");
    }
    echo "PASS: cascade and transaction rollback; test records were not saved\n";
} catch (Throwable $error) {
    if ($connection !== null && $connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, "FAIL at $stage (" . get_class($error) . "). Check configuration/schema or inspect this stage.\n");
    exit(1);
}
