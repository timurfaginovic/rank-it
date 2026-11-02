<?php

require_once __DIR__ . '/BaseDao.php';

class ChallengeItemDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('challenge_items', ['challenge_id', 'item_text', 'correct_position'], $connection);
    }

    protected function validateReferences(array $data, ?int $id = null): void
    {
        if ($id === null) {
            return;
        }
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM attempt_items ai JOIN attempts a ON a.id = ai.attempt_id WHERE ai.challenge_item_id = :id AND a.challenge_id <> :challenge_id');
        $statement->execute(['id' => $id, 'challenge_id' => $data['challenge_id']]);
        if ($statement->fetchColumn() > 0) {
            throw new InvalidArgumentException('This item is referenced by attempts for another challenge.');
        }
    }
}
