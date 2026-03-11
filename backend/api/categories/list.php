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
        'SELECT id, mood, label, tag1, tag2, tag3, grad_c1, grad_c2, grad_c3
         FROM categories
         ORDER BY id ASC'
    );

    $rows = $stmt->fetchAll();

    $categories = array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'mood' => (string) $row['mood'],
            'label' => (string) $row['label'],
            'tag1' => (string) $row['tag1'],
            'tag2' => (string) $row['tag2'],
            'tag3' => (string) $row['tag3'],
            'grad_c1' => (string) $row['grad_c1'],
            'grad_c2' => (string) $row['grad_c2'],
            'grad_c3' => (string) $row['grad_c3']
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['message' => '카테고리 목록을 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
