<?php

require_once __DIR__ . '/BaseDao.php';

class AttemptDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('attempts', ['user_id', 'challenge_id', 'score', 'completed_at'], $connection);
    }

    protected function validateReferences(array $data, ?int $id = null): void
    {
        if ($id === null) {
            return;
        }
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM attempt_items ai JOIN challenge_items ci ON ci.id = ai.challenge_item_id WHERE ai.attempt_id = :id AND ci.challenge_id <> :challenge_id');
        $statement->execute(['id' => $id, 'challenge_id' => $data['challenge_id']]);
        if ($statement->fetchColumn() > 0) {
            throw new InvalidArgumentException('The existing answers belong to another challenge.');
        }
    }

    public function getByChallengeId(int $id): array
    {
        $statement = $this->connection->prepare('SELECT * FROM attempts WHERE challenge_id = :id ORDER BY id');
        $statement->execute(['id' => $id]);
        return $statement->fetchAll();
    }

    public function getByUserAndChallenge(int $userId, int $challengeId): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM attempts WHERE user_id = :user_id AND challenge_id = :challenge_id');
        $statement->execute(['user_id' => $userId, 'challenge_id' => $challengeId]);
        return $statement->fetch() ?: null;
    }

    public function getByIdForUpdate(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM attempts WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }
}
