<?php

require_once __DIR__ . '/BaseDao.php';

class AttemptItemDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('attempt_items', ['attempt_id', 'challenge_item_id', 'selected_position'], $connection);
    }

    protected function validateReferences(array $data, ?int $id = null): void
    {
        if (!isset($data['attempt_id'], $data['challenge_item_id'])) {
            throw new InvalidArgumentException('An attempt ID and challenge item ID are required.');
        }
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM attempts a JOIN challenge_items ci ON ci.challenge_id = a.challenge_id WHERE a.id = :attempt_id AND ci.id = :challenge_item_id');
        $statement->execute(['attempt_id' => $data['attempt_id'], 'challenge_item_id' => $data['challenge_item_id']]);
        if ((int) $statement->fetchColumn() !== 1) {
            throw new InvalidArgumentException('The answer item must belong to the attempt challenge.');
        }
    }
}
