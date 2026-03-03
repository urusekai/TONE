// login -> main
window.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.querySelector('.login-form');
  if (!loginForm) return;

  loginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    window.location.href = './main.html';
  });
});
