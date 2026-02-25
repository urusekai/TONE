const miniThumb = document.querySelector('.mini-player .mini-thumb');
const mainPlayer = document.querySelector('.main-player');
const closeBtn = document.querySelector('.main-player__back-btn');

if (!miniThumb || !mainPlayer || !closeBtn) {
  // 모듈은 페이지마다 공통 로드될 수 있으므로, 대상 요소가 없으면 종료한다.
} else {
  const openMainPlayer = () => mainPlayer.classList.add('is-active');
  const closeMainPlayer = () => mainPlayer.classList.remove('is-active');

  miniThumb.addEventListener('click', openMainPlayer);
  closeBtn.addEventListener('click', closeMainPlayer);
}
