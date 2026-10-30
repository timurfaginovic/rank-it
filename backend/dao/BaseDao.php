<?php

require_once __DIR__ . '/Database.php';

abstract class BaseDao
{
    protected PDO $connection;
    private string $table;
    private array $columns;

    protected function __construct(string $table, array $columns, ?PDO $connection = null)
    {
        $this->table = $table;
        $this->columns = $columns;
        $this->connection = $connection ?? Database::getConnection();
    }

    public function getAll(): array
    {
        $statement = $this->connection->prepare("SELECT * FROM {$this->table} ORDER BY id");
        $statement->execute();
        return $statement->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $statement = $this->connection->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $this->validateColumns($data);
        $this->validateReferences($data);
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $statement = $this->connection->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $statement->execute($data);
        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->validateColumns($data);
        $existing = $this->getById($id);
        if ($existing === null) {
            return false;
        }
        $this->validateReferences(array_merge($existing, $data), $id);
        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = "$column = :$column";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $assignments) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $data['id'] = $id;
        return $statement->execute($data);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $statement->execute(['id' => $id]);
        return $statement->rowCount() > 0;
    }

    private function validateColumns(array $data): void
    {
        if (!$data || array_diff(array_keys($data), $this->columns)) {
            throw new InvalidArgumentException('Provide non-empty data with only the allowed entity columns.');
        }
    }

    protected function validateReferences(array $data, ?int $id = null): void
    {
    }
}
