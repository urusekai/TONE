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

$month = trim((string) ($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    echo json_encode(['message' => 'month는 YYYY-MM 형식이어야 합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$monthDate = DateTimeImmutable::createFromFormat('Y-m', $month);
$monthErrors = DateTimeImmutable::getLastErrors();
if (
    !$monthDate instanceof DateTimeImmutable ||
    ($monthErrors !== false && (($monthErrors['warning_count'] ?? 0) > 0 || ($monthErrors['error_count'] ?? 0) > 0))
) {
    http_response_code(400);
    echo json_encode(['message' => '유효한 month 값이 아닙니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$monthStart = $monthDate->setDate((int) $monthDate->format('Y'), (int) $monthDate->format('m'), 1);
$nextMonthStart = $monthStart->modify('+1 month');

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare(
        'SELECT
            ce.id,
            ce.entry_date,
            ce.memo,
            ce.playlist_id,
            p.color_name,
            p.pantone_code,
            p.color_hex,
            t.title AS track_title,
            t.artist AS track_artist,
            t.cover_filename
         FROM calendar_entries ce
         INNER JOIN playlists p
           ON p.id = ce.playlist_id
         LEFT JOIN playlist_tracks pt
           ON pt.playlist_id = p.id
          AND pt.track_order = 1
         LEFT JOIN tracks t
           ON t.id = pt.track_id
         WHERE ce.user_uuid = :user_uuid
           AND ce.entry_date >= :month_start
           AND ce.entry_date < :next_month_start
         ORDER BY ce.entry_date ASC'
    );
    $stmt->execute([
        'user_uuid' => $userUuid,
        'month_start' => $monthStart->format('Y-m-d'),
        'next_month_start' => $nextMonthStart->format('Y-m-d')
    ]);

    $entries = array_map(
        static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'entryDate' => (string) $row['entry_date'],
                'memo' => (string) ($row['memo'] ?? ''),
                'playlistId' => (int) $row['playlist_id'],
                'name' => (string) $row['color_name'],
                'number' => (string) $row['pantone_code'],
                'color' => (string) $row['color_hex'],
                'music' => [
                    'title' => (string) ($row['track_title'] ?? ''),
                    'artist' => (string) ($row['track_artist'] ?? ''),
                    'cover' => MediaUrl::buildCoverUrl($row['cover_filename'] ?? null)
                ]
            ];
        },
        $stmt->fetchAll()
    );

    echo json_encode([
        'success' => true,
        'month' => $monthStart->format('Y-m'),
        'entries' => $entries
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '캘린더 기록을 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
