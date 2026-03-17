$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$envPath = Join-Path $projectRoot '.env'

if (-not (Test-Path $envPath)) {
    throw ".env 파일을 찾을 수 없습니다: $envPath"
}

$envMap = @{}
Get-Content $envPath | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq '' -or $line.StartsWith('#')) {
        return
    }

    $parts = $line -split '=', 2
    if ($parts.Count -eq 2) {
        $envMap[$parts[0].Trim()] = $parts[1]
    }
}

$apiKey = $envMap['OPENAI_API_KEY']
$model = $envMap['OPENAI_MODEL']

if ([string]::IsNullOrWhiteSpace($apiKey)) {
    throw 'OPENAI_API_KEY 값이 비어 있습니다.'
}

if ([string]::IsNullOrWhiteSpace($model)) {
    throw 'OPENAI_MODEL 값이 비어 있습니다.'
}

$schema = @{
    table = 'playlists'
    description = '색상 기반 플레이리스트 추천 대상 테이블'
    selection_goal = '사용자 답변과 가장 자연스럽게 맞는 플레이리스트 3개를 추천한다.'
    fields = @(
        @{ name = 'id'; type = 'int'; required = $true; description = '플레이리스트 고유 ID' },
        @{ name = 'color_name'; type = 'string'; required = $true; description = '플레이리스트를 구분하기 위한 표시용 색상 이름' },
        @{ name = 'energy_level'; type = 'enum(low|medium|high)'; required = $true; description = '사용자의 현재 에너지 상태와 대응되는 축' },
        @{ name = 'emotion_temperature'; type = 'enum(cool|neutral|warm)'; required = $true; description = '사용자의 감정 온도와 대응되는 축' },
        @{ name = 'desired_mood'; type = 'enum(stability|focus|refresh|immersion|release)'; required = $true; description = '지금 사용자에게 필요한 분위기 축' },
        @{ name = 'day_pace'; type = 'enum(slow|steady|fast)'; required = $true; description = '오늘 하루의 속도감 축' },
        @{ name = 'record_focus'; type = 'enum(emotion|atmosphere|movement|recovery|confidence)'; required = $true; description = '오늘 기록에서 더 남기고 싶은 핵심 축' }
    )
}

$questionnaire = @(
    @{
        order = 1
        question = '지금 에너지는 어느 쪽에 가까운가요?'
        target_field = 'energy_level'
        choices = @(
            @{ label = '낮음'; value = 'low' },
            @{ label = '보통'; value = 'medium' },
            @{ label = '높음'; value = 'high' }
        )
    },
    @{
        order = 2
        question = '오늘의 감정 온도는 어떤가요?'
        target_field = 'emotion_temperature'
        choices = @(
            @{ label = '차분함'; value = 'cool' },
            @{ label = '중간'; value = 'neutral' },
            @{ label = '뜨거움'; value = 'warm' }
        )
    },
    @{
        order = 3
        question = '지금 필요한 분위기는 무엇인가요?'
        target_field = 'desired_mood'
        choices = @(
            @{ label = '안정'; value = 'stability' },
            @{ label = '집중'; value = 'focus' },
            @{ label = '환기'; value = 'refresh' },
            @{ label = '몰입'; value = 'immersion' },
            @{ label = '해소'; value = 'release' }
        )
    },
    @{
        order = 4
        question = '오늘 하루의 속도감은 어땠나요?'
        target_field = 'day_pace'
        choices = @(
            @{ label = '느림'; value = 'slow' },
            @{ label = '일정함'; value = 'steady' },
            @{ label = '빠름'; value = 'fast' }
        )
    },
    @{
        order = 5
        question = '오늘 기록에 더 남기고 싶은 건?'
        target_field = 'record_focus'
        choices = @(
            @{ label = '감정'; value = 'emotion' },
            @{ label = '분위기'; value = 'atmosphere' },
            @{ label = '움직임'; value = 'movement' },
            @{ label = '회복'; value = 'recovery' },
            @{ label = '자신감'; value = 'confidence' }
        )
    }
)

$userAnswers = @{
    energy_level = 'medium'
    emotion_temperature = 'warm'
    desired_mood = 'refresh'
    day_pace = 'steady'
    record_focus = 'confidence'
}

