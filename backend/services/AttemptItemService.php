<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/AttemptService.php';

class AttemptItemService extends BaseService
{
    private AttemptDao $attempts;
    private ChallengeItemDao $items;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? Database::getConnection();
        $this->dao = new AttemptItemDao($this->connection);
        $this->attempts = new AttemptDao($this->connection);
        $this->items = new ChallengeItemDao($this->connection);
    }

    private function editable(int $id): array
    {
        self::positiveId($id);
        $attempt = $this->attempts->getByIdForUpdate($id);
        if (!$attempt) {
            throw new ServiceException('Parent attempt not found.', 404);
        }
        if (count($this->dao->getByAttemptId($id)) === 5) {
            throw new ServiceException('Completed answers are protected. Correct the whole ranking through the attempt endpoint.');
        }
        return $attempt;
    }

    private function validate(array $data, ?array $existing = null): array
    {
        $this->fields($data, $existing ? ['challenge_item_id', 'selected_position'] : ['attempt_id', 'challenge_item_id', 'selected_position'], $existing ? [] : ['attempt_id', 'challenge_item_id', 'selected_position']);
        $record = array_merge($existing ?? [], $data);
        self::positiveId($record['attempt_id']);
        $attempt = $this->editable($record['attempt_id']);
        self::positiveId($record['challenge_item_id']);
        $item = $this->items->getById($record['challenge_item_id']);
        if (!$item) {
            throw new ServiceException('Challenge item not found.', 404);
        }
        if ((int) $item['challenge_id'] !== (int) $attempt['challenge_id']) {
            throw new ServiceException('The item belongs to another challenge.');
        }
        if (!is_int($record['selected_position']) || $record['selected_position'] < 1 || $record['selected_position'] > 5) {
            throw new ServiceException('selected_position must be an integer from 1 to 5.');
        }
        foreach ($this->dao->getByAttemptId($record['attempt_id']) as $answer) {
            if ($existing && $answer['id'] === $existing['id']) {
                continue;
            }
            if ($answer['selected_position'] === $record['selected_position'] || $answer['challenge_item_id'] === $record['challenge_item_id']) {
                throw new ServiceException('That position or item is already selected.');
            }
        }
        return array_intersect_key($record, array_flip(['attempt_id', 'challenge_item_id', 'selected_position']));
    }

    private function refreshScore(int $id): void
    {
        $attempt = $this->attempts->getById($id);
        $answers = $this->dao->getByAttemptId($id);
        if (count($answers) === 5 && (new ChallengeDao($this->connection))->getById((int) $attempt['challenge_id'])['status'] !== 'Published') {
            throw new ServiceException('Only published challenges accept completed attempts.');
        }
        $score = count($answers) === 5
            ? AttemptService::calculateScore($this->items->getByChallengeId((int) $attempt['challenge_id']), array_map('intval', array_column($answers, 'challenge_item_id')))
            : 0;
        $data = ['score' => $score];
        if (count($answers) === 5) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        $this->attempts->update($id, $data);
    }

    public function create(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $record = $this->validate($data);
            $id = $this->dao->create($record);
            $this->refreshScore($record['attempt_id']);
            return $this->getById($id);
        });
    }

    public function update(int $id, array $data): array
    {
        return $this->transaction(function () use ($id, $data) {
            $record = $this->validate($data, $this->getById($id));
            $this->dao->update($id, $record);
            $this->refreshScore($record['attempt_id']);
            return $this->getById($id);
        });
    }

    public function delete(int $id): array
    {
        return $this->transaction(function () use ($id) {
            $record = $this->getById($id);
            $this->editable((int) $record['attempt_id']);
            $result = parent::delete($id);
            $this->refreshScore((int) $record['attempt_id']);
            return $result;
        });
    }
}
