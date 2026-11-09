<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';

class UserService extends BaseService
{
    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? Database::getConnection();
        $this->dao = new UserDao($this->connection);
    }

    public function getAll(): array
    {
        return array_map([$this, 'withoutPassword'], parent::getAll());
    }

    public function getById(int $id): array
    {
        return $this->withoutPassword(parent::getById($id));
    }

    private function withoutPassword(array $row): array
    {
        unset($row['password_hash']);
        return $row;
    }

    private function validate(array $data, ?int $id = null): array
    {
        $this->fields($data, ['name', 'email', 'password', 'role'], $id === null ? ['name', 'email', 'password'] : []);
        if (array_key_exists('name', $data)) {
            $data['name'] = self::text($data['name'], 'name', 100);
        }
        if (array_key_exists('email', $data)) {
            $data['email'] = self::text($data['email'], 'email', 254);
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ServiceException('Provide a valid email address.');
            }
            $existing = $this->dao->getByEmail($data['email']);
            if ($existing && (int) $existing['id'] !== $id) {
                throw new ServiceException('That email is already in use.');
            }
        }
        if (array_key_exists('role', $data) && !in_array($data['role'], ['user', 'admin'], true)) {
            throw new ServiceException('role must be user or admin.');
        }
        if (array_key_exists('password', $data)) {
            if (!is_string($data['password']) || strlen($data['password']) < 8 || strlen($data['password']) > 72 || str_contains($data['password'], "\0")) {
                throw new ServiceException('password must contain 8 to 72 bytes without null characters.');
            }
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        return $data;
    }

    public function create(array $data): array
    {
        return $this->getById($this->dao->create($this->validate($data)));
    }

    public function update(int $id, array $data): array
    {
        $this->getById($id);
        $this->dao->update($id, $this->validate($data, $id));
        return $this->getById($id);
    }
}
