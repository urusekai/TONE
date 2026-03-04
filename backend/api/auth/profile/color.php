<?php

require_once __DIR__ . '/../../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'POST 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    http_response_code(401);
    echo json_encode(['message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['message' => '잘못된 요청 본문입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$profileColor = trim((string) ($payload['profileColor'] ?? ''));
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $profileColor)) {
    http_response_code(422);
    echo json_encode(['message' => '프로필 색상 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare(
        'UPDATE users
         SET profile_color = :profile_color
         WHERE user_uuid = :user_uuid'
    );
    $stmt->execute([
        'profile_color' => strtoupper($profileColor),
        'user_uuid' => $userUuid
    ]);

    echo json_encode([
        'success' => true,
        'profileColor' => strtoupper($profileColor)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '프로필 색상 변경 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}

