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
    http_response_code(405);
    echo json_encode(['message' => 'POST 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    http_response_code(401);
    echo json_encode(['message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['message' => '잘못된 요청 본문입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$entryDate = trim((string) ($payload['entryDate'] ?? ''));
$memo = trim((string) ($payload['memo'] ?? ''));
$playlistId = isset($payload['playlistId']) ? (int) $payload['playlistId'] : 0;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) {
    http_response_code(422);
    echo json_encode(['message' => 'entryDate는 YYYY-MM-DD 형식이어야 합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$entryDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $entryDate);
$entryDateErrors = DateTimeImmutable::getLastErrors();
if (
    !$entryDateObj instanceof DateTimeImmutable ||
    ($entryDateErrors !== false && (($entryDateErrors['warning_count'] ?? 0) > 0 || ($entryDateErrors['error_count'] ?? 0) > 0))
) {
    http_response_code(422);
    echo json_encode(['message' => '유효한 entryDate 값이 아닙니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (function_exists('mb_strlen') ? mb_strlen($memo) > 50 : strlen($memo) > 50) {
    http_response_code(422);
    echo json_encode(['message' => '메모는 50자 이하로 입력해주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    $existingStmt = $pdo->prepare(
        'SELECT id, playlist_id
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
        http_response_code(422);
        echo json_encode(['message' => 'playlistId가 필요합니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

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
        http_response_code(404);
        echo json_encode(['message' => '플레이리스트를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
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
            'memo' => $memo,
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
            'memo' => $memo
        ]);
        $entryId = (int) $pdo->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'entry' => [
            'id' => $entryId,
            'entryDate' => $entryDate,
            'memo' => $memo,
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
