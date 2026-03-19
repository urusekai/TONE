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

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '[]', true);
if (!is_array($payload)) {
    app_error('잘못된 요청 본문입니다.', 400);
}

$query = trim((string) ($payload['query'] ?? $payload['q'] ?? ''));
if ($query === '') {
    app_error('검색어를 입력해 주세요.', 400);
}

try {
    $promptConfig = require __DIR__ . '/../../prompts/search.php';
    if (!is_array($promptConfig)) {
        throw new RuntimeException('검색 프롬프트를 불러오지 못했습니다.');
    }

    $apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? ''));
    $model = trim((string) ($_ENV['OPENAI_MODEL'] ?? 'gpt-5-mini'));

    if ($apiKey === '') {
        app_error('OpenAI API 키가 설정되지 않았습니다.', 500);
    }

    if ($model === '') {
        app_error('OpenAI 모델이 설정되지 않았습니다.', 500);
    }

    $pdo = Database::getConnection();
    $playlists = loadSearchPlaylists($pdo);
    if ($playlists === []) {
        app_error('검색 가능한 플레이리스트가 없습니다.', 404);
    }

    // 1차 LLM은 검색어를 5축으로 단순 해석만 한다.
    $interpretedQuery = requestSearchIntent($apiKey, $model, $promptConfig, $query);

    // 로컬 서버에서 문자열 점수 + 느낌 점수를 합쳐 전체 순위를 만든다.
    $rankedCandidates = rankSearchCandidates($playlists, $query, $interpretedQuery);
    $positiveCandidates = array_values(array_filter(
        $rankedCandidates,
        static fn(array $candidate): bool => ((float) $candidate['score']) > 0
    ));
    $topCandidates = count($positiveCandidates) >= 3
        ? array_slice($positiveCandidates, 0, 8)
        : array_slice($rankedCandidates, 0, 8);

    if ($topCandidates === []) {
        app_error('검색 결과를 찾지 못했습니다.', 404);
    }

    $fallback = detectSearchFallback($query, $interpretedQuery, $rankedCandidates);
    if ($fallback['mode'] === 'unsupported') {
        echo json_encode([
            'success' => true,
            'summary' => $fallback['summary'],
            'results' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $searchMode = resolveSearchMode($interpretedQuery, $topCandidates);

    // 2차 LLM은 상위 후보만 보고 상단 해설과 카드별 짧은 해설을 만든다.
    $aiResult = requestSearchResult(
        $apiKey,
        $model,
        $promptConfig,
        $query,
        $searchMode,
        $topCandidates
    );

    $selectedIds = array_values(array_map('intval', (array) ($aiResult['playlist_ids'] ?? [])));
    $summary = trim((string) ($aiResult['summary'] ?? ''));

    if (count($selectedIds) !== 3 || $summary === '') {
        app_error('AI 검색 해설 결과를 읽지 못했습니다.', 500);
    }

    if ($fallback['mode'] === 'soft') {
        $summary = $fallback['summary_prefix'] . ' ' . $summary;
    }

    $candidatesById = [];
    foreach ($topCandidates as $candidate) {
        $candidatesById[(int) $candidate['id']] = $candidate;
    }

    $results = [];
    foreach ($selectedIds as $playlistId) {
        if (!isset($candidatesById[$playlistId])) {
            continue;
        }

        $candidate = $candidatesById[$playlistId];
        $results[] = [
            'id' => (int) $candidate['id'],
            'pantone_code' => (string) $candidate['pantone_code'],
            'color_name' => (string) $candidate['color_name'],
            'color_hex' => (string) $candidate['color_hex'],
            'category_label' => (string) $candidate['category_label'],
            'preview_tracks' => $candidate['preview_tracks'],
            'total_tracks' => (int) $candidate['total_tracks'],
        ];
    }

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    app_error('검색 결과를 불러오지 못했습니다: ' . getSearchErrorMessage($e), 500);
}

// ==================================================
// 데이터 로딩
// ==================================================

function loadSearchPlaylists(PDO $pdo): array
{
    $stmt = $pdo->query(
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
            p.play_count,
            c.mood AS category_mood,
            c.label AS category_label,
            c.tag1,
            c.tag2,
            c.tag3,
            pt.track_order,
            t.title AS track_title,
            t.artist AS track_artist
         FROM playlists p
         INNER JOIN categories c
           ON c.id = p.category_id
         LEFT JOIN playlist_tracks pt
           ON pt.playlist_id = p.id
         LEFT JOIN tracks t
           ON t.id = pt.track_id
         ORDER BY p.id ASC, pt.track_order ASC'
    );

    $rows = $stmt->fetchAll();
    $playlists = [];

    foreach ($rows as $row) {
        $playlistId = (int) $row['id'];

        if (!isset($playlists[$playlistId])) {
            $playlists[$playlistId] = [
                'id' => $playlistId,
                'pantone_code' => (string) $row['pantone_code'],
                'color_name' => (string) $row['color_name'],
                'color_hex' => (string) $row['color_hex'],
                'energy_level' => (string) $row['energy_level'],
                'emotion_temperature' => (string) $row['emotion_temperature'],
                'desired_mood' => (string) $row['desired_mood'],
                'day_pace' => (string) $row['day_pace'],
                'record_focus' => (string) $row['record_focus'],
                'play_count' => (int) $row['play_count'],
                'category_mood' => (string) $row['category_mood'],
                'category_label' => (string) $row['category_label'],
                'category_tags' => [
                    (string) $row['tag1'],
                    (string) $row['tag2'],
                    (string) $row['tag3'],
                ],
                'tracks' => [],
                'preview_tracks' => [],
                'total_tracks' => 0,
            ];
        }

        $title = trim((string) ($row['track_title'] ?? ''));
        $artist = trim((string) ($row['track_artist'] ?? ''));

        if ($title === '' && $artist === '') {
            continue;
        }

        $playlists[$playlistId]['tracks'][] = [
            'title' => $title,
            'artist' => $artist,
        ];
        $playlists[$playlistId]['total_tracks']++;

        if (count($playlists[$playlistId]['preview_tracks']) < 3) {
            $playlists[$playlistId]['preview_tracks'][] = [
                'title' => $title,
                'artist' => $artist,
            ];
        }
    }

    return array_values($playlists);
}

// ==================================================
// 1차 LLM: 검색어 해석
// ==================================================

function requestSearchIntent(string $apiKey, string $model, array $promptConfig, string $query): array
{
    $systemPrompt = trim((string) ($promptConfig['intent_system_prompt'] ?? ''));
    $userTemplate = (string) ($promptConfig['intent_user_prompt_template'] ?? '');

    if ($systemPrompt === '' || $userTemplate === '') {
        throw new RuntimeException('검색어 해석 프롬프트가 비어 있습니다.');
    }

    $userPrompt = strtr($userTemplate, ['{{query}}' => $query]);
    $responsePayload = requestOpenAiJson(
        $apiKey,
        $model,
        buildPromptInput($systemPrompt, $userPrompt),
        [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'query_type' => [
                    'type' => 'string',
                    'enum' => ['entity', 'mood', 'hybrid'],
                ],
                'energy_level' => buildEnumArraySchema(['low', 'medium', 'high']),
                'emotion_temperature' => buildEnumArraySchema(['cool', 'neutral', 'heated']),
                'desired_mood' => buildEnumArraySchema(['stability', 'focus', 'refresh', 'immersion', 'release']),
                'day_pace' => buildEnumArraySchema(['slow', 'steady', 'fast']),
                'record_focus' => buildEnumArraySchema(['emotion', 'atmosphere', 'movement', 'recovery', 'confidence']),
            ],
            'required' => [
                'query_type',
                'energy_level',
                'emotion_temperature',
                'desired_mood',
                'day_pace',
                'record_focus',
            ],
        ],
        'search_query_intent'
    );

    return [
        'query_type' => (string) ($responsePayload['decoded']['query_type'] ?? 'hybrid'),
        'energy_level' => normalizeEnumArray($responsePayload['decoded']['energy_level'] ?? [], ['low', 'medium', 'high']),
        'emotion_temperature' => normalizeEnumArray($responsePayload['decoded']['emotion_temperature'] ?? [], ['cool', 'neutral', 'heated']),
        'desired_mood' => normalizeEnumArray($responsePayload['decoded']['desired_mood'] ?? [], ['stability', 'focus', 'refresh', 'immersion', 'release']),
        'day_pace' => normalizeEnumArray($responsePayload['decoded']['day_pace'] ?? [], ['slow', 'steady', 'fast']),
        'record_focus' => normalizeEnumArray($responsePayload['decoded']['record_focus'] ?? [], ['emotion', 'atmosphere', 'movement', 'recovery', 'confidence']),
    ];
}

// ==================================================
// 서버 점수화
// ==================================================

function rankSearchCandidates(array $playlists, string $query, array $interpretedQuery): array
{
    $normalizedQuery = normalizeSearchText($query);
    $queryTokens = tokenizeSearchText($query);
    $queryType = (string) ($interpretedQuery['query_type'] ?? 'hybrid');

    $queryTypeMultipliers = [
        'entity' => ['lexical' => 1.4, 'intent' => 0.6],
        'mood' => ['lexical' => 0.8, 'intent' => 1.3],
        'hybrid' => ['lexical' => 1.0, 'intent' => 1.0],
    ];
    $multipliers = $queryTypeMultipliers[$queryType] ?? $queryTypeMultipliers['hybrid'];

    $ranked = [];

    foreach ($playlists as $playlist) {
        $lexical = calculateLexicalScore($playlist, $normalizedQuery, $queryTokens);
        $intent = calculateIntentScore($playlist, $interpretedQuery);

        $score = ($lexical['score'] * $multipliers['lexical']) + ($intent['score'] * $multipliers['intent']);

        if ($lexical['score'] === 0.0 && $intent['score'] === 0.0) {
            $score -= 5;
        }

        $ranked[] = [
            'id' => (int) $playlist['id'],
            'pantone_code' => (string) $playlist['pantone_code'],
            'color_name' => (string) $playlist['color_name'],
            'color_hex' => (string) $playlist['color_hex'],
            'category_label' => (string) $playlist['category_label'],
            'category_mood' => (string) $playlist['category_mood'],
            'energy_level' => (string) $playlist['energy_level'],
            'emotion_temperature' => (string) $playlist['emotion_temperature'],
            'desired_mood' => (string) $playlist['desired_mood'],
            'day_pace' => (string) $playlist['day_pace'],
            'record_focus' => (string) $playlist['record_focus'],
            'preview_tracks' => $playlist['preview_tracks'],
            'total_tracks' => (int) $playlist['total_tracks'],
            'score' => round($score, 2),
            'lexical_score' => round($lexical['score'], 2),
            'intent_score' => round($intent['score'], 2),
            'play_count' => (int) $playlist['play_count'],
        ];
    }

    usort(
        $ranked,
        static function (array $left, array $right): int {
            if ($left['score'] !== $right['score']) {
                return $right['score'] <=> $left['score'];
            }

            if ($left['lexical_score'] !== $right['lexical_score']) {
                return $right['lexical_score'] <=> $left['lexical_score'];
            }

            if ($left['intent_score'] !== $right['intent_score']) {
                return $right['intent_score'] <=> $left['intent_score'];
            }

            if ($left['play_count'] !== $right['play_count']) {
                return $right['play_count'] <=> $left['play_count'];
            }

            return $left['id'] <=> $right['id'];
        }
    );

    return $ranked;
}

function calculateLexicalScore(array $playlist, string $normalizedQuery, array $queryTokens): array
{
    $score = 0.0;

    $artists = [];
    $titles = [];
    foreach ($playlist['tracks'] as $track) {
        $artists[] = normalizeSearchText((string) $track['artist']);
        $titles[] = normalizeSearchText((string) $track['title']);
    }

    $colorName = normalizeSearchText((string) $playlist['color_name']);
    $pantone = normalizeSearchText((string) $playlist['pantone_code']);
    $categoryLabel = normalizeSearchText((string) $playlist['category_label']);
    $categoryMood = normalizeSearchText((string) $playlist['category_mood']);
    $categoryTags = array_map('normalizeSearchText', (array) $playlist['category_tags']);

    if ($normalizedQuery !== '') {
        if (in_array($normalizedQuery, $artists, true)) {
            $score += 20;
        } elseif (containsAnyText($artists, $normalizedQuery)) {
            $score += 12;
        }

        if (in_array($normalizedQuery, $titles, true)) {
            $score += 18;
        } elseif (containsAnyText($titles, $normalizedQuery)) {
            $score += 10;
        }

        if ($normalizedQuery === $colorName) {
            $score += 12;
        } elseif (containsText($colorName, $normalizedQuery)) {
            $score += 7;
        }

        if ($normalizedQuery === $pantone) {
            $score += 10;
        } elseif (containsText($pantone, $normalizedQuery)) {
            $score += 6;
        }

        if ($normalizedQuery === $categoryLabel || $normalizedQuery === $categoryMood) {
            $score += 8;
        } elseif (containsText($categoryLabel, $normalizedQuery) || containsText($categoryMood, $normalizedQuery)) {
            $score += 5;
        }

        if (in_array($normalizedQuery, $categoryTags, true)) {
            $score += 8;
        } elseif (containsAnyText($categoryTags, $normalizedQuery)) {
            $score += 5;
        }
    }

    $combinedText = implode(' ', array_filter(array_merge(
        [$colorName, $pantone, $categoryLabel, $categoryMood],
        $categoryTags,
        $artists,
        $titles
    )));

    $tokenMatches = 0;
    foreach ($queryTokens as $token) {
        if (containsText($combinedText, $token)) {
            $tokenMatches++;
        }
    }

    if ($tokenMatches > 0) {
        $score += min(6, (float) $tokenMatches);
    }

    return [
        'score' => $score,
    ];
}

function calculateIntentScore(array $playlist, array $interpretedQuery): array
{
    $score = 0.0;

    $weights = [
        'energy_level' => 2.0,
        'emotion_temperature' => 2.0,
        'desired_mood' => 4.0,
        'day_pace' => 2.0,
        'record_focus' => 3.0,
    ];

    foreach ($weights as $field => $weight) {
        $values = is_array($interpretedQuery[$field] ?? null) ? $interpretedQuery[$field] : [];
        if ($values === []) {
            continue;
        }

        $playlistValue = (string) ($playlist[$field] ?? '');
        if (in_array($playlistValue, $values, true)) {
            $score += $weight;
        }
    }

    return [
        'score' => $score,
    ];
}

function resolveSearchMode(array $interpretedQuery, array $topCandidates): string
{
    $queryType = (string) ($interpretedQuery['query_type'] ?? 'hybrid');
    $topCandidate = $topCandidates[0] ?? null;

    if (!is_array($topCandidate)) {
        return 'hybrid';
    }

    $lexical = (float) ($topCandidate['lexical_score'] ?? 0);
    $intent = (float) ($topCandidate['intent_score'] ?? 0);

    if ($queryType === 'entity' || $lexical >= ($intent + 5)) {
        return 'lexical';
    }

    if ($queryType === 'mood' || $intent > $lexical) {
        return 'intent';
    }

    return 'hybrid';
}

function detectSearchFallback(string $query, array $interpretedQuery, array $rankedCandidates): array
{
    $queryType = (string) ($interpretedQuery['query_type'] ?? 'hybrid');
    $hasIntentAxes =
        ($interpretedQuery['energy_level'] ?? []) !== [] ||
        ($interpretedQuery['emotion_temperature'] ?? []) !== [] ||
        ($interpretedQuery['desired_mood'] ?? []) !== [] ||
        ($interpretedQuery['day_pace'] ?? []) !== [] ||
        ($interpretedQuery['record_focus'] ?? []) !== [];

    $maxLexical = 0.0;
    $maxScore = 0.0;

    foreach ($rankedCandidates as $candidate) {
        $maxLexical = max($maxLexical, (float) ($candidate['lexical_score'] ?? 0));
        $maxScore = max($maxScore, (float) ($candidate['score'] ?? 0));
    }

    $hasHistoryPhrase = (bool) preg_match(
        '/(저번주|지난주|이번주|어제|오늘|최근|요즘|자주\s*듣|많이\s*듣|들었던|기억나|뭐였지|내가|언제)/u',
        $query
    );

    // 개인 기록형 질문은 현재 검색 API가 직접 답할 수 없다.
    if ($hasHistoryPhrase && $maxLexical < 8 && !$hasIntentAxes) {
        return [
            'mode' => 'unsupported',
            'summary' => formatQuotedQuery($query) . ' 같은 질문은 아직 지원하지 않아요. '
                . '지금은 곡, 아티스트, 색상, 분위기 검색 중심으로만 찾을 수 있어요.',
        ];
    }

    // 직접 검색으로 보이는데 일치하는 문자열 단서가 거의 없으면 부드럽게 대체 추천으로 안내한다.
    if ($queryType === 'entity' && $maxLexical < 8 && $maxScore > 0) {
        return [
            'mode' => 'soft',
            'summary_prefix' => formatQuotedQuery($query)
                . ' 검색 결과는 직접 찾지 못했어요. 대신 가까운 분위기의 플레이리스트를 추천해드릴게요.',
        ];
    }

    // 전체 점수가 너무 낮으면 결과 없음으로 처리한다.
    if ($maxScore <= 0) {
        return [
            'mode' => 'unsupported',
            'summary' => formatQuotedQuery($query)
                . '에 맞는 곡이나 색은 아직 찾지 못했어요. 다른 아티스트명이나 분위기로 다시 검색해 주세요.',
        ];
    }

    return [
        'mode' => 'normal',
    ];
}

// ==================================================
// 2차 LLM: 해설 생성
// ==================================================

function requestSearchResult(
    string $apiKey,
    string $model,
    array $promptConfig,
    string $query,
    string $searchMode,
    array $topCandidates
): array {
    $systemPrompt = trim((string) ($promptConfig['result_system_prompt'] ?? ''));
    $userTemplate = (string) ($promptConfig['result_user_prompt_template'] ?? '');

    if ($systemPrompt === '' || $userTemplate === '') {
        throw new RuntimeException('검색 결과 해설 프롬프트가 비어 있습니다.');
    }

    $promptCandidates = array_map(
        static function (array $candidate): array {
            return [
                'id' => (int) $candidate['id'],
                'pantone_code' => (string) $candidate['pantone_code'],
                'color_name' => (string) $candidate['color_name'],
                'category_label' => (string) $candidate['category_label'],
                'energy_level' => (string) $candidate['energy_level'],
                'emotion_temperature' => (string) $candidate['emotion_temperature'],
                'desired_mood' => (string) $candidate['desired_mood'],
                'day_pace' => (string) $candidate['day_pace'],
                'record_focus' => (string) $candidate['record_focus'],
                'preview_tracks' => $candidate['preview_tracks'],
            ];
        },
        $topCandidates
    );

    $userPrompt = strtr($userTemplate, [
        '{{query}}' => $query,
        '{{search_mode}}' => $searchMode,
        '{{candidates_json}}' => json_encode($promptCandidates, JSON_UNESCAPED_UNICODE),
    ]);

    $responsePayload = requestOpenAiJson(
        $apiKey,
        $model,
        buildPromptInput($systemPrompt, $userPrompt),
        [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'summary' => ['type' => 'string'],
                'playlist_ids' => [
                    'type' => 'array',
                    'minItems' => 3,
                    'maxItems' => 3,
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'required' => ['summary', 'playlist_ids'],
        ],
        'search_result_summary'
    );

    $decoded = is_array($responsePayload['decoded']) ? $responsePayload['decoded'] : [];
    $playlistIds = array_values(array_map('intval', (array) ($decoded['playlist_ids'] ?? [])));

    return [
        'summary' => trim((string) ($decoded['summary'] ?? '')),
        'playlist_ids' => $playlistIds,
    ];
}

// ==================================================
// OpenAI 공통
// ==================================================

function buildPromptInput(string $systemPrompt, string $userPrompt): array
{
    return [
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
    ];
}

function buildEnumArraySchema(array $enumValues): array
{
    return [
        'type' => 'array',
        'items' => [
            'type' => 'string',
            'enum' => array_values($enumValues),
        ],
    ];
}

function requestOpenAiJson(
    string $apiKey,
    string $model,
    array $input,
    array $schema,
    string $schemaName
): array {
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
            'input' => $input,
            'text' => [
                'verbosity' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'schema' => $schema,
                    'strict' => true,
                ],
            ],
            'reasoning' => [
                'effort' => 'low',
            ],
            'max_output_tokens' => 700,
        ],
    ]);

    $responsePayload = json_decode((string) $response->getBody(), true);
    if (!is_array($responsePayload)) {
        return [
            'decoded' => [],
        ];
    }

    $textOutput = findOpenAiOutputText($responsePayload);
    if ($textOutput === null) {
        return [
            'decoded' => [],
        ];
    }

    return [
        'decoded' => json_decode($textOutput, true) ?: [],
    ];
}

