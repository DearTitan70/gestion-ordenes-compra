<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $rol_id = $_POST['rol_id'] ?? '';
    $area_id = $_POST['area_id'] ?? '';

    // Validaciones básicas
    if (!$nombre || !$apellido || !$correo || !$contrasena || !$rol_id || !$area_id) {
        header('Location: register.php?error=Todos los campos son obligatorios');
        exit;
    }

    // Validar correo único
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE correo = ?");
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=El correo ya está registrado');
        exit;
    }

    // Encriptar contraseña
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $pdo->prepare("INSERT INTO usuario (nombre, apellido, correo, contrasena, rol_id, area_id) VALUES (?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$nombre, $apellido, $correo, $hash, $rol_id, $area_id]);
        header('Location: register.php?success=1');
        exit;
    } catch (Exception $e) {
        header('Location: register.php?error=Error al registrar usuario');
        exit;
    }
} else {
    header('Location: register.php');
    exit;
}
?>