<?php

require_once __DIR__ . '/../../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'GET 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 네이버 앱 설정값 확인
$clientId = trim((string) ($_ENV['NAVER_CLIENT_ID'] ?? ''));
$redirectUri = trim((string) ($_ENV['NAVER_REDIRECT_URI'] ?? ''));

if ($clientId === '' || $redirectUri === '') {
    http_response_code(500);
    echo json_encode(['message' => '네이버 로그인 설정값이 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF 방지를 위한 state 생성/저장
$state = bin2hex(random_bytes(16));
$_SESSION['naver_oauth_state'] = $state;

$query = http_build_query([
    'response_type' => 'code',
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'state' => $state
]);

// 네이버 인증 페이지로 이동
header('Location: https://nid.naver.com/oauth2.0/authorize?' . $query);
exit;
