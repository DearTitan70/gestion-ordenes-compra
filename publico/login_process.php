<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';

    // Buscar usuario por correo
    $stmt = $pdo->prepare('SELECT u.id, u.nombre, u.apellido, u.password, r.name AS rol, a.nombre AS area
                           FROM users u
                           JOIN roles r ON u.role_id_oc = r.id
                           JOIN area a ON u.area_id = a.id
                           WHERE u.correo = ?');
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Login correcto
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['apellido'] = $usuario['apellido'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['area'] = $usuario['area'];

        // Redirigir según el rol
        switch ($usuario['rol']) {
            case 'GESTOR':
                header('Location: dashboard_gestor.php');
                break;
            case 'APROBADOR_AREA':
                header('Location: dashboard_aprobador_area.php');
                break;
            case 'APROBADOR_GENERAL':
                header('Location: dashboard_aprobador_general.php');
                break;
            case 'VISUALIZADOR':
                header('Location: dashboard_visualizador.php');
                break;
            default:
                header('Location: index.php?error=Rol no reconocido');
        }
        exit;
    } else {
        // Login incorrecto
        header('Location: index.php?error=Correo o contraseña incorrectos');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>