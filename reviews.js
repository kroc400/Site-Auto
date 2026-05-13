// ========== ЗАГРУЗКА ОТЗЫВОВ ==========
async function loadReviewsFromDB() {
    const container = document.getElementById('reviewsGrid');
    if (!container) return;
    
    try {
        const response = await fetch('/api/get_reviews.php');
        const reviews = await response.json();
        
        if (reviews.error) {
            container.innerHTML = `<div class="error-message">${reviews.error}</div>`;
            return;
        }
        
        if (!reviews.length) {
            container.innerHTML = '<div class="no-reviews">Пока нет отзывов.</div>';
            return;
        }
        
        container.innerHTML = reviews.map(review => `
            <div class="review-card">
                <div class="review-header">
                    <strong>${escapeHtml(review.user_name)}</strong>
                    <span class="review-rating">Оценка: ${review.rating}/10</span>
                    
                </div>
                <div class="review-date">${review.date}</div>
                <div class="review-comment">${escapeHtml(review.comment)}</div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Ошибка:', error);
        container.innerHTML = '<div class="error-message">Ошибка загрузки отзывов</div>';
    }
}

// ========== ЗАЩИТА ОТ XSS ==========
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ========== ПРОВЕРКА АВТОРИЗАЦИИ И ПОКАЗ ФОРМЫ ==========
async function checkAuthAndShowForm() {
    try {
        const response = await fetch('/api/check_session.php');
        const data = await response.json();
        
        const formContainer = document.getElementById('reviewFormContainer');
        if (formContainer) {
            if (data.logged_in) {
                formContainer.style.display = 'block';
            } else {
                formContainer.style.display = 'none';
                // Удаляем старое сообщение, если есть
                const oldWarning = document.querySelector('.auth-warning');
                if (oldWarning) oldWarning.remove();
                // Добавляем новое сообщение
                const message = document.createElement('div');
                message.className = 'auth-warning';
                message.innerHTML = 'Чтобы оставить отзыв, <a href="login.html">войдите в аккаунт</a>';
                formContainer.parentNode.insertBefore(message, formContainer);
            }
        }
    } catch (error) {
        console.error('Ошибка проверки авторизации:', error);
    }
}

// ========== ОТПРАВКА ОТЗЫВА ==========
async function submitReview() {
    const ratingInput = document.getElementById('reviewRating');
    const commentTextarea = document.getElementById('reviewComment');
    const messageDiv = document.getElementById('reviewMessage');
    const submitBtn = document.getElementById('submitReviewBtn');
    
    const rating = parseInt(ratingInput?.value || '0');
    const comment = commentTextarea?.value.trim() || '';
    
    // Валидация
    if (rating < 1 || rating > 10) {
        if (messageDiv) {
            messageDiv.textContent = 'Оценка должна быть от 1 до 10';
            messageDiv.style.color = 'red';
        }
        return;
    }
    
    if (comment.length < 1) {
        if (messageDiv) {
            messageDiv.textContent = 'Комментарий должен содержать минимум 1 символ';
            messageDiv.style.color = 'red';
        }
        return;
    }
    
    // Блокируем кнопку
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';
    }
    
    try {
        const response = await fetch('/api/add_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ rating, comment })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            if (messageDiv) {
                messageDiv.textContent = result.message;
                messageDiv.style.color = 'green';
            }
            // Очищаем форму
            if (ratingInput) ratingInput.value = '';
            if (commentTextarea) commentTextarea.value = '';
            // Перезагружаем список отзывов
            await loadReviewsFromDB();
        } else {
            if (messageDiv) {
                messageDiv.textContent = result.error || 'Ошибка отправки';
                messageDiv.style.color = 'red';
            }
        }
    } catch (error) {
        console.error('Ошибка:', error);
        if (messageDiv) {
            messageDiv.textContent = 'Ошибка соединения';
            messageDiv.style.color = 'red';
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Отправить отзыв';
        }
        setTimeout(() => {
            if (messageDiv) messageDiv.textContent = '';
        }, 5000);
    }
}

// ========== НАЗНАЧЕНИЕ ОБРАБОТЧИКОВ ==========
document.addEventListener('DOMContentLoaded', () => {
    loadReviewsFromDB();
    checkAuthAndShowForm();
    
    const submitBtn = document.getElementById('submitReviewBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitReview);
    }
});