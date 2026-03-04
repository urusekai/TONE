<?php

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 프리플라이트 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'POST 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// JSON 본문 파싱
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['message' => '잘못된 요청 본문입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 입력값 정리
$id = trim((string) ($payload['id'] ?? ''));
$password = (string) ($payload['password'] ?? '');

// 필수값 검증
if ($id === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['message' => '아이디와 비밀번호를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    // 일반(local) 계정만 조회
    $stmt = $pdo->prepare(
        'SELECT user_uuid, id, email, password_hash, nickname, provider
         FROM users
         WHERE id = :id AND provider = :provider
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $id,
        'provider' => 'local'
    ]);

    $user = $stmt->fetch();

    // 아이디/비밀번호 검증
    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['message' => '아이디 또는 비밀번호가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 로그인 성공: 세션에 사용자 식별값 저장
    $_SESSION['user_uuid'] = $user['user_uuid'];

    echo json_encode([
        'success' => true,
        'user' => [
            'user_uuid' => $user['user_uuid'],
            'id' => $user['id'],
            'email' => $user['email'],
            'nickname' => $user['nickname'],
            'provider' => $user['provider']
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => '로그인 처리 중 서버 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
