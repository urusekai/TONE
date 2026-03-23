<?php

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 프리플라이트 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    app_error('GET 요청만 허용됩니다.', 405);
}

// 로그인 여부 확인
$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    app_error('로그인이 필요합니다.', 401);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare(
        'SELECT user_uuid, id, email, nickname, profile_color, provider, membership_plan
         FROM users
         WHERE user_uuid = :user_uuid
         LIMIT 1'
    );
    $stmt->execute(['user_uuid' => $userUuid]);

    $user = $stmt->fetch();
    if (!$user) {
        app_error('사용자 정보를 찾을 수 없습니다.', 404);
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
    echo json_encode(['message' => '사용자 정보를 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
