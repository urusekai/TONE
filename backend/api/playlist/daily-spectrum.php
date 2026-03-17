<?php

require_once __DIR__ . '/../../bootstrap.php';

use GuzzleHttp\Client;

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

$userUuid = Auth::requireAuthenticatedUser();

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '[]', true);
if (!is_array($payload)) {
    app_error('잘못된 요청 본문입니다.', 400);
}

$answersInput = is_array($payload['answers'] ?? null) ? $payload['answers'] : $payload;
$userAnswers = normalizeDailySpectrumAnswers($answersInput);

try {
    $promptConfig = require __DIR__ . '/../../prompts/daily-spectrum.php';
    if (!is_array($promptConfig)) {
        throw new RuntimeException('프롬프트 설정을 불러오지 못했습니다.');
    }

    $pdo = Database::getConnection();

    $playlistStmt = $pdo->prepare(
        'SELECT
            p.id,
            p.pantone_code,
            p.color_name,
            p.color_hex,
            p.energy_level,
            p.emotion_temperature,
            p.desired_mood,
            p.day_pace,
            p.record_focus,
            CASE WHEN pal.user_uuid IS NULL THEN 0 ELSE 1 END AS saved
         FROM playlists p
         LEFT JOIN palette_logs pal
           ON pal.playlist_id = p.id
          AND pal.user_uuid = :saved_user_uuid
         ORDER BY p.id ASC'
    );
    $playlistStmt->execute(['saved_user_uuid' => $userUuid]);
    $playlistRows = $playlistStmt->fetchAll();

    if (count($playlistRows) < 3) {
        app_error('추천 가능한 플레이리스트가 부족합니다.', 404);
    }

    $promptPlaylists = array_map(
        static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'color_name' => (string) $row['color_name'],
                'energy_level' => (string) $row['energy_level'],
                'emotion_temperature' => (string) $row['emotion_temperature'],
                'desired_mood' => (string) $row['desired_mood'],
                'day_pace' => (string) $row['day_pace'],
                'record_focus' => (string) $row['record_focus'],
            ];
        },
        $playlistRows
    );

    [$systemPrompt, $userPrompt] = buildDailySpectrumPrompts(
        $promptConfig,
        $userAnswers,
        $promptPlaylists
    );

    $llmResult = null;
    $apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? ''));
    $model = trim((string) ($_ENV['OPENAI_MODEL'] ?? 'gpt-5-mini'));

    if ($apiKey === '') {
        app_error('OpenAI API 키가 설정되지 않았습니다.', 500);
    }

    if ($model === '') {
        app_error('OpenAI 모델이 설정되지 않았습니다.', 500);
    }

    $llmResult = requestDailySpectrumRecommendations($apiKey, $model, $systemPrompt, $userPrompt);

    if (!isValidDailySpectrumResult($llmResult, $playlistRows)) {
        app_error('LLM 추천 결과 형식이 올바르지 않습니다.', 500);
    }

    $selectedIds = array_values(array_map('intval', $llmResult['playlist_ids']));
    $playlistsById = [];

    foreach ($playlistRows as $row) {
        $playlistsById[(int) $row['id']] = [
            'id' => (int) $row['id'],
            'pantone_code' => (string) $row['pantone_code'],
            'color_name' => (string) $row['color_name'],
            'color_hex' => (string) $row['color_hex'],
            'saved' => (bool) $row['saved'],
        ];
    }

    $spectrumPlaylists = [];
    foreach ($selectedIds as $playlistId) {
        if (isset($playlistsById[$playlistId])) {
            $spectrumPlaylists[] = $playlistsById[$playlistId];
        }
    }

    if (count($spectrumPlaylists) !== 3) {
        app_error('LLM이 반환한 플레이리스트를 응답 데이터로 구성하지 못했습니다.', 500);
    }

    echo json_encode([
        'success' => true,
        'playlist_ids' => $selectedIds,
        'explanation' => (string) $llmResult['explanation'],
        'spectrumPlaylists' => $spectrumPlaylists,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    app_error('데일리 스펙트럼 추천을 불러오지 못했습니다: ' . $e->getMessage(), 500);
}

function normalizeDailySpectrumAnswers(array $input): array
{
    $allowedValues = [
        'energy_level' => ['low', 'medium', 'high'],
        'emotion_temperature' => ['cool', 'neutral', 'warm'],
        'desired_mood' => ['stability', 'focus', 'refresh', 'immersion', 'release'],
        'day_pace' => ['slow', 'steady', 'fast'],
        'record_focus' => ['emotion', 'atmosphere', 'movement', 'recovery', 'confidence'],
    ];

    $normalized = [];

    foreach ($allowedValues as $field => $allowed) {
        $value = strtolower(trim((string) ($input[$field] ?? '')));
        if ($value === '' || !in_array($value, $allowed, true)) {
            app_error("유효한 {$field} 값이 필요합니다.", 400);
        }

        $normalized[$field] = $value;
    }

    return $normalized;
}

function buildDailySpectrumPrompts(array $promptConfig, array $userAnswers, array $promptPlaylists): array
{
    $systemPrompt = trim((string) ($promptConfig['system_prompt'] ?? ''));
    $userTemplate = (string) ($promptConfig['user_prompt_template'] ?? '');
    $schema = $promptConfig['schema'] ?? [];
    $questionnaire = $promptConfig['questionnaire'] ?? [];

    if ($systemPrompt === '' || $userTemplate === '' || !is_array($schema) || !is_array($questionnaire)) {
        throw new RuntimeException('프롬프트 템플릿이 올바르지 않습니다.');
    }

    $userPrompt = strtr($userTemplate, [
        '{{questionnaire_json}}' => json_encode($questionnaire, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        '{{schema_json}}' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        '{{user_answers_json}}' => json_encode($userAnswers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        '{{playlists_json}}' => json_encode($promptPlaylists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    ]);

    return [$systemPrompt, $userPrompt];
}

function requestDailySpectrumRecommendations(
    string $apiKey,
    string $model,
    string $systemPrompt,
    string $userPrompt
): ?array {
    $client = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'timeout' => 30,
    ]);

    $response = $client->post('responses', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $systemPrompt,
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $userPrompt,
                        ],
                    ],
                ],
            ],
            'text' => [
                'verbosity' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'daily_spectrum_recommendation',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['playlist_ids', 'explanation'],
                        'properties' => [
                            'playlist_ids' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'explanation' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            'reasoning' => [
                'effort' => 'low',
            ],
            'max_output_tokens' => 320,
        ],
    ]);

    $responsePayload = json_decode((string) $response->getBody(), true);
    if (!is_array($responsePayload)) {
        return null;
    }

    $textOutput = extractDailySpectrumOutputText($responsePayload);
    if ($textOutput === null) {
        return null;
    }

    $decoded = json_decode($textOutput, true);
    return is_array($decoded) ? $decoded : null;
}

