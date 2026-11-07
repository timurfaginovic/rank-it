<?php

require_once __DIR__ . '/../services/ServiceException.php';

class Api
{
    public static function body(): array
    {
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            throw new ServiceException('Send a JSON object with Content-Type: application/json.');
        }
        try {
            $body = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new ServiceException('The request contains malformed JSON.');
        }
        if (!$body instanceof stdClass) {
            throw new ServiceException('The request body must be a JSON object.');
        }
        return (array) $body;
    }

    public static function id(string $value): int
    {
        if (!ctype_digit($value) || strlen($value) > 10 || (int) $value < 1 || (int) $value > 4294967295) {
            throw new ServiceException('IDs must be positive integers.');
        }
        return (int) $value;
    }

    public static function run(callable $operation, int $status = 200): void
    {
        try {
            Flight::json(['data' => $operation()], $status);
        } catch (Throwable $error) {
            self::error($error);
        }
    }

    public static function error(Throwable $error): void
    {
        $status = 500;
        $message = 'The server could not complete this request.';
        if ($error instanceof ServiceException) {
            $status = $error->status;
            $message = $error->getMessage();
        } elseif ($error instanceof InvalidArgumentException) {
            $status = 400;
            $message = $error->getMessage();
        } elseif ($error instanceof PDOException && in_array($error->errorInfo[1] ?? 0, [1048, 1062, 1265, 1364, 1366, 1406, 1451, 1452, 3819], true)) {
            $status = 400;
            $message = 'The record violates a data constraint or is referenced by another record.';
        }
        Flight::response()->clear();
        Flight::json(['error' => $message], $status);
    }
}