$playlists = @(
    @{ id = 1; color_name = 'Viva Magenta'; energy_level = 'high'; emotion_temperature = 'warm'; desired_mood = 'release'; day_pace = 'fast'; record_focus = 'confidence' },
    @{ id = 2; color_name = 'Electric Blue'; energy_level = 'high'; emotion_temperature = 'cool'; desired_mood = 'focus'; day_pace = 'fast'; record_focus = 'movement' },
    @{ id = 3; color_name = 'Fiery Red'; energy_level = 'high'; emotion_temperature = 'warm'; desired_mood = 'immersion'; day_pace = 'fast'; record_focus = 'confidence' },
    @{ id = 4; color_name = 'Cyber Yellow'; energy_level = 'high'; emotion_temperature = 'warm'; desired_mood = 'refresh'; day_pace = 'fast'; record_focus = 'movement' },
    @{ id = 5; color_name = 'Classic Blue'; energy_level = 'low'; emotion_temperature = 'cool'; desired_mood = 'stability'; day_pace = 'slow'; record_focus = 'emotion' },
    @{ id = 6; color_name = 'Dusk Blue'; energy_level = 'low'; emotion_temperature = 'neutral'; desired_mood = 'stability'; day_pace = 'slow'; record_focus = 'recovery' },
    @{ id = 7; color_name = 'Anthracite'; energy_level = 'low'; emotion_temperature = 'cool'; desired_mood = 'immersion'; day_pace = 'slow'; record_focus = 'emotion' },
    @{ id = 8; color_name = 'Deep Wisteria'; energy_level = 'medium'; emotion_temperature = 'neutral'; desired_mood = 'immersion'; day_pace = 'steady'; record_focus = 'emotion' },
    @{ id = 9; color_name = 'Living Coral'; energy_level = 'medium'; emotion_temperature = 'warm'; desired_mood = 'refresh'; day_pace = 'steady'; record_focus = 'movement' },
    @{ id = 10; color_name = 'Peach Echo'; energy_level = 'medium'; emotion_temperature = 'warm'; desired_mood = 'refresh'; day_pace = 'steady'; record_focus = 'atmosphere' },
    @{ id = 11; color_name = 'Ultra Violet'; energy_level = 'medium'; emotion_temperature = 'neutral'; desired_mood = 'immersion'; day_pace = 'steady'; record_focus = 'atmosphere' },
    @{ id = 12; color_name = 'Nugget Gold'; energy_level = 'medium'; emotion_temperature = 'warm'; desired_mood = 'release'; day_pace = 'steady'; record_focus = 'confidence' },
    @{ id = 13; color_name = 'Illuminating'; energy_level = 'high'; emotion_temperature = 'warm'; desired_mood = 'refresh'; day_pace = 'fast'; record_focus = 'confidence' },
    @{ id = 14; color_name = 'Sunny Lime'; energy_level = 'high'; emotion_temperature = 'warm'; desired_mood = 'refresh'; day_pace = 'fast'; record_focus = 'recovery' },
    @{ id = 15; color_name = 'Sky Blue'; energy_level = 'medium'; emotion_temperature = 'cool'; desired_mood = 'refresh'; day_pace = 'steady'; record_focus = 'atmosphere' },
    @{ id = 16; color_name = 'Prism Pink'; energy_level = 'medium'; emotion_temperature = 'warm'; desired_mood = 'refresh'; day_pace = 'steady'; record_focus = 'confidence' },
    @{ id = 17; color_name = 'Cloud Dancer'; energy_level = 'low'; emotion_temperature = 'cool'; desired_mood = 'stability'; day_pace = 'slow'; record_focus = 'recovery' },
    @{ id = 18; color_name = 'Glacier Gray'; energy_level = 'low'; emotion_temperature = 'neutral'; desired_mood = 'stability'; day_pace = 'slow'; record_focus = 'emotion' },
    @{ id = 19; color_name = 'Quiet Shade'; energy_level = 'low'; emotion_temperature = 'cool'; desired_mood = 'focus'; day_pace = 'steady'; record_focus = 'atmosphere' },
    @{ id = 20; color_name = 'Warm Sand'; energy_level = 'low'; emotion_temperature = 'warm'; desired_mood = 'stability'; day_pace = 'slow'; record_focus = 'recovery' },
    @{ id = 21; color_name = 'Insignia Blue'; energy_level = 'high'; emotion_temperature = 'cool'; desired_mood = 'focus'; day_pace = 'fast'; record_focus = 'emotion' },
    @{ id = 22; color_name = 'Biking Red'; energy_level = 'high'; emotion_temperature = 'warm'; desired_mood = 'release'; day_pace = 'fast'; record_focus = 'movement' },
    @{ id = 23; color_name = 'Jet Black'; energy_level = 'high'; emotion_temperature = 'neutral'; desired_mood = 'immersion'; day_pace = 'fast'; record_focus = 'movement' },
    @{ id = 24; color_name = 'Iron'; energy_level = 'medium'; emotion_temperature = 'neutral'; desired_mood = 'focus'; day_pace = 'steady'; record_focus = 'confidence' }
)

