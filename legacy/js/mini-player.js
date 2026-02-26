export function initMiniPlayer() {
  const app = document.querySelector('.app');
  const miniPlayer = document.querySelector('.mini-player');
  const miniCloseBtn = document.querySelector('.mini-btn--close');

  if (!app || !miniPlayer || !miniCloseBtn) return;

  miniCloseBtn.addEventListener('click', () => {
    miniPlayer.classList.add('is-hidden');
    app.classList.remove('has-mini');
  });
}
