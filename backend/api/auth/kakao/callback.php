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
    app_error('GET 요청만 허용됩니다.', 405);
}

// 콜백 파라미터 확인
$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$savedState = (string) ($_SESSION['kakao_oauth_state'] ?? '');
unset($_SESSION['kakao_oauth_state']);

if ($code === '' || $state === '' || $savedState === '' || !hash_equals($savedState, $state)) {
    redirectFrontend('/login', ['social' => 'kakao', 'error' => 'invalid_state']);
}

$clientId = trim((string) ($_ENV['KAKAO_CLIENT_ID'] ?? ''));
$clientSecret = trim((string) ($_ENV['KAKAO_CLIENT_SECRET'] ?? ''));
$redirectUri = trim((string) ($_ENV['KAKAO_REDIRECT_URI'] ?? ''));

if ($clientId === '' || $redirectUri === '') {
    redirectFrontend('/login', ['social' => 'kakao', 'error' => 'config_missing']);
}

try {
    $http = new Client(['timeout' => 10]);

    // 인가 코드를 액세스 토큰으로 교환
    $tokenResponse = $http->post('https://kauth.kakao.com/oauth/token', [
        'form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'code' => $code,
            'client_secret' => $clientSecret
        ]
    ]);

    $tokenData = json_decode((string) $tokenResponse->getBody(), true);
    $accessToken = (string) ($tokenData['access_token'] ?? '');

    if ($accessToken === '') {
        redirectFrontend('/login', ['social' => 'kakao', 'error' => 'token_failed']);
    }

    // 카카오 사용자 정보 조회
    $profileResponse = $http->get('https://kapi.kakao.com/v2/user/me', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken
        ]
    ]);

    $profileData = json_decode((string) $profileResponse->getBody(), true);

    $provider = 'kakao';
    $providerId = trim((string) ($profileData['id'] ?? ''));
    if ($providerId === '') {
        redirectFrontend('/login', ['social' => 'kakao', 'error' => 'profile_failed']);
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
    redirectFrontend('/login', ['social' => 'kakao', 'error' => 'server_error']);
}
