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

    $countStmt = $pdo->query('SELECT COUNT(*) FROM playlists');
    $playlistCount = (int) $countStmt->fetchColumn();

    if ($playlistCount < 1) {
        app_error('플레이리스트를 찾을 수 없습니다.', 404);
    }

    $today = new DateTimeImmutable('today');
    $todayKey = $today->format('Y-m-d');
    $epoch = new DateTimeImmutable('2026-01-01');
    $dayOffset = (int) $epoch->diff($today)->format('%r%a');
    $offset = (($dayOffset % $playlistCount) + $playlistCount) % $playlistCount;

    $playlistStmt = $pdo->prepare(
        'SELECT p.id
         FROM playlists p
         ORDER BY p.id ASC
         LIMIT 1 OFFSET :offset_value'
    );
    $playlistStmt->bindValue(':offset_value', $offset, PDO::PARAM_INT);
    $playlistStmt->execute();
    $playlist = $playlistStmt->fetch();

    if (!$playlist) {
        app_error('오늘의 플레이리스트를 찾을 수 없습니다.', 404);
    }

    $playlistId = (int) $playlist['id'];

    $saveStmt = $pdo->prepare(
        'INSERT INTO calendar_entries (user_uuid, entry_date, playlist_id, memo)
         VALUES (:user_uuid, :entry_date, :playlist_id, \'\')
         ON DUPLICATE KEY UPDATE
           playlist_id = VALUES(playlist_id),
           memo = memo'
    );
    $saveStmt->execute([
        'user_uuid' => $userUuid,
        'entry_date' => $todayKey,
        'playlist_id' => $playlistId
    ]);

    $entryStmt = $pdo->prepare(
        'SELECT id, memo
         FROM calendar_entries
         WHERE user_uuid = :user_uuid
           AND entry_date = :entry_date
         LIMIT 1'
    );
    $entryStmt->execute([
        'user_uuid' => $userUuid,
        'entry_date' => $todayKey
    ]);
    $entry = $entryStmt->fetch();

    echo json_encode([
        'success' => true,
        'entry' => [
            'id' => (int) ($entry['id'] ?? 0),
            'entryDate' => $todayKey,
            'playlistId' => $playlistId,
            'memo' => (string) ($entry['memo'] ?? '')
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '오늘 캘린더 색 기록에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
}
