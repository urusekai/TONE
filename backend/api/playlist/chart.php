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

try {
    $pdo = Database::getConnection();

    if ($userUuid !== '') {
        $stmt = $pdo->prepare(
            'SELECT
                p.id,
                p.pantone_code,
                p.color_name,
                p.color_hex,
                p.like_count,
                p.play_count,
                c.mood,
                c.label AS category_label,
                COUNT(pt.track_id) AS total_tracks,
                MAX(CASE WHEN pl.user_uuid IS NULL THEN 0 ELSE 1 END) AS liked
             FROM playlists p
             INNER JOIN categories c
               ON c.id = p.category_id
             LEFT JOIN playlist_tracks pt
               ON pt.playlist_id = p.id
             LEFT JOIN playlist_likes pl
               ON pl.playlist_id = p.id
              AND pl.user_uuid = :user_uuid
             GROUP BY
                p.id,
                p.pantone_code,
                p.color_name,
                p.color_hex,
                p.like_count,
                p.play_count,
                c.mood,
                c.label
             ORDER BY p.like_count DESC, p.play_count DESC, p.id ASC'
        );
        $stmt->execute(['user_uuid' => $userUuid]);
    } else {
        $stmt = $pdo->query(
            'SELECT
                p.id,
                p.pantone_code,
                p.color_name,
                p.color_hex,
                p.like_count,
                p.play_count,
                c.mood,
                c.label AS category_label,
                COUNT(pt.track_id) AS total_tracks,
                0 AS liked
             FROM playlists p
             INNER JOIN categories c
               ON c.id = p.category_id
             LEFT JOIN playlist_tracks pt
               ON pt.playlist_id = p.id
             GROUP BY
                p.id,
                p.pantone_code,
                p.color_name,
                p.color_hex,
                p.like_count,
                p.play_count,
                c.mood,
                c.label
             ORDER BY p.like_count DESC, p.play_count DESC, p.id ASC'
        );
    }

    $rows = $stmt->fetchAll();

    $playlists = array_map(
        static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'pantone_code' => (string) $row['pantone_code'],
                'color_name' => (string) $row['color_name'],
                'color_hex' => (string) $row['color_hex'],
                'like_count' => (int) $row['like_count'],
                'play_count' => (int) $row['play_count'],
                'mood' => (string) $row['mood'],
                'category_label' => (string) $row['category_label'],
                'totalTracks' => (int) $row['total_tracks'],
                'liked' => (bool) $row['liked']
            ];
        },
        $rows
    );

    echo json_encode([
        'success' => true,
        'playlists' => $playlists
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '컬러차트 정보를 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
