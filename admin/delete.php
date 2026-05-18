<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

$table = $_GET['table'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$allowed_tables = ['cars', 'orders', 'reviews', 'users'];
if (!in_array($table, $allowed_tables) || $id <= 0) {
    die('Неверный запрос');
}

$stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
$stmt->execute([$id]);
header("Location: $table.php");
exit;
?>