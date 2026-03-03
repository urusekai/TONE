const duplicateModal = document.getElementById('duplicateModal');
const profileModal = document.getElementById('profileModal');
const duplicateIdText = document.getElementById('duplicateIdText');
const profilePreview = document.getElementById('mainProfileCircle');

let selectedTempColor = '';
let profileTargets = [];
let profileConfirmCallback = null;

function resolveTarget(target) {
  if (!target) return null;
  if (typeof target === 'string') return document.querySelector(target);
  return target;
}

function getBaseColor() {
  for (const target of profileTargets) {
    const element = resolveTarget(target);
    if (!element) continue;
    const color = window.getComputedStyle(element).backgroundColor;
    if (color) return color;
  }
  return '#B7AEA6';
}

function resetProfileModal() {
  if (!profileModal) return;
  selectedTempColor = '';
  profileModal
    .querySelectorAll('.color-circle')
    .forEach((circle) => circle.classList.remove('selected'));
  if (profilePreview) {
    profilePreview.style.backgroundColor = getBaseColor();
  }
}

function applyProfileColor(color) {
  if (!color) return;
  profileTargets.forEach((target) => {
    const element = resolveTarget(target);
    if (element) {
      element.style.backgroundColor = color;
    }
  });
}

function openDuplicateModal(idValue = 'admin') {
  if (!duplicateModal) return;
  if (duplicateIdText) {
    duplicateIdText.textContent = idValue || 'admin';
  }
  duplicateModal.classList.add('active');
}

function closeDuplicateModal() {
  if (!duplicateModal) return;
  duplicateModal.classList.remove('active');
}

function openProfileModal(options = {}) {
  if (!profileModal) return;
  profileTargets = Array.isArray(options.targets) ? options.targets : [];
  profileConfirmCallback = typeof options.onConfirm === 'function' ? options.onConfirm : null;
  resetProfileModal();
  profileModal.classList.add('active');
}

function closeProfileModal(confirm = false) {
  if (!profileModal) return;
  if (confirm && selectedTempColor) {
    applyProfileColor(selectedTempColor);
  }
  profileModal.classList.remove('active');

  if (confirm && profileConfirmCallback) {
    profileConfirmCallback(selectedTempColor);
  }

  selectedTempColor = '';
  profileTargets = [];
  profileConfirmCallback = null;
}

function selectColor(button) {
  if (!profileModal || !button) return;

  profileModal
    .querySelectorAll('.color-circle')
    .forEach((circle) => circle.classList.remove('selected'));
  button.classList.add('selected');

  selectedTempColor = button.dataset.color || window.getComputedStyle(button).backgroundColor;
  if (profilePreview) {
    profilePreview.style.backgroundColor = selectedTempColor;
  }
}

document.addEventListener('click', (event) => {
  const colorCircle = event.target.closest('.color-circle');
  if (colorCircle && profileModal && profileModal.contains(colorCircle)) {
    selectColor(colorCircle);
    return;
  }

  const closeButton = event.target.closest('[data-popup-close]');
  if (!closeButton) return;

  const type = closeButton.dataset.popupClose;
  const confirm = closeButton.dataset.popupConfirm === 'true';

  if (type === 'duplicate') {
    closeDuplicateModal();
    return;
  }

  if (type === 'profile') {
    closeProfileModal(confirm);
  }
});

// 레거시 인라인 호출 호환을 위한 전역 함수
window.openModal = (type, data = '') => {
  if (type === 'duplicate') {
    openDuplicateModal(data);
    return;
  }
  if (type === 'profile') {
    openProfileModal();
  }
};

window.closeModal = (type) => {
  if (type === 'duplicate') {
    closeDuplicateModal();
    return;
  }
  if (type === 'profile') {
    closeProfileModal(true);
  }
};

window.selectColor = selectColor;

// 페이지별 스크립트에서 사용하는 전역 API
window.TonePopup = {
  openDuplicateModal,
  openProfileModal,
  closeDuplicateModal,
  closeProfileModal
};
