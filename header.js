const template = document.createElement('template');
template.innerHTML = `
    <header class="header">
        <div class="header-actions">
            <!-- <button
                class="header-burger-button"
                type="button">
                <svg width="28" height="22" viewBox="0 0 28 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <line x1="2" y1="2" x2="26" y2="2" stroke="#262626" stroke-width="4" stroke-linecap="round"/>
                <line x1="2" y1="11" x2="26" y2="11" stroke="#262626" stroke-width="4" stroke-linecap="round"/>
                <line x1="2" y1="20" x2="26" y2="20" stroke="#262626" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </button> -->
        </div>
        <a class="header-logo" href="/">
            <img
                class="header-logo"
                src="./images/logo.png"
                alt="Logo"
                width="198", height="50" loading="lazy"/>
        </a>
        <nav class="header-menu">
            <ul class="header-menu-list">
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/">О компании</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/">Техцентр</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/">Отзывы</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/">Контакты</a>
                </li>
            </ul>
        </nav>
        <div class="mobile-contacts">
        <div class="mobile-contact-item">
            <img src="/icons/mobile-logo.svg" alt="Phone" class="mobile-icon">
            <span class="mobile-text-main">+7(800)555-35-35</span>
        </div>
        <div class="mobile-contact-item">
            <span class="mobile-text-second">+7(495)228-12-34</span>
        </div>
        </div>
    </header>
`;

// Находим первый <header> на странице и заменяем его содержимое
const headerElement = document.querySelector('header');
if (headerElement) {
  headerElement.innerHTML = '';
  headerElement.appendChild(template.content);
}
export {}