// ========== СООТВЕТСТВИЕ ЦВЕТОВ HEX ==========
const colorMap = {
    "Красный": "#CA0100",
    "Красный (Red)": "#CA0100",
    "Синий": "#1E3A8A",
    "Синий (Blue)": "#1E3A8A",
    "Черный": "#1A1A1A",
    "Черный (Black)": "#1A1A1A",
    "Белый": "#F5F5F5",
    "Белый (White)": "#F5F5F5",
    "Серый": "#888888",
    "Серый (Grey)": "#888888",
    "Зеленый": "#4CAF50",
    "Желтый": "#FFC107"
};

// ========== ОСНОВНЫЕ ФУНКЦИИ ==========
async function loadCarDetails() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const carId = parseInt(urlParams.get('id'));
        if (!carId) throw new Error('Не указан ID автомобиля');

        const response = await fetch(`api/car.php?id=${carId}`);
        if (!response.ok) throw new Error('Ошибка загрузки данных');

        const car = await response.json();
        if (car && !car.error) {
            updateCarDisplay(car);
            setupBooking(car);
        } else {
            showError(car?.error || 'Автомобиль не найден');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        showError('Ошибка загрузки данных: ' + error.message);
    }
}

function formatPrice(price) {
    if (price === undefined || price === null) return '— ₽';
    return price.toLocaleString('ru-RU') + ' ₽';
}

function fillDimensions(dimensions) {
    const lengthEl = document.getElementById('dimension-length');
    if (lengthEl && dimensions.length !== undefined) lengthEl.textContent = dimensions.length;
    const widthBackEl = document.getElementById('dimension-width-back');
    if (widthBackEl && dimensions.width !== undefined) widthBackEl.textContent = dimensions.width;
    const widthFrontEl = document.getElementById('dimension-width-front');
    if (widthFrontEl && dimensions.width !== undefined) widthFrontEl.textContent = dimensions.width;
    const heightEl = document.getElementById('dimension-height');
    if (heightEl && dimensions.height !== undefined) heightEl.textContent = dimensions.height;
}

function updateCarDisplay(car) {
    const bannerTitle = document.querySelector('.car-title-banner');
    if (bannerTitle) bannerTitle.textContent = car.title;
    document.getElementById('car-title').textContent = car.title;
    document.getElementById('car-price').textContent = `${formatPrice(car.price_value)}`;
    document.getElementById('car-procent').textContent = `${car.procent}`;
    document.title = car.title;

    if (car.dimensions) {
        fillDimensions(car.dimensions);
        fillSpecsTable(car.dimensions);
    }

    const bannerImg = document.querySelector('.Toyota_Camry-banner-img');
    if (bannerImg && car.image_url) {
        bannerImg.src = car.image_url;
        bannerImg.alt = car.title;
        bannerImg.onerror = () => { this.src = '/images/placeholder.jpg'; };
    }

    const equipment = car.equipment || {};
    const equipmentGrid = document.querySelector('.equipment-grid');
    if (equipmentGrid) {
        equipmentGrid.innerHTML = '';
        const categoryNames = {
            'безопасность': 'Безопасность',
            'экстерьер': 'Экстерьер',
            'интерьер': 'Интерьер',
            'опции': 'Опции',
            'мультимедиа': 'Мультимедиа',
            'двигатель': 'Двигатель',
            'трансмиссия': 'Трансмиссия'
        };
        let hasVisibleCategories = false;
        for (const [categoryKey, items] of Object.entries(equipment)) {
            if (items && Array.isArray(items) && items.length > 0) {
                hasVisibleCategories = true;
                const columnElement = document.createElement('div');
                columnElement.className = 'equipment-column';
                const titleElement = document.createElement('h3');
                titleElement.className = 'equipment-title';
                titleElement.textContent = categoryNames[categoryKey] || categoryKey.charAt(0).toUpperCase() + categoryKey.slice(1);
                const hrBefore = document.createElement('hr');
                hrBefore.className = 'equipment-hr';
                const listElement = document.createElement('ul');
                items.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    listElement.appendChild(li);
                });
                const hrAfter = document.createElement('hr');
                hrAfter.className = 'equipment-hr';
                columnElement.appendChild(titleElement);
                columnElement.appendChild(hrBefore);
                columnElement.appendChild(listElement);
                columnElement.appendChild(hrAfter);
                equipmentGrid.appendChild(columnElement);
            }
        }
        if (!hasVisibleCategories) {
            equipmentGrid.innerHTML = '<div class="no-equipment-message">Информация о комплектации отсутствует</div>';
        } else {
            setTimeout(alignColumnHeights, 100);
        }
    }

    setupColorPicker(car);
}

function alignColumnHeights() {
    const columns = document.querySelectorAll('.equipment-column');
    if (!columns.length) return;
    columns.forEach(col => {
        const list = col.querySelector('ul');
        if (list) list.style.minHeight = 'auto';
    });
    let maxHeight = 0;
    columns.forEach(col => {
        const title = col.querySelector('.equipment-title');
        const list = col.querySelector('ul');
        const hrBefore = col.querySelector('.equipment-hr:first-of-type');
        if (title && list && hrBefore) {
            const h = title.offsetHeight + hrBefore.offsetHeight + list.offsetHeight;
            if (h > maxHeight) maxHeight = h;
        }
    });
    columns.forEach(col => {
        const title = col.querySelector('.equipment-title');
        const list = col.querySelector('ul');
        const hrBefore = col.querySelector('.equipment-hr:first-of-type');
        if (title && list && hrBefore) {
            list.style.minHeight = (maxHeight - title.offsetHeight - hrBefore.offsetHeight) + 'px';
        }
    });
}

function fillSpecsTable(dimensions) {
    const dimensionMap = {
        'Длина кузова, мм': 'length',
        'Ширина кузова, мм': 'width',
        'Высота кузова, мм': 'height',
        'Колёсная база, мм': 'wheelbase',
        'Дорожный просвет, мм': 'ground_clearance'
    };
    document.querySelectorAll('.specs-table-row').forEach(row => {
        const label = row.querySelector('.specs-label');
        const value = row.querySelector('.specs-value');
        if (label && value) {
            const field = dimensionMap[label.textContent.trim()];
            value.textContent = (field && dimensions[field] !== undefined) ? dimensions[field] : '—';
        }
    });
}

function showError(message) {
    const priceElem = document.getElementById('car-price');
    if (priceElem) {
        priceElem.textContent = message;
        priceElem.style.color = 'red';
    }
    const equipmentGrid = document.querySelector('.equipment-grid');
    if (equipmentGrid) {
        equipmentGrid.innerHTML = '<div class="no-equipment-message">Не удалось загрузить данные</div>';
    }
}

// ========== БРОНИРОВАНИЕ ==========
let currentBookingCar = null;

