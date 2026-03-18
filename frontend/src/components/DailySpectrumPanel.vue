<template>
  <section class="panel spectrum" style="--panel-delay: 48ms">
    <div class="panel-head">
      <h3>Daily Spectrum</h3>
    </div>

    <p v-if="isDailyLoading" class="spectrum-state">오늘의 톤을 먼저 불러오는 중...</p>
    <p v-else-if="dailyErrorMessage" class="spectrum-state">
      오늘의 톤을 불러오지 못해 추천을 표시할 수 없습니다.
    </p>

    <div v-else-if="panelStep === 'intro'" class="spectrum-step spectrum-step-intro">
      <p class="spectrum-intro-copy">오늘의 감정을 색과 음악으로 변환해드릴게요</p>
      <button class="spectrum-primary-btn" type="button" @click="startSpectrum">시작하기</button>
    </div>

    <div v-else-if="panelStep === 'question'" class="spectrum-step spectrum-step-question">
      <div class="spectrum-question-block">
        <p class="spectrum-question">{{ currentQuestion?.question }}</p>

        <div class="spectrum-choice-row">
          <div class="spectrum-choice-list">
            <button
              v-for="choice in currentQuestionChoices"
              :key="choice.value"
              class="spectrum-choice-btn"
              :class="{ 'is-selected': selectedAnswer === choice.value }"
              type="button"
              @click="selectAnswer(choice.value)"
            >
              {{ choice.label }}
            </button>
          </div>
        </div>
      </div>

      <div class="spectrum-nav">
        <button class="spectrum-ghost-btn" type="button" @click="goPrevQuestion">이전</button>
        <button
          class="spectrum-primary-btn"
          type="button"
          :disabled="!selectedAnswer"
          @click="goNextQuestion"
        >
          {{ isLastQuestion ? '완료' : '다음' }}
        </button>
      </div>
    </div>

    <div v-else-if="panelStep === 'loading'" class="spectrum-step spectrum-step-loading">
      <p class="spectrum-loading-copy">감정을 색으로 변환하는 중...</p>
    </div>

    <div v-else-if="panelStep === 'typing'" class="spectrum-step spectrum-step-typing">
      <p class="spectrum-typing-copy">
        <span>{{ typedExplanation }}</span>
        <span aria-hidden="true" class="spectrum-typing-copy-ghost">{{ remainingExplanation }}</span>
      </p>
    </div>

    <div v-else-if="panelStep === 'result'" class="spectrum-step spectrum-step-result">
      <p v-if="spectrumErrorMessage" class="spectrum-state">{{ spectrumErrorMessage }}</p>
      <p v-else-if="!spectrumPlaylists.length" class="spectrum-state">추천 결과가 없습니다.</p>

      <template v-else>
        <div class="spec-track">
          <Swiper
            class="spec-swiper"
            :modules="swiperModules"
            :slides-per-view="'auto'"
            :space-between="14"
            :free-mode="{ enabled: true, momentumBounce: false }"
            :grab-cursor="true"
            :resistance-ratio="0"
            :watch-overflow="true"
          >
            <SwiperSlide
              v-for="playlist in spectrumPlaylists"
              :key="playlist.id"
              class="spec-slide"
            >
              <RouterLink class="spec-card" :to="playlistTo(playlist.id)">
                <div class="spec-color" :style="{ '--c': playlist.color_hex }"></div>
                <div class="spec-body">
                  <div class="spec-meta">
                    <span class="spec-code">{{ playlist.pantone_code }}</span>
                    <button
                      class="mini-add"
                      type="button"
                      aria-label="팔레트 로그 저장"
                      :disabled="isPalettePending(playlist.id)"
                      @click.capture.stop.prevent="emit('toggle-palette', playlist)"
                    >
                      <img :src="isSaved(playlist.id) ? addCompleteIcon : addIcon" alt="저장" />
                    </button>
                  </div>
                  <div class="spec-name">{{ playlist.color_name }}</div>
                </div>
              </RouterLink>
            </SwiperSlide>
          </Swiper>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { FreeMode } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/free-mode';
import { apiRequest } from '@/services/httpClient';
import dailySpectrumQuestionnaire from '@/data/daily-spectrum-questionnaire.json';
import addIcon from '@/assets/icons/add.svg';
import addCompleteIcon from '@/assets/icons/addComplete.svg';

