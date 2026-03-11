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
$savedState = (string) ($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);

if ($code === '' || $state === '' || $savedState === '' || !hash_equals($savedState, $state)) {
    redirectFrontend('/login', ['social' => 'google', 'error' => 'invalid_state']);
}

$clientId = trim((string) ($_ENV['GOOGLE_CLIENT_ID'] ?? ''));
$clientSecret = trim((string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? ''));
$redirectUri = trim((string) ($_ENV['GOOGLE_REDIRECT_URI'] ?? ''));

if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
    redirectFrontend('/login', ['social' => 'google', 'error' => 'config_missing']);
}

try {
    $http = new Client(['timeout' => 10]);

    // 인가 코드를 액세스 토큰으로 교환
    $tokenResponse = $http->post('https://oauth2.googleapis.com/token', [
        'form_params' => [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ]
    ]);

    $tokenData = json_decode((string) $tokenResponse->getBody(), true);
    $accessToken = (string) ($tokenData['access_token'] ?? '');

    if ($accessToken === '') {
        redirectFrontend('/login', ['social' => 'google', 'error' => 'token_failed']);
    }

    // 구글 사용자 정보 조회
    $profileResponse = $http->get('https://www.googleapis.com/oauth2/v2/userinfo', [
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken
        ]
    ]);

    $profileData = json_decode((string) $profileResponse->getBody(), true);

    $provider = 'google';
    $providerId = trim((string) ($profileData['id'] ?? ''));
    if ($providerId === '') {
        redirectFrontend('/login', ['social' => 'google', 'error' => 'profile_failed']);
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
    redirectFrontend('/login', ['social' => 'google', 'error' => 'server_error']);
}
