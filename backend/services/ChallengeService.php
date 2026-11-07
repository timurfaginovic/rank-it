<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ChallengeDao.php';
require_once __DIR__ . '/../dao/ChallengeItemDao.php';
require_once __DIR__ . '/../dao/AttemptDao.php';

class ChallengeService extends BaseService
{
    private ChallengeItemDao $items;
    private AttemptDao $attempts;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? Database::getConnection();
        $this->dao = new ChallengeDao($this->connection);
        $this->items = new ChallengeItemDao($this->connection);
        $this->attempts = new AttemptDao($this->connection);
    }

    private function validate(array $data, bool $creating): array
    {
        $this->fields($data, ['title', 'description', 'challenge_date', 'status', 'items'], $creating ? ['title', 'description', 'challenge_date'] : []);
        foreach (['title' => 200, 'description' => 2000] as $field => $max) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::text($data[$field], $field, $max);
            }
        }
        if (array_key_exists('challenge_date', $data)) {
            if (!is_string($data['challenge_date']) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $data['challenge_date'])) {
                throw new ServiceException('challenge_date must be a valid YYYY-MM-DD date.');
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['challenge_date']);
            if (!$date || $date->format('Y-m-d') !== $data['challenge_date'] || $date->format('Y') < '1000') {
                throw new ServiceException('challenge_date must be a valid YYYY-MM-DD date.');
            }
        }
        if (array_key_exists('status', $data) && !in_array($data['status'], ['Draft', 'Pending', 'Published'], true)) {
            throw new ServiceException('status must be Draft, Pending or Published.');
        }
        if (array_key_exists('items', $data)) {
            if (!is_array($data['items']) || !array_is_list($data['items']) || count($data['items']) !== 5) {
                throw new ServiceException('items must contain five texts in correct order.');
            }
            $data['items'] = array_map(fn($text) => self::text($text, 'item', 100), $data['items']);
            if (count(array_unique(array_map('mb_strtolower', $data['items']))) !== 5) {
                throw new ServiceException('The five items must be different.');
            }
        }
        return $data;
    }

    private function publish(int $id, array $record): void
    {
        if ($record['status'] !== 'Published') {
            return;
        }
        $other = $this->dao->getPublishedByDate($record['challenge_date'], $id);
        if ($other && (int) $other['id'] !== $id) {
            throw new ServiceException('A published challenge already exists for this date.');
        }
        $items = $this->items->getByChallengeId($id);
        if (array_map('intval', array_column($items, 'correct_position')) !== [1, 2, 3, 4, 5]) {
            throw new ServiceException('Publishing requires five items at positions 1 through 5.');
        }
        $texts = array_map(fn($item) => mb_strtolower(self::text($item['item_text'], 'item_text', 100)), $items);
        if (count(array_unique($texts)) !== 5) {
            throw new ServiceException('Publishing requires five different item texts.');
        }
    }

    private function replaceItems(int $id, array $texts): void
    {
        foreach ($this->items->getByChallengeId($id) as $item) {
            $this->items->delete((int) $item['id']);
        }
        foreach ($texts as $index => $text) {
            $this->items->create(['challenge_id' => $id, 'item_text' => $text, 'correct_position' => $index + 1]);
        }
    }

    public function create(array $data): array
    {
        $data = $this->validate($data, true);
        return $this->transaction(function () use ($data) {
            $texts = $data['items'] ?? null;
            unset($data['items']);
            $data['status'] ??= 'Draft';
            $id = $this->dao->create($data);
            if ($texts !== null) {
                $this->replaceItems($id, $texts);
            }
            $this->publish($id, $data);
            return $this->getById($id);
        });
    }

    public function update(int $id, array $data): array
    {
        $this->getById($id);
        $data = $this->validate($data, false);
        return $this->transaction(function () use ($id, $data) {
            $record = $this->dao->getByIdForUpdate($id);
            if ($this->attempts->getByChallengeId($id)) {
                throw new ServiceException('Challenges with attempts are protected from edits.');
            }
            if (isset($data['items'])) {
                $this->replaceItems($id, $data['items']);
                unset($data['items']);
            }
            if ($data) {
                $this->dao->update($id, $data);
            }
            $this->publish($id, array_merge($record, $data));
            return $this->getById($id);
        });
    }

    public function delete(int $id): array
    {
        $this->getById($id);
        return $this->transaction(function () use ($id) {
            $this->dao->getByIdForUpdate($id);
            if ($this->attempts->getByChallengeId($id)) {
                throw new ServiceException('Delete the related attempts before deleting this challenge.');
            }
            foreach ($this->items->getByChallengeId($id) as $item) {
                $this->items->delete((int) $item['id']);
            }
            return parent::delete($id);
        });
    }
}
