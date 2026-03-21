<template>
  <section
    class="panel spectrum"
    :class="{ 'is-result-panel': panelStep === 'result' }"
    style="--panel-delay: 48ms"
  >
    <div class="panel-head" :class="{ 'is-result-head': panelStep === 'result' }">
      <h3>Daily Spectrum</h3>
      <p v-if="panelStep === 'result'" class="spectrum-result-guide">
        카드를 눌러 오늘의 톤을 변경하세요.
      </p>
    </div>

    <p v-if="isDailyLoading" class="spectrum-state">오늘의 톤을 불러오는 중...</p>
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
          <p class="spectrum-intro-copy">오늘의 감정과 분위기를 톤으로 변환해드릴게요</p>
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

              <div
                class="spectrum-choice-row"
                :class="{
                  'is-face-row': isFaceQuestion(currentQuestion?.field),
                  'is-dot-row': isDotQuestion(currentQuestion?.field)
                }"
              >
                <div
                  v-if="isDotQuestion(currentQuestion?.field)"
                  class="spectrum-dot-rail"
                  :class="{ 'has-selection': hasDotSelection }"
                  :style="dotRailStyle"
                >
                  <span class="spectrum-dot-indicator" aria-hidden="true"></span>
                  <button
                    v-for="choice in currentQuestionChoices"
                    :key="choice.value"
                    class="spectrum-dot-segment"
                    :class="{ 'is-selected': selectedAnswer === choice.value }"
                    type="button"
                    :aria-pressed="selectedAnswer === choice.value"
                    @click="selectAnswer(choice.value)"
                  >
                    <span class="spectrum-dot-segment-body">
                      <span class="spectrum-dot-segment-dot" aria-hidden="true">
                        <img
                          class="spectrum-dot-segment-icon"
                          :src="getChoiceIcon(currentQuestion?.field, choice.value)"
                          alt=""
                        />
                      </span>
                      <span class="spectrum-dot-segment-label">{{ choice.label }}</span>
                    </span>
                  </button>
                </div>

                <div v-else-if="isFaceQuestion(currentQuestion?.field)" class="spectrum-face-rail">
                  <button
                    v-for="choice in currentQuestionChoices"
                    :key="choice.value"
                    class="spectrum-face-segment"
                    :class="{
                      'is-active': isFaceStepActive(choice.value),
                      'is-selected': selectedAnswer === choice.value
                    }"
                    type="button"
                    :aria-label="choice.label"
                    :aria-pressed="selectedAnswer === choice.value"
                    @click="selectAnswer(choice.value)"
                  >
                    <span class="spectrum-face-segment-body">
                      <img
                        class="spectrum-face-segment-icon"
                        :src="getFaceRailIcon(choice.value)"
                        alt=""
                        aria-hidden="true"
                      />
                    </span>
                  </button>
                </div>

                <div v-else class="spectrum-choice-list">
                  <button
                    v-for="choice in currentQuestionChoices"
                    :key="choice.value"
                    class="spectrum-choice-btn"
                    :class="{ 'is-selected': selectedAnswer === choice.value }"
                    type="button"
                    :aria-pressed="selectedAnswer === choice.value"
                    @click="selectAnswer(choice.value)"
                  >
                    <span class="spectrum-choice-content">
                      <template v-if="isIconQuestion(currentQuestion?.field)">
                        <img
                          class="spectrum-choice-icon"
                          :src="getChoiceIcon(currentQuestion?.field, choice.value)"
                          alt=""
                          aria-hidden="true"
                        />
                      </template>

                      <span class="spectrum-choice-label">{{ choice.label }}</span>
                    </span>
                  </button>
                </div>
              </div>
            </div>

            <div class="spectrum-nav">
              <button class="spectrum-ghost-btn" type="button" @click="goPrevQuestion">이전</button>
              <SpectrumProgressDots
                class="spectrum-progress"
                :total="questions.length"
                :current-index="questionIndex"
              />
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
          <button class="spectrum-primary-btn" type="button" @click="openSpectrumResult">
            결과 보기
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
            <div class="spec-shell">
              <div class="spec-track">
                <Swiper
                  class="spec-swiper"
                  :modules="swiperModules"
                  :slides-per-view="'auto'"
                  :space-between="4"
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
                    <article
                      class="spec-card"
                      :class="{
                        'is-selected': selectedTonePlaylistId === playlist.id,
                        'is-applying': applyingTonePlaylistId === playlist.id
                      }"
                    >
                      <div
                        class="spec-card-select"
                        role="button"
                        tabindex="0"
                        :aria-label="`${playlist.color_name}을 오늘의 톤으로 적용`"
                        :aria-pressed="selectedTonePlaylistId === playlist.id"
                        @click="applySpectrumTone(playlist)"
                        @keydown.enter.prevent="applySpectrumTone(playlist)"
                        @keydown.space.prevent="applySpectrumTone(playlist)"
                      >
                        <div class="spec-color" :style="{ '--c': playlist.color_hex }"></div>
                        <div class="spec-body">
                          <div class="spec-meta">
                            <span class="spec-code">{{ playlist.pantone_code }}</span>
                          </div>
                          <div class="spec-name">{{ playlist.color_name }}</div>
                        </div>
                      </div>
                    </article>
                  </SwiperSlide>
                </Swiper>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { FreeMode } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/free-mode';
