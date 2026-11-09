<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ChallengeItemDao.php';
require_once __DIR__ . '/../dao/ChallengeDao.php';
require_once __DIR__ . '/../dao/AttemptDao.php';

class ChallengeItemService extends BaseService
{
    private ChallengeDao $challenges;
    private AttemptDao $attempts;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? Database::getConnection();
        $this->dao = new ChallengeItemDao($this->connection);
        $this->challenges = new ChallengeDao($this->connection);
        $this->attempts = new AttemptDao($this->connection);
    }

    private function editable(int $id): void
    {
        $challenge = $this->challenges->getByIdForUpdate($id);
        if (!$challenge) {
            throw new ServiceException('Parent challenge not found.', 404);
        }
        if ($challenge['status'] === 'Published' || $this->attempts->getByChallengeId($id)) {
            throw new ServiceException('Items of published challenges or challenges with attempts cannot be edited individually.');
        }
    }

    private function validate(array $data, ?array $existing = null): array
    {
        $this->fields($data, ['challenge_id', 'item_text', 'correct_position'], $existing === null ? ['challenge_id', 'item_text', 'correct_position'] : []);
        $record = array_merge($existing ?? [], $data);
        self::positiveId($record['challenge_id']);
        if ($existing && $record['challenge_id'] !== $existing['challenge_id']) {
            throw new ServiceException('Items cannot be moved between challenges.');
        }
        $this->editable($record['challenge_id']);
        $record['item_text'] = self::text($record['item_text'], 'item_text', 100);
        if (!is_int($record['correct_position']) || $record['correct_position'] < 1 || $record['correct_position'] > 5) {
            throw new ServiceException('correct_position must be an integer from 1 to 5.');
        }
        foreach ($this->dao->getByChallengeId($record['challenge_id']) as $item) {
            if ($existing && $item['id'] === $existing['id']) {
                continue;
            }
            if ($item['correct_position'] === $record['correct_position'] || mb_strtolower($item['item_text']) === mb_strtolower($record['item_text'])) {
                throw new ServiceException('That position or item text is already used in this challenge.');
            }
        }
        return array_intersect_key($record, array_flip(['challenge_id', 'item_text', 'correct_position']));
    }

    public function create(array $data): array
    {
        return $this->transaction(fn() => $this->getById($this->dao->create($this->validate($data))));
    }

    public function update(int $id, array $data): array
    {
        return $this->transaction(function () use ($id, $data) {
            $record = $this->validate($data, $this->getById($id));
            $this->dao->update($id, $record);
            return $this->getById($id);
        });
    }

    public function delete(int $id): array
    {
        return $this->transaction(function () use ($id) {
            $record = $this->getById($id);
            $this->editable((int) $record['challenge_id']);
            return parent::delete($id);
        });
    }
}
