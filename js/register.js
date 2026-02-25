// register -> main
window.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.querySelector('.register-form');
  if (!registerForm) return;

  registerForm.addEventListener('submit', (event) => {
    event.preventDefault();
    window.location.href = './main.html';
  });
});
