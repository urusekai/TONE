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

$playlistId = filter_var(
    $payload['playlist_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($playlistId === false) {
    http_response_code(400);
    echo json_encode(['message' => '유효한 playlist_id가 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
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
        http_response_code(404);
        echo json_encode(['message' => '사용자 정보를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $playlistStmt = $pdo->prepare(
        'SELECT id
         FROM playlists
         WHERE id = :playlist_id
         LIMIT 1
         FOR UPDATE'
    );
    $playlistStmt->execute(['playlist_id' => $playlistId]);
    $playlist = $playlistStmt->fetch();

    if (!$playlist) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['message' => '플레이리스트를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $saveStmt = $pdo->prepare(
        'SELECT created_at
         FROM palette_logs
         WHERE user_uuid = :user_uuid
           AND playlist_id = :playlist_id
         LIMIT 1'
    );
    $saveStmt->execute([
        'user_uuid' => $userUuid,
        'playlist_id' => $playlistId
    ]);
    $existingLog = $saveStmt->fetch();

    if ($existingLog) {
        $deleteStmt = $pdo->prepare(
            'DELETE FROM palette_logs
             WHERE user_uuid = :user_uuid
               AND playlist_id = :playlist_id'
        );
        $deleteStmt->execute([
            'user_uuid' => $userUuid,
            'playlist_id' => $playlistId
        ]);

        $saved = false;
        $createdAt = null;
    } else {
        $insertStmt = $pdo->prepare(
            'INSERT INTO palette_logs (user_uuid, playlist_id)
             VALUES (:user_uuid, :playlist_id)'
        );
        $insertStmt->execute([
            'user_uuid' => $userUuid,
            'playlist_id' => $playlistId
        ]);

        $createdAtStmt = $pdo->prepare(
            'SELECT created_at
             FROM palette_logs
             WHERE user_uuid = :user_uuid
               AND playlist_id = :playlist_id
             LIMIT 1'
        );
        $createdAtStmt->execute([
            'user_uuid' => $userUuid,
            'playlist_id' => $playlistId
        ]);

        $saved = true;
        $createdAt = $createdAtStmt->fetchColumn() ?: null;
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'playlist_id' => (int) $playlistId,
        'saved' => $saved,
        'created_at' => $createdAt
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['message' => '팔레트 로그 저장 처리 중 서버 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}
