<?php

require_once __DIR__ . '/../../../bootstrap.php';

use GuzzleHttp\Client;

function frontendBaseUrl(): string
{
    return rtrim((string) ($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
}

function redirectFrontend(string $path, array $query = []): void
{
    $url = frontendBaseUrl() . $path;

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    header('Location: ' . $url);
    exit;
}

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'GET 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 콜백 파라미터 확인
$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$savedState = (string) ($_SESSION['naver_oauth_state'] ?? '');
unset($_SESSION['naver_oauth_state']);

if ($code === '' || $state === '' || $savedState === '' || !hash_equals($savedState, $state)) {
    redirectFrontend('/login', ['social' => 'naver', 'error' => 'invalid_state']);
}

$clientId = trim((string) ($_ENV['NAVER_CLIENT_ID'] ?? ''));
$clientSecret = trim((string) ($_ENV['NAVER_CLIENT_SECRET'] ?? ''));

if ($clientId === '' || $clientSecret === '') {
    redirectFrontend('/login', ['social' => 'naver', 'error' => 'config_missing']);
}

try {
    $http = new Client(['timeout' => 10]);

    // 인가 코드를 액세스 토큰으로 교환
    $tokenResponse = $http->get('https://nid.naver.com/oauth2.0/token', [
        'query' => [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'state' => $state
        ]
    ]);

    $tokenData = json_decode((string) $tokenResponse->getBody(), true);
    $accessToken = trim((string) ($tokenData['access_token'] ?? ''));

    if ($accessToken === '') {
        redirectFrontend('/login', ['social' => 'naver', 'error' => 'token_failed']);
    }

    // 네이버 사용자 정보 조회
    $profileResponse = $http->get('https://openapi.naver.com/v1/nid/me', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken
        ]
    ]);

    $profileData = json_decode((string) $profileResponse->getBody(), true);
    $provider = 'naver';
    $providerId = trim((string) ($profileData['response']['id'] ?? ''));

    if ($providerId === '') {
        redirectFrontend('/login', ['social' => 'naver', 'error' => 'profile_failed']);
    }

    $pdo = Database::getConnection();

    // 1) provider + provider_id가 이미 있으면 기존 사용자 로그인
    $providerStmt = $pdo->prepare(
        'SELECT user_uuid FROM users WHERE provider = :provider AND provider_id = :provider_id LIMIT 1'
    );
    $providerStmt->execute([
        'provider' => $provider,
        'provider_id' => $providerId
    ]);
    $foundByProvider = $providerStmt->fetch();

    if ($foundByProvider) {
        $_SESSION['user_uuid'] = $foundByProvider['user_uuid'];
        redirectFrontend('/main');
    }

    // 2) 신규 소셜 계정은 항상 추가 정보 입력 화면으로 이동
    redirectFrontend('/social-complete', [
        'provider' => $provider,
        'providerId' => $providerId
    ]);
} catch (Throwable $e) {
    redirectFrontend('/login', ['social' => 'naver', 'error' => 'server_error']);
}
