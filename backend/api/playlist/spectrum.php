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

$playlistId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$playlistId) {
    app_error('유효한 플레이리스트 id가 필요합니다.', 400);
}

try {
    $pdo = Database::getConnection();

    $currentPlaylistStmt = $pdo->prepare(
        'SELECT id, category_id
         FROM playlists
         WHERE id = :playlist_id
         LIMIT 1'
    );
    $currentPlaylistStmt->execute(['playlist_id' => $playlistId]);
    $currentPlaylist = $currentPlaylistStmt->fetch();

    if (!$currentPlaylist) {
        app_error('플레이리스트를 찾을 수 없습니다.', 404);
    }

    $spectrumStmt = $pdo->prepare(
        'SELECT
            p.id,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            CASE WHEN pal.user_uuid IS NULL THEN 0 ELSE 1 END AS saved
         FROM playlists p
         LEFT JOIN palette_logs pal
           ON pal.playlist_id = p.id
          AND pal.user_uuid = :saved_user_uuid
         WHERE p.category_id = :category_id
           AND p.id != :playlist_id
         ORDER BY p.id ASC'
    );
    $spectrumStmt->execute([
        'saved_user_uuid' => $userUuid,
        'category_id' => $currentPlaylist['category_id'],
        'playlist_id' => $playlistId
    ]);

    $spectrumPlaylists = array_map(
        static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'pantone_code' => (string) $row['pantone_code'],
                'color_name' => (string) $row['color_name'],
                'color_hex' => (string) $row['color_hex'],
                'saved' => (bool) $row['saved']
            ];
        },
        $spectrumStmt->fetchAll()
    );

    echo json_encode([
        'success' => true,
        'daily_playlist_id' => (int) $playlistId,
        'spectrumPlaylists' => $spectrumPlaylists
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '데일리 스펙트럼 정보를 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