$questionnaireJson = $questionnaire | ConvertTo-Json -Depth 10
$schemaJson = $schema | ConvertTo-Json -Depth 10
$userAnswersJson = $userAnswers | ConvertTo-Json -Depth 10
$playlistsJson = $playlists | ConvertTo-Json -Depth 10

$systemPrompt = @'
당신은 사용자의 상태를 해석해 색상 기반 플레이리스트 3개를 추천하는 큐레이터다.

판단 기준:
- 사용자의 답변 5개 축을 가장 우선해서 본다.
- 추천 대상은 반드시 입력으로 제공된 playlists 목록 안에서만 고른다.
- color_name은 플레이리스트를 구분하기 위한 표시용 정보일 뿐이며, 추천 판단의 핵심 근거로 사용하지 않는다.
- 정확히 3개의 서로 다른 playlist_id를 추천한다.
- 각 추천은 사용자 답변과의 적합성이 분명해야 한다.
- 3개 모두 완전히 같은 느낌만 반복하지 말고, 답변과 맞는 범위 안에서 약간의 결 차이는 허용한다.
- 입력에 없는 필드나 임의의 사실을 만들지 않는다.
- 우선순위는 5개 축의 전체 조합 적합도다.

출력 규칙:
- 반드시 JSON만 출력한다.
- playlist_ids 배열 길이는 반드시 3이어야 한다.
- explanation은 한국어로 자연스럽게 작성한다.
- explanation은 1~2문장으로 짧게 작성한다.
'@

$userPrompt = @"
[질문지 JSON]
$questionnaireJson

[playlists 스키마 JSON]
$schemaJson

[사용자 답변 JSON]
[주의: 사용자 답변 값은 프론트에서 이미 영어 enum 값으로 정규화되어 전달된 값이다.]
$userAnswersJson

[추천 대상 playlists 행 JSON]
$playlistsJson

작업:
1. 사용자 답변 5개 축과 각 플레이리스트 행의 5개 축을 비교한다.
2. color_name은 플레이리스트를 식별하기 위한 표시용 정보로만 참고하고, 추천 판단은 반드시 5개 축을 기준으로 한다.
3. 전체적으로 가장 잘 맞는 플레이리스트 3개를 고른다.
4. 결과는 반드시 JSON만 출력한다.
5. playlist_ids 배열에는 서로 다른 playlist_id 3개만 넣는다.
6. explanation에는 사용자의 답변 내용을 자연스럽게 요약하고, 왜 이 3개를 골랐는지 추천 이유를 함께 쓴다.
"@

$headers = @{
    Authorization = "Bearer $apiKey"
    'Content-Type' = 'application/json'
}

$bodyObject = @{
    model = $model
    reasoning = @{
        effort = 'low'
    }
    input = @(
        @{
            role = 'system'
            content = @(
                @{
                    type = 'input_text'
                    text = $systemPrompt
                }
            )
        },
        @{
            role = 'user'
            content = @(
                @{
                    type = 'input_text'
                    text = $userPrompt
                }
            )
        }
    )
    text = @{
        verbosity = 'low'
        format = @{
            type = 'json_schema'
            name = 'daily_spectrum_recommendation'
            strict = $true
            schema = @{
                type = 'object'
                additionalProperties = $false
                required = @('playlist_ids', 'explanation')
                properties = @{
                    playlist_ids = @{
                        type = 'array'
                        items = @{
                            type = 'integer'
                        }
                    }
                    explanation = @{
                        type = 'string'
                    }
                }
            }
        }
    }
    max_output_tokens = 1000
}

$bodyJson = $bodyObject | ConvertTo-Json -Depth 30

Write-Host ('playlist count: {0}' -f $playlists.Count)
Write-Host ('system prompt chars: {0}' -f $systemPrompt.Length)
Write-Host ('user prompt chars: {0}' -f $userPrompt.Length)
Write-Host 'Sending request to OpenAI Responses API...'

$startedAt = Get-Date

$response = Invoke-RestMethod `
    -Uri 'https://api.openai.com/v1/responses' `
    -Method POST `
    -Headers $headers `
    -Body $bodyJson

$elapsed = (Get-Date) - $startedAt

Write-Host ("Completed in {0:N2}s" -f $elapsed.TotalSeconds)
$response | ConvertTo-Json -Depth 30
