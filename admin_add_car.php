<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}
require_once 'config/database.php';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $price_value = (int)($_POST['price_value'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 1);
    $procent = $_POST['procent'] ?? null;
    
    // Обработка размеров
    $dimensions = [
        'length' => (int)($_POST['length'] ?? 0),
        'width' => (int)($_POST['width'] ?? 0),
        'height' => (int)($_POST['height'] ?? 0),
        'wheelbase' => (int)($_POST['wheelbase'] ?? 0),
        'ground_clearance' => (int)($_POST['ground_clearance'] ?? 0)
    ];
    
    // Преобразование текста комплектации в JSON
    $equipmentText = $_POST['equipment'] ?? '';
    $equipment = [];
    
    if (!empty($equipmentText)) {
        // Разбиваем по строкам
        $lines = explode("\n", $equipmentText);
        $currentCategory = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Проверяем, является ли строка названием категории [category]
            if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
                $currentCategory = trim($matches[1]);
                if (!isset($equipment[$currentCategory])) {
                    $equipment[$currentCategory] = [];
                }
            } elseif ($currentCategory !== null) {
                // Добавляем пункт в текущую категорию
                $equipment[$currentCategory][] = $line;
            }
        }
    }
    
    // Загрузка изображения
    $image_url = null;
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/cars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['car_image']['tmp_name'], $uploadPath)) {
            $image_url = '/uploads/cars/' . $fileName;
        }
    }
    
    // Сохранение в БД
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cars (title, price_value, stock_quantity, procent, equipment, dimensions, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $price_value,
            $stock_quantity,
            $procent,
            json_encode($equipment, JSON_UNESCAPED_UNICODE),
            json_encode($dimensions, JSON_UNESCAPED_UNICODE),
            $image_url
        ]);
        
        $success = "Автомобиль успешно добавлен!";
    } catch (PDOException $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление автомобиля</title>
    <link rel="stylesheet" href="./styles.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #f5f5f5;
            border-radius: 25px;
        }
        .form-container h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            font-family: monospace;
            height: 300px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        button {
            background: #e63946;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background: #c1121f;
        }
        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .preview-img {
            max-width: 200px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <header></header>
    <main class="content">
        <div class="form-container">
            <h1>➕ Добавление автомобиля</h1>
            
            <?php if (isset($success)): ?>
                <div class="message success">✅ <?= $success ?> <a href="admin.php?tab=cars">Вернуться к списку</a></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="message error">❌ <?= $error ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <!-- Основная информация -->
                <div class="form-group">
                    <label>Название автомобиля *</label>
                    <input type="text" name="title" required placeholder="Например: Audi RS7 Sportback">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Цена (число) *</label>
                        <input type="number" name="price_value" required placeholder="4050000">
                    </div>
                    <div class="form-group">
                        <label>Количество в наличии</label>
                        <input type="number" name="stock_quantity" value="1" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Процент / Кредит</label>
                    <input type="text" name="procent" placeholder="Например: Кредит от 31,9%">
                </div>
                
                <!-- Размеры -->
                <div class="form-group">
                    <label>Размеры (в мм)</label>
                    <div class="form-row">
                        <input type="number" name="length" placeholder="Длина">
                        <input type="number" name="width" placeholder="Ширина">
                        <input type="number" name="height" placeholder="Высота">
                    </div>
                    <div class="form-row" style="margin-top: 10px;">
                        <input type="number" name="wheelbase" placeholder="Колёсная база">
                        <input type="number" name="ground_clearance" placeholder="Клиренс">
                    </div>
                </div>
                
                <!-- Комплектация (текстовый редактор) -->
                <div class="form-group">
                    <label>Комплектация</label>
                    <textarea name="equipment" placeholder='[технические характеристики]
Количество мест: 5
Длина x Ширина x Высота: 5009 мм x 2118 мм x 1424 мм
Колёсная база: 2930 мм

[двигатель]
Тип: Гибрид (MHEV), бензиновый
Рабочий объем: 3996 см³ (4.0 л)
Мощность: 630 л.с.

[управление]
Передняя подвеска: Многорычажная независимая
Задняя подвеска: Многорычажная независимая'></textarea>
                    <div class="help-text">
                        📌 <strong>Формат ввода:</strong><br>
                        • Категория пишется в квадратных скобках: <code>[название категории]</code><br>
                        • Пункты категории пишутся каждый с новой строки<br>
                        • Пустая строка разделяет категории
                    </div>
                </div>
                
                <!-- Загрузка изображения -->
                <div class="form-group">
                    <label>Изображение автомобиля</label>
                    <input type="file" name="car_image" accept="image/*" id="carImageInput">
                    <img id="imagePreview" class="preview-img" alt="Preview">
                </div>
                
                <button type="submit">💾 Сохранить автомобиль</button>
            </form>
        </div>
    </main>
    <footer></footer>
    
    <script src="header.js" type="module"></script>
    <script src="footer.js" type="module"></script>
    
    <script>
        // Предпросмотр изображения
        document.getElementById('carImageInput').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(e.target.files[0]);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>