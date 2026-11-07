<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/AttemptDao.php';
require_once __DIR__ . '/../dao/AttemptItemDao.php';
require_once __DIR__ . '/../dao/ChallengeDao.php';
require_once __DIR__ . '/../dao/ChallengeItemDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class AttemptService extends BaseService
{
    private AttemptItemDao $answers;
    private ChallengeDao $challenges;
    private ChallengeItemDao $items;
    private UserDao $users;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? Database::getConnection();
        $this->dao = new AttemptDao($this->connection);
        $this->answers = new AttemptItemDao($this->connection);
        $this->challenges = new ChallengeDao($this->connection);
        $this->items = new ChallengeItemDao($this->connection);
        $this->users = new UserDao($this->connection);
    }

    public static function calculateScore(array $items, mixed $ranking): int
    {
        if (!is_array($ranking) || !array_is_list($ranking) || count($ranking) !== 5 || count($items) !== 5) {
            throw new ServiceException('ranking must be an ordered list of five challenge item IDs.');
        }
        foreach ($ranking as $id) {
            self::positiveId($id);
        }
        $expected = array_map('intval', array_column($items, 'id'));
        $selected = $ranking;
        sort($expected);
        sort($selected);
        if ($selected !== $expected) {
            throw new ServiceException('ranking must include every challenge item exactly once.');
        }
        $positions = array_column($items, 'correct_position', 'id');
        $score = 0;
        foreach ($ranking as $index => $id) {
            if ((int) $positions[$id] === $index + 1) {
                $score += 20;
            }
        }
        return $score;
    }

    private function saveAnswers(int $id, array $ranking): void
    {
        foreach ($this->answers->getByAttemptId($id) as $answer) {
            $this->answers->delete((int) $answer['id']);
        }
        foreach ($ranking as $index => $itemId) {
            $this->answers->create(['attempt_id' => $id, 'challenge_item_id' => $itemId, 'selected_position' => $index + 1]);
        }
    }

    public function create(array $data): array
    {
        $this->fields($data, ['user_id', 'challenge_id', 'ranking'], ['user_id', 'challenge_id', 'ranking']);
        self::positiveId($data['user_id']);
        self::positiveId($data['challenge_id']);
        return $this->transaction(function () use ($data) {
            $challenge = $this->challenges->getByIdForUpdate($data['challenge_id']);
            if (!$this->users->getById($data['user_id']) || !$challenge) {
                throw new ServiceException('User or challenge not found.', 404);
            }
            if ($challenge['status'] !== 'Published') {
                throw new ServiceException('Only published challenges accept completed attempts.');
            }
            if ($this->dao->getByUserAndChallenge($data['user_id'], $data['challenge_id'])) {
                throw new ServiceException('This user already has an attempt for this challenge.');
            }
            $score = self::calculateScore($this->items->getByChallengeId($data['challenge_id']), $data['ranking']);
            $id = $this->dao->create(['user_id' => $data['user_id'], 'challenge_id' => $data['challenge_id'], 'score' => $score, 'completed_at' => date('Y-m-d H:i:s')]);
            $this->saveAnswers($id, $data['ranking']);
            return $this->getById($id);
        });
    }

    public function update(int $id, array $data): array
    {
        $this->fields($data, ['ranking'], ['ranking']);
        return $this->transaction(function () use ($id, $data) {
            $this->getById($id);
            $record = $this->dao->getByIdForUpdate($id);
            $challenge = $this->challenges->getByIdForUpdate((int) $record['challenge_id']);
            if ($challenge['status'] !== 'Published') {
                throw new ServiceException('Only published challenges accept completed attempts.');
            }
            $score = self::calculateScore($this->items->getByChallengeId((int) $record['challenge_id']), $data['ranking']);
            $this->saveAnswers($id, $data['ranking']);
            $this->dao->update($id, ['score' => $score]);
            return $this->getById($id);
        });
    }
}
