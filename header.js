const template = document.createElement('template');
template.innerHTML = `
    <header class="header">
        <div class="header-actions">
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
                    <a class="header-menu-link" href="/company_info.html">О компании</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/technical_center.html">Техцентр</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/">Отзывы</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/contacts.html">Контакты</a>
                </li>
                <li class="header-menu-item">
                    <a class="header-menu-link" href="/account.html">Личный кабинет</a>
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