<?php

require_once __DIR__ . '/../../bootstrap.php';

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

$plan = trim((string) ($payload['plan'] ?? ''));
if (!in_array($plan, ['basic', 'pro'], true)) {
    http_response_code(422);
    echo json_encode(['message' => '이용권 값이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    $updateStmt = $pdo->prepare(
        'UPDATE users
         SET membership_plan = :plan
         WHERE user_uuid = :user_uuid'
    );
    $updateStmt->execute([
        'plan' => $plan,
        'user_uuid' => $userUuid
    ]);

    $userStmt = $pdo->prepare(
        'SELECT user_uuid, id, email, nickname, profile_color, provider, membership_plan
         FROM users
         WHERE user_uuid = :user_uuid
         LIMIT 1'
    );
    $userStmt->execute(['user_uuid' => $userUuid]);
    $user = $userStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['message' => '사용자 정보를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'user_uuid' => $user['user_uuid'],
            'id' => $user['id'],
            'email' => $user['email'],
            'nickname' => $user['nickname'],
            'provider' => $user['provider'],
            'profileColor' => $user['profile_color'],
            'membershipPlan' => $user['membership_plan']
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '이용권 변경 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}