import dailySpectrumQuestionnaire from '@/data/daily-spectrum-questionnaire.json';
import greatIcon from '@/assets/icons/greatIcon.svg';
import okayIcon from '@/assets/icons/okayIcon.svg';
import terribleIcon from '@/assets/icons/terribleIcon.svg';
import nonSelectIcon from '@/assets/icons/nonSelectIcon.svg';
import redDot from '@/assets/icons/redDot.svg';
import blueDot from '@/assets/icons/blueDot.svg';
import greenDot from '@/assets/icons/greenDot.svg';
import orangeDot from '@/assets/icons/orangeDot.svg';
import yellowDot from '@/assets/icons/yellowDot.svg';
import SpectrumProgressDots from '@/components/SpectrumProgressDots.vue';
import { useDailySpectrumStore } from '@/stores/dailySpectrum';
import { useCalendarStore } from '@/stores/calendarStore';
import { useToastStore } from '@/stores/toast';
import { showAlert } from '@/utils/alert';

const choiceIconMap = {
  energy_level: {
    low: terribleIcon,
    medium: okayIcon,
    high: greatIcon
  },
  emotion_temperature: {
    cool: terribleIcon,
    neutral: okayIcon,
    heated: greatIcon
  },
  day_pace: {
    slow: terribleIcon,
    steady: okayIcon,
    fast: greatIcon
  },
  desired_mood: {
    stability: redDot,
    focus: blueDot,
    refresh: greenDot,
    immersion: orangeDot,
    release: yellowDot
  },
  record_focus: {
    emotion: redDot,
    atmosphere: blueDot,
    movement: greenDot,
    recovery: orangeDot,
    confidence: yellowDot
  }
};

