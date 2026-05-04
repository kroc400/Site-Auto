async function loadCarDetails() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const carId = parseInt(urlParams.get('id'));

    if (!carId) {
      throw new Error('Не указан ID автомобиля');
    }

    // Вместо cars-data.json → api/car.php
    const response = await fetch(`api/car.php?id=${carId}`);
    if (!response.ok) throw new Error('Ошибка загрузки данных');

    const car = await response.json();
    
    if (car && !car.error) {
      updateCarDisplay(car);
      // Сохраняем данные авто для бронирования
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
  // Длина (боковой вид)
  const lengthEl = document.getElementById('dimension-length');
  if (lengthEl && dimensions.length !== undefined) {
    lengthEl.textContent = dimensions.length;
  }

  // Ширина (вид сзади)
  const widthBackEl = document.getElementById('dimension-width-back');
  if (widthBackEl && dimensions.width !== undefined) {
    widthBackEl.textContent = dimensions.width;
  }

  // Ширина (вид спереди)
  const widthFrontEl = document.getElementById('dimension-width-front');
  if (widthFrontEl && dimensions.width !== undefined) {
    widthFrontEl.textContent = dimensions.width;
  }

  // Высота кузова
  const heightEl = document.getElementById('dimension-height');
  if (heightEl && dimensions.height !== undefined) {
    heightEl.textContent = dimensions.height;
  }
}


function updateCarDisplay(car) {
  // #region
  const titleElements = document.querySelectorAll('#car-title');
  const bannerTitle = document.querySelector('.car-title-banner');
  if (bannerTitle) bannerTitle.textContent = car.title;
  const specsTitle = document.getElementById('car-title');
  //#endregion

  document.getElementById('car-title').textContent = car.title;
  document.getElementById('car-price').textContent = `${formatPrice(car.price_value)}`;
  document.getElementById('car-procent').textContent = `${car.procent}`;
  document.title = car.title;

  if (car.dimensions) {
    fillDimensions(car.dimensions);
    fillSpecsTable(car.dimensions);
  } else {
    console.warn('Данные о размерах отсутствуют для автомобиля:', car.title);
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
        titleElement.textContent = categoryNames[categoryKey] ||
          categoryKey.charAt(0).toUpperCase() + categoryKey.slice(1);

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
      const noEquipmentMessage = document.createElement('div');
      noEquipmentMessage.className = 'no-equipment-message';
      noEquipmentMessage.textContent = 'Информация о комплектации отсутствует';
      noEquipmentMessage.style.textAlign = 'center';
      noEquipmentMessage.style.padding = '20px';
      noEquipmentMessage.style.fontSize = '18px';
      equipmentGrid.appendChild(noEquipmentMessage);
    } else {
      const resizeObserver = new ResizeObserver(entries => {
        requestAnimationFrame(() => {
          alignColumnHeights();
        });
      });
      resizeObserver.observe(equipmentGrid);
      alignColumnHeights();
    }
  }
}

function alignColumnHeights() {
  const columns = document.querySelectorAll('.equipment-column');
  if (columns.length === 0) return;

  columns.forEach(col => {
    const list = col.querySelector('ul');
    if (list) list.style.minHeight = 'auto';
  });

  let maxHeight = 0;
  columns.forEach(col => {
    const title = col.querySelector('.equipment-title');
    const list = col.querySelector('ul');
    const hrBefore = col.querySelector('.equipment-hr:first-of-type');
    if (!title || !list || !hrBefore) return;
    const currentHeight = title.offsetHeight + hrBefore.offsetHeight + list.offsetHeight;
    if (currentHeight > maxHeight) maxHeight = currentHeight;
  });

  columns.forEach(col => {
    const title = col.querySelector('.equipment-title');
    const list = col.querySelector('ul');
    const hrBefore = col.querySelector('.equipment-hr:first-of-type');
    if (!title || !list || !hrBefore) return;
    const targetListHeight = maxHeight - title.offsetHeight - hrBefore.offsetHeight;
    list.style.minHeight = targetListHeight + 'px';
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
    const labelElement = row.querySelector('.specs-label');
    const valueElement = row.querySelector('.specs-value');
    if (labelElement && valueElement) {
      const labelText = labelElement.textContent.trim();
      const dimensionField = dimensionMap[labelText];
      if (dimensionField && dimensions[dimensionField] !== undefined) {
        valueElement.textContent = dimensions[dimensionField];
      } else {
        valueElement.textContent = '—';
      }
    }
  });
}

