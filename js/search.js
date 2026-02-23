const searchData = {
  tags: ['Coral', 'White', '카더가든', '파란색', '여름노래'],
  recentColors: [
    { name: 'Coral', code: '#E89B92' },
    { name: 'White', code: '#FFFFFF' },
    { name: 'Gray', code: '#C0C0C0' },
    { name: 'Olive', code: '#ADB696' },
    { name: 'Purple', code: '#8D91C7' },
    { name: 'Pink', code: '#FFC0CB' }
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
  // 1. 최근 검색어 (가로 스크롤)
  document.getElementById('tag-list').innerHTML = searchData.tags
    .map(
      (t) => `
        <div class="tag">
            ${t}
            <button class="btn-delete"><img src="./assets/icons/remove.svg" alt="삭제"></button>
        </div>
    `
    )
    .join('');

  // 2. 최근 컬러 (가로 스크롤)
  document.getElementById('color-circle-list').innerHTML = searchData.recentColors
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
  document.getElementById('color-card-grid').innerHTML = searchData.recommended
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
document.addEventListener('DOMContentLoaded', renderSearchPage);
