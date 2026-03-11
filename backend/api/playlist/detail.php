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
    app_error('GET 요청만 허용됩니다.', 405);
}

$userUuid = Auth::requireAuthenticatedUser();

$playlistId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$playlistId) {
    app_error('유효한 플레이리스트 id가 필요합니다.', 400);
}

try {
    $pdo = Database::getConnection();

    $playlistStmt = $pdo->prepare(
        'SELECT
            p.id,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.like_count,
            p.play_count,
            c.id AS category_id,
            c.mood AS category_mood,
            c.label AS category_label,
            CASE WHEN pl.user_uuid IS NULL THEN 0 ELSE 1 END AS liked,
            CASE WHEN pal.user_uuid IS NULL THEN 0 ELSE 1 END AS saved
         FROM playlists p
         INNER JOIN categories c
           ON c.id = p.category_id
         LEFT JOIN playlist_likes pl
           ON pl.playlist_id = p.id
          AND pl.user_uuid = :like_user_uuid
         LEFT JOIN palette_logs pal
           ON pal.playlist_id = p.id
          AND pal.user_uuid = :saved_user_uuid
         WHERE p.id = :playlist_id
         LIMIT 1'
    );
    $playlistStmt->execute([
        'playlist_id' => $playlistId,
        'like_user_uuid' => $userUuid,
        'saved_user_uuid' => $userUuid
    ]);
    $playlist = $playlistStmt->fetch();

    if (!$playlist) {
        app_error('플레이리스트를 찾을 수 없습니다.', 404);
    }

    $tracksStmt = $pdo->prepare(
        'SELECT
            t.id,
            t.title,
            t.artist,
            t.audio_filename,
            t.cover_filename,
            t.video_filename,
            t.duration_ms,
            pt.track_order
         FROM playlist_tracks pt
         INNER JOIN tracks t
           ON t.id = pt.track_id
         WHERE pt.playlist_id = :playlist_id
         ORDER BY pt.track_order ASC'
    );
    $tracksStmt->execute(['playlist_id' => $playlistId]);
    $trackRows = $tracksStmt->fetchAll();

    $tracks = array_map(
        static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'artist' => (string) $row['artist'],
                'cover_url' => MediaUrl::buildCoverUrl($row['cover_filename']),
                'audio_url' => MediaUrl::buildAudioUrl($row['audio_filename']),
                'video_url' => MediaUrl::buildVideoUrl($row['video_filename'] ?? null),
                'duration_ms' => (int) $row['duration_ms'],
                'track_order' => (int) $row['track_order']
            ];
        },
        $trackRows
    );

    echo json_encode([
        'success' => true,
        'playlist' => [
            'id' => (int) $playlist['id'],
            'pantone_code' => (string) $playlist['pantone_code'],
            'color_name' => (string) $playlist['color_name'],
            'color_hex' => (string) $playlist['color_hex'],
            'like_count' => (int) $playlist['like_count'],
            'liked' => (bool) $playlist['liked'],
            'saved' => (bool) $playlist['saved'],
            'play_count' => (int) $playlist['play_count'],
            'category' => [
                'id' => (int) $playlist['category_id'],
                'mood' => (string) $playlist['category_mood'],
                'label' => (string) $playlist['category_label']
            ]
        ],
        'tracks' => $tracks
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '플레이리스트 정보를 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
