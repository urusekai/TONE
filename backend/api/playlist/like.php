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

$playlistId = filter_var(
    $payload['playlist_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($playlistId === false) {
    app_error('유효한 playlist_id가 필요합니다.', 400);
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare(
        'SELECT user_uuid
         FROM users
         WHERE user_uuid = :user_uuid
         LIMIT 1'
    );
    $userStmt->execute(['user_uuid' => $userUuid]);
    $user = $userStmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        app_error('사용자 정보를 찾을 수 없습니다.', 404);
    }

    $playlistStmt = $pdo->prepare(
        'SELECT id, like_count
         FROM playlists
         WHERE id = :playlist_id
         LIMIT 1
         FOR UPDATE'
    );
    $playlistStmt->execute(['playlist_id' => $playlistId]);
    $playlist = $playlistStmt->fetch();

    if (!$playlist) {
        $pdo->rollBack();
        app_error('플레이리스트를 찾을 수 없습니다.', 404);
    }

    $likeStmt = $pdo->prepare(
        'SELECT 1
         FROM playlist_likes
         WHERE user_uuid = :user_uuid
           AND playlist_id = :playlist_id
         LIMIT 1'
    );
    $likeStmt->execute([
        'user_uuid' => $userUuid,
        'playlist_id' => $playlistId
    ]);
    $alreadyLiked = (bool) $likeStmt->fetchColumn();

    if ($alreadyLiked) {
        $deleteStmt = $pdo->prepare(
            'DELETE FROM playlist_likes
             WHERE user_uuid = :user_uuid
               AND playlist_id = :playlist_id'
        );
        $deleteStmt->execute([
            'user_uuid' => $userUuid,
            'playlist_id' => $playlistId
        ]);

        $updateCountStmt = $pdo->prepare(
            'UPDATE playlists
             SET like_count = GREATEST(like_count - 1, 0)
             WHERE id = :playlist_id'
        );
        $updateCountStmt->execute(['playlist_id' => $playlistId]);

        $liked = false;
    } else {
        $insertStmt = $pdo->prepare(
            'INSERT INTO playlist_likes (user_uuid, playlist_id)
             VALUES (:user_uuid, :playlist_id)'
        );
        $insertStmt->execute([
            'user_uuid' => $userUuid,
            'playlist_id' => $playlistId
        ]);

        $updateCountStmt = $pdo->prepare(
            'UPDATE playlists
             SET like_count = like_count + 1
             WHERE id = :playlist_id'
        );
        $updateCountStmt->execute(['playlist_id' => $playlistId]);

        $liked = true;
    }

    $countStmt = $pdo->prepare(
        'SELECT like_count
         FROM playlists
         WHERE id = :playlist_id
         LIMIT 1'
    );
    $countStmt->execute(['playlist_id' => $playlistId]);
    $likeCount = (int) $countStmt->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'playlist_id' => (int) $playlistId,
        'liked' => $liked,
        'like_count' => $likeCount
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['message' => '플레이리스트 좋아요 처리 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}
