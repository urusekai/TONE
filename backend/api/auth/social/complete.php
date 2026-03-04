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

// JSON 본문 파싱
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['message' => '잘못된 요청 본문입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 입력값 정리
$provider = trim((string) ($payload['provider'] ?? ''));
$providerId = trim((string) ($payload['providerId'] ?? ''));
$email = trim((string) ($payload['email'] ?? ''));
$nickname = trim((string) ($payload['nickname'] ?? ''));

// provider 검증
if (!in_array($provider, ['kakao', 'google', 'naver'], true)) {
    http_response_code(422);
    echo json_encode(['message' => '지원하지 않는 소셜 로그인 유형입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// provider_id 검증
if ($providerId === '') {
    http_response_code(422);
    echo json_encode(['message' => '소셜 사용자 정보가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
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

    // provider + provider_id 중복 확인
    $providerStmt = $pdo->prepare(
        'SELECT user_uuid, id, email, nickname, provider
         FROM users
         WHERE provider = :provider AND provider_id = :provider_id
         LIMIT 1'
    );
    $providerStmt->execute([
        'provider' => $provider,
        'provider_id' => $providerId
    ]);
    $foundByProvider = $providerStmt->fetch();

    if ($foundByProvider) {
        $_SESSION['user_uuid'] = $foundByProvider['user_uuid'];
        echo json_encode([
            'success' => true,
            'user' => $foundByProvider
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 이메일 중복 확인
    $emailStmt = $pdo->prepare('SELECT user_uuid FROM users WHERE email = :email LIMIT 1');
    $emailStmt->execute(['email' => $email]);
    if ($emailStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => '이미 가입된 이메일입니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 신규 소셜 계정 생성
    $userUuid = generateUuidV4();
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (user_uuid, id, email, password_hash, nickname, provider, provider_id)
         VALUES (:user_uuid, :id, :email, :password_hash, :nickname, :provider, :provider_id)'
    );
    $insertStmt->execute([
        'user_uuid' => $userUuid,
        'id' => null,
        'email' => $email,
        'password_hash' => null,
        'nickname' => $nickname,
        'provider' => $provider,
        'provider_id' => $providerId
    ]);

    $_SESSION['user_uuid'] = $userUuid;

    echo json_encode([
        'success' => true,
        'user' => [
            'user_uuid' => $userUuid,
            'id' => null,
            'email' => $email,
            'nickname' => $nickname,
            'provider' => $provider
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '소셜 회원가입 처리 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}
