<?php

require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    public function __construct(?PDO $connection = null)
    {
        parent::__construct('users', ['name', 'email', 'password_hash', 'role'], $connection);
    }
}
