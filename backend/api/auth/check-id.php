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
    http_response_code(405);
    echo json_encode(['message' => 'GET 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 쿼리 파라미터에서 아이디 가져오기
$id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';

// 필수값 검증
if ($id === '') {
    http_response_code(400);
    echo json_encode(['message' => '아이디를 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 아이디 형식 검증
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $id)) {
    http_response_code(400);
    echo json_encode(['message' => '아이디는 4~20자의 영문, 숫자, 언더스코어(_)만 가능합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    // 같은 아이디가 이미 있는지 조회
    $stmt = $pdo->prepare('SELECT user_uuid FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    $exists = (bool) $stmt->fetch();

    echo json_encode([
        'id' => $id,
        'available' => !$exists
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'message' => '중복확인 중 서버 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
