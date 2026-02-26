window.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.querySelector('.register-form');
  const idInput = document.getElementById('register-id');
  const idCheckButton = document.querySelector('.id-check');

  if (!registerForm) return;

  if (idCheckButton) {
    idCheckButton.addEventListener('click', () => {
      const idValue = idInput ? idInput.value.trim() : '';
      if (window.TonePopup) {
        window.TonePopup.openDuplicateModal(idValue || 'admin');
      }
    });
  }

  registerForm.addEventListener('submit', (event) => {
    event.preventDefault();

    if (!window.TonePopup) {
      window.location.href = './main.html';
      return;
    }

    window.TonePopup.openProfileModal({
      onConfirm: () => {
        window.location.href = './main.html';
      }
    });
  });
});
