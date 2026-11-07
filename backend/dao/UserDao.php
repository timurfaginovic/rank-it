<?php

require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('users', ['name', 'email', 'password_hash', 'role'], $connection);
    }

    public function getByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        return $statement->fetch() ?: null;
    }
}