function showError(message) {
  const titleElem = document.getElementById('car-title');
  if (titleElem) titleElem.textContent = 'Ошибка';
  
  const priceElem = document.getElementById('car-price');
  if (priceElem) {
    priceElem.textContent = message;
    priceElem.style.color = 'red';
  }

  // Скрываем блоки с размерами, если есть ошибка
  const dimensionContainers = [
    '.car-side-view',
    '.car-back-view',
    '.car-front-view',
    '.car-height-view'
  ];

  dimensionContainers.forEach(selector => {
    const element = document.querySelector(selector);
    if (element) element.style.display = 'none';
  });

  // Очищаем таблицу характеристик
  document.querySelectorAll('.specs-table .specs-value').forEach(el => {
    el.textContent = '—';
  });

  // Очищаем блок комплектации
  const equipmentGrid = document.querySelector('.equipment-grid');
  if (equipmentGrid) {
    equipmentGrid.innerHTML = '<div class="no-equipment-message" style="text-align:center;padding:20px;font-size:18px;">Не удалось загрузить данные об автомобиле</div>';
  }
}

// ========== ИЗБРАННОЕ ==========
let currentCarId = null;
let favoriteStatus = false;

async function checkAuthStatus() {
    try {
        const response = await fetch('/api/check_session.php');
        const data = await response.json();
        return data.logged_in;
    } catch (error) {
        console.error('Ошибка проверки авторизации:', error);
        return false;
    }
}

async function checkFavoriteStatus(carId) {
    try {
        const response = await fetch('/api/get_favorites.php');
        const favorites = await response.json();
        
        if (favorites.error) return false;
        
        return favorites.some(car => car.id === carId);
    } catch (error) {
        console.error('Ошибка проверки избранного:', error);
        return false;
    }
}

function updateFavoriteButton(isFavorite) {
    const btn = document.getElementById('favoriteBtn');
    if (!btn) return;
    
    if (isFavorite) {
        btn.textContent = '✅ В избранном';
        btn.classList.add('active');
    } else {
        btn.textContent = '❤️ В избранное';
        btn.classList.remove('active');
    }
    favoriteStatus = isFavorite;
}

async function toggleFavorite(carId) {
    const messageDiv = document.getElementById('favoriteMessage');
    
    const isLoggedIn = await checkAuthStatus();
    if (!isLoggedIn) {
        messageDiv.textContent = '⚠️ Войдите в аккаунт, чтобы добавить в избранное';
        messageDiv.className = 'favorite-message error';
        setTimeout(() => {
            messageDiv.textContent = '';
        }, 3000);
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
            messageDiv.textContent = result.message;
            messageDiv.className = 'favorite-message success';
        } else {
            messageDiv.textContent = result.message || 'Ошибка';
            messageDiv.className = 'favorite-message error';
        }
        
        setTimeout(() => {
            messageDiv.textContent = '';
        }, 2000);
    } catch (error) {
        messageDiv.textContent = 'Ошибка соединения';
        messageDiv.className = 'favorite-message error';
        console.error('Ошибка:', error);
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
    
    // Создаём кнопку бронирования, если её ещё нет
    if (!document.getElementById('bookingBtn')) {
        const bannerBody = document.querySelector('.Toyota_Camry_banner-body');
        if (bannerBody) {
            const bookingHTML = `
                <div class="booking-section" style="margin-top: 20px;">
                    <button id="bookingBtn" class="booking-btn">📞 Забронировать</button>
                </div>
                <div id="bookingModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="modal-close">&times;</span>
                        <h3>Бронирование автомобиля</h3>
                        <div id="bookingCarInfo"></div>
                        <input type="text" id="bookingName" placeholder="Ваше имя" autocomplete="name">
                        <input type="tel" id="bookingPhone" placeholder="+7 (XXX) XXX-XX-XX" autocomplete="tel">
                        <button id="submitBookingBtn">Отправить заявку</button>
                        <div id="bookingMessage"></div>
                    </div>
                </div>
            `;
            bannerBody.insertAdjacentHTML('beforeend', bookingHTML);
            
            // Добавляем стили для модального окна, если их нет
            if (!document.getElementById('bookingStyles')) {
                const styles = document.createElement('style');
                styles.id = 'bookingStyles';
                styles.textContent = `
                    .modal {
                        position: fixed;
                        z-index: 1000;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .modal-content {
                        background-color: #fff;
                        padding: 30px;
                        border-radius: 12px;
                        width: 90%;
                        max-width: 400px;
                        position: relative;
                    }
                    .modal-close {
                        position: absolute;
                        top: 10px;
                        right: 15px;
                        font-size: 24px;
                        cursor: pointer;
                    }
                    .modal-content input {
                        width: 100%;
                        padding: 10px;
                        margin: 10px 0;
                        border: 1px solid #ddd;
                        border-radius: 6px;
                        box-sizing: border-box;
                    }
                    .booking-btn {
                        background-color: #e63946;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        font-size: 18px;
                        border-radius: 8px;
                        cursor: pointer;
                        width: 100%;
                    }
                    .booking-btn:hover {
                        background-color: #c1121f;
                    }
                    #submitBookingBtn {
                        background-color: #1a1a1a;
                        color: white;
                        border: none;
                        padding: 12px;
                        border-radius: 6px;
                        cursor: pointer;
                        width: 100%;
                        margin-top: 10px;
                    }
                    #submitBookingBtn:hover {
                        background-color: #e63946;
                    }
                `;
                document.head.appendChild(styles);
            }
            
            initBookingListeners();
        }
    }
}

