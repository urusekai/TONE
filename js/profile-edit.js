window.addEventListener('DOMContentLoaded', () => {
  const colorChangeButton = document.querySelector('.profile-color-btn');
  const mainAvatar = document.getElementById('profileMainAvatar');
  const headerAvatar = document.getElementById('profileHeaderAvatar');

  if (!colorChangeButton || !window.TonePopup) return;

  colorChangeButton.addEventListener('click', () => {
    window.TonePopup.openProfileModal({
      targets: [mainAvatar, headerAvatar]
    });
  });
});
