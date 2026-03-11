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

$userUuid = trim((string) ($_SESSION['user_uuid'] ?? ''));
if ($userUuid === '') {
    app_error('로그인이 필요합니다.', 401);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare(
        'SELECT
            pl.user_uuid,
            pl.playlist_id,
            pl.created_at,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.like_count,
            p.play_count,
            c.mood,
            c.label AS category_label,
            COUNT(pt.track_id) AS total_tracks
         FROM palette_logs pl
         INNER JOIN playlists p
           ON p.id = pl.playlist_id
         INNER JOIN categories c
           ON c.id = p.category_id
         LEFT JOIN playlist_tracks pt
           ON pt.playlist_id = p.id
         WHERE pl.user_uuid = :user_uuid
         GROUP BY
            pl.user_uuid,
            pl.playlist_id,
            pl.created_at,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.like_count,
            p.play_count,
            c.mood,
            c.label
         ORDER BY pl.created_at DESC, pl.playlist_id DESC'
    );
    $stmt->execute(['user_uuid' => $userUuid]);
    $rows = $stmt->fetchAll();

    $paletteLogs = array_map(
        static function (array $row): array {
            return [
                'playlist_id' => (int) $row['playlist_id'],
                'created_at' => (string) $row['created_at'],
                'playlist' => [
                    'id' => (int) $row['playlist_id'],
                    'pantone_code' => (string) $row['pantone_code'],
                    'color_name' => (string) $row['color_name'],
                    'color_hex' => (string) $row['color_hex'],
                    'like_count' => (int) $row['like_count'],
                    'play_count' => (int) $row['play_count'],
                    'totalTracks' => (int) $row['total_tracks'],
                    'mood' => (string) $row['mood'],
                    'category_label' => (string) $row['category_label']
                ]
            ];
        },
        $rows
    );

    echo json_encode([
        'success' => true,
        'paletteLogs' => $paletteLogs
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '팔레트 로그 목록을 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
