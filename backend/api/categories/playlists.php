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

$userUuid = Auth::requireAuthenticatedUser();

$mood = trim((string) ($_GET['mood'] ?? ''));
if ($mood === '') {
    http_response_code(400);
    echo json_encode(['message' => 'mood 값이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = Database::getConnection();

    $categoryStmt = $pdo->prepare(
        'SELECT id, mood, label, tag1, tag2, tag3, grad_c1, grad_c2, grad_c3
         FROM categories
         WHERE mood = :mood
         LIMIT 1'
    );
    $categoryStmt->execute(['mood' => $mood]);
    $category = $categoryStmt->fetch();

    if (!$category) {
        http_response_code(404);
        echo json_encode(['message' => '카테고리를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $playlistsStmt = $pdo->prepare(
        'SELECT
            p.id,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.like_count,
            p.play_count,
            COUNT(pt.track_id) AS total_tracks,
            MAX(CASE WHEN pl.user_uuid IS NULL THEN 0 ELSE 1 END) AS liked
         FROM playlists p
         LEFT JOIN playlist_tracks pt
           ON pt.playlist_id = p.id
         LEFT JOIN playlist_likes pl
           ON pl.playlist_id = p.id
          AND pl.user_uuid = :user_uuid
         WHERE p.category_id = :category_id
         GROUP BY
            p.id,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.like_count,
            p.play_count
         ORDER BY p.id ASC'
    );
    $playlistsStmt->execute([
        'category_id' => $category['id'],
        'user_uuid' => $userUuid
    ]);
    $playlistRows = $playlistsStmt->fetchAll();

    $previewStmt = $pdo->prepare(
        'SELECT
            pt.playlist_id,
            pt.track_order,
            t.title,
            t.artist
         FROM playlist_tracks pt
         INNER JOIN tracks t
           ON t.id = pt.track_id
         INNER JOIN playlists p
           ON p.id = pt.playlist_id
         WHERE p.category_id = :category_id
         ORDER BY pt.playlist_id ASC, pt.track_order ASC'
    );
    $previewStmt->execute(['category_id' => $category['id']]);
    $previewRows = $previewStmt->fetchAll();

    $previewSongsByPlaylist = [];
    foreach ($previewRows as $row) {
        $playlistId = (int) $row['playlist_id'];

        if (!isset($previewSongsByPlaylist[$playlistId])) {
            $previewSongsByPlaylist[$playlistId] = [];
        }

        if (count($previewSongsByPlaylist[$playlistId]) >= 3) {
            continue;
        }

        $previewSongsByPlaylist[$playlistId][] = [
            'title' => (string) $row['title'],
            'artist' => (string) $row['artist']
        ];
    }

    $playlists = array_map(
        static function (array $row) use ($previewSongsByPlaylist): array {
            $playlistId = (int) $row['id'];

            return [
                'id' => $playlistId,
                'pantone_code' => (string) $row['pantone_code'],
                'color_name' => (string) $row['color_name'],
                'color_hex' => (string) $row['color_hex'],
                'like_count' => (int) $row['like_count'],
                'liked' => (bool) $row['liked'],
                'play_count' => (int) $row['play_count'],
                'totalTracks' => (int) $row['total_tracks'],
                'previewSongs' => $previewSongsByPlaylist[$playlistId] ?? []
            ];
        },
        $playlistRows
    );

    echo json_encode([
        'success' => true,
        'category' => [
            'id' => (int) $category['id'],
            'mood' => (string) $category['mood'],
            'label' => (string) $category['label'],
            'tag1' => (string) $category['tag1'],
            'tag2' => (string) $category['tag2'],
            'tag3' => (string) $category['tag3'],
            'grad_c1' => (string) $category['grad_c1'],
            'grad_c2' => (string) $category['grad_c2'],
            'grad_c3' => (string) $category['grad_c3']
        ],
        'playlists' => $playlists
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '플레이리스트 목록을 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
