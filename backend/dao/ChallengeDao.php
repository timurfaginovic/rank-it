<?php

require_once __DIR__ . '/BaseDao.php';

class ChallengeDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('challenges', ['title', 'description', 'challenge_date', 'status'], $connection);
    }
}
