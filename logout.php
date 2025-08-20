<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET online = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Eliminar sesión
session_unset();
session_destroy();

// Redirigir al inicio
header("Location: index.php");
exit();
