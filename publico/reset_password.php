
<?php
session_start();
require_once '../config/db.php';

// --- FUNCIONES AUXILIARES ---

function obtenerResetPorToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT pr.*, u.correo FROM password_resets pr JOIN users u ON pr.usuario_id = u.id WHERE pr.token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function actualizarPassword($usuario_id, $nueva_password) {
    global $pdo;
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $usuario_id]);
}

function eliminarToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
}

// --- LÓGICA PRINCIPAL ---

$token = $_GET['token'] ?? '';
$reset = null;
$error = '';
$success = '';

if ($token) {
    $reset = obtenerResetPorToken($token);
    if (!$reset) {
        $error = 'El enlace no es válido o ya fue utilizado.';
    } elseif (strtotime($reset['expires_at']) < time()) {
        $error = 'El enlace ha expirado. Solicita uno nuevo.';
        eliminarToken($token);
        $reset = null;
    }
} else {
    $error = 'Falta el token de recuperación.';
}

// Procesar el formulario de nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        actualizarPassword($reset['usuario_id'], $password);
        eliminarToken($token);
        $success = '¡Contraseña restablecida correctamente! Ahora puedes <a href="index.php">iniciar sesión</a>.';
        $reset = null;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña - Gestión O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <img src="img/FDS_Logo.webp">
        <h2>Restablecer contraseña</h2>
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif ($success): ?>
            <p style="color:green;"><?php echo $success; ?></p>
        <?php elseif ($reset): ?>
            <form action="reset_password.php?token=<?php echo urlencode($token); ?>" method="post">
                <label for="password">Nueva contraseña:</label>
                <input type="password" id="password" name="password" required minlength="6">
                <label for="password2">Repite la nueva contraseña:</label>
                <input type="password" id="password2" name="password2" required minlength="6">
                <button type="submit">Restablecer contraseña</button>
            </form>
        <?php endif; ?>
        <div style="margin-top:15px;">
            <a href="index.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>
