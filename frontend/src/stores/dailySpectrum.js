import { ref } from 'vue';
import { defineStore } from 'pinia';
import { apiRequest } from '@/services/httpClient';

function normalizeDailyPlaylistId(value) {
  return String(value ?? '').trim();
}

export const useDailySpectrumStore = defineStore('dailySpectrum', () => {
  const activeDailyPlaylistId = ref('');
  const panelStep = ref('intro');
  const spectrumErrorMessage = ref('');
  const spectrumPlaylists = ref([]);
  const resultExplanation = ref('');
  const typedExplanation = ref('');
  const isSubmittingSpectrum = ref(false);
  const answers = ref({});
  const questionIndex = ref(0);

  let typingTimer = null;
  let submitRequestId = 0;

  function stopExplanationTyping() {
    if (typingTimer) {
      window.clearInterval(typingTimer);
      typingTimer = null;
    }
  }

  function resetQuestionFlow() {
    answers.value = {};
    questionIndex.value = 0;
  }

  function clearSpectrumResult() {
    stopExplanationTyping();
    spectrumErrorMessage.value = '';
    spectrumPlaylists.value = [];
    resultExplanation.value = '';
    typedExplanation.value = '';
    isSubmittingSpectrum.value = false;
  }

  function resetSpectrum() {
    submitRequestId += 1;
    panelStep.value = 'intro';
    clearSpectrumResult();
    resetQuestionFlow();
  }

  function syncDailyPlaylist(dailyPlaylistId) {
    const normalizedDailyPlaylistId = normalizeDailyPlaylistId(dailyPlaylistId);
    if (!normalizedDailyPlaylistId || normalizedDailyPlaylistId === activeDailyPlaylistId.value) {
      return false;
    }

    activeDailyPlaylistId.value = normalizedDailyPlaylistId;
    resetSpectrum();
    return true;
  }

  function clearDailyPlaylist() {
    if (!activeDailyPlaylistId.value) {
      return;
    }

    activeDailyPlaylistId.value = '';
    resetSpectrum();
  }

  function startSpectrum() {
    clearSpectrumResult();
    resetQuestionFlow();
    panelStep.value = 'question';
  }

  function setPanelStep(nextStep) {
    panelStep.value = String(nextStep || 'intro');
  }

  function setQuestionIndex(nextIndex) {
    questionIndex.value = Math.max(0, Number(nextIndex) || 0);
  }

  function setAnswer(field, value) {
    const normalizedField = String(field || '').trim();
    if (!normalizedField) {
      return;
    }

    answers.value = {
      ...answers.value,
      [normalizedField]: value
    };
  }

  function openSpectrumResult() {
    stopExplanationTyping();
    panelStep.value = 'result';
  }

  function startExplanationTyping(text, options = {}) {
    stopExplanationTyping();

    const source = String(text ?? '').trim();
    resultExplanation.value = source;
    panelStep.value = 'typing';

    if (!source) {
      typedExplanation.value = '';
      panelStep.value = 'result';
      return;
    }

    const shouldResume = options.resume === true;
    let index = shouldResume ? typedExplanation.value.length : 0;

    if (!shouldResume) {
      typedExplanation.value = '';
    }

    if (index >= source.length) {
      typedExplanation.value = source;
      return;
    }

    typingTimer = window.setInterval(() => {
      index += 1;
      typedExplanation.value = source.slice(0, index);

      if (index >= source.length) {
        stopExplanationTyping();
      }
    }, 42);
  }

  function resumeTypingIfNeeded() {
    if (
      typingTimer ||
      panelStep.value !== 'typing' ||
      isSubmittingSpectrum.value ||
      !resultExplanation.value ||
      typedExplanation.value.length >= resultExplanation.value.length
    ) {
      return;
    }

    startExplanationTyping(resultExplanation.value, { resume: true });
  }

  async function submitSpectrum() {
    if (!activeDailyPlaylistId.value) {
      stopExplanationTyping();
      spectrumErrorMessage.value = '오늘의 톤을 먼저 불러와야 합니다.';
      panelStep.value = 'result';
      return;
    }

    clearSpectrumResult();
    isSubmittingSpectrum.value = true;

    const requestId = ++submitRequestId;

    try {
      const result = await apiRequest(
        '/api/playlist/daily-spectrum.php',
        {
          method: 'POST',
          body: {
            daily_playlist_id: activeDailyPlaylistId.value,
            answers: answers.value
          }
        },
        '데일리 스펙트럼 추천을 불러오지 못했습니다.'
      );

      if (requestId !== submitRequestId) {
        return;
      }

      spectrumPlaylists.value = Array.isArray(result?.spectrumPlaylists) ? result.spectrumPlaylists : [];
      resultExplanation.value =
        typeof result?.explanation === 'string' ? result.explanation.trim() : '';
      isSubmittingSpectrum.value = false;

      if (spectrumPlaylists.value.length && resultExplanation.value) {
        startExplanationTyping(resultExplanation.value);
        return;
      }
    } catch (error) {
      if (requestId !== submitRequestId) {
        return;
      }

      clearSpectrumResult();
      spectrumErrorMessage.value =
        error instanceof Error ? error.message : '데일리 스펙트럼 추천을 불러오지 못했습니다.';
    }

    panelStep.value = 'result';
  }

  return {
    activeDailyPlaylistId,
    panelStep,
    spectrumErrorMessage,
    spectrumPlaylists,
    resultExplanation,
    typedExplanation,
    isSubmittingSpectrum,
    answers,
    questionIndex,
    syncDailyPlaylist,
    clearDailyPlaylist,
    startSpectrum,
    setPanelStep,
    setQuestionIndex,
    setAnswer,
    openSpectrumResult,
    resumeTypingIfNeeded,
    submitSpectrum
  };
});
