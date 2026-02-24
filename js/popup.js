// popup.js

let selectedTempColor = ''; // 고른 색상을 잠시 보관할 변수

function openModal(type, data) {
  const modal = document.getElementById('commonModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalBody = document.getElementById('modalBody');

  modal.classList.add('active');

  if (type === 'duplicate') {
    modalTitle.innerText = '중복확인';
    modalBody.innerHTML = `
      <div class="modal-content-card">
        <p class="modal-body-text">
          <span class="highlight">${data}</span> 아이디<br>
          이용가능 합니다.
        </p>
      </div>
    `;
  } else if (type === 'profile') {
    modalTitle.innerText = '';
    modalBody.innerHTML = `
      <div id="mainProfileCircle" class="profile-main-circle">+</div>
      <p class="helper-text">프로필 색상을 선택해주세요</p>
      <div class="modal-content-card">
        <div class="color-picker-container">
          <div class="color-circle color-pink" onclick="selectColor(this)"></div>
          <div class="color-circle color-purple" onclick="selectColor(this)"></div>
          <div class="color-circle color-blue" onclick="selectColor(this)"></div>
          <div class="color-circle color-orange" onclick="selectColor(this)"></div>
          <div class="color-circle color-green" onclick="selectColor(this)"></div>
        </div>
      </div>
    `;
  }
}

// 색상 선택 시 실행
function selectColor(element) {
  const circles = document.querySelectorAll('.color-circle');
  circles.forEach((c) => c.classList.remove('selected'));
  element.classList.add('selected');

  // 선택한 배경색 가져오기
  selectedTempColor = window.getComputedStyle(element).backgroundColor;

  // 모달 내 미리보기 원형 색상 변경
  const mainCircle = document.getElementById('mainProfileCircle');
  if (mainCircle) {
    mainCircle.style.backgroundColor = selectedTempColor;
  }
}

// '확인' 버튼 클릭 시 호출되는 함수 수정
function closeModal() {
  // 만약 색상이 선택된 상태라면 헤더 아바타에 적용
  if (selectedTempColor) {
    const myAvatar = document.getElementById('myAvatar');
    if (myAvatar) {
      myAvatar.style.backgroundColor = selectedTempColor;
    }
  }

  // 모달 닫기
  document.getElementById('commonModal').classList.remove('active');

  // 상태 초기화 (다음 번을 위해)
  selectedTempColor = '';
}