const props = defineProps({
  isDailyLoading: {
    type: Boolean,
    default: false
  },
  dailyErrorMessage: {
    type: String,
    default: ''
  },
  dailyPlaylistId: {
    type: [Number, String],
    default: null
  },
  playlistTo: {
    type: Function,
    required: true
  },
  isSaved: {
    type: Function,
    required: true
  },
  isPalettePending: {
    type: Function,
    required: true
  }
});

const emit = defineEmits(['toggle-palette']);

const swiperModules = [FreeMode];
const panelStep = ref('intro');
const spectrumErrorMessage = ref('');
const spectrumPlaylists = ref([]);
const resultExplanation = ref('');
const typedExplanation = ref('');
const answers = ref({});
const questionIndex = ref(0);
const questions = Array.isArray(dailySpectrumQuestionnaire.questions)
  ? dailySpectrumQuestionnaire.questions
  : [];

const currentQuestion = computed(() => questions[questionIndex.value] ?? null);
const currentQuestionChoices = computed(() =>
  Array.isArray(currentQuestion.value?.choices) ? currentQuestion.value.choices : []
);
const selectedAnswer = computed(() =>
  currentQuestion.value?.field ? (answers.value[currentQuestion.value.field] ?? '') : ''
);
const isLastQuestion = computed(() => questionIndex.value >= questions.length - 1);
const remainingExplanation = computed(() =>
  resultExplanation.value.slice(typedExplanation.value.length)
);

let typingTimer = null;
let typingDoneTimer = null;

function resetQuestionFlow() {
  answers.value = {};
  questionIndex.value = 0;
}

function clearSpectrumResult() {
  spectrumErrorMessage.value = '';
  spectrumPlaylists.value = [];
  resultExplanation.value = '';
  typedExplanation.value = '';
}

function resetSpectrum() {
  stopExplanationTyping();
  panelStep.value = 'intro';
  clearSpectrumResult();
  resetQuestionFlow();
}

function startSpectrum() {
  stopExplanationTyping();
  clearSpectrumResult();
  resetQuestionFlow();
  panelStep.value = 'question';
}

function selectAnswer(value) {
  const field = currentQuestion.value?.field;
  if (!field) {
    return;
  }

  answers.value = {
    ...answers.value,
    [field]: value
  };
}

function goPrevQuestion() {
  if (questionIndex.value === 0) {
    panelStep.value = 'intro';
    return;
  }

  questionIndex.value -= 1;
}

async function goNextQuestion() {
  if (!selectedAnswer.value) {
    return;
  }

  if (!isLastQuestion.value) {
    questionIndex.value += 1;
    return;
  }

  await submitSpectrum();
}

async function submitSpectrum() {
  if (!props.dailyPlaylistId) {
    stopExplanationTyping();
    spectrumErrorMessage.value = '오늘의 톤을 먼저 불러와야 합니다.';
    panelStep.value = 'result';
    return;
  }

  stopExplanationTyping();
  panelStep.value = 'loading';
  clearSpectrumResult();

  try {
    const result = await apiRequest(
      '/api/playlist/daily-spectrum.php',
      {
        method: 'POST',
        body: {
          daily_playlist_id: props.dailyPlaylistId,
          answers: answers.value
        }
      },
      '데일리 스펙트럼 추천을 불러오지 못했습니다.'
    );

    spectrumPlaylists.value = Array.isArray(result?.spectrumPlaylists)
      ? result.spectrumPlaylists
      : [];
    resultExplanation.value =
      typeof result?.explanation === 'string' ? result.explanation.trim() : '';

    if (spectrumPlaylists.value.length && resultExplanation.value) {
      startExplanationTyping(resultExplanation.value);
      return;
    }
  } catch (error) {
    clearSpectrumResult();
    spectrumErrorMessage.value =
      error instanceof Error ? error.message : '데일리 스펙트럼 추천을 불러오지 못했습니다.';
  }

  panelStep.value = 'result';
}

function stopExplanationTyping() {
  if (typingTimer) {
    window.clearInterval(typingTimer);
    typingTimer = null;
  }

  if (typingDoneTimer) {
    window.clearTimeout(typingDoneTimer);
    typingDoneTimer = null;
  }
}

function startExplanationTyping(text) {
  stopExplanationTyping();
  typedExplanation.value = '';
  panelStep.value = 'typing';

  const source = String(text ?? '');
  if (!source) {
    panelStep.value = 'result';
    return;
  }

  let index = 0;
  typingTimer = window.setInterval(() => {
    index += 1;
    typedExplanation.value = source.slice(0, index);

    if (index >= source.length) {
      stopExplanationTyping();
      typingDoneTimer = window.setTimeout(() => {
        typingDoneTimer = null;
        panelStep.value = 'result';
      }, 280);
    }
  }, 48);
}