function setupBooking(car) {
    currentBookingCar = {
        id: car.id,
        title: car.title,
        price: formatPrice(car.price_value)
    };

    const bookingBtn = document.getElementById('bookingBtn');
    const modal = document.getElementById('bookingModal');
    const closeBtn = document.querySelector('.modal-close');
    const submitBtn = document.getElementById('submitBookingBtn');

    if (!bookingBtn) {
        console.error('Кнопка бронирования не найдена в DOM');
        return;
    }

    const newBookingBtn = bookingBtn.cloneNode(true);
    bookingBtn.parentNode.replaceChild(newBookingBtn, bookingBtn);

    newBookingBtn.addEventListener('click', () => {
        if (!currentBookingCar) {
            console.error('Нет данных об автомобиле');
            return;
        }
        const carInfoDiv = document.getElementById('bookingCarInfo');
        if (carInfoDiv) {
            carInfoDiv.innerHTML = `<p><strong>${currentBookingCar.title}</strong><br>Цена: ${currentBookingCar.price}</p>`;
        }
        fetch('/api/user_profile.php')
            .then(res => res.json())
            .then(user => {
                const nameInput = document.getElementById('bookingName');
                if (nameInput && user.name) nameInput.value = user.name;
            })
            .catch(() => {});
        if (modal) modal.style.display = 'flex';
    });

    if (closeBtn) {
        const newCloseBtn = closeBtn.cloneNode(true);
        closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
        newCloseBtn.addEventListener('click', () => {
            if (modal) modal.style.display = 'none';
        });
    }

    if (modal) {
        window.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    }

    if (submitBtn) {
        const newSubmitBtn = submitBtn.cloneNode(true);
        submitBtn.parentNode.replaceChild(newSubmitBtn, submitBtn);

        newSubmitBtn.addEventListener('click', async () => {
            const nameInput = document.getElementById('bookingName');
            const phoneInput = document.getElementById('bookingPhone');
            const consentCheckbox = document.getElementById('bookingConsent');
            const messageDiv = document.getElementById('bookingMessage');

            const name = nameInput ? nameInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';

            if (!name || !phone) {
                if (messageDiv) {
                    messageDiv.textContent = 'Заполните имя и телефон';
                    messageDiv.style.color = 'red';
                }
                return;
            }

            if (!consentCheckbox || !consentCheckbox.checked) {
                if (messageDiv) {
                    messageDiv.textContent = 'Подтвердите согласие с Политикой конфиденциальности и Пользовательским соглашением, а также дайте согласие на получение звонка';
                    messageDiv.style.color = 'red';
                }
                return;
            }

            newSubmitBtn.disabled = true;
            newSubmitBtn.textContent = 'Отправка...';

            try {
                const response = await fetch('/api/callback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name,
                        phone: phone,
                        car_id: currentBookingCar.id,
                        car_title: currentBookingCar.title,
                        car_price: currentBookingCar.price,
                        consent: true
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (messageDiv) {
                        messageDiv.textContent = result.message;
                        messageDiv.style.color = 'green';
                    }
                    newSubmitBtn.textContent = '✓ Заявка отправлена';
                    if (nameInput) nameInput.value = '';
                    if (phoneInput) phoneInput.value = '';
                    if (consentCheckbox) consentCheckbox.checked = false;
                } else {
                    if (messageDiv) {
                        messageDiv.textContent = result.error || 'Ошибка отправки';
                        messageDiv.style.color = 'red';
                    }
                    newSubmitBtn.disabled = false;
                    newSubmitBtn.textContent = 'Отправить заявку';
                }
            } catch (error) {
                console.error('Ошибка:', error);
                if (messageDiv) {
                    messageDiv.textContent = 'Ошибка соединения';
                    messageDiv.style.color = 'red';
                }
                newSubmitBtn.disabled = false;
                newSubmitBtn.textContent = 'Отправить заявку';
            }
        });
    }
}

// ========== ИЗБРАННОЕ ==========
let currentCarId = null;

async function checkAuthStatus() {
    try {
        const response = await fetch('/api/check_session.php');
        const data = await response.json();
        return data.logged_in;
    } catch {
        return false;
    }
}

async function checkFavoriteStatus(carId) {
    try {
        const response = await fetch('/api/get_favorites.php');
        const favorites = await response.json();
        if (favorites.error) return false;
        return favorites.some(car => car.id === carId);
    } catch {
        return false;
    }
}

function updateFavoriteButton(isFavorite) {
    const btn = document.getElementById('favoriteBtn');
    if (!btn) return;
    if (isFavorite) {
        btn.textContent = '✅';
        btn.classList.add('active');
    } else {
        btn.textContent = '❤️';
        btn.classList.remove('active');
    }
}

