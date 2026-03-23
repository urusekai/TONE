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

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    app_error('잘못된 요청 본문입니다.', 400);
}

$entryDate = trim((string) ($payload['entryDate'] ?? ''));
$hasMemo = array_key_exists('memo', $payload);
$memo = $hasMemo ? trim((string) ($payload['memo'] ?? '')) : null;
$playlistId = isset($payload['playlistId']) ? (int) $payload['playlistId'] : 0;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) {
    app_error('entryDate는 YYYY-MM-DD 형식이어야 합니다.', 400);
}

$entryDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $entryDate);
$entryDateErrors = DateTimeImmutable::getLastErrors();
if (
    !$entryDateObj instanceof DateTimeImmutable ||
    ($entryDateErrors !== false && (($entryDateErrors['warning_count'] ?? 0) > 0 || ($entryDateErrors['error_count'] ?? 0) > 0))
) {
    app_error('유효한 entryDate 값이 필요합니다.', 400);
}

if ($hasMemo && (function_exists('mb_strlen') ? mb_strlen($memo) > 50 : strlen($memo) > 50)) {
    app_error('메모는 50자 이하로 입력해 주세요.', 400);
}

try {
    $pdo = Database::getConnection();

    $existingStmt = $pdo->prepare(
        'SELECT id, playlist_id, memo
         FROM calendar_entries
         WHERE user_uuid = :user_uuid
           AND entry_date = :entry_date
         LIMIT 1'
    );
    $existingStmt->execute([
        'user_uuid' => $userUuid,
        'entry_date' => $entryDate
    ]);
    $existingEntry = $existingStmt->fetch();

    $resolvedPlaylistId = $playlistId > 0 ? $playlistId : (int) ($existingEntry['playlist_id'] ?? 0);
    if ($resolvedPlaylistId < 1) {
        app_error('playlistId가 필요합니다.', 400);
    }

    $resolvedMemo = $hasMemo
        ? (string) $memo
        : (string) ($existingEntry['memo'] ?? '');

    $playlistStmt = $pdo->prepare(
        'SELECT
            p.id,
            p.color_name,
            p.pantone_code,
            p.color_hex,
            t.title AS track_title,
            t.artist AS track_artist,
            t.cover_filename
         FROM playlists p
         LEFT JOIN playlist_tracks pt
           ON pt.playlist_id = p.id
          AND pt.track_order = 1
         LEFT JOIN tracks t
           ON t.id = pt.track_id
         WHERE p.id = :playlist_id
         LIMIT 1'
    );
    $playlistStmt->execute(['playlist_id' => $resolvedPlaylistId]);
    $playlist = $playlistStmt->fetch();

    if (!$playlist) {
        app_error('플레이리스트를 찾을 수 없습니다.', 404);
    }

    if ($existingEntry) {
        $saveStmt = $pdo->prepare(
            'UPDATE calendar_entries
             SET playlist_id = :playlist_id,
                 memo = :memo
             WHERE id = :id'
        );
        $saveStmt->execute([
            'playlist_id' => $resolvedPlaylistId,
            'memo' => $resolvedMemo,
            'id' => (int) $existingEntry['id']
        ]);
        $entryId = (int) $existingEntry['id'];
    } else {
        $saveStmt = $pdo->prepare(
            'INSERT INTO calendar_entries (user_uuid, entry_date, playlist_id, memo)
             VALUES (:user_uuid, :entry_date, :playlist_id, :memo)'
        );
        $saveStmt->execute([
            'user_uuid' => $userUuid,
            'entry_date' => $entryDate,
            'playlist_id' => $resolvedPlaylistId,
            'memo' => $resolvedMemo
        ]);
        $entryId = (int) $pdo->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'entry' => [
            'id' => $entryId,
            'entryDate' => $entryDate,
            'memo' => $resolvedMemo,
            'playlistId' => (int) $playlist['id'],
            'name' => (string) $playlist['color_name'],
            'number' => (string) $playlist['pantone_code'],
            'color' => (string) $playlist['color_hex'],
            'music' => [
                'title' => (string) ($playlist['track_title'] ?? ''),
                'artist' => (string) ($playlist['track_artist'] ?? ''),
                'cover' => MediaUrl::buildCoverUrl($playlist['cover_filename'] ?? null)
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '캘린더 기록 저장에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
}
