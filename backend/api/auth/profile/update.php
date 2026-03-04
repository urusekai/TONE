<?php

require_once __DIR__ . '/../../../bootstrap.php';

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

// 로그인 여부 확인
$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    http_response_code(401);
    echo json_encode(['message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
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
$email = trim((string) ($payload['email'] ?? ''));
$nickname = trim((string) ($payload['nickname'] ?? ''));
$password = (string) ($payload['password'] ?? '');

// 필수값 검증
if ($email === '' || $nickname === '') {
    http_response_code(422);
    echo json_encode(['message' => '이메일과 닉네임은 필수입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 이메일 형식 검증
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['message' => '이메일 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 닉네임 길이 검증 (2~5자)
$nicknameLength = function_exists('mb_strlen') ? mb_strlen($nickname) : strlen($nickname);
if ($nicknameLength < 2 || $nicknameLength > 5) {
    http_response_code(422);
    echo json_encode(['message' => '닉네임은 2~5자로 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 비밀번호가 들어온 경우만 검증
if ($password !== '' && strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['message' => '비밀번호는 8자 이상이어야 합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    // 현재 로그인 사용자 확인
    $userStmt = $pdo->prepare(
        'SELECT user_uuid, id, email, nickname, provider
         FROM users
         WHERE user_uuid = :user_uuid
         LIMIT 1'
    );
    $userStmt->execute(['user_uuid' => $userUuid]);
    $currentUser = $userStmt->fetch();

    if (!$currentUser) {
        http_response_code(404);
        echo json_encode(['message' => '사용자 정보를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 이메일 중복 확인(본인 제외)
    $emailStmt = $pdo->prepare(
        'SELECT user_uuid
         FROM users
         WHERE email = :email AND user_uuid <> :user_uuid
         LIMIT 1'
    );
    $emailStmt->execute([
        'email' => $email,
        'user_uuid' => $userUuid
    ]);
    if ($emailStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => '이미 가입된 이메일입니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 비밀번호 포함/미포함 분기 업데이트
    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare(
            'UPDATE users
             SET email = :email,
                 nickname = :nickname,
                 password_hash = :password_hash
             WHERE user_uuid = :user_uuid'
        );
        $updateStmt->execute([
            'email' => $email,
            'nickname' => $nickname,
            'password_hash' => $passwordHash,
            'user_uuid' => $userUuid
        ]);
    } else {
        $updateStmt = $pdo->prepare(
            'UPDATE users
             SET email = :email,
                 nickname = :nickname
             WHERE user_uuid = :user_uuid'
        );
        $updateStmt->execute([
            'email' => $email,
            'nickname' => $nickname,
            'user_uuid' => $userUuid
        ]);
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'user_uuid' => $currentUser['user_uuid'],
            'id' => $currentUser['id'],
            'email' => $email,
            'nickname' => $nickname,
            'provider' => $currentUser['provider']
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '프로필 수정 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}

