<?php

if (PHP_SAPI !== 'cli') exit('Run from the command line.');
require_once __DIR__ . '/../dao/AttemptDao.php';
$base = $argv[1] ?? 'http://127.0.0.1:8000';
if (!preg_match('~^http://127\.0\.0\.1:[0-9]+$~', $base)) exit("Use a localhost API URL.\n");

function requestM3(string $method, string $path, mixed $data = null, int $expected = 200, bool $raw = false): array
{
    global $base;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 20]);
    if ($data !== null) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $raw ? $data : json_encode($data));
    }
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($status !== $expected) throw new RuntimeException("$method $path returned $status; expected $expected.");
    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($json) || ($expected >= 400 ? !isset($json['error']) : !isset($json['data']))) throw new RuntimeException('Unexpected JSON structure.');
    return $json['data'] ?? $json;
}

$users = $challenges = $attempts = [];
$resources = ['users', 'challenges', 'challenge-items', 'attempts', 'attempt-items'];
$before = [];
$exitCode = 0;
try {
    foreach ($resources as $resource) {
        $before[$resource] = requestM3('GET', '/api/' . $resource);
        requestM3('GET', '/api/' . $resource . '/4294967295', null, 404);
        requestM3('GET', '/api/' . $resource . '/invalid', null, 400);
    }
    $user = requestM3('POST', '/api/users', ['name' => 'M3 HTTP check', 'email' => 'm3-http-' . bin2hex(random_bytes(6)) . '@example.com', 'password' => 'test-only-pass'], 201);
    $users[] = $user['id'];
    if (isset($user['password_hash'])) throw new RuntimeException('Password hash exposed.');
    requestM3('GET', '/api/users/' . $user['id']);
    requestM3('PATCH', '/api/users/' . $user['id'], ['name' => "HTTP O'Reilly"]);
    requestM3('PATCH', '/api/users/' . $user['id'], ['password_hash' => 'bad'], 400);
    requestM3('POST', '/api/users', '{bad', 400, true);
    requestM3('POST', '/api/users', [], 400);
    requestM3('POST', '/api/challenges', ['title' => 'Bad date', 'description' => 'Invalid', 'challenge_date' => "\0"], 400);
    $count = count(requestM3('GET', '/api/challenges'));
    requestM3('POST', '/api/challenges', ['title' => 'Bad publication', 'description' => 'No items', 'challenge_date' => '2091-01-01', 'status' => 'Published'], 400);
    if (count(requestM3('GET', '/api/challenges')) !== $count) throw new RuntimeException('Failed publication left a partial record.');
    $challenge = requestM3('POST', '/api/challenges', ['title' => 'HTTP check', 'description' => 'Smallest first.', 'challenge_date' => '2091-01-01'], 201);
    $challenges[] = $challenge['id'];
    requestM3('GET', '/api/challenges/' . $challenge['id']);
    $later = requestM3('POST', '/api/challenges', ['title'=>'Other published check','description'=>'Smallest first.','challenge_date'=>'2091-01-02','status'=>'Published','items'=>['A','B','C','D','E']], 201);
    $challenges[] = $later['id'];
    requestM3('PATCH', '/api/challenges/' . $challenge['id'], ['challenge_date'=>'2091-01-02','status'=>'Published','items'=>['A','B','C','D','E']], 400);
    requestM3('DELETE', '/api/challenges/' . $later['id']);
    array_pop($challenges);
    requestM3('PATCH', '/api/challenges/' . $challenge['id'], ['title' => 'HTTP updated']);
    $item = requestM3('POST', '/api/challenge-items', ['challenge_id' => $challenge['id'], 'item_text' => 'Temporary item', 'correct_position' => 1], 201);
    requestM3('GET', '/api/challenge-items/' . $item['id']);
    requestM3('PATCH', '/api/challenge-items/' . $item['id'], ['item_text' => 'Updated item']);
    requestM3('DELETE', '/api/challenge-items/' . $item['id']);
    requestM3('PATCH', '/api/challenges/' . $challenge['id'], ['items' => ['One','Two','Three','Four','Five']]);
    $rows = array_values(array_filter(requestM3('GET', '/api/challenge-items'), fn($row) => $row['challenge_id'] === $challenge['id']));
    usort($rows, fn($a,$b) => $a['correct_position'] <=> $b['correct_position']);
    $ranking = array_column($rows, 'id');
    foreach (['Draft', 'Pending'] as $status) {
        requestM3('PATCH', '/api/challenges/' . $challenge['id'], ['status' => $status]);
        $partial = (new AttemptDao())->create(['user_id' => $user['id'], 'challenge_id' => $challenge['id'], 'score' => 0, 'completed_at' => '2091-01-01 00:00:00']);
        $attempts[] = $partial;
        $original = requestM3('GET', '/api/attempts/' . $partial);
        requestM3('PATCH', '/api/attempts/' . $partial, ['ranking' => $ranking], 400);
        $answers = array_filter(requestM3('GET', '/api/attempt-items'), fn($answer) => $answer['attempt_id'] === $partial);
        if (requestM3('GET', '/api/attempts/' . $partial) !== $original || $answers) throw new RuntimeException('Rejected ranking update changed an unpublished attempt.');
        requestM3('DELETE', '/api/attempts/' . $partial);
        array_pop($attempts);
    }
    requestM3('PATCH', '/api/challenges/' . $challenge['id'], ['status' => 'Published']);
    $attempt = requestM3('POST', '/api/attempts', ['user_id' => $user['id'], 'challenge_id' => $challenge['id'], 'ranking' => $ranking], 201);
    $attempts[] = $attempt['id'];
    if ($attempt['score'] !== 100) throw new RuntimeException('HTTP score incorrect.');
    requestM3('GET', '/api/attempts/' . $attempt['id']);
    [$ranking[0],$ranking[1]] = [$ranking[1],$ranking[0]];
    $updated = requestM3('PATCH', '/api/attempts/' . $attempt['id'], ['ranking' => $ranking]);
    if ($updated['score'] !== 60) throw new RuntimeException('HTTP score correction incorrect.');
    $answers = array_values(array_filter(requestM3('GET','/api/attempt-items'),fn($a)=>$a['attempt_id']===$attempt['id']));
    requestM3('DELETE', '/api/attempt-items/' . $answers[0]['id'], null, 400);
    requestM3('DELETE', '/api/attempts/' . $attempt['id']);
    $attempts = [];
    // A maintenance fixture exercises answer CRUD without weakening completed-attempt protection.
    $partial = (new AttemptDao())->create(['user_id'=>$user['id'],'challenge_id'=>$challenge['id'],'score'=>0,'completed_at'=>'2091-01-01 00:00:00']);
    $attempts[] = $partial;
    foreach (['abc', null, [], true, 0, -1, 1.5, (string) $partial, 4294967296] as $invalidId) {
        requestM3('POST', '/api/attempt-items', ['attempt_id' => $invalidId, 'challenge_item_id' => $ranking[0], 'selected_position' => 1], 400);
    }
    requestM3('POST', '/api/attempt-items', ['attempt_id' => 4294967295, 'challenge_item_id' => $ranking[0], 'selected_position' => 1], 404);
    $answer = requestM3('POST', '/api/attempt-items', ['attempt_id'=>$partial,'challenge_item_id'=>$ranking[0],'selected_position'=>1], 201);
    requestM3('GET', '/api/attempt-items/' . $answer['id']);
    requestM3('PATCH', '/api/attempt-items/' . $answer['id'], ['selected_position'=>2]);
    requestM3('DELETE', '/api/attempt-items/' . $answer['id']);
    requestM3('DELETE', '/api/attempt-items/' . $answer['id'], null, 404);
    requestM3('DELETE', '/api/attempts/' . $partial);
    $attempts=[];
    requestM3('DELETE', '/api/challenges/' . $challenge['id']); $challenges=[];
    requestM3('DELETE', '/api/users/' . $user['id']); $users=[];
    requestM3('DELETE', '/api/users/' . $user['id'], null, 404);
    requestM3('GET', '/api/no-such-endpoint', null, 404);
    requestM3('POST', '/api/external/population-draft', ['year' => '2023'], 400);
    requestM3('GET', '/config/config.php', null, 404);
    requestM3('GET', '/docs/../../config/config.php', null, 404);
    foreach ($resources as $resource) {
        if (requestM3('GET', '/api/' . $resource) !== $before[$resource]) throw new RuntimeException('Original records changed: ' . $resource);
    }
    echo "PASS: all 25 CRUD operations; 200/201/400/404 JSON, invalid attempt IDs, unpublished ranking rejection, hash protection, publication rollback, ranking correction, protected answers, public-root boundaries and fixture cleanup\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    foreach ($attempts as $id) requestM3('DELETE','/api/attempts/'.$id);
    foreach ($challenges as $id) requestM3('DELETE','/api/challenges/'.$id);
    foreach ($users as $id) requestM3('DELETE','/api/users/'.$id);
}
exit($exitCode);
