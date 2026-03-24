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

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query(
        'SELECT
            ce.memo,
            ce.entry_date
         FROM calendar_entries ce
         WHERE ce.memo IS NOT NULL
           AND TRIM(ce.memo) <> ""
         ORDER BY RAND()'
    );

    $echoNotes = array_map(
        static function (array $row): array {
            return [
                'memo' => (string) $row['memo'],
                'entryDate' => (string) $row['entry_date'],
            ];
        },
        $stmt->fetchAll()
    );

    echo json_encode([
        'success' => true,
        'echoNotes' => $echoNotes,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '에코 노트를 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
