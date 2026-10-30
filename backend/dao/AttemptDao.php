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
}
