export function initMiniPlayer() {
  const app = document.querySelector('.app');
  const miniPlayer = document.querySelector('.mini-player');
  const miniCloseBtn = document.querySelector('.mini-btn--close');

  if (!app || !miniPlayer || !miniCloseBtn) return;

  // 미니 플레이어 열기/닫기 함수
  const closeMiniPlayer = () => {
    miniPlayer.classList.add('is-hidden');
    app.classList.remove('has-mini');
  };

  // 미니 플레이어 열기 함수
  const openMiniPlayer = () => {
    miniPlayer.classList.remove('is-hidden');
    app.classList.add('has-mini');
  };

  // 닫기 버튼 클릭 시 미니 플레이어 닫기
  miniCloseBtn.addEventListener('click', closeMiniPlayer);

  // 터치 이벤트를 활용한 스와이프 제스처 처리
  let startY = 0;
  let isSwipingDownOnMini = false;
  let isSwipingUpToOpen = false;

  const CLOSE_SWIPE_THRESHOLD = 70;
  const OPEN_SWIPE_THRESHOLD = 70;
  const BOTTOM_EDGE_TRIGGER = 90;

  // 미니 플레이어에서 아래로 스와이프하여 닫기
  miniPlayer.addEventListener(
    'touchstart',
    (event) => {
      if (miniPlayer.classList.contains('is-hidden')) return;
      startY = event.touches[0].clientY;
      isSwipingDownOnMini = true;
    },
    { passive: true }
  );

  // 스와이프 중일 때 미니 플레이어 위치 업데이트
  miniPlayer.addEventListener(
    'touchmove',
    (event) => {
      if (!isSwipingDownOnMini) return;

      const currentY = event.touches[0].clientY;
      const deltaY = currentY - startY;
      if (deltaY <= 0) return;

      miniPlayer.style.transform = `translate(-50%, ${Math.min(deltaY, 140)}px)`;
    },
    { passive: true }
  );

  // 스와이프가 끝났을 때 미니 플레이어 닫기 여부 결정
  miniPlayer.addEventListener('touchend', (event) => {
    if (!isSwipingDownOnMini) return;
    isSwipingDownOnMini = false;

    const endY = event.changedTouches[0].clientY;
    const deltaY = endY - startY;
    miniPlayer.style.transform = '';

    if (deltaY > CLOSE_SWIPE_THRESHOLD) {
      closeMiniPlayer();
    }
  });

  // 화면 하단에서 위로 스와이프하여 미니 플레이어 열기
  app.addEventListener(
    'touchstart',
    (event) => {
      if (!miniPlayer.classList.contains('is-hidden')) return;

      const touchY = event.touches[0].clientY;
      const viewportHeight = window.innerHeight;
      const isBottomEdge = viewportHeight - touchY <= BOTTOM_EDGE_TRIGGER;

      if (!isBottomEdge) return;

      startY = touchY;
      isSwipingUpToOpen = true;
    },
    { passive: true }
  );

  // 스와이프 중일 때 미니 플레이어 위치 업데이트
  app.addEventListener(
    'touchend',
    (event) => {
      if (!isSwipingUpToOpen) return;
      isSwipingUpToOpen = false;

      const endY = event.changedTouches[0].clientY;
      const deltaY = startY - endY;

      if (deltaY > OPEN_SWIPE_THRESHOLD) {
        openMiniPlayer();
      }
    },
    { passive: true }
  );
}