function extractDailySpectrumOutputText(array $responsePayload): ?string
{
    $topLevel = trim((string) ($responsePayload['output_text'] ?? ''));
    if ($topLevel !== '') {
        return $topLevel;
    }

    $outputItems = $responsePayload['output'] ?? null;
    if (!is_array($outputItems)) {
        return null;
    }

    foreach ($outputItems as $item) {
        if (!is_array($item)) {
            continue;
        }

        $contentItems = $item['content'] ?? null;
        if (!is_array($contentItems)) {
            continue;
        }

        foreach ($contentItems as $content) {
            if (!is_array($content)) {
                continue;
            }

            $text = trim((string) ($content['text'] ?? ''));
            if ($text !== '') {
                return $text;
            }
        }
    }

    return null;
}

function isValidDailySpectrumResult(?array $result, array $playlistRows): bool
{
    if (!is_array($result)) {
        return false;
    }

    if (!isset($result['playlist_ids']) || !is_array($result['playlist_ids'])) {
        return false;
    }

    if (count($result['playlist_ids']) !== 3) {
        return false;
    }

    $existingIds = [];
    foreach ($playlistRows as $row) {
        $existingIds[(int) $row['id']] = true;
    }

    $uniqueIds = [];
    foreach ($result['playlist_ids'] as $playlistId) {
        if (!is_int($playlistId) && !ctype_digit((string) $playlistId)) {
            return false;
        }

        $playlistId = (int) $playlistId;
        if (!isset($existingIds[$playlistId])) {
            return false;
        }

        $uniqueIds[$playlistId] = true;
    }

    if (count($uniqueIds) !== 3) {
        return false;
    }

    $explanation = trim((string) ($result['explanation'] ?? ''));
    return $explanation !== '';
}
