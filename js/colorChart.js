document.querySelectorAll('.cc-item').forEach((item) => {
  item.querySelector('.cc-link').addEventListener('click', (e) => {
    if (e.target.closest('button')) return;

    document.querySelectorAll('.cc-item.is-active').forEach((el) => {
      if (el !== item) el.classList.remove('is-active');
    });

    item.classList.toggle('is-active');
  });
});
