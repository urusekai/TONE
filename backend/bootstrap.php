<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Auth.php';

use Dotenv\Dotenv;

session_start(); 

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

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