function findOpenAiOutputText(array $responsePayload): ?string
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

// ==================================================
// 문자열 유틸
// ==================================================

function normalizeSearchText(string $value): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }

    return strtolower($value);
}

function tokenizeSearchText(string $value): array
{
    $normalized = normalizeSearchText($value);
    if ($normalized === '') {
        return [];
    }

    $tokens = preg_split('/\s+/u', $normalized) ?: [];

    return array_values(array_filter(
        $tokens,
        static function (string $token): bool {
            if ($token === '') {
                return false;
            }

            if (function_exists('mb_strlen')) {
                return mb_strlen($token, 'UTF-8') >= 2;
            }

            return strlen($token) >= 2;
        }
    ));
}

function containsText(string $haystack, string $needle): bool
{
    if ($haystack === '' || $needle === '') {
        return false;
    }

    if (function_exists('mb_stripos')) {
        return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    return stripos($haystack, $needle) !== false;
}

function containsAnyText(array $haystacks, string $needle): bool
{
    foreach ($haystacks as $haystack) {
        if (containsText((string) $haystack, $needle)) {
            return true;
        }
    }

    return false;
}

function normalizeEnumArray(array $values, array $allowed): array
{
    $normalized = [];

    foreach ($values as $value) {
        $stringValue = strtolower(trim((string) $value));
        if ($stringValue === '' || !in_array($stringValue, $allowed, true)) {
            continue;
        }

        $normalized[] = $stringValue;
    }

    return array_values(array_unique($normalized));
}

function formatQuotedQuery(string $query): string
{
    $trimmed = trim($query);
    if ($trimmed === '') {
        return '이 검색어';
    }

    return '"' . $trimmed . '"';
}

// ==================================================
// 에러 메시지
// ==================================================

function getSearchErrorMessage(Throwable $error): string
{
    if (method_exists($error, 'getResponse')) {
        $response = $error->getResponse();
        if ($response !== null && method_exists($response, 'getBody')) {
            $body = (string) $response->getBody();
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $apiMessage = trim((string) ($decoded['error']['message'] ?? ''));
                    if ($apiMessage !== '') {
                        return $apiMessage;
                    }
                }

                return $body;
            }
        }
    }

    return $error->getMessage();
}