async function toggleFavorite(carId) {
    const messageDiv = document.getElementById('favoriteMessage');
    const isLoggedIn = await checkAuthStatus();
    if (!isLoggedIn) {
        if (messageDiv) {
            messageDiv.textContent = 'Войдите в аккаунт';
            messageDiv.className = 'favorite-message error';
            setTimeout(() => { if (messageDiv) messageDiv.textContent = ''; }, 3000);
        }
        return;
    }
    try {
        const response = await fetch('/api/favorites_toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ car_id: carId })
        });
        const result = await response.json();
        if (result.success) {
            updateFavoriteButton(result.action === 'added');
            if (messageDiv) {
                messageDiv.textContent = result.message;
                messageDiv.className = 'favorite-message success';
            }
        } else {
            if (messageDiv) {
                messageDiv.textContent = result.message || 'Ошибка';
                messageDiv.className = 'favorite-message error';
            }
        }
        setTimeout(() => { if (messageDiv) messageDiv.textContent = ''; }, 2000);
    } catch {
        if (messageDiv) {
            messageDiv.textContent = 'Ошибка соединения';
            messageDiv.className = 'favorite-message error';
        }
    }
}

async function setupFavorites() {
    const urlParams = new URLSearchParams(window.location.search);
    currentCarId = parseInt(urlParams.get('id'));
    if (currentCarId) {
        const isFavorite = await checkFavoriteStatus(currentCarId);
        updateFavoriteButton(isFavorite);
        const btn = document.getElementById('favoriteBtn');
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            newBtn.addEventListener('click', () => toggleFavorite(currentCarId));
        }
    }
}

// ========== ВЫБОР ЦВЕТА (кружочки) ==========
function setupColorPicker(car) {
    const container = document.getElementById('colorButtonsList');
    if (!container) return;

    const colorImages = car.color_images;
    if (!colorImages || Object.keys(colorImages).length === 0) {
        container.style.display = 'none';
        const wrapper = document.querySelector('.color-picker-wrapper');
        if (wrapper) wrapper.style.display = 'none';
        return;
    }

    container.style.display = 'flex';
    const wrapper = document.querySelector('.color-picker-wrapper');
    if (wrapper) wrapper.style.display = 'flex';
    container.innerHTML = '';

    const bannerImg = document.querySelector('.Toyota_Camry-banner-img');
    if (!bannerImg) return;

    function changeColor(colorName, imageUrl) {
        bannerImg.style.transition = 'opacity 0.25s ease-in-out';
        bannerImg.style.opacity = '0.4';
        setTimeout(() => {
            bannerImg.src = imageUrl;
            bannerImg.onload = () => {
                bannerImg.style.opacity = '1';
                setTimeout(() => {
                    bannerImg.style.transition = '';
                }, 250);
            };
            bannerImg.onerror = () => {
                bannerImg.style.opacity = '1';
                bannerImg.style.transition = '';
            };
        }, 150);
        updateActiveButton(colorName);
    }

    function updateActiveButton(activeColorName) {
        document.querySelectorAll('.color-btn').forEach(btn => {
            if (btn.dataset.color === activeColorName) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    for (const [colorName, imageUrl] of Object.entries(colorImages)) {
        const btn = document.createElement('button');
        btn.classList.add('color-btn');
        btn.dataset.color = colorName;
        btn.title = colorName;
        const hex = colorMap[colorName] || '#CCCCCC';
        btn.style.cssText = `
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: ${hex};
            border: 2px solid #ddd;
            cursor: pointer;
            transition: transform 0.1s, border 0.1s;
            margin: 4px;
        `;
        btn.addEventListener('click', () => changeColor(colorName, imageUrl));
        container.appendChild(btn);
    }

    const firstColor = Object.keys(colorImages)[0];
    updateActiveButton(firstColor);
    if (bannerImg.src !== colorImages[firstColor]) {
        bannerImg.src = colorImages[firstColor];
    }
}

// ========== ЗАПУСК ==========
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('resize', () => requestAnimationFrame(alignColumnHeights));
    loadCarDetails();
    setTimeout(setupFavorites, 500);
});