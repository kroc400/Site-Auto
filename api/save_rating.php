<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $design = (int)$_POST['design'];
    $usability = (int)$_POST['usability'];
    $information = (int)$_POST['information'];
    $speed = (int)$_POST['speed'];
    $orders = (int)$_POST['orders'];
    $average = (float)$_POST['average_score'];
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            design INT NOT NULL,
            usability INT NOT NULL,
            information INT NOT NULL,
            speed INT NOT NULL,
            orders INT NOT NULL,
            average_score DECIMAL(3,1) NOT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO ratings (design, usability, information, speed, orders, average_score, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$design, $usability, $information, $speed, $orders, $average, $user_id]);
        
        echo "<script>alert('Спасибо за оценку! Средний балл: $average'); window.location.href = '../index.html';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Ошибка: " . $e->getMessage() . "'); window.location.href = '../index.html';</script>";
    }
} else {
    header('Location: ../index.html');
}
?>