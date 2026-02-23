/* paletteLog.js - stable */

document.addEventListener('DOMContentLoaded', () => {
  const stack = document.querySelector('.pl-stack');
  if (!stack) return;

  // 카드 목록 가져오기
  const getCards = () => Array.from(document.querySelectorAll('.pl-card'));

  // 1) 순차 등장
  document.body.classList.add('js-anim');
  getCards().forEach((card, i) => {
    card.style.setProperty('--delay', `${i * 50}ms`);
    requestAnimationFrame(() => card.classList.add('is-in'));
  });

  // 2) z-index 자동 부여 (카드 추가돼도 유지)
  function applyStackZIndex() {
    const cards = getCards();
    const total = cards.length;
    cards.forEach((card, i) => {
      card.style.zIndex = String(total - i);
    });
  }
  applyStackZIndex();

  // 3) 보드 높이 자동 계산 (auto 보드에서도 stack minHeight로 여백 제어)
  function updateBoardHeight() {
    const cards = getCards();
    if (!cards.length) return;

    const overlap = 50; // CSS margin-top:-28px
    const firstExtra = 0; // 첫 카드 -70 보정(70-28)

    const cardHeight = cards[0].offsetHeight;
    const total = cards.length;

    const totalHeight = cardHeight + (total - 1) * (cardHeight - overlap);

    stack.style.minHeight = totalHeight + 'px';
  }
  updateBoardHeight();
  window.addEventListener('resize', updateBoardHeight);

  // 4) 카드 추가/삭제 감지 → z-index/높이 자동 갱신
  const mo = new MutationObserver(() => {
    applyStackZIndex();
    updateBoardHeight();

    // 새로 들어온 카드도 순차 등장 처리(원하면)
    getCards().forEach((card) => {
      if (!card.classList.contains('is-in')) {
        card.classList.add('is-in');
      }
    });
  });
  mo.observe(stack, { childList: true });

  // 5) 클릭한 카드 1장만 오른쪽 슬라이드
  let isTransitioning = false;

  function goToPlayer(payload) {
    console.log('[TODO] goToPlayer()', payload);
  }

  stack.addEventListener('click', (e) => {
    const link = e.target.closest('.pl-link');
    if (!link) return;

    e.preventDefault();
    if (isTransitioning) return;

    const card = link.closest('.pl-card');
    if (!card) return;

    isTransitioning = true;
    stack.classList.add('is-lock');

    // exit하는 카드는 무조건 최상단
    card.classList.add('is-exit-right');

    const payload = {
      date: card.querySelector('.pl-time')?.textContent?.trim() || '',
      name: card.querySelector('.pl-name')?.textContent?.trim() || '',
      color: getComputedStyle(card).getPropertyValue('--card')?.trim() || ''
    };

    const onEnd = (ev) => {
      if (ev.propertyName !== 'transform') return;
      card.removeEventListener('transitionend', onEnd);
      goToPlayer(payload);
    };

    card.addEventListener('transitionend', onEnd);
  });
});
