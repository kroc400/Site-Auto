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
    } else {
      showError(car?.error || 'Автомобиль не найден');
    }
  } catch (error) {
    console.error('Ошибка:', error);
    showError('Ошибка загрузки данных: ' + error.message);
  }
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
  document.getElementById('car-price').textContent = `от ${car.price}`;
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
  document.getElementById('car-title').textContent = 'Ошибка';
  document.getElementById('car-price').textContent = message;
  document.getElementById('car-price').style.color = 'red';

  // // Скрываем блоки с размерами, если есть ошибка
  // const dimensionContainers = [
  //   '.car-side-view',
  //   '.car-back-view',
  //   '.car-front-view',
  //   '.car-height-view'
  // ];

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

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
  // Добавляем обработчик изменения размера окна для перевыравнивания колонок
  window.addEventListener('resize', function() {
    requestAnimationFrame(() => {
      alignColumnHeights();
    });
  });


// Переменная для хранения текущего ID автомобиля и статуса избранного
let currentCarId = null;
let favoriteStatus = false;

// Функция проверки авторизации
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

// Функция проверки, есть ли авто в избранном
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

// Функция обновления кнопки
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

// Функция добавления/удаления из избранного
async function toggleFavorite(carId) {
    const messageDiv = document.getElementById('favoriteMessage');
    
    // Проверяем авторизацию
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

// Модифицируем существующую функцию loadCarDetails
// (нужно добавить сохранение ID и проверку избранного)
const originalLoadCarDetails = loadCarDetails;
window.loadCarDetails = async function() {
    await originalLoadCarDetails();
    
    // Получаем ID из URL
    const urlParams = new URLSearchParams(window.location.search);
    currentCarId = parseInt(urlParams.get('id'));
    
    if (currentCarId) {
        // Проверяем, в избранном ли этот автомобиль
        const isFavorite = await checkFavoriteStatus(currentCarId);
        updateFavoriteButton(isFavorite);
        
        // Назначаем обработчик кнопки
        const btn = document.getElementById('favoriteBtn');
        if (btn) {
            btn.onclick = () => toggleFavorite(currentCarId);
        }
    }
};

// Переопределяем loadCarDetails
loadCarDetails = window.loadCarDetails;







  // Загружаем данные автомобиля
  loadCarDetails();
});