function formatKey(year, month, day) {
  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

function getTodayKey() {
  const today = new Date();
  return formatKey(today.getFullYear(), today.getMonth() + 1, today.getDate());
}

function getChoiceIcon(field, value) {
  return choiceIconMap[field]?.[value] ?? '';
}

function isIconQuestion(field) {
  return Boolean(choiceIconMap[field]);
}

function isFaceQuestion(field) {
  return ['energy_level', 'emotion_temperature', 'day_pace'].includes(field);
}

function isDotQuestion(field) {
  return ['desired_mood', 'record_focus'].includes(field);
}

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

const emit = defineEmits(['toggle-palette', 'daily-tone-changed']);

const swiperModules = [FreeMode];
const todayKey = getTodayKey();
const applyingTonePlaylistId = ref(null);
const spectrumStore = useDailySpectrumStore();
const calendarStore = useCalendarStore();
const toast = useToastStore();

const {
  panelStep,
  spectrumErrorMessage,
  spectrumPlaylists,
  resultExplanation,
  typedExplanation,
  isSubmittingSpectrum,
  answers,
  questionIndex
} = storeToRefs(spectrumStore);

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
const selectedChoiceIndex = computed(() =>
  currentQuestionChoices.value.findIndex((choice) => choice.value === selectedAnswer.value)
);
const hasDotSelection = computed(
  () => isDotQuestion(currentQuestion.value?.field) && selectedChoiceIndex.value >= 0
);
const dotRailStyle = computed(() => {
  if (!isDotQuestion(currentQuestion.value?.field)) {
    return null;
  }

  return {
    '--dot-choice-count': Math.max(currentQuestionChoices.value.length, 1),
    '--dot-choice-index': Math.max(selectedChoiceIndex.value, 0)
  };
});
const isLastQuestion = computed(() => questionIndex.value >= questions.length - 1);
const remainingExplanation = computed(() =>
  resultExplanation.value.slice(typedExplanation.value.length)
);
const selectedTonePlaylistId = computed(() => {
  const playlistId = calendarStore.calendarData[todayKey]?.playlistId;
  return playlistId == null ? null : Number(playlistId);
});

function getFaceRailIcon(value) {
  const field = currentQuestion.value?.field;
  if (!isFaceQuestion(field)) {
    return getChoiceIcon(field, value);
  }

  const selectedFaceValue = selectedAnswer.value;
  if (!selectedFaceValue) {
    return nonSelectIcon;
  }

  return isFaceStepActive(value)
    ? (choiceIconMap[field]?.[selectedFaceValue] ?? nonSelectIcon)
    : nonSelectIcon;
}

function isFaceStepActive(value) {
  if (!isFaceQuestion(currentQuestion.value?.field)) {
    return false;
  }

  const selectedFaceValue = selectedAnswer.value;
  const selectedIndex = currentQuestionChoices.value.findIndex(
    (choice) => choice.value === selectedFaceValue
  );
  const choiceIndex = currentQuestionChoices.value.findIndex((choice) => choice.value === value);

  return selectedIndex !== -1 && choiceIndex !== -1 && choiceIndex <= selectedIndex;
}

function startSpectrum() {
  spectrumStore.startSpectrum();
}

function selectAnswer(value) {
  const field = currentQuestion.value?.field;
  if (!field) {
    return;
  }

  spectrumStore.setAnswer(field, value);
}

function goPrevQuestion() {
  if (questionIndex.value === 0) {
    spectrumStore.setPanelStep('intro');
    return;
  }

  spectrumStore.setQuestionIndex(questionIndex.value - 1);
}

async function goNextQuestion() {
  if (!selectedAnswer.value || isSubmittingSpectrum.value) {
    return;
  }

  if (!isLastQuestion.value) {
    spectrumStore.setQuestionIndex(questionIndex.value + 1);
    return;
  }

  await spectrumStore.submitSpectrum();
}

function openSpectrumResult() {
  spectrumStore.openSpectrumResult();
}

async function applySpectrumTone(playlist) {
  const playlistId = Number(playlist?.id);
  if (!playlistId || applyingTonePlaylistId.value === playlistId) {
    return;
  }

  try {
    applyingTonePlaylistId.value = playlistId;
    await calendarStore.saveTone(todayKey, playlistId);
    emit('daily-tone-changed');
    toast.show('오늘의 톤이 변경되었습니다');
  } catch (error) {
    const message = error instanceof Error ? error.message : '오늘의 톤을 변경하지 못했습니다.';
    showAlert(message);
  } finally {
    applyingTonePlaylistId.value = null;
  }
}

watch(
  [() => props.dailyPlaylistId, () => props.dailyErrorMessage],
  ([nextDailyPlaylistId, nextDailyErrorMessage]) => {
    if (nextDailyPlaylistId != null && String(nextDailyPlaylistId).trim()) {
      spectrumStore.syncDailyPlaylist(nextDailyPlaylistId);
      return;
    }

    if (nextDailyErrorMessage) {
      spectrumStore.clearDailyPlaylist();
    }
  },
  { immediate: true }
);

spectrumStore.resumeTypingIfNeeded();
</script>

<style scoped>
.panel.spectrum {
  display: flex;
  flex-direction: column;
  height: 240px;
  overflow: hidden;
  transition: background 220ms ease;
}

.panel.spectrum.is-result-panel {
  background: linear-gradient(180deg, #ccd9f0 0%, #ffffff 78%);
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

.panel-head.is-result-head {
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.panel.spectrum h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: -0.2px;
  color: #3f5f73;
}

.spectrum-result-guide {
  flex: 1;
  min-width: 0;
  margin: 0;
  max-width: none;
  font-size: 10px;
  font-weight: 600;
  line-height: 1.2;
  color: #587084;
  text-align: right;
  white-space: nowrap;
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
  transform: translateY(8px);
  transition:
    opacity 260ms ease,
    transform 260ms ease,
    visibility 0s linear 260ms;
}

.spectrum-layer.is-active {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transform: translateY(0);
  transition:
    opacity 260ms ease,
    transform 260ms ease,
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
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding-top: 2px;
  overflow: hidden;
}

.spectrum-intro-copy,
.spectrum-question {
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
  width: 100%;
  min-width: 0;
  overflow-x: auto;
  padding: 4px;
  scrollbar-width: none;
}

.spectrum-choice-row::-webkit-scrollbar {
  display: none;
}

.spectrum-choice-row.is-dot-row,
.spectrum-choice-row.is-face-row {
  justify-content: center;
  overflow: visible;
  padding: 0;
}

.spectrum-choice-list {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex: 0 0 auto;
  width: max-content;
  min-width: 100%;
  margin: 0;
  padding: 0;
}

.spectrum-face-rail {
  --face-rail-max-width: 170px;
  --face-rail-height: 50px;
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  width: min(100%, var(--face-rail-max-width));
  min-width: 0;
  height: var(--face-rail-height);
  padding: 0;
  border-radius: calc(var(--face-rail-height) / 2);
  background: #ffffff;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.94),
    inset 0 0 0 1px rgba(63, 95, 115, 0.07),
    inset 0 -2px 5px rgba(63, 95, 115, 0.05);
}

.spectrum-face-segment {
  min-width: 0;
  border: 0;
  padding: 0;
  background: transparent;
  cursor: pointer;
}

.spectrum-face-segment-body {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 6px 4px;
  transition: transform 140ms cubic-bezier(0.22, 1, 0.36, 1);
}

.spectrum-face-segment-icon {
  width: 31px;
  height: 31px;
  display: block;
  object-fit: contain;
  opacity: 0.74;
  transform: scale(1);
  filter: saturate(0.94) brightness(0.98);
  will-change: transform, opacity, filter;
  transition:
    opacity 180ms ease-out,
    transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 180ms ease-out;
}

.spectrum-face-segment:hover .spectrum-face-segment-icon {
  transform: scale(1.05);
}

.spectrum-face-segment:active .spectrum-face-segment-body {
  transform: scale(0.97);
}

.spectrum-face-segment.is-active .spectrum-face-segment-icon {
  opacity: 1;
  filter: none;
}

.spectrum-face-segment.is-selected .spectrum-face-segment-icon {
  animation: spectrum-face-step-settle 220ms cubic-bezier(0.22, 1, 0.36, 1);
}

.spectrum-face-segment:focus-visible {
  outline: none;
}

.spectrum-face-segment:focus-visible .spectrum-face-segment-body {
  box-shadow: inset 0 0 0 1px rgba(63, 95, 115, 0.2);
}

.spectrum-dot-rail {
  --dot-rail-max-width: 225px;
  --dot-rail-height: 45px;
  --dot-indicator-inset-x: 6px;
  --dot-indicator-inset-y: 4px;
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: repeat(var(--dot-choice-count, 5), minmax(0, 1fr));
  width: min(100%, var(--dot-rail-max-width));
  min-width: 0;
  height: var(--dot-rail-height);
  padding: 0;
  border-radius: calc(var(--dot-rail-height) / 2);
  background: #ffffff;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.94),
    inset 0 0 0 1px rgba(63, 95, 115, 0.07),
    inset 0 -2px 5px rgba(63, 95, 115, 0.05);
}

.spectrum-dot-indicator {
  position: absolute;
  top: var(--dot-indicator-inset-y);
  bottom: var(--dot-indicator-inset-y);
  left: var(--dot-indicator-inset-x);
  width: calc((100% - (var(--dot-indicator-inset-x) * 2)) / var(--dot-choice-count, 5));
  border-radius: 19px;
  background: rgba(227, 221, 216, 0.7);
  border: 1px solid rgba(206, 196, 186, 0.72);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.65),
    inset 0 -2px 4px rgba(130, 118, 107, 0.14);
  opacity: 0;
  transform: translate3d(calc(var(--dot-choice-index, 0) * 100%), 0, 0);
  will-change: transform;
  backface-visibility: hidden;
  transition:
    transform 250ms cubic-bezier(0.22, 1, 0.36, 1),
    opacity 90ms linear;
  pointer-events: none;
}

.spectrum-dot-rail.has-selection .spectrum-dot-indicator {
  opacity: 1;
}

.spectrum-choice-btn {
  --choice-icon-opacity: 1;
  --choice-icon-scale: 1;
  --choice-icon-filter: none;
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid rgba(63, 95, 115, 0.18);
  border-radius: 999px;
  background: #ffffff;
  padding: 7px 11px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  white-space: nowrap;
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

.spectrum-choice-content {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 0;
}

.spectrum-choice-icon {
  display: block;
  width: 18px;
  height: 18px;
  object-fit: contain;
  flex-shrink: 0;
  opacity: var(--choice-icon-opacity);
  transform: scale(var(--choice-icon-scale));
  filter: var(--choice-icon-filter);
  transition:
    opacity 240ms ease-out,
    transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
    filter 240ms ease-out;
}

.spectrum-dot-segment {
  position: relative;
  z-index: 1;
  min-width: 0;
  border: 0;
  padding: 0;
  background: transparent;
  color: #587084;
  cursor: pointer;
}

.spectrum-dot-segment-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  width: 100%;
  height: 100%;
  min-width: 0;
  padding: 4px 2px;
  border-radius: 19px;
  transition:
    transform 140ms cubic-bezier(0.22, 1, 0.36, 1),
    color 180ms ease-out;
}

.spectrum-dot-segment-dot {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #d7d9dd;
  transition:
    transform 180ms cubic-bezier(0.22, 1, 0.36, 1),
    background-color 180ms ease-out,
    opacity 180ms ease-out;
}

.spectrum-dot-segment-icon {
  width: 14px;
  height: 14px;
  display: block;
  opacity: 0;
  transform: scale(0.92);
  transition:
    opacity 140ms linear,
    transform 180ms cubic-bezier(0.22, 1, 0.36, 1);
}

.spectrum-dot-segment-label {
  display: block;
  min-width: 0;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.02em;
  color: #587084;
  transition: color 180ms ease-out;
}

.spectrum-dot-segment:hover .spectrum-dot-segment-body {
  transform: scale(1.02);
}

.spectrum-dot-segment:hover .spectrum-dot-segment-dot {
  transform: scale(1.05);
}

.spectrum-dot-segment:active .spectrum-dot-segment-body {
  transform: scale(0.98);
}

.spectrum-dot-segment.is-selected .spectrum-dot-segment-dot {
  background: transparent;
  transform: scale(1.02);
}

.spectrum-dot-segment.is-selected .spectrum-dot-segment-icon {
  opacity: 1;
  transform: scale(1);
}

.spectrum-dot-segment.is-selected .spectrum-dot-segment-label {
  color: #3f5f73;
}

.spectrum-dot-segment:not(.is-selected) .spectrum-dot-segment-icon {
  opacity: 0;
}

.spectrum-dot-segment:focus-visible {
  outline: none;
}

.spectrum-dot-segment:focus-visible .spectrum-dot-segment-body {
  box-shadow: inset 0 0 0 1px rgba(63, 95, 115, 0.2);
}

.spectrum-choice-label {
  display: inline-block;
  min-width: 0;
}

.spectrum-choice-btn.is-selected {
  border-color: var(--color-primary);
  background: #ffffff;
  color: var(--color-text-primary);
  font-weight: 700;
  transform: translateY(-1px);
}

.spectrum-nav {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding-top: 6px;
  min-height: 34px;
}

.spectrum-progress {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.spectrum-primary-btn,
.spectrum-ghost-btn {
  min-width: 58px;
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
  opacity: 0.6;
  cursor: default;
}

.spectrum-ghost-btn {
  border: 1px solid var(--color-text-primary);
  background: #ffffff;
  color: var(--color-text-primary);
}

.spectrum-typing-copy {
  margin: 0;
  text-align: center;
  color: #4b6477;
  line-height: 1.5;
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

.spec-shell {
  position: relative;
  flex: 1;
  min-width: 0;
  min-height: 0;
  width: calc(100% + 16px);
  margin-right: -16px;
  padding: 8px 0 4px;
  border-radius: 17px;
  overflow: hidden;
  background: transparent;
  border: 0;
  box-shadow: none;
}

.spec-shell::before {
  content: none;
}

.spec-track {
  position: relative;
  z-index: 1;
  min-width: 0;
  min-height: 0;
  height: 100%;
  padding-left: 0;
}

.spec-swiper {
  height: 100%;
  overflow: visible;
  padding: 0 0 2px 0;
  margin: 0;
  border-radius: 0;
  background: transparent;
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

.spec-swiper :deep(.swiper-wrapper) {
  align-items: stretch;
}

.spec-slide {
  width: min(286px, calc(100vw - 90px));
  display: flex;
  align-items: flex-start;
}

.spec-card {
  position: relative;
  width: 100%;
  height: auto;
  aspect-ratio: 272 / 162;
  border-radius: 17px;
  padding: 4px 4px 8px 4px;
  margin-left: 18px;
  background: #ffffff;
  box-shadow:
    0 12px 28px rgba(63, 95, 115, 0.16),
    0 3px 10px rgba(63, 95, 115, 0.08);
  overflow: hidden;
  border: 0;
  color: inherit;
  transition:
    transform 180ms ease,
    box-shadow 180ms ease,
    border-color 180ms ease,
    opacity 180ms ease;
}

.spec-card.is-selected {
  box-shadow:
    0 16px 34px rgba(63, 95, 115, 0.18),
    0 0 0 1px rgba(63, 95, 115, 0.08);
  transform: translateY(-2px);
}

.spec-card.is-applying {
  opacity: 0.78;
}

.spec-card-select {
  display: grid;
  grid-template-rows: minmax(0, 1.1fr) minmax(0, 0.9fr);
  width: 100%;
  height: 100%;
  overflow: hidden;
  border-radius: 13px;
  color: inherit;
  text-align: left;
  cursor: pointer;
}

.spec-card-select:hover .spec-color {
  transform: translateY(-1px);
}

.spec-card-select:focus-visible {
  outline: none;
  box-shadow: inset 0 0 0 2px rgba(63, 95, 115, 0.16);
}

.spec-color {
  position: relative;
  background: var(--c, #3fb9c8);
  border-radius: 13px 13px 0 0;
  transition: transform 180ms ease;
}

.spec-body {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  padding: 18px 20px 18px;
  background: #ffffff;
}

.spec-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding-right: 0;
}

.spec-code {
  font-size: 16px;
  font-weight: 500;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: #3f5f73;
}

.spec-name {
  margin-top: 6px;
  font-size: 27px;
  font-weight: 700;
  line-height: 0.98;
  letter-spacing: -0.04em;
  color: #3f5f73;
}

@media (max-width: 420px) {
  .spectrum-face-rail {
    width: min(100%, 160px);
    height: 46px;
  }

  .spectrum-face-segment-icon {
    width: 28px;
    height: 28px;
  }

  .spectrum-dot-rail {
    width: min(100%, 216px);
    height: 43px;
  }

  .spectrum-choice-label {
    font-size: 11px;
  }

  .spectrum-dot-segment-label {
    font-size: 10px;
  }

  .spectrum-result-guide {
    font-size: 9px;
  }

  .spec-shell {
    width: calc(100% + 16px);
    margin-right: -16px;
    padding: 7px 0 4px;
  }

  .spec-track {
    padding-left: 0;
  }

  .spec-swiper {
    padding-right: 0;
  }

  .spec-slide {
    width: min(270px, calc(100vw - 98px));
  }

  .spec-body {
    padding: 16px 18px 15px;
  }

  .spec-code {
    font-size: 16px;
  }

  .spec-name {
    font-size: 24px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .panel.spectrum,
  .spectrum-dot-indicator,
  .spectrum-face-segment-body,
  .spectrum-face-segment-icon,
  .spectrum-dot-segment-body,
  .spectrum-dot-segment-dot,
  .spectrum-dot-segment-icon,
  .spectrum-dot-segment-label,
  .spec-card,
  .spec-card-select,
  .spec-color {
    transition: none;
    animation: none;
  }
}

@keyframes spectrum-loading-orbit {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

@keyframes spectrum-face-step-settle {
  0% {
    transform: scale(1);
  }

  55% {
    transform: scale(1.1);
  }

  100% {
    transform: scale(1);
  }
}
</style>
