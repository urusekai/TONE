// popup.js

const duplicateModal = document.getElementById('duplicateModal');
const profileModal = document.getElementById('profileModal');
const duplicateIdText = document.getElementById('duplicateIdText');
const myAvatar = document.getElementById('myAvatar');

let selectedTempColor = '';

function openModal(type, data = '') {
  // Vue 전환 시: classList.add('active') 대신 isDuplicateOpen / isProfileOpen 같은 상태값 사용
  if (type === 'duplicate' && duplicateModal) {
    if (duplicateIdText) {
      duplicateIdText.textContent = data || 'admin';
    }
    duplicateModal.classList.add('active');
    return;
  }

  if (type === 'profile' && profileModal) {
    selectedTempColor = '';
    const circles = profileModal.querySelectorAll('.color-circle');
    circles.forEach((circle) => circle.classList.remove('selected'));

    const preview = document.getElementById('mainProfileCircle');
    if (preview) {
      preview.style.backgroundColor = '#B7AEA6';
    }

    profileModal.classList.add('active');
  }
}

function selectColor(button) {
  if (!profileModal) return;

  const circles = profileModal.querySelectorAll('.color-circle');
  circles.forEach((circle) => circle.classList.remove('selected'));
  button.classList.add('selected');

  selectedTempColor = button.dataset.color || window.getComputedStyle(button).backgroundColor;

  const preview = document.getElementById('mainProfileCircle');
  if (preview) {
    preview.style.backgroundColor = selectedTempColor;
  }
}

function closeModal(type) {
  // Vue 전환 시: classList.remove('active') 대신 상태값 false 처리
  if (type === 'duplicate' && duplicateModal) {
    duplicateModal.classList.remove('active');
    return;
  }

  if (type === 'profile' && profileModal) {
    if (selectedTempColor && myAvatar) {
      myAvatar.style.backgroundColor = selectedTempColor;
    }
    profileModal.classList.remove('active');
    selectedTempColor = '';
  }
}
