<?php

return [
    'version' => '2026-03-17',
    'schema' => [
        'table' => 'playlists',
        'description' => '색상 기반 플레이리스트 추천 대상 테이블',
        'selection_goal' => '사용자 답변과 가장 자연스럽게 맞는 플레이리스트 3개를 추천한다.',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'int',
                'required' => true,
                'description' => '플레이리스트 고유 ID',
            ],
            [
                'name' => 'color_name',
                'type' => 'string',
                'required' => true,
                'description' => '플레이리스트를 구분하기 위한 표시용 색상 이름',
            ],
            [
                'name' => 'energy_level',
                'type' => 'enum(low|medium|high)',
                'required' => true,
                'description' => '사용자의 현재 에너지 상태와 대응되는 축',
            ],
            [
                'name' => 'emotion_temperature',
                'type' => 'enum(cool|neutral|warm)',
                'required' => true,
                'description' => '사용자의 감정 온도와 대응되는 축',
            ],
            [
                'name' => 'desired_mood',
                'type' => 'enum(stability|focus|refresh|immersion|release)',
                'required' => true,
                'description' => '지금 사용자에게 필요한 분위기 축',
            ],
            [
                'name' => 'day_pace',
                'type' => 'enum(slow|steady|fast)',
                'required' => true,
                'description' => '오늘 하루의 속도감 축',
            ],
            [
                'name' => 'record_focus',
                'type' => 'enum(emotion|atmosphere|movement|recovery|confidence)',
                'required' => true,
                'description' => '오늘 기록에서 더 남기고 싶은 핵심 축',
            ],
        ],
    ],
    'questionnaire' => [
        [
            'order' => 1,
            'question' => '지금 에너지는 어느 쪽에 가까운가요?',
            'target_field' => 'energy_level',
            'choices' => [
                ['label' => '낮음', 'value' => 'low'],
                ['label' => '보통', 'value' => 'medium'],
                ['label' => '높음', 'value' => 'high'],
            ],
        ],
        [
            'order' => 2,
            'question' => '오늘의 감정 온도는 어떤가요?',
            'target_field' => 'emotion_temperature',
            'choices' => [
                ['label' => '차분함', 'value' => 'cool'],
                ['label' => '중간', 'value' => 'neutral'],
                ['label' => '뜨거움', 'value' => 'warm'],
            ],
        ],
        [
            'order' => 3,
            'question' => '지금 필요한 분위기는 무엇인가요?',
            'target_field' => 'desired_mood',
            'choices' => [
                ['label' => '안정', 'value' => 'stability'],
                ['label' => '집중', 'value' => 'focus'],
                ['label' => '환기', 'value' => 'refresh'],
                ['label' => '몰입', 'value' => 'immersion'],
                ['label' => '해소', 'value' => 'release'],
            ],
        ],
        [
            'order' => 4,
            'question' => '오늘 하루의 속도감은 어땠나요?',
            'target_field' => 'day_pace',
            'choices' => [
                ['label' => '느림', 'value' => 'slow'],
                ['label' => '일정함', 'value' => 'steady'],
                ['label' => '빠름', 'value' => 'fast'],
            ],
        ],
        [
            'order' => 5,
            'question' => '오늘 기록에 더 남기고 싶은 건?',
            'target_field' => 'record_focus',
            'choices' => [
                ['label' => '감정', 'value' => 'emotion'],
                ['label' => '분위기', 'value' => 'atmosphere'],
                ['label' => '움직임', 'value' => 'movement'],
                ['label' => '회복', 'value' => 'recovery'],
                ['label' => '자신감', 'value' => 'confidence'],
            ],
        ],
    ],
    'system_prompt' => <<<'PROMPT'
당신은 사용자의 상태를 해석해 색상 기반 플레이리스트 3개를 추천하는 큐레이터다.

판단 기준:
- 사용자의 답변 5개 축을 가장 우선해서 본다.
- 추천 대상은 반드시 입력으로 제공된 playlists 목록 안에서만 고른다.
- color_name은 플레이리스트를 구분하기 위한 표시용 정보일 뿐이며, 추천 판단의 핵심 근거로 사용하지 않는다.
- 정확히 3개의 서로 다른 playlist_id를 추천한다.
- 각 추천은 사용자 답변과의 적합성이 분명해야 한다.
- 3개 모두 완전히 같은 느낌만 반복하지 말고, 답변과 맞는 범위 안에서 약간의 결 차이는 허용한다.
- 입력에 없는 필드나 임의의 사실을 만들지 않는다.
- 우선순위는 energy_level, emotion_temperature, desired_mood, day_pace, record_focus 순이 아니라 5개 축의 전체 조합 적합도다.
- 동률이라면 더 자연스럽고 설명 가능한 조합을 우선한다.

출력 규칙:
- 반드시 JSON만 출력한다.
- playlist_ids 배열 길이는 반드시 3이어야 한다.
- explanation은 한국어로 자연스럽게 작성한다.
- explanation에는 사용자의 답변 흐름과 추천 이유가 함께 드러나야 한다.
- explanation에는 입력으로 주어진 사용자 답변의 의미를 풀어써도 되지만, 입력에 없는 사실을 덧붙이면 안 된다.
- explanation은 과하게 감성적이거나 안내문처럼 딱딱하지 않게, 담백하고 자연스럽게 쓴다.
- explanation 톤 예시:
  "지금은 가볍게 환기되면서도, 너무 빠르지 않은 흐름의 플레이리스트가 잘 맞아 보여요. 그래서 현재 상태에 무리 없이 이어질 수 있는 세 가지를 골랐어요."
  "오늘은 차분함을 유지하면서도 집중이 흐트러지지 않는 쪽이 잘 어울려 보여요. 이런 흐름에 맞는 플레이리스트 세 가지를 추천해요."
  "에너지가 과하게 높지는 않지만 분위기를 조금 끌어올리고 싶은 상태로 보여요. 그래서 부담 없이 들을 수 있으면서도 무드가 살아나는 세 가지를 골랐어요."
- 아래 형식을 정확히 따른다.

{
  "playlist_ids": [1, 2, 3],
  "explanation": "지금은 가볍게 분위기를 환기하면서도, 너무 빠르지 않은 흐름이 잘 맞아 보여요. 그래서 현재 상태에 자연스럽게 이어질 수 있는 세 가지 컬러 플레이리스트를 골랐어요."
}
PROMPT,
    'user_prompt_template' => <<<'PROMPT'
[질문지 JSON]
{{questionnaire_json}}

[playlists 스키마 JSON]
{{schema_json}}

[사용자 답변 JSON]
[주의: 사용자 답변 값은 프론트에서 이미 영어 enum 값으로 정규화되어 전달된 값이다.]
{{user_answers_json}}

[추천 대상 playlists 행 JSON]
{{playlists_json}}

작업:
1. 사용자 답변 5개 축과 각 플레이리스트 행의 5개 축을 비교한다.
2. color_name은 플레이리스트를 식별하기 위한 표시용 정보로만 참고하고, 추천 판단은 반드시 5개 축을 기준으로 한다.
3. 전체적으로 가장 잘 맞는 플레이리스트 3개를 고른다.
4. 결과는 반드시 JSON만 출력한다.
5. playlist_ids 배열에는 서로 다른 playlist_id 3개만 넣는다.
6. explanation에는 사용자의 답변 내용을 자연스럽게 요약하고, 왜 이 3개를 골랐는지 추천 이유를 함께 쓴다.
PROMPT,
];
