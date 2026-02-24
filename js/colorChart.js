document.querySelectorAll('.cc-item').forEach((item) => {
  item.querySelector('.cc-link').addEventListener('click', (e) => {
    if (e.target.closest('button')) return;

    document.querySelectorAll('.cc-item.is-active').forEach((el) => {
      if (el !== item) el.classList.remove('is-active');
    });

    item.classList.toggle('is-active');
  });
});

function getBrightness(hex) {
  hex = hex.replace('#', '');

  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);

  return (r * 299 + g * 587 + b * 114) / 1000;
}

document.querySelectorAll('.cc-item').forEach((item) => {
  const color = item.dataset.color || getComputedStyle(item).getPropertyValue('--color').trim();
  const brightness = getBrightness(color);

  const likeCount = item.querySelector('.cc-like-count');
  const icons = item.querySelectorAll('.cc-like-btn img, .cc-add img');

  // 기준값 170
  if (brightness > 170) {
    // 밝은 색 → 어두운 아이콘
    likeCount.style.color = '#6B6E6E'; // Hematite
    icons.forEach((icon) => (icon.style.filter = 'none'));
  } else {
    // 어두운 색 → 밝은 아이콘
    likeCount.style.color = '#F2F2EE'; // Cloud Dancer
    icons.forEach((icon) => (icon.style.filter = 'brightness(0) invert(1)'));
  }
});
