// ========== ОСНОВНЫЕ ФУНКЦИИ (без изменений) ==========
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
      initBooking(car);
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
        items.forEach(item => { const li = document.createElement('li'); li.textContent = item; listElement.appendChild(li); });
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
      alignColumnHeights();
    }
  }
}

function alignColumnHeights() {
  const columns = document.querySelectorAll('.equipment-column');
  if (!columns.length) return;
  columns.forEach(col => { const list = col.querySelector('ul'); if (list) list.style.minHeight = 'auto'; });
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
  if (priceElem) { priceElem.textContent = message; priceElem.style.color = 'red'; }
  const equipmentGrid = document.querySelector('.equipment-grid');
  if (equipmentGrid) equipmentGrid.innerHTML = '<div class="no-equipment-message">Не удалось загрузить данные</div>';
}

// ========== БРОНИРОВАНИЕ (ТОЛЬКО ДЛЯ CAR_TEMPLATE) ==========
let currentBookingCar = null;

function initBooking(car) {
    currentBookingCar = {
        id: car.id,
        title: car.title,
        price: formatPrice(car.price_value)
    };
    
    // Ищем контейнер для кнопки
    let targetContainer = document.querySelector('.Toyota_Camry_banner-body') || 
                          document.querySelector('.car-gift-container')?.parentElement ||
                          document.querySelector('.banner .Toyota_Camry_banner-body');
    
    if (targetContainer && !document.getElementById('bookingBtn')) {
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'booking-container';
        buttonContainer.style.marginTop = '20px';
        buttonContainer.style.textAlign = 'center';
        buttonContainer.innerHTML = `<button id="bookingBtn" class="booking-btn" style="background-color: #e63946; color: white; border: none; padding: 12px 24px; font-size: 18px; border-radius: 8px; cursor: pointer; width: 100%; max-width: 300px; font-weight: bold;">📞 Забронировать</button>`;
        targetContainer.appendChild(buttonContainer);
        
        if (!document.getElementById('bookingModal')) {
            const modalHTML = `
                <div id="bookingModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
                    <div style="background-color: #fff; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; position: relative; margin: auto;">
                        <span id="bookingModalClose" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer;">&times;</span>
                        <h3 style="margin-top: 0;">Бронирование автомобиля</h3>
                        <div id="bookingCarInfo"></div>
                        <input type="text" id="bookingName" placeholder="Ваше имя" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                        <input type="tel" id="bookingPhone" placeholder="+7 (XXX) XXX-XX-XX" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box;">
                        <button id="submitBookingBtn" style="background-color: #1a1a1a; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 10px;">Отправить заявку</button>
                        <div id="bookingMessage" style="margin-top: 10px; text-align: center;"></div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
        
        // Назначаем обработчики
        const bookingBtn = document.getElementById('bookingBtn');
        const modal = document.getElementById('bookingModal');
        const closeBtn = document.getElementById('bookingModalClose');
        
        if (bookingBtn) {
            bookingBtn.onclick = () => {
                document.getElementById('bookingCarInfo').innerHTML = `<p><strong>${currentBookingCar.title}</strong><br>Цена: ${currentBookingCar.price}</p>`;
                fetch('/api/user_profile.php').then(res => res.json()).then(user => { if (user.name) document.getElementById('bookingName').value = user.name; }).catch(() => {});
                if (modal) modal.style.display = 'flex';
            };
        }
        if (closeBtn) closeBtn.onclick = () => { if (modal) modal.style.display = 'none'; };
        window.onclick = (e) => { if (e.target === modal && modal) modal.style.display = 'none'; };
        
        const submitBtn = document.getElementById('submitBookingBtn');
        if (submitBtn) {
            submitBtn.onclick = async () => {
                const name = document.getElementById('bookingName').value.trim();
                const phone = document.getElementById('bookingPhone').value.trim();
                const messageDiv = document.getElementById('bookingMessage');
                if (!name || !phone) { messageDiv.textContent = 'Заполните имя и телефон'; messageDiv.style.color = 'red'; return; }
                submitBtn.disabled = true; submitBtn.textContent = 'Отправка...';
                try {
                    const response = await fetch('/api/callback.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name, phone, car_id: currentBookingCar.id, car_title: currentBookingCar.title, car_price: currentBookingCar.price })
                    });
                    const result = await response.json();
                    if (response.ok && result.success) {
                        messageDiv.textContent = result.message; messageDiv.style.color = 'green';
                        setTimeout(() => { if (modal) modal.style.display = 'none'; document.getElementById('bookingName').value = ''; document.getElementById('bookingPhone').value = ''; messageDiv.textContent = ''; }, 3000);
                    } else { messageDiv.textContent = result.error || 'Ошибка отправки'; messageDiv.style.color = 'red'; submitBtn.disabled = false; submitBtn.textContent = 'Отправить заявку'; }
                } catch (error) { messageDiv.textContent = 'Ошибка соединения'; messageDiv.style.color = 'red'; submitBtn.disabled = false; submitBtn.textContent = 'Отправить заявку'; }
            };
        }
    }
}

// ========== ИЗБРАННОЕ ==========
let currentCarId = null;

async function checkAuthStatus() {
    try { const response = await fetch('/api/check_session.php'); const data = await response.json(); return data.logged_in; } catch { return false; }
}

async function checkFavoriteStatus(carId) {
    try { const response = await fetch('/api/get_favorites.php'); const favorites = await response.json(); if (favorites.error) return false; return favorites.some(car => car.id === carId); } catch { return false; }
}

function updateFavoriteButton(isFavorite) {
    const btn = document.getElementById('favoriteBtn');
    if (!btn) return;
    if (isFavorite) { btn.textContent = '✅ В избранном'; btn.classList.add('active'); } 
    else { btn.textContent = '❤️ В избранное'; btn.classList.remove('active'); }
}

async function toggleFavorite(carId) {
    const messageDiv = document.getElementById('favoriteMessage');
    const isLoggedIn = await checkAuthStatus();
    if (!isLoggedIn) { messageDiv.textContent = '⚠️ Войдите в аккаунт'; messageDiv.className = 'favorite-message error'; setTimeout(() => { messageDiv.textContent = ''; }, 3000); return; }
    try {
        const response = await fetch('/api/favorites_toggle.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ car_id: carId }) });
        const result = await response.json();
        if (result.success) { updateFavoriteButton(result.action === 'added'); messageDiv.textContent = result.message; messageDiv.className = 'favorite-message success'; } 
        else { messageDiv.textContent = result.message || 'Ошибка'; messageDiv.className = 'favorite-message error'; }
        setTimeout(() => { messageDiv.textContent = ''; }, 2000);
    } catch { messageDiv.textContent = 'Ошибка соединения'; messageDiv.className = 'favorite-message error'; }
}

async function setupFavorites() {
    const urlParams = new URLSearchParams(window.location.search);
    currentCarId = parseInt(urlParams.get('id'));
    if (currentCarId) {
        const isFavorite = await checkFavoriteStatus(currentCarId);
        updateFavoriteButton(isFavorite);
        const btn = document.getElementById('favoriteBtn');
        if (btn) btn.onclick = () => toggleFavorite(currentCarId);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('resize', () => requestAnimationFrame(() => alignColumnHeights()));
    loadCarDetails();
    setTimeout(setupFavorites, 500);
});