<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/DailyTone.php';

use Dotenv\Dotenv;

session_start(); 

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$appTimezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'Asia/Seoul'));
if ($appTimezone === '' || @date_default_timezone_set($appTimezone) === false) {
    date_default_timezone_set('Asia/Seoul');
}

function app_error(string $message, int $status = 400, array $payload = []): never
{
    http_response_code(200);
    echo json_encode(
        array_merge(
            [
                'success' => false,
                'status' => $status,
                'message' => $message,
            ],
            $payload
        ),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
