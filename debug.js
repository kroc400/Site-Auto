// js/debug.js – универсальный режим разработки для всех страниц
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    let isDev = urlParams.get('dev') === '1';
    if (!isDev && sessionStorage.getItem('devmode') === 'true') isDev = true;
    if (urlParams.get('dev') === '1') {
        sessionStorage.setItem('devmode', 'true');
        if (window.history.replaceState) {
            const cleanUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }
    if (!isDev) return;

    console.log('🛠️ DEV-режим включён – используются тестовые данные');

    const devBadge = document.createElement('div');
    devBadge.textContent = 'DEV MODE';
    devBadge.style.cssText = `position:fixed;top:10px;right:10px;background:#ff5722;color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:bold;z-index:9999;font-family:monospace;box-shadow:0 2px 6px rgba(0,0,0,0.3);pointer-events:none;`;
    const closeBtn = document.createElement('span');
    closeBtn.textContent = ' ✕';
    closeBtn.style.cssText = `margin-left:8px;cursor:pointer;font-size:14px;font-weight:bold;pointer-events:auto;`;
    closeBtn.title = 'Отключить DEV-режим и перезагрузить страницу';
    closeBtn.onclick = (e) => { e.stopPropagation(); sessionStorage.removeItem('devmode'); location.reload(); };
    devBadge.appendChild(closeBtn);
    document.body.appendChild(devBadge);

    // Мок-данные с разными ценами и количеством
    const mockCars = [
        {
            id: 1,
            title: 'Toyota Camry (тест)',
            price_value: 5000000,
            stock_quantity: 3,
            procent: 'Кредит от 31,9%',
            image_url: '/images/placeholder.png',
            equipment: { безопасность: ['ABS', 'ESP'], экстерьер: [], интерьер: [] },
            dimensions: { length: 4885, width: 1840, height: 1445 }
        },
        {
            id: 2,
            title: 'Honda Civic (тест)',
            price_value: 3400000,
            stock_quantity: 1,
            procent: 'Кредит от 6,2%',
            image_url: '/images/placeholder.png',
            equipment: { безопасность: ['ABS'], экстерьер: [], интерьер: [] },
            dimensions: { length: 4674, width: 1801, height: 1415 }
        },
        {
            id: 3,
            title: 'Renault Logan (тест)',
            price_value: 999999000,
            stock_quantity: 5,
            procent: 'Кредит от 666,6%',
            image_url: '/images/placeholder.png',
            equipment: { безопасность: ['ABS'], экстерьер: [], интерьер: [] },
            dimensions: { length: 4359, width: 1733, height: 1517 }
        },
        {
            id: 4,
            title: 'Audi RS7 (тест)',
            price_value: 4050000,
            stock_quantity: 2,
            procent: 'Кредит от 31,9%',
            image_url: '/images/placeholder.png',
            equipment: {},
            dimensions: {}
        }
    ];

    const mockSession = {
        logged_in: true,
        user_id: 999,
        username: 'devuser',
        role: 'user'
    };

    const mockProfile = {
        username: 'devuser',
        email: 'dev@example.com',
        surname: 'Иванов',
        name: 'Иван',
        patronymic: 'Иванович',
        phone: '+7(800)555-35-35'
    };

    function getMockFavorites() {
        return mockCars.map(car => ({ ...car, added_at: new Date().toISOString() }));
    }

    const mockReviews = [
        { id: 1, user_name: 'Алексей (тест)', rating: 10, comment: 'Отлично!', date: '15.05.2024' },
        { id: 2, user_name: 'Екатерина (тест)', rating: 9, comment: 'Хорошо', date: '10.05.2024' }
    ];
    function getMockReviews() { return mockReviews; }

    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        const url = args[0];
        const urlStr = typeof url === 'string' ? url : url.url;
        console.log(`[DEV] Запрос: ${urlStr}`);

        if (urlStr.includes('/api/cars.php')) {
            console.log('[DEV] Возвращаем мок-список авто');
            return new Response(JSON.stringify(mockCars), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/car.php')) {
            const idMatch = urlStr.match(/[?&]id=(\d+)/);
            const id = idMatch ? parseInt(idMatch[1]) : 0;
            const car = mockCars.find(c => c.id === id) || null;
            return new Response(JSON.stringify(car || { error: 'Автомобиль не найден' }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/check_session.php')) {
            return new Response(JSON.stringify(mockSession), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/user_profile.php') && args[1]?.method !== 'POST') {
            return new Response(JSON.stringify(mockProfile), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/user_profile.php') && args[1]?.method === 'POST') {
            return new Response(JSON.stringify({ success: true, message: 'Профиль сохранён (тест)' }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/get_favorites.php')) {
            return new Response(JSON.stringify(getMockFavorites()), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/favorites_toggle.php')) {
            return new Response(JSON.stringify({ success: true, message: 'Действие выполнено (тест)', action: 'added' }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/logout.php')) {
            sessionStorage.removeItem('devmode');
            return new Response(JSON.stringify({ success: true }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/get_reviews.php')) {
            return new Response(JSON.stringify(getMockReviews()), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        if (urlStr.includes('/api/add_review.php')) {
            return new Response(JSON.stringify({ success: true, message: 'Ваш отзыв добавлен! (тестовый режим)' }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        console.log('[DEV] Проксируем запрос (не мок):', urlStr);
        return originalFetch.apply(this, args);
    };

    if (typeof window.formatPrice !== 'function') {
        window.formatPrice = function(price) {
            if (price === undefined || price === null) return '— ₽';
            return price.toLocaleString('ru-RU') + ' ₽';
        };
        console.log('[DEV] Добавлена глобальная функция formatPrice');
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.loadCarsFromDB === 'function') window.loadCarsFromDB();
        if (typeof window.loadFavorites === 'function') window.loadFavorites();
        if (typeof window.loadUserProfile === 'function') window.loadUserProfile();
        if (typeof window.checkAuth === 'function') window.checkAuth();
        if (typeof window.loadReviewsFromDB === 'function') window.loadReviewsFromDB();
    });

    function initBookingModalForDev() {
        const bookingBtn = document.getElementById('bookingBtn');
        const modal = document.getElementById('bookingModal');
        if (!bookingBtn || !modal) return;
        console.log('[DEV] Принудительная инициализация модального окна бронирования');
        if (!window.currentBookingCar) {
            window.currentBookingCar = { id: 999, title: 'Тестовый автомобиль (DEV)', price: '5 000 000 ₽' };
        }
        const newBtn = bookingBtn.cloneNode(true);
        bookingBtn.parentNode.replaceChild(newBtn, bookingBtn);
        newBtn.addEventListener('click', () => {
            const carInfoDiv = document.getElementById('bookingCarInfo');
            if (carInfoDiv && window.currentBookingCar) carInfoDiv.innerHTML = `<p><strong>${window.currentBookingCar.title}</strong><br>Цена: ${window.currentBookingCar.price}</p>`;
            modal.style.display = 'flex';
        });
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            const newClose = closeBtn.cloneNode(true);
            closeBtn.parentNode.replaceChild(newClose, closeBtn);
            newClose.addEventListener('click', () => modal.style.display = 'none');
        }
        window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
        const submitBtn = document.getElementById('submitBookingBtn');
        if (submitBtn) {
            const newSubmit = submitBtn.cloneNode(true);
            submitBtn.parentNode.replaceChild(newSubmit, submitBtn);
            newSubmit.addEventListener('click', () => {
                const nameInput = document.getElementById('bookingName');
                const phoneInput = document.getElementById('bookingPhone');
                const messageDiv = document.getElementById('bookingMessage');
                const name = nameInput?.value.trim() || 'Тест';
                const phone = phoneInput?.value.trim() || '+79991234567';
                if (!name || !phone) {
                    if (messageDiv) { messageDiv.textContent = 'Заполните имя и телефон'; messageDiv.style.color = 'red'; }
                    return;
                }
                if (messageDiv) { messageDiv.textContent = '✅ Заявка принята (тест). Номер заказа: 999.'; messageDiv.style.color = 'green'; }
                newSubmit.disabled = true;
                newSubmit.textContent = '✓ Заявка отправлена';
                if (nameInput) nameInput.value = '';
                if (phoneInput) phoneInput.value = '';
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookingModalForDev);
    } else {
        initBookingModalForDev();
    }

    const originalFetchForDev = window.fetch;
    window.fetch = async function(...args) {
        const urlStr = args[0];
        if (typeof urlStr === 'string' && urlStr.includes('/api/callback.php')) {
            console.log('[DEV] Перехват отправки формы бронирования – возвращаем заглушку');
            return new Response(JSON.stringify({ success: true, message: 'Заявка принята (тестовый режим). Номер заказа: 999.' }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        }
        return originalFetchForDev.apply(this, args);
    };
})();