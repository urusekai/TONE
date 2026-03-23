<?php

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_error('POST 요청만 허용됩니다.', 405);
}

$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    app_error('로그인이 필요합니다.', 401);
}

try {
    $pdo = Database::getConnection();
    $todayKey = DailyTone::getTodayKey();
    $entry = DailyTone::ensureTodayEntry($pdo, $userUuid, $todayKey);

    echo json_encode([
        'success' => true,
        'entry' => [
            'id' => (int) ($entry['id'] ?? 0),
            'entryDate' => $todayKey,
            'playlistId' => (int) ($entry['playlist_id'] ?? 0),
            'memo' => (string) ($entry['memo'] ?? '')
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '오늘 캘린더 색 기록에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
}
