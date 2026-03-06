<?php

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'GET 요청만 허용됩니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    http_response_code(401);
    echo json_encode(['message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    $countStmt = $pdo->query('SELECT COUNT(*) FROM playlists');
    $playlistCount = (int) $countStmt->fetchColumn();

    if ($playlistCount < 1) {
        http_response_code(404);
        echo json_encode(['message' => '플레이리스트를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $today = new DateTimeImmutable('today');
    $todayKey = $today->format('Y-m-d');
    $epoch = new DateTimeImmutable('2026-01-01');
    $dayOffset = (int) $epoch->diff($today)->format('%r%a');
    $offset = (($dayOffset % $playlistCount) + $playlistCount) % $playlistCount;

    $dailyStmt = $pdo->prepare(
        'SELECT
            p.id,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.like_count,
            p.play_count,
            c.mood,
            c.label AS category_label,
            CASE WHEN pal.user_uuid IS NULL THEN 0 ELSE 1 END AS saved,
            t.title AS track_title,
            t.artist AS track_artist,
            t.cover_filename AS track_cover_filename,
            t.audio_filename AS track_audio_filename
         FROM playlists p
         INNER JOIN categories c
           ON c.id = p.category_id
         LEFT JOIN palette_logs pal
           ON pal.playlist_id = p.id
          AND pal.user_uuid = :saved_user_uuid
         LEFT JOIN playlist_tracks pt
           ON pt.playlist_id = p.id
          AND pt.track_order = 1
         LEFT JOIN tracks t
           ON t.id = pt.track_id
         ORDER BY p.id ASC
         LIMIT 1 OFFSET :offset_value'
    );
    $dailyStmt->bindValue(':saved_user_uuid', $userUuid, PDO::PARAM_STR);
    $dailyStmt->bindValue(':offset_value', $offset, PDO::PARAM_INT);
    $dailyStmt->execute();
    $playlist = $dailyStmt->fetch();

    if (!$playlist) {
        http_response_code(404);
        echo json_encode(['message' => '오늘의 플레이리스트를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'date' => $todayKey,
        'playlist' => [
            'id' => (int) $playlist['id'],
            'pantone_code' => (string) $playlist['pantone_code'],
            'color_name' => (string) $playlist['color_name'],
            'color_hex' => (string) $playlist['color_hex'],
            'like_count' => (int) $playlist['like_count'],
            'play_count' => (int) $playlist['play_count'],
            'mood' => (string) $playlist['mood'],
            'category_label' => (string) $playlist['category_label'],
            'saved' => (bool) $playlist['saved'],
            'track' => [
                'title' => (string) ($playlist['track_title'] ?? ''),
                'artist' => (string) ($playlist['track_artist'] ?? ''),
                'cover_url' => MediaUrl::buildCoverUrl($playlist['track_cover_filename'] ?? null),
                'audio_url' => MediaUrl::buildAudioUrl($playlist['track_audio_filename'] ?? null),
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '오늘의 톤을 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
