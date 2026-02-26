const searchData = {
  tags: ['Coral', 'White', '카더가든', '파란색', '여름노래'],
  recentColors: [
    { name: 'Butter cream', code: '#EFE1A7' },
    { name: 'Cerulean', code: '#9BB7D4' },
    { name: 'Tangerine', code: '#DD4124' },
    { name: 'Rose Quartz', code: '#F7CAC9' },
    { name: 'Classic Blue', code: '#0F4C81' },
    { name: 'Green Ash', code: '#A0DAA9' },
    { name: 'Viva Magenta', code: '#BE3455' },
    { name: 'Ultimate Gray', code: '#939597' }
  ],
  recommended: [
    { brand: 'PANTONE', name: 'Pale Khaki', code: '#C4B495' },
    { brand: 'PANTONE', name: 'Gray Lilac', code: '#D6CBD3' },
    { brand: 'PANTONE', name: 'Gray Sand', code: '#E7D1B6' },
    { brand: 'PANTONE', name: 'Viva Magenta', code: '#BE3455' },
    { brand: 'PANTONE', name: 'Pale Dogwood', code: '#8D91C7' },
    { brand: 'PANTONE', name: 'Color Pale Dogwood', code: '#ADB696' }
  ]
};

function renderSearchPage() {
  const tagListEl = document.getElementById('tag-list');
  const colorCircleListEl = document.getElementById('color-circle-list');
  const colorCardGridEl = document.getElementById('color-card-grid');

  if (!tagListEl || !colorCircleListEl || !colorCardGridEl) return;

  // 1. 최근 검색어 (가로 스크롤)
  tagListEl.innerHTML = searchData.tags
    .map(
      (t) => `
        <div class="tag">
            ${t}
            <button class="btn-delete"><img src="./assets/icons/remove.svg" alt="삭제"></button>
        </div>
    `
    )
    .join('');

  // 2. 최근 컬러 (아이템은 한 줄, 텍스트는 최대 2줄 래핑)
  colorCircleListEl.innerHTML = searchData.recentColors
    .map(
      (c) => `
        <div class="color-item">
            <div class="color-circle" style="background-color: ${c.code}"></div>
            <span class="color-label">${c.name}</span>
        </div>
    `
    )
    .join('');

  // 3. 인기 추천 컬러 (2열 그리드)
  colorCardGridEl.innerHTML = searchData.recommended
    .map(
      (r) => `
        <article class="pantone-card">
            <div class="card-color-top" style="background-color: ${r.code}"></div>
            <div class="card-info-bottom">
                <span class="brand-name">${r.brand}</span>
                <p class="color-detail-name">${r.name}</p>
            </div>
        </article>
    `
    )
    .join('');
}

// 페이지 로드 시 실행
document.addEventListener('DOMContentLoaded', () => {
  renderSearchPage();

  const goToPlaylist = () => {
    window.location.href = './playlist.html';
  };

  const colorCircleListEl = document.getElementById('color-circle-list');
  const colorCardGridEl = document.getElementById('color-card-grid');

  if (colorCircleListEl) {
    colorCircleListEl.addEventListener('click', (event) => {
      const colorItem = event.target.closest('.color-item');
      if (!colorItem) return;
      goToPlaylist();
    });
  }

  if (colorCardGridEl) {
    colorCardGridEl.addEventListener('click', (event) => {
      const card = event.target.closest('.pantone-card');
      if (!card) return;
      goToPlaylist();
    });
  }

  const searchFormEl = document.querySelector('.search-input-box');
  if (!searchFormEl) return;

  // 디자인 단계에서는 페이지 리로드 없이 검색 UI만 유지한다.
  searchFormEl.addEventListener('submit', (event) => {
    event.preventDefault();
  });
});
