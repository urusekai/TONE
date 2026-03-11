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
    app_error('POST 요청만 허용됩니다.', 405);
}

// 로그인 여부 확인
$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    app_error('로그인이 필요합니다.', 401);
}

try {
    $pdo = Database::getConnection();

    // 세션 사용자 계정 삭제
    $stmt = $pdo->prepare('DELETE FROM users WHERE user_uuid = :user_uuid LIMIT 1');
    $stmt->execute(['user_uuid' => $userUuid]);

    if ($stmt->rowCount() < 1) {
        app_error('삭제할 회원 정보를 찾을 수 없습니다.', 404);
    }

    // 세션 데이터 제거
    $_SESSION = [];

    // 세션 쿠키 제거
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    // 세션 종료
    session_destroy();

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '회원 탈퇴 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}
