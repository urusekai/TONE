<template>
  <section class="panel spectrum" style="--panel-delay: 48ms">
    <div class="panel-head">
      <h3>Daily Spectrum</h3>
    </div>

    <p v-if="isDailyLoading" class="spectrum-state">오늘의 톤을 먼저 불러오는 중 ...</p>
    <p v-else-if="dailyErrorMessage" class="spectrum-state">
      오늘의 톤을 불러오지 못해 추천을 표시할 수 없습니다.
    </p>

    <div v-else class="spectrum-stage">
      <div
        class="spectrum-layer"
        :class="{ 'is-active': panelStep === 'intro' }"
        :aria-hidden="panelStep !== 'intro'"
        :inert="panelStep !== 'intro'"
      >
        <div class="spectrum-step spectrum-step-intro">
          <p class="spectrum-intro-copy">오늘의 감정을 색과 음악으로 변환해드릴게요</p>
          <button class="spectrum-primary-btn" type="button" @click="startSpectrum">
            시작하기
          </button>
        </div>
      </div>

      <div
        class="spectrum-layer"
        :class="{ 'is-active': panelStep === 'question' }"
        :aria-hidden="panelStep !== 'question'"
        :inert="panelStep !== 'question'"
      >
        <div class="spectrum-step spectrum-step-question">
          <div
            class="spectrum-question-shell"
            :class="{ 'is-submitting': isSubmittingSpectrum }"
            :aria-busy="isSubmittingSpectrum"
          >
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

          <div
            class="spectrum-question-overlay"
            :class="{ 'is-active': isSubmittingSpectrum }"
            aria-hidden="true"
          >
            <div class="spectrum-loading-orbit">
              <span class="spectrum-loading-orb spectrum-loading-orb-violet"></span>
              <span class="spectrum-loading-orb spectrum-loading-orb-orange"></span>
              <span class="spectrum-loading-orb spectrum-loading-orb-rose"></span>
            </div>
          </div>
        </div>
      </div>

      <div
        class="spectrum-layer"
        :class="{ 'is-active': panelStep === 'typing' }"
        :aria-hidden="panelStep !== 'typing'"
        :inert="panelStep !== 'typing'"
      >
        <div class="spectrum-step spectrum-step-typing">
          <div class="spectrum-typing-body">
            <p class="spectrum-typing-copy">
              <span>{{ typedExplanation }}</span>
              <span aria-hidden="true" class="spectrum-typing-copy-ghost">{{
                remainingExplanation
              }}</span>
            </p>
          </div>
          <button
            class="spectrum-primary-btn"
            type="button"
            @click="openSpectrumResult"
          >
            확인하기
          </button>
        </div>
      </div>

      <div
        class="spectrum-layer"
        :class="{ 'is-active': panelStep === 'result' }"
        :aria-hidden="panelStep !== 'result'"
        :inert="panelStep !== 'result'"
      >
        <div class="spectrum-step spectrum-step-result">
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
      </div>
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
const isExplanationComplete = ref(false);
const isSubmittingSpectrum = ref(false);
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

function resetQuestionFlow() {
  answers.value = {};
  questionIndex.value = 0;
}

function clearSpectrumResult() {
  spectrumErrorMessage.value = '';
  spectrumPlaylists.value = [];
  resultExplanation.value = '';
  typedExplanation.value = '';
  isExplanationComplete.value = false;
  isSubmittingSpectrum.value = false;
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
  if (!selectedAnswer.value || isSubmittingSpectrum.value) {
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
  clearSpectrumResult();
  isSubmittingSpectrum.value = true;

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

    isSubmittingSpectrum.value = false;

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
}

function openSpectrumResult() {
  stopExplanationTyping();
  isExplanationComplete.value = true;
  panelStep.value = 'result';
}

function startExplanationTyping(text) {
  stopExplanationTyping();
  typedExplanation.value = '';
  isExplanationComplete.value = false;
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
      isExplanationComplete.value = true;
    }
  }, 60);
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

.spectrum-stage {
  display: grid;
  flex: 1;
  min-width: 0;
  min-height: 0;
}

.spectrum-layer {
  grid-area: 1 / 1;
  display: flex;
  min-width: 0;
  min-height: 0;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition:
    opacity 560ms ease,
    visibility 0s linear 560ms;
}

.spectrum-layer.is-active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transition:
    opacity 560ms ease,
    visibility 0s linear 0s;
}

.spectrum-step {
  flex: 1;
  min-width: 0;
}

.spectrum-step-intro {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 18px;
  align-items: center;
  justify-content: center;
}

.spectrum-step-question {
  position: relative;
  display: flex;
  flex: 1;
  flex-direction: column;
}

.spectrum-step-typing {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
}

.spectrum-step-result {
  display: grid;
  gap: 8px;
  min-height: 0;
  padding-top: 2px;
}

.spectrum-intro-copy,
.spectrum-question,
.spectrum-typing-copy {
  margin: 0;
  text-align: center;
  color: #4b6477;
  line-height: 1.5;
}

.spectrum-question {
  font-size: 12px;
}

.spectrum-loading-orbit {
  position: relative;
  width: 64px;
  height: 40px;
  animation: spectrum-loading-orbit 2s linear infinite;
  transform-origin: center;
}

.spectrum-loading-orb {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 5px;
  height: 5px;
  border-radius: 999px;
  box-shadow: 0 3px 6px rgba(63, 95, 115, 0.14);
  transform: translate(-50%, -50%) rotate(var(--orb-angle)) translateY(calc(var(--orb-radius) * -1));
}

.spectrum-loading-orb-violet {
  --orb-angle: -52deg;
  --orb-radius: 12px;
  background: #8b5cf6;
}

.spectrum-loading-orb-orange {
  --orb-angle: 0deg;
  --orb-radius: 12px;
  background: #fb923c;
}

.spectrum-loading-orb-rose {
  --orb-angle: 52deg;
  --orb-radius: 12px;
  background: #f59aa8;
}

.spectrum-question-shell {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
  transition:
    opacity 220ms ease,
    filter 220ms ease;
}

.spectrum-question-shell.is-submitting {
  opacity: 0.36;
  filter: saturate(0.82) brightness(1.04);
}

.spectrum-question-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.26);
  opacity: 0;
  pointer-events: none;
  transition: opacity 220ms ease;
}

.spectrum-question-overlay.is-active {
  opacity: 1;
  pointer-events: auto;
}

.spectrum-typing-body {
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  min-height: 0;
}

.spectrum-step-typing .spectrum-primary-btn {
  align-self: center;
}

.spectrum-question-block {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 18px;
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

@keyframes spectrum-loading-orbit {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}
</style>
