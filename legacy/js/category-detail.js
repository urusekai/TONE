document.addEventListener('DOMContentLoaded', () => {
  const colorList = document.querySelector('.color-list');
  if (!colorList) return;

  colorList.addEventListener('click', (event) => {
    const card = event.target.closest('.color-card');
    if (!card) return;
    window.location.href = './playlist.html';
  });
});
