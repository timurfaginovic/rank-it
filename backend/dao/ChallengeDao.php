<?php

require_once __DIR__ . '/BaseDao.php';

class ChallengeDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('challenges', ['title', 'description', 'challenge_date', 'status'], $connection);
    }

    public function getPublishedByDate(string $date, int $excludeId = 0): ?array
    {
        $statement = $this->connection->prepare("SELECT * FROM challenges WHERE challenge_date = :date AND status = 'Published' AND id <> :exclude_id FOR UPDATE");
        $statement->execute(['date' => $date, 'exclude_id' => $excludeId]);
        return $statement->fetch() ?: null;
    }

    public function getByIdForUpdate(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM challenges WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }
}
