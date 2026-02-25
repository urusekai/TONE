// 페이지 로드 후 실행
document.addEventListener('DOMContentLoaded', () => {
  const memoInput = document.getElementById('memoInput');
  const currentCount = document.getElementById('currentCount');

  // 입력이 발생할 때마다 실행되는 이벤트
  memoInput.addEventListener('input', () => {
    // 현재 입력된 글자 수 계산
    const length = memoInput.value.length;
    
    // 화면에 반영
    currentCount.textContent = length;

    // (선택사항) 80자에 도달하면 숫자를 빨간색으로 바꾸는 효과
    if (length >= 80) {
      currentCount.style.color = '#ff6b6b';
    } else {
      currentCount.style.color = '#8a8a8a';
    }
  });
});