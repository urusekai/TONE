<?php

require_once __DIR__ . '/../../bootstrap.php';

use GuzzleHttp\Client;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$requestStartedAt = microtime(true);

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

// -----------------------------
// 1. 요청 파싱
// -----------------------------

// 요청 본문에서 설문 답변만 추려서 enum 값으로 정리한다.
$answersInput = is_array($payload['answers'] ?? null) ? $payload['answers'] : $payload;
$userAnswers = parseDailySpectrumAnswers($answersInput);
$excludedPlaylistId = parseExcludedDailySpectrumPlaylistId($payload);

try {
  // -----------------------------
  // 2. 데이터 조회와 후보 선정
  // -----------------------------

  // 프롬프트 템플릿을 불러온다.
  $promptConfig = require __DIR__ . '/../../prompts/daily-spectrum.php';
  if (!is_array($promptConfig)) {
    throw new RuntimeException('프롬프트 설정을 불러오지 못했습니다.');
  }

  // 추천 후보로 쓸 플레이리스트 전체 목록을 조회한다.
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

  // 오늘의 데일리 톤은 추천 후보에서 제외한다.
  if ($excludedPlaylistId !== null) {
    $playlistRows = array_values(array_filter(
      $playlistRows,
      static fn(array $row): bool => (int) $row['id'] !== $excludedPlaylistId
    ));
  }

  if (count($playlistRows) < 3) {
    app_error('추천 가능한 플레이리스트가 부족합니다.', 404);
  }

  // LLM에 넘길 후보 12개를 서버에서 먼저 추린다.
  $candidateRows = pickDailySpectrumCandidates($playlistRows, $userAnswers, 12);

  // -----------------------------
  // 3. LLM 호출
  // -----------------------------

  // 프롬프트와 응답에 함께 쓸 질문-답변 쌍을 만든다.
  $questionAnswerPairs = buildDailySpectrumQuestionAnswerPairs($userAnswers);

  // 프롬프트에 넣을 후보 데이터만 간단히 정리한다.
  $promptPlaylists = array_map(
    static function (array $row): array {
      return [
        'id' => (int) $row['id'],
        'rank' => (int) $row['rank'],
        'match_score' => (int) $row['match_score'],
        'color_name' => (string) $row['color_name'],
        'energy_level' => (string) $row['energy_level'],
        'emotion_temperature' => (string) $row['emotion_temperature'],
        'desired_mood' => (string) $row['desired_mood'],
        'day_pace' => (string) $row['day_pace'],
        'record_focus' => (string) $row['record_focus'],
      ];
    },
    $candidateRows
  );

  // system/user 프롬프트 문자열을 완성한다.
  [$systemPrompt, $userPrompt] = createDailySpectrumPrompts(
    $promptConfig,
    $userAnswers,
    $questionAnswerPairs,
    $promptPlaylists
  );
  $llmInput = buildDailySpectrumLlmInput($systemPrompt, $userPrompt);

  // 모델 호출에 필요한 환경 변수를 읽는다.
  $apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? ''));
  $model = trim((string) ($_ENV['OPENAI_MODEL'] ?? 'gpt-5-mini'));

  if ($apiKey === '') {
    app_error('OpenAI API 키가 설정되지 않았습니다.', 500);
  }

  if ($model === '') {
    app_error('OpenAI 모델이 설정되지 않았습니다.', 500);
  }

  // OpenAI Responses API를 호출한다.
  $llmStartedAt = microtime(true);
  $llmResponse = requestDailySpectrumResult($apiKey, $model, $llmInput);
  $llmElapsedMs = (int) round((microtime(true) - $llmStartedAt) * 1000);
  $llmUsage = is_array($llmResponse['usage'] ?? null) ? $llmResponse['usage'] : [];

  // -----------------------------
  // 4. 응답 조립
  // -----------------------------

  // LLM 응답에서 화면에 쓸 값만 느슨하게 꺼낸다.
  $selectedIds = array_values(array_map('intval', (array) ($llmResponse['playlist_ids'] ?? [])));
  $explanation = trim((string) ($llmResponse['explanation'] ?? ''));

  if ($selectedIds === [] || $explanation === '') {
    app_error('LLM 추천 결과를 읽지 못했습니다.', 500);
  }

  // 최종 응답에 넣을 플레이리스트 기본 정보만 id 기준으로 준비한다.
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

  // LLM이 고른 id 순서대로 응답용 playlist 목록을 만든다.
  $spectrumPlaylists = [];
  foreach ($selectedIds as $playlistId) {
    if (isset($playlistsById[$playlistId])) {
      $spectrumPlaylists[] = $playlistsById[$playlistId];
    }
  }

  // 프론트에서 바로 쓸 수 있는 형태로 응답한다.
  echo json_encode([
    'success' => true,
    'playlist_ids' => $selectedIds,
    'explanation' => $explanation,
    'spectrumPlaylists' => $spectrumPlaylists,
    'questionAnswerPairs' => $questionAnswerPairs,
    'llmInput' => $llmInput,
    'usedModel' => $model,
    'llmUsage' => $llmUsage,
    'timing' => [
      'total_ms' => (int) round((microtime(true) - $requestStartedAt) * 1000),
      'llm_ms' => $llmElapsedMs,
    ],
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  app_error('데일리 스펙트럼 추천을 불러오지 못했습니다: ' . getDailySpectrumErrorMessage($e), 500);
}

// ==================================================
// 입력 처리
// ==================================================

// 설문 답변을 서버 내부에서 쓰는 enum 값 형태로 정리한다.
function parseDailySpectrumAnswers(array $input): array
{
  $allowedValues = [
    'energy_level' => ['low', 'medium', 'high'],
    'emotion_temperature' => ['cool', 'neutral', 'heated'],
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

// 오늘의 데일리 톤 id를 숫자로 정리한다.
function parseExcludedDailySpectrumPlaylistId(array $payload): ?int
{
  $rawId = $payload['daily_playlist_id'] ?? $payload['dailyPlaylistId'] ?? null;
  if ($rawId === null || $rawId === '') {
    return null;
  }

  if (is_int($rawId) || ctype_digit((string) $rawId)) {
    return (int) $rawId;
  }

  app_error('유효한 daily_playlist_id 값이 필요합니다.', 400);
}

// ==================================================
// 프롬프트 구성
// ==================================================

// 프롬프트 템플릿에 사용자 답변, 질문-답변 쌍, 후보 목록을 채워 넣는다.
function createDailySpectrumPrompts(
  array $promptConfig,
  array $userAnswers,
  array $questionAnswerPairs,
  array $promptPlaylists
): array {
  $systemPrompt = trim((string) ($promptConfig['system_prompt'] ?? ''));
  $userTemplate = (string) ($promptConfig['user_prompt_template'] ?? '');

  if ($systemPrompt === '' || $userTemplate === '') {
    throw new RuntimeException('프롬프트 템플릿이 올바르지 않습니다.');
  }

  $userPrompt = strtr($userTemplate, [
    '{{user_answers_json}}' => json_encode($userAnswers, JSON_UNESCAPED_UNICODE),
    '{{question_answer_pairs_json}}' => json_encode($questionAnswerPairs, JSON_UNESCAPED_UNICODE),
    '{{playlists_json}}' => json_encode($promptPlaylists, JSON_UNESCAPED_UNICODE),
  ]);

  return [$systemPrompt, $userPrompt];
}

// OpenAI Responses API에 넘길 input 배열을 만든다.
function buildDailySpectrumLlmInput(string $systemPrompt, string $userPrompt): array
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

// 응답에 실어 보낼 질문-답변 쌍을 설문 JSON 기준으로 만든다.
function buildDailySpectrumQuestionAnswerPairs(array $userAnswers): array
{
  $questionnaire = loadDailySpectrumQuestionnaire();
  $questions = is_array($questionnaire['questions'] ?? null) ? $questionnaire['questions'] : [];
  $pairs = [];

  foreach ($questions as $question) {
    if (!is_array($question)) {
      continue;
    }

    $field = (string) ($question['field'] ?? '');
    if ($field === '' || !isset($userAnswers[$field])) {
      continue;
    }

    $value = (string) $userAnswers[$field];
    $label = $value;
    $choices = is_array($question['choices'] ?? null) ? $question['choices'] : [];

    foreach ($choices as $choice) {
      if (!is_array($choice)) {
        continue;
      }

      if ((string) ($choice['value'] ?? '') === $value) {
        $label = (string) ($choice['label'] ?? $value);
        break;
      }
    }

    $pairs[] = [
      'field' => $field,
      'question' => (string) ($question['question'] ?? ''),
      'value' => $value,
      'label' => $label,
    ];
  }

  return $pairs;
}

// 프론트 설문 JSON을 읽어서 질문/선택지 메타를 가져온다.
function loadDailySpectrumQuestionnaire(): array
{
  static $questionnaire = null;

  if (is_array($questionnaire)) {
    return $questionnaire;
  }

  $path = __DIR__ . '/../../../frontend/src/data/daily-spectrum-questionnaire.json';
  if (!is_file($path)) {
    $questionnaire = [];
    return $questionnaire;
  }

  $decoded = json_decode((string) file_get_contents($path), true);
  $questionnaire = is_array($decoded) ? $decoded : [];

  return $questionnaire;
}

// ==================================================
// 후보 선정
// ==================================================

// 전체 플레이리스트 중에서 LLM에 보낼 상위 후보만 추린다.
function pickDailySpectrumCandidates(array $playlistRows, array $userAnswers, int $limit = 8): array
{
  $rankedRows = [];

  foreach ($playlistRows as $row) {
    // 플레이리스트 1개에 대한 매칭 점수를 계산한다.
    // 사용자 의도(desired_mood, record_focus)를 상태 축보다 더 크게 반영한다.
    $score = 0;
    $priorityMatches = 0;
    $stateMatches = 0;

    $energyScore = scoreScaleMatch(
      (string) $userAnswers['energy_level'],
      (string) $row['energy_level'],
      ['low', 'medium', 'high']
    );
    $emotionScore = scoreScaleMatch(
      (string) $userAnswers['emotion_temperature'],
      (string) $row['emotion_temperature'],
      ['cool', 'neutral', 'heated']
    );

    $score += $energyScore;
    $score += $emotionScore;

    if ($energyScore === 2) {
      $stateMatches++;
    }
    if ($emotionScore === 2) {
      $stateMatches++;
    }

    if ((string) $userAnswers['desired_mood'] === (string) $row['desired_mood']) {
      $score += 8;
      $priorityMatches += 2;
    }

    if ((string) $userAnswers['record_focus'] === (string) $row['record_focus']) {
      $score += 6;
      $priorityMatches++;
    }

    $rankedRows[] = [
      'row' => $row,
      'score' => $score,
      'priority_matches' => $priorityMatches,
      'state_matches' => $stateMatches,
    ];
  }

  usort(
    $rankedRows,
    static function (array $left, array $right): int {
      // 후보 순위를 정한다.
      if ($left['score'] !== $right['score']) {
        return $right['score'] <=> $left['score'];
      }

      if ($left['priority_matches'] !== $right['priority_matches']) {
        return $right['priority_matches'] <=> $left['priority_matches'];
      }

      if ($left['state_matches'] !== $right['state_matches']) {
        return $right['state_matches'] <=> $left['state_matches'];
      }

      return ((int) $left['row']['id']) <=> ((int) $right['row']['id']);
    }
  );

  $topRows = array_slice($rankedRows, 0, max(3, $limit));

  foreach ($topRows as $index => &$item) {
    $item['row']['rank'] = $index + 1;
    $item['row']['match_score'] = (int) $item['score'];
  }
  unset($item);

  return array_map(
    static function (array $item): array {
      return $item['row'];
    },
    $topRows
  );
}

// 순서가 있는 축을 기준으로 가까운 정도를 점수로 바꾼다.
function scoreScaleMatch(string $target, string $candidate, array $scale): int
{
  $targetIndex = array_search($target, $scale, true);
  $candidateIndex = array_search($candidate, $scale, true);

  if ($targetIndex === false || $candidateIndex === false) {
    return 0;
  }

  $distance = abs($targetIndex - $candidateIndex);
  if ($distance === 0) {
    return 2;
  }

  if ($distance === 1) {
    return 1;
  }

  return 0;
}

// ==================================================
// LLM 호출과 응답 파싱
// ==================================================

// OpenAI에 추천 결과 생성을 요청한다.
function requestDailySpectrumResult(
  string $apiKey,
  string $model,
  array $input
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
          'name' => 'daily_spectrum_result',
          'schema' => [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
              'playlist_ids' => [
                'type' => 'array',
                'items' => [
                  'type' => 'integer',
                ],
                'minItems' => 3,
                'maxItems' => 3,
              ],
              'explanation' => [
                'type' => 'string',
              ],
            ],
            'required' => ['playlist_ids', 'explanation'],
          ],
          'strict' => true,
        ],
      ],
      'reasoning' => [
        'effort' => 'low',
      ],
      'max_output_tokens' => 500,
    ],
  ]);

  $responsePayload = json_decode((string) $response->getBody(), true);
  if (!is_array($responsePayload)) {
    return [
      'playlist_ids' => [],
      'explanation' => '',
      'usage' => [],
    ];
  }

  // 응답 본문에서 모델이 만든 텍스트 부분만 찾는다.
  $textOutput = findDailySpectrumOutputText($responsePayload);
  if ($textOutput === null) {
    return [
      'playlist_ids' => [],
      'explanation' => '',
      'usage' => parseDailySpectrumUsage($responsePayload),
    ];
  }

  $decoded = json_decode($textOutput, true);
  return [
    'playlist_ids' => array_values((array) ($decoded['playlist_ids'] ?? [])),
    'explanation' => trim((string) ($decoded['explanation'] ?? '')),
    'usage' => parseDailySpectrumUsage($responsePayload),
  ];
}

// Responses API 응답에서 실제 텍스트 출력만 꺼낸다.
function findDailySpectrumOutputText(array $responsePayload): ?string
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

// 응답에서 토큰 사용량만 따로 정리한다.
function parseDailySpectrumUsage(array $responsePayload): array
{
  $usage = $responsePayload['usage'] ?? null;
  if (!is_array($usage)) {
    return [];
  }

  $inputTokensDetails = is_array($usage['input_tokens_details'] ?? null)
    ? $usage['input_tokens_details']
    : [];
  $outputTokensDetails = is_array($usage['output_tokens_details'] ?? null)
    ? $usage['output_tokens_details']
    : [];

  return [
    'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
    'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
    'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
    'cached_tokens' => (int) ($inputTokensDetails['cached_tokens'] ?? 0),
    'reasoning_tokens' => (int) ($outputTokensDetails['reasoning_tokens'] ?? 0),
  ];
}

// ==================================================
// 에러 메시지 정리
// ==================================================

// OpenAI 에러를 사용자 응답에 넣을 문자열로 정리한다.
function getDailySpectrumErrorMessage(Throwable $error): string
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
