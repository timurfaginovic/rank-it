<?php

require_once __DIR__ . '/ServiceException.php';
require_once __DIR__ . '/../dao/Database.php';

abstract class BaseService
{
    protected PDO $connection;
    protected BaseDao $dao;

    public function getAll(): array
    {
        return $this->dao->getAll();
    }

    public function getById(int $id): array
    {
        self::positiveId($id);
        $row = $this->dao->getById($id);
        if ($row === null) {
            throw new ServiceException('Record not found.', 404);
        }
        return $row;
    }

    public function delete(int $id): array
    {
        $this->getById($id);
        $this->dao->delete($id);
        return ['id' => $id];
    }

    protected function fields(array $data, array $allowed, array $required = []): void
    {
        if (!$data || array_diff(array_keys($data), $allowed)) {
            throw new ServiceException('Provide a non-empty object containing only supported fields.');
        }
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ServiceException("$field is required.");
            }
        }
    }

    protected static function text(mixed $value, string $field, int $max): string
    {
        if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $max) {
            throw new ServiceException("$field must be non-empty text, up to $max characters.");
        }
        return trim($value);
    }

    public static function positiveId(mixed $value): int
    {
        if (!is_int($value) || $value < 1 || $value > 4294967295) {
            throw new ServiceException('IDs must be positive integers.');
        }
        return $value;
    }

    protected function transaction(callable $operation): mixed
    {
        $ownsTransaction = !$this->connection->inTransaction();
        if ($ownsTransaction) {
            $this->connection->beginTransaction();
        }
        try {
            $result = $operation();
            if ($ownsTransaction) {
                $this->connection->commit();
            }
            return $result;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $error;
        }
    }
}
