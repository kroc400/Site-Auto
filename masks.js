document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM загружен');

  const inputs = {
    surname: document.getElementById('surname'),
    name: document.getElementById('name'),
    patronymic: document.getElementById('patronymic'),
    phone: document.getElementById('phone'),
    email: document.getElementById('email')
  };

  const masks = {};

  // Применяем маски только для существующих элементов
  if (inputs.surname) {
    masks.surname = IMask(inputs.surname, { mask: /^[А-Яа-яЁё\s]*$/ });
  }
  if (inputs.name) {
    masks.name = IMask(inputs.name, { mask: /^[А-Яа-яЁё\s]*$/ });
  }
  if (inputs.patronymic) {
    masks.patronymic = IMask(inputs.patronymic, { mask: /^[А-Яа-яЁё\s]*$/ });
  }
  if (inputs.phone) {
    masks.phone = IMask(inputs.phone, { mask: '+7 (000) 000-00-00' });
    inputs.phone.placeholder = '+7 (___) ___-__-__';
  }


//   НЕ РАБОТАЕТ

  // РАБОЧАЯ маска для email — только IMask, без дополнительных обработчиков
  if (inputs.email) {
    masks.email = IMask(inputs.email, {
      mask: Function,
      prepare: String.prototype.toLowerCase,
      validate: function(value) {
        // Упрощённое регулярное выражение — надёжнее и проще
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
      }
    });

    // Добавляем обработчик для очистки при вставке (paste)
    inputs.email.addEventListener('paste', function(e) {
      setTimeout(() => {
        const cleanedValue = this.value.replace(/[^a-zA-Z0-9._@-]/g, '');
        if (cleanedValue !== this.value) {
          this.value = cleanedValue;
          masks.email.updateValue(); // Синхронизируем маску
        }
      }, 0);
    });
  }

  if (inputs.email && masks.email) {
  inputs.email.addEventListener('input', function() {
    if (masks.email.isValid) {
      this.classList.remove('invalid');
      this.classList.add('valid');
    } else {
      this.classList.remove('valid');
      this.classList.add('invalid');
    }
  });
}


  console.log('Маски применены для доступных полей');
});
