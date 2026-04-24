async function loadCarDetails() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const carId = parseInt(urlParams.get('id'));

    if (!carId) {
      throw new Error('Не указан ID автомобиля');
    }

    const response = await fetch('cars-data.json');
    if (!response.ok) throw new Error('Ошибка загрузки данных');

    const cars = await response.json();
    const car = cars.find(c => c.id === carId);

    if (car) {
      updateCarDisplay(car);
    } else {
      showError('Автомобиль не найден');
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

  // Загружаем данные автомобиля
  loadCarDetails();
});
