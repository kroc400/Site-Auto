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

function updateCarDisplay(car) {
  // Заполняем основные поля
  document.getElementById('car-title').textContent = car.title;
  document.getElementById('car-price').textContent = `от ${car.price}`;
  document.getElementById('car-procent').textContent = `${car.procent}`;
  document.title = car.title;

  // Получаем данные комплектации (если отсутствуют — пустой объект)
  const equipment = car.equipment || {};

  // Получаем контейнер для колонок
  const equipmentGrid = document.querySelector('.equipment-grid');

  // Очищаем контейнер от статического контента
  equipmentGrid.innerHTML = '';

  // Словарь для перевода ключей на русский (можно расширить)
  const categoryNames = {
    'безопасность': 'Безопасность',
    'экстерьер': 'Экстерьер',
    'интерьер': 'Интерьер',
    'опции': 'Опции',
    'мультимедиа': 'Мультимедиа',
    'двигатель': 'Двигатель',
    'трансмиссия': 'Трансмиссия'
  };

  // Флаг: есть ли хотя бы одна непустая категория
  let hasVisibleCategories = false;

  // Перебираем все категории в данных комплектации
  for (const [categoryKey, items] of Object.entries(equipment)) {
    // Проверяем, что категория существует и не пуста
    if (items && Array.isArray(items) && items.length > 0) {
      hasVisibleCategories = true;

      // Создаём колонку для этой категории
      const columnElement = document.createElement('div');
      columnElement.className = 'equipment-column';

      // Заголовок колонки
      const titleElement = document.createElement('h3');
      titleElement.className = 'equipment-title';
      titleElement.textContent = categoryNames[categoryKey] ||
        categoryKey.charAt(0).toUpperCase() + categoryKey.slice(1);

      // Первый hr
      const hrBefore = document.createElement('hr');
      hrBefore.className = 'equipment-hr';

      // Список пунктов
      const listElement = document.createElement('ul');

      // Заполняем список пунктами
      items.forEach(item => {
        const li = document.createElement('li');
        li.textContent = item;
        listElement.appendChild(li);
      });

      // Второй hr
      const hrAfter = document.createElement('hr');
      hrAfter.className = 'equipment-hr';

      // Собираем колонку В ПРАВИЛЬНОМ ПОРЯДКЕ:
      columnElement.appendChild(titleElement);  // 1. Заголовок
      columnElement.appendChild(hrBefore);     // 2. Первый hr
      columnElement.appendChild(listElement);   // 3. Список
      columnElement.appendChild(hrAfter);      // 4. Второй hr

      // Добавляем колонку в контейнер
      equipmentGrid.appendChild(columnElement);
    }
  }

  // Если нет ни одной непустой категории — показываем сообщение
  if (!hasVisibleCategories) {
    const noEquipmentMessage = document.createElement('div');
    noEquipmentMessage.className = 'no-equipment-message';
    noEquipmentMessage.textContent = 'Информация о комплектации отсутствует';
    noEquipmentMessage.style.textAlign = 'center';
    noEquipmentMessage.style.padding = '20px';
    noEquipmentMessage.style.fontSize = '18px';
    equipmentGrid.appendChild(noEquipmentMessage);
  } else {
    // Создаём и настраиваем ResizeObserver
    const resizeObserver = new ResizeObserver(entries => {
      requestAnimationFrame(() => {
        alignColumnHeights();
      });
    });

    // Подключаем наблюдатель к контейнеру
    resizeObserver.observe(equipmentGrid);

    // Первоначальный вызов выравнивания
    alignColumnHeights();
  }
}

// Функция для выравнивания высоты колонок
function alignColumnHeights() {
  const columns = document.querySelectorAll('.equipment-column');
  if (columns.length === 0) return;

  // Сбрасываем высоту списка перед пересчётом
  columns.forEach(col => {
    const list = col.querySelector('ul');
    if (list) list.style.minHeight = 'auto';
  });

  let maxHeight = 0;

  // Находим максимальную высоту содержимого (заголовок + hr + список)
  columns.forEach(col => {
    const title = col.querySelector('.equipment-title');
    const list = col.querySelector('ul');
    const hrBefore = col.querySelector('.equipment-hr:first-of-type');

    if (!title || !list || !hrBefore) return;

    // Высота до конца списка (без второго hr)
    const currentHeight = title.offsetHeight + hrBefore.offsetHeight + list.offsetHeight;
    if (currentHeight > maxHeight) {
      maxHeight = currentHeight;
    }
  });

  // Устанавливаем минимальную высоту списка так, чтобы второй hr был на одном уровне
  columns.forEach(col => {
    const title = col.querySelector('.equipment-title');
    const list = col.querySelector('ul');
    const hrBefore = col.querySelector('.equipment-hr:first-of-type');

    if (!title || !list || !hrBefore) return;

    const targetListHeight = maxHeight - title.offsetHeight - hrBefore.offsetHeight;

    // Устанавливаем минимальную высоту списка (чтобы не сжимался ниже содержимого)
    list.style.minHeight = targetListHeight + 'px';
  });
}

function showError(message) {
  document.getElementById('car-title').textContent = 'Ошибка';
  document.getElementById('car-price').textContent = message;
  document.getElementById('car-price').style.color = 'red';
}

document.addEventListener('DOMContentLoaded', loadCarDetails);
