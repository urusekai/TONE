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
$email = trim((string) ($payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');
$nickname = trim((string) ($payload['nickname'] ?? ''));
$profileColor = trim((string) ($payload['profileColor'] ?? ''));

// 필수값 검증
if ($id === '' || $email === '' || $password === '' || $nickname === '') {
    http_response_code(422);
    echo json_encode(['message' => '필수 항목을 모두 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 아이디 형식 검증
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $id)) {
    http_response_code(422);
    echo json_encode(['message' => '아이디는 4~20자의 영문, 숫자, 언더스코어(_)만 가능합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 이메일 형식 검증
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['message' => '이메일 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 비밀번호 길이 검증
if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['message' => '비밀번호는 8자 이상이어야 합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 닉네임 길이 검증 (2~5자)
$nicknameLength = function_exists('mb_strlen') ? mb_strlen($nickname) : strlen($nickname);
if ($nicknameLength < 2 || $nicknameLength > 5) {
    http_response_code(422);
    echo json_encode(['message' => '닉네임은 2~5자로 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 프로필 색상 선택 여부 검증
if ($profileColor === '') {
    http_response_code(422);
    echo json_encode(['message' => '프로필 색상을 선택해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $profileColor)) {
    http_response_code(422);
    echo json_encode(['message' => '프로필 색상 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// UUID v4 생성 함수
function generateUuidV4(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

try {
    $pdo = Database::getConnection();

    // 아이디 중복 확인
    $idCheckStmt = $pdo->prepare('SELECT user_uuid FROM users WHERE id = :id LIMIT 1');
    $idCheckStmt->execute(['id' => $id]);
    if ($idCheckStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => '이미 사용 중인 아이디입니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 이메일 중복 확인
    $emailCheckStmt = $pdo->prepare('SELECT user_uuid FROM users WHERE email = :email LIMIT 1');
    $emailCheckStmt->execute(['email' => $email]);
    if ($emailCheckStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => '이미 가입된 이메일입니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 저장용 데이터 준비
    $userUuid = generateUuidV4();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // 회원가입 정보 저장
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (user_uuid, id, email, password_hash, nickname, profile_color, provider, provider_id)
         VALUES (:user_uuid, :id, :email, :password_hash, :nickname, :profile_color, :provider, :provider_id)'
    );

    $insertStmt->execute([
        'user_uuid' => $userUuid,
        'id' => $id,
        'email' => $email,
        'password_hash' => $passwordHash,
        'nickname' => $nickname,
        'profile_color' => strtoupper($profileColor),
        'provider' => 'local',
        'provider_id' => null
    ]);

    $_SESSION['user_uuid'] = $userUuid;

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'user' => [
            'user_uuid' => $userUuid,
            'id' => $id,
            'email' => $email,
            'nickname' => $nickname,
            'provider' => 'local',
            'profileColor' => strtoupper($profileColor)
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => '회원가입 처리 중 서버 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
