<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id_oc = $_POST['role_id_oc'] ?? '';
    $area_id = $_POST['area_id'] ?? '';

    // Validaciones básicas
    if (!$nombre || !$apellido || !$correo || !$password || !$role_id_oc || !$area_id) {
        header('Location: register.php?error=Todos los campos son obligatorios');
        exit;
    }

    // Validar correo único
    $stmt = $pdo->prepare("SELECT id FROM users WHERE correo = ? AND (username IS NULL OR username = '')");
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=El correo ya está registrado');
        exit;
    }

    // Encriptar contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $pdo->prepare("INSERT INTO users (nombre, apellido, correo, password, role_id_oc, area_id) VALUES (?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$nombre, $apellido, $correo, $hash, $role_id_oc, $area_id]);
        header('Location: register.php?success=1');
        exit;
    } catch (Exception $e) {
        die("Error al registrar usuario: " . $e->getMessage());
        exit;
    }
} else {
    header('Location: register.php');
    exit;
}
?>