const template = document.createElement('template');
template.innerHTML = `
  <footer class="footer">
        <section class="footer-section">
            <div class="footer-container">
                <div class="left-column">
                    <div class="diller-info">© 2026 Автосалон "ТулГУ". Официальный дилер</div>
                    <div class="links">
                        <a href="#">Политика конфиденциальности</a>
                        <a href="#">Пользовательское соглашение</a>
                    </div>
                </div>
                <div class="right-column">
                    <div class="offert-text">
                        Обращаем Ваше внимание на то, что данный интернет-сайт носит исключительно информационный характер и ни при каких условиях не является публичной офертой, определяемой положениями Статьи 437 Гражданского кодекса Российской Федерации.
                    </div>
                </div>
            </div>
        </section>
    </footer>
`;

// Находим первый <footer> на странице и заменяем его содержимое
const footerElement = document.querySelector('footer');
if (footerElement) {
  footerElement.innerHTML = '';
  footerElement.appendChild(template.content);
}
