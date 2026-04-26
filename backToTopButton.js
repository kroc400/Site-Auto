(function() {
  // Создаём кнопку
  const backToTopBtn = document.createElement('button');
  backToTopBtn.id = 'backToTop';
  backToTopBtn.title = 'Наверх';
  backToTopBtn.innerHTML = '↑';
  backToTopBtn.style.cssText = `
    display: none; /* Скрыта по умолчанию */
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000; /* Поверх всех элементов */
    width: 50px;
    height: 50px;
    border: none;
    border-radius: var(--border-radius-light); /* Круглая форма */
    background-color:var(--color-dark);
    color: var(--color-light-grey);
    font-size: 20px;
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
  `;

  // Добавляем в тело документа
  document.body.appendChild(backToTopBtn);

  // Логика показа/скрытия
  function toggleBackToTop() {
    if (window.pageYOffset > window.innerHeight) {
      backToTopBtn.style.display = 'block';
    } else {
      backToTopBtn.style.display = 'none';
    }
  }

  toggleBackToTop();
  window.addEventListener('scroll', toggleBackToTop);

  // Плавная прокрутка
  backToTopBtn.addEventListener('click', function() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  // Стили при наведении
  backToTopBtn.addEventListener('mouseenter', function() {
    backToTopBtn.style.backgroundColor = 'var(--color-medium-grey)';
    backToTopBtn.style.transform = 'scale(1.1)';
    backToTopBtn.style.opacity = '1';
  });

  backToTopBtn.addEventListener('mouseleave', function() {
    backToTopBtn.style.backgroundColor = 'var(--color-dark)';
    backToTopBtn.style.transform = 'scale(1)';
    backToTopBtn.style.opacity = '0.7';
  });
})();
