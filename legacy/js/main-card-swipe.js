// swipe-scroll.js (또는 main.js에 넣어도 됨)
document.addEventListener('DOMContentLoaded', () => {
  const row = document.querySelector('.spec-row');
  if (!row) return;

  let isDown = false;
  let startX = 0;
  let startScrollLeft = 0;
  let pointerId = null;

  // 드래그 중 텍스트 선택 방지 + 커서
  row.style.cursor = 'grab';
  row.style.userSelect = 'none';

  const onPointerDown = (e) => {
    // 링크 클릭/버튼 클릭은 막지 않되, 드래그로 스크롤은 가능하게
    // (버튼 눌렀는데 드래그 처리되는 걸 줄이려고 왼쪽 버튼만)
    if (e.pointerType === 'mouse' && e.button !== 0) return;

    isDown = true;
    pointerId = e.pointerId;
    row.setPointerCapture(pointerId);

    startX = e.clientX;
    startScrollLeft = row.scrollLeft;

    row.style.cursor = 'grabbing';
    row.classList.add('is-dragging');
  };

  const onPointerMove = (e) => {
    if (!isDown) return;

    const dx = e.clientX - startX;
    row.scrollLeft = startScrollLeft - dx;
  };

  const endDrag = () => {
    if (!isDown) return;

    isDown = false;
    pointerId = null;

    row.style.cursor = 'grab';
    row.classList.remove('is-dragging');
  };

  row.addEventListener('pointerdown', onPointerDown);
  row.addEventListener('pointermove', onPointerMove);
  row.addEventListener('pointerup', endDrag);
  row.addEventListener('pointercancel', endDrag);
  row.addEventListener('pointerleave', endDrag);

  // 드래그 중에는 링크 클릭이 튀지 않게(작은 이동은 클릭 허용)
  let moved = 0;
  row.addEventListener('pointerdown', () => (moved = 0));

  row.addEventListener('pointermove', (e) => {
    if (!isDown) return;
    moved += Math.abs(e.movementX || 0);
  });

  row.addEventListener(
    'click',
    (e) => {
      // 드래그로 어느 정도 움직였으면 클릭(링크 이동) 막기
      if (moved > 6) {
        e.preventDefault();
        e.stopPropagation();
      }
    },
    true
  );
});

function getBrightness(hex) {
  hex = hex.replace('#', '');

  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);

  return (r * 299 + g * 587 + b * 114) / 1000;
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.log-item').forEach((item) => {
    const bg = getComputedStyle(item).getPropertyValue('--bg').trim();

    if (!bg) return;

    const brightness = getBrightness(bg);

    if (brightness > 170) {
      // 밝은 배경 → 어두운 글자
      item.style.color = '#6B6E6E'; // 진한 텍스트
    } else {
      // 어두운 배경 → 밝은 글자
      item.style.color = '#F2F2EE'; // Cloud Dancer
    }
  });
});

document.querySelectorAll('.log-item').forEach((item) => {
  const bg = getComputedStyle(item).getPropertyValue('--bg').trim();
  if (!bg) return;

  const brightness = getBrightness(bg);

  if (brightness > 170) {
    item.style.color = '#1E2328';
    item.classList.add('is-light');
    item.classList.remove('is-dark');
  } else {
    item.style.color = '#F2F2EE';
    item.classList.add('is-dark');
    item.classList.remove('is-light');
  }
});