function initBookingListeners() {
    // Открытие модального окна
    const bookingBtn = document.getElementById('bookingBtn');
    const modal = document.getElementById('bookingModal');
    const closeBtn = document.querySelector('.modal-close');
    
    if (bookingBtn) {
        bookingBtn.addEventListener('click', () => {
            if (currentBookingCar) {
                const carInfoDiv = document.getElementById('bookingCarInfo');
                if (carInfoDiv) {
                    carInfoDiv.innerHTML = `<p><strong>${currentBookingCar.title}</strong><br>Цена: ${currentBookingCar.price}</p>`;
                }
                
                // Подставляем имя, если пользователь авторизован
                fetch('/api/user_profile.php')
                    .then(res => res.json())
                    .then(user => {
                        if (user.name) document.getElementById('bookingName').value = user.name;
                    })
                    .catch(() => {});
            }
            if (modal) modal.style.display = 'flex';
        });
    }
    
    // Закрытие модального окна
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (modal) modal.style.display = 'none';
        });
    }
    
    // Клик вне модального окна
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });
    
    // Отправка заявки
    const submitBtn = document.getElementById('submitBookingBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            const name = document.getElementById('bookingName').value.trim();
            const phone = document.getElementById('bookingPhone').value.trim();
            const messageDiv = document.getElementById('bookingMessage');
            
            if (!name || !phone) {
                messageDiv.textContent = 'Заполните имя и телефон';
                messageDiv.style.color = 'red';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
            
            try {
                const response = await fetch('/api/callback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name,
                        phone: phone,
                        car_id: currentBookingCar.id,
                        car_title: currentBookingCar.title,
                        car_price: currentBookingCar.price
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    messageDiv.textContent = result.message;
                    messageDiv.style.color = 'green';
                    setTimeout(() => {
                        const modal = document.getElementById('bookingModal');
                        if (modal) modal.style.display = 'none';
                        messageDiv.textContent = '';
                    }, 3000);
                } else {
                    messageDiv.textContent = result.error || 'Ошибка отправки';
                    messageDiv.style.color = 'red';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Отправить заявку';
                }
            } catch (error) {
                messageDiv.textContent = 'Ошибка соединения';
                messageDiv.style.color = 'red';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Отправить заявку';
            }
        });
    }
}

// ========== ИНИЦИАЛИЗАЦИЯ ==========
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('resize', function() {
        requestAnimationFrame(() => {
            alignColumnHeights();
        });
    });

    // Загружаем данные автомобиля
    loadCarDetails();
    
    // Настройка избранного после загрузки авто
    const originalLoadComplete = loadCarDetails;
    window.loadCarDetailsWithFav = async function() {
        await originalLoadComplete();
        
        const urlParams = new URLSearchParams(window.location.search);
        currentCarId = parseInt(urlParams.get('id'));
        
        if (currentCarId) {
            const isFavorite = await checkFavoriteStatus(currentCarId);
            updateFavoriteButton(isFavorite);
            
            const btn = document.getElementById('favoriteBtn');
            if (btn) {
                btn.onclick = () => toggleFavorite(currentCarId);
            }
        }
    };
    
    loadCarDetails = window.loadCarDetailsWithFav;
    loadCarDetails();
});