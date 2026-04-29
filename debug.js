// js/debug.js – универсальный режим разработки для всех страниц
(function() {
    // Проверяем наличие параметра dev=1 в URL или сохранённого флага
    const urlParams = new URLSearchParams(window.location.search);
    let isDev = urlParams.get('dev') === '1';

    if (!isDev && sessionStorage.getItem('devmode') === 'true') {
        isDev = true;
    }

    if (urlParams.get('dev') === '1') {
        sessionStorage.setItem('devmode', 'true');
        // Убираем параметр из URL для чистоты, но режим остаётся
        if (window.history.replaceState) {
            const cleanUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    if (!isDev) return; // обычный режим – ничего не делаем

    // ========== DEV-режим активен ==========
    console.log('🛠️ DEV-режим включён – используются тестовые данные');

    // Добавляем визуальную метку в правый верхний угол
    const devBadge = document.createElement('div');
    devBadge.textContent = 'DEV MODE';
    devBadge.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        background: #ff5722;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        z-index: 9999;
        font-family: monospace;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        pointer-events: none;
    `;
    const closeBtn = document.createElement('span');
    closeBtn.textContent = ' ✕';
    closeBtn.style.cssText = `
        margin-left: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        pointer-events: auto;
    `;
    closeBtn.title = 'Отключить DEV-режим и перезагрузить страницу';
    closeBtn.onclick = (e) => {
        e.stopPropagation();
        sessionStorage.removeItem('devmode');
        location.reload();
    };
    devBadge.appendChild(closeBtn);
    document.body.appendChild(devBadge);

    // ========== МОК-ДАННЫЕ ==========
    // Соответствуют новой структуре (price_value, image_url, title, procent)
    const mockCars = [
        {
            id: 1,
            title: 'Toyota Camry (тест)',
            price_value: 5000000,
            procent: 'Кредит от 31,9%',
            image_url: '/images/banner-car1.png',
            equipment: {
                безопасность: ['ABS', 'ESP', 'Подушки безопасности'],
                экстерьер: ['Легкосплавные диски', 'Рейлинги'],
                интерьер: ['Кожаный салон', 'Климат-контроль']
            },
            dimensions: {
                length: 4885,
                width: 1840,
                height: 1445,
                wheelbase: 2825,
                ground_clearance: 145
            }
        },
        {
            id: 2,
            title: 'Honda Civic (тест)',
            price_value: 3400000,
            procent: 'Кредит от 6,2%',
            image_url: '/images/banner-car1.png',
            equipment: {
                безопасность: ['ABS', 'EBD'],
                экстерьер: ['Спортивный обвес'],
                интерьер: ['Мультируль']
            },
            dimensions: {
                length: 4674,
                width: 1801,
                height: 1415,
                wheelbase: 2736,
                ground_clearance: 135
            }
        },
        {
            id: 3,
            title: 'Renault Logan (тест)',
            price_value: 999999000,
            procent: 'Кредит от 666,6%',
            image_url: '/images/banner-car1.png',
            equipment: {
                безопасность: ['ABS', 'Подушка водителя'],
                экстерьер: ['Стальные диски'],
                интерьер: ['Аудиоподготовка']
            },
            dimensions: {
                length: 4359,
                width: 1733,
                height: 1517,
                wheelbase: 2634,
                ground_clearance: 163
            }
        }
    ];

    // Мок-данные для сессии (авторизован)
    const mockSession = {
        logged_in: true,
        user_id: 999,
        username: 'devuser',
        role: 'user'
    };

    // Мок-данные профиля
    const mockProfile = {
        username: 'devuser',
        email: 'dev@example.com',
        surname: 'Иванов',
        name: 'Иван',
        patronymic: 'Иванович',
        phone: '+7(800)555-35-35'
    };

    // Мок-данные избранного (те же автомобили)
    function getMockFavorites() {
        return mockCars.map(car => ({
            ...car,
            added_at: new Date().toISOString()
        }));
    }

    // ========== ПЕРЕХВАТ FETCH ==========
    const originalFetch = window.fetch;

    window.fetch = async function(...args) {
        const url = args[0];
        const urlStr = typeof url === 'string' ? url : url.url;

        // Логируем все запросы в dev-режиме
        console.log(`[DEV] Запрос: ${urlStr}`);

        // ----- 1. Список автомобилей -----
        if (urlStr.includes('/api/cars.php')) {
            console.log('[DEV] Возвращаем мок-список авто');
            return new Response(JSON.stringify(mockCars), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 2. Один автомобиль -----
        if (urlStr.includes('/api/car.php')) {
            const idMatch = urlStr.match(/[?&]id=(\d+)/);
            const id = idMatch ? parseInt(idMatch[1]) : 0;
            const car = mockCars.find(c => c.id === id) || null;
            console.log(`[DEV] Возвращаем авто ID=${id}`, car);
            return new Response(JSON.stringify(car || { error: 'Автомобиль не найден' }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 3. Сессия (проверка авторизации) -----
        if (urlStr.includes('/api/check_session.php')) {
            console.log('[DEV] Возвращаем сессию (залогинен)');
            return new Response(JSON.stringify(mockSession), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 4. Профиль пользователя (GET) -----
        if (urlStr.includes('/api/user_profile.php') && args[1]?.method !== 'POST') {
            console.log('[DEV] Возвращаем мок-профиль');
            return new Response(JSON.stringify(mockProfile), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 5. Сохранение профиля (POST) -----
        if (urlStr.includes('/api/user_profile.php') && args[1]?.method === 'POST') {
            console.log('[DEV] Сохранение профиля (заглушка)');
            return new Response(JSON.stringify({ success: true, message: 'Профиль сохранён (тест)' }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 6. Получение избранного -----
        if (urlStr.includes('/api/get_favorites.php')) {
            const favorites = getMockFavorites();
            console.log('[DEV] Возвращаем избранное (2 авто)');
            return new Response(JSON.stringify(favorites), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 7. Добавление/удаление из избранного -----
        if (urlStr.includes('/api/favorites_toggle.php')) {
            console.log('[DEV] Переключение избранного (заглушка – ничего не меняем)');
            return new Response(JSON.stringify({ success: true, message: 'Действие выполнено (тест)', action: 'added' }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // ----- 8. Выход -----
        if (urlStr.includes('/api/logout.php')) {
            console.log('[DEV] Выход (заглушка, просто очищаем локальный флаг)');
            sessionStorage.removeItem('devmode');
            return new Response(JSON.stringify({ success: true }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // Все остальные запросы (например, статика, картинки) идут в реальность
        console.log('[DEV] Проксируем запрос (не мок):', urlStr);
        return originalFetch.apply(this, args);
    };

    // ========== ДОПОЛНИТЕЛЬНО: ГЛОБАЛЬНАЯ ФУНКЦИЯ ФОРМАТИРОВАНИЯ ЦЕНЫ ==========
    // Если её ещё нет на странице, добавляем
    if (typeof window.formatPrice !== 'function') {
        window.formatPrice = function(price) {
            if (price === undefined || price === null) return '— ₽';
            return price.toLocaleString('ru-RU') + ' ₽';
        };
        console.log('[DEV] Добавлена глобальная функция formatPrice');
    }

    // Для страниц, где используется авто-загрузка данных, можно принудительно перезапустить их
    // (но лучше, чтобы они сами перевызвали свои функции после загрузки)
    document.addEventListener('DOMContentLoaded', function() {
        // Если на странице есть функция loadCarsFromDB – перезапускаем
        if (typeof window.loadCarsFromDB === 'function') {
            console.log('[DEV] Вызываем loadCarsFromDB (перезагрузка карточек)');
            window.loadCarsFromDB();
        }
        // Если на странице есть функция loadFavorites – перезапускаем
        if (typeof window.loadFavorites === 'function') {
            console.log('[DEV] Вызываем loadFavorites (обновление избранного)');
            window.loadFavorites();
        }
        // Если на странице есть функция loadUserProfile – перезапускаем
        if (typeof window.loadUserProfile === 'function') {
            console.log('[DEV] Вызываем loadUserProfile');
            window.loadUserProfile();
        }
        // Если на странице есть функция checkAuth – перезапускаем
        if (typeof window.checkAuth === 'function') {
            console.log('[DEV] Вызываем checkAuth');
            window.checkAuth();
        }
    });
})();