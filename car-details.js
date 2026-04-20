async function loadCarDetails() {
  try {
    // Получаем ID из URL
    const urlParams = new URLSearchParams(window.location.search);
    const carId = parseInt(urlParams.get('id'));

    if (!carId) {
      throw new Error('Не указан ID автомобиля');
    }

    // Загружаем данные
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
  document.getElementById('car-title').textContent = car.title;
  document.getElementById('car-price').textContent = `от ${car.price}`;
  document.getElementById('car-procent').textContent = `от ${car.procent}`;
  document.title = car.title;
}

function showError(message) {
  document.getElementById('car-title').textContent = 'Ошибка';
  document.getElementById('car-price').textContent = message;
  document.getElementById('car-price').style.color = 'red';
}

// Загружаем данные при загрузке страницы
document.addEventListener('DOMContentLoaded', loadCarDetails);