watch(
  () => props.dailyPlaylistId,
  () => {
    resetSpectrum();
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  stopExplanationTyping();
});
</script>

<style scoped>
.panel.spectrum {
  display: flex;
  flex-direction: column;
  height: 240px;
}
.panel-head {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  margin-bottom: 14px;
  position: relative;
  z-index: 2;
  flex-shrink: 0;
}

.panel.spectrum h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: -0.2px;
  color: #3f5f73;
}

.spectrum-step {
  flex: 1;
  min-width: 0;
}

.spectrum-step-intro,
.spectrum-step-loading,
.spectrum-step-typing {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 18px;
  align-items: center;
  justify-content: center;
}

.spectrum-step-question {
  display: flex;
  flex: 1;
  flex-direction: column;
}

.spectrum-step-result {
  display: grid;
  gap: 8px;
  min-height: 0;
  padding-top: 2px;
}

.spectrum-intro-copy,
.spectrum-question,
.spectrum-loading-copy,
.spectrum-typing-copy {
  margin: 0;
  text-align: center;
  color: #4b6477;
  line-height: 1.5;
}

.spectrum-question {
  font-size: 12px;
}

.spectrum-question-block {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 0;
}

.spectrum-choice-row {
  display: flex;
  justify-content: center;
  min-width: 0;
}

.spectrum-choice-list {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  overflow-x: auto;
  width: 100%;
  margin: 0;
  padding: 4px 0;
  scrollbar-width: none;
}

.spectrum-choice-list::-webkit-scrollbar {
  display: none;
}

.spectrum-choice-btn {
  flex: 0 0 auto;
  border: 1px solid rgba(63, 95, 115, 0.18);
  border-radius: 999px;
  background: #ffffff;
  padding: 7px 11px;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition:
    background-color 180ms ease,
    border-color 180ms ease,
    color 180ms ease,
    transform 180ms ease,
    box-shadow 180ms ease;
  will-change: transform, border-color, color;
}

.spectrum-choice-btn.is-selected {
  border-color: var(--color-primary);
  background: #ffffff;
  color: var(--color-text-primary);
  font-weight: 700;
  transform: translateY(-1px);
}

.spectrum-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.spectrum-primary-btn,
.spectrum-ghost-btn {
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.spectrum-primary-btn {
  border: 0;
  background: var(--color-primary);
  color: #ffffff;
}

.spectrum-primary-btn:disabled {
  opacity: 0.45;
  cursor: default;
}

.spectrum-ghost-btn {
  border: 1px solid var(--color-text-primary);
  background: #ffffff;
  color: var(--color-text-primary);
}

.spectrum-typing-copy {
  max-width: 260px;
  min-height: calc(13px * 1.5 * 2);
  white-space: pre-line;
  word-break: keep-all;
}

.spectrum-typing-copy-ghost {
  visibility: hidden;
}

.spectrum-state {
  padding: 12px 0 2px;
  font-size: 13px;
  color: #6b7280;
  text-align: center;
}

.spec-track {
  min-width: 0;
}

.spec-swiper {
  padding: 20px 16px 16px;
  margin: -20px -16px -16px;
  border-radius: 18px;
  background: #fff;
}

.spec-swiper,
.spec-swiper :deep(.swiper-wrapper),
.spec-swiper :deep(.swiper-slide) {
  cursor: grab;
}

.spec-swiper:active,
.spec-swiper:active :deep(.swiper-wrapper),
.spec-swiper:active :deep(.swiper-slide) {
  cursor: grabbing;
}

.spec-slide {
  width: min(268px, calc(100vw - 114px));
}

.spec-card {
  display: block;
  height: 100%;
  border-radius: 17px;
  background: #fff;
  box-shadow: 0px 0px 21.3px -3px rgba(0, 0, 0, 0.25);
  text-decoration: none;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.05);
  color: inherit;
  cursor: pointer;
}

.spec-color {
  height: 90px;
  background: var(--c, #3fb9c8);
  border-radius: 17px 17px 0 0;
  border: 4px solid #fff;
}

.spec-body {
  padding: 12px 14px 14px;
}

.spec-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.spec-code {
  font-size: 16px;
  font-weight: 700;
}

.mini-add {
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  cursor: pointer;
}

.mini-add:disabled {
  opacity: 0.7;
}

.mini-add img {
  width: 14px;
  height: 14px;
}

.spec-name {
  margin-top: 2px;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.4px;
}
</style>
