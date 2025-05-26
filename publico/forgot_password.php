<?php
session_start();
require_once '../config/db.php';
require_once '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- FUNCIONES AUXILIARES ---
function buscarUsuarioPorCorreo($correo) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, correo FROM users WHERE correo = ?");
    $stmt->execute([$correo]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function generarTokenSeguro($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

function guardarTokenRecuperacion($usuario_id, $token) {
    global $pdo;
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $pdo->prepare("DELETE FROM password_resets WHERE usuario_id = ?")->execute([$usuario_id]);
    // Inserta nuevo token
    $stmt = $pdo->prepare("INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $token, $expires_at]);
}

function enviarCorreoRecuperacion($correo, $token) {
    $enlace = "https://fds.com.co/oc/publico/reset_password.php?token=" . urlencode($token);
    $asunto = "Recupera tu contraseña";
    $mensaje = "Hola,<br><br>Para restablecer tu contraseña haz clic en el siguiente enlace:<br><a href=\"$enlace\">$enlace</a><br><br>Si no solicitaste este cambio, ignora este correo.";

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'mail.fds.com.co'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'notificaciones@fds.com.co'; 
        $mail->Password = 'S1ST3NFDS-'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port = 465; 

        // Remitente y destinatario
        $mail->setFrom('notificaciones@fds.com.co', 'Gestión O.C.');
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $mensaje));

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo: {$mail->ErrorInfo}");
        return false;
    }
}

// --- PROCESAMIENTO DEL FORMULARIO ---

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');

    if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $usuario = buscarUsuarioPorCorreo($correo);
        if ($usuario) {
            $token = generarTokenSeguro();
            guardarTokenRecuperacion($usuario['id'], $token);
            enviarCorreoRecuperacion($usuario['correo'], $token);
        }
        $mensaje = 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';
    } else {
        $mensaje = 'Por favor, ingresa un correo electrónico válido.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña - Gestión O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <img src="img/FDS_Logo.webp">
        <h2>¿Olvidaste tu contraseña?</h2>
        <p>Ingresa tu correo electrónico y te enviaremos instrucciones para restablecer tu contraseña.</p>
        <?php if ($mensaje): ?>
            <p style="color:green;"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>
        <form action="forgot_password.php" method="post">
            <label for="correo">Correo electrónico:</label>
            <input type="email" id="correo" name="correo" required>
            <button type="submit">Enviar instrucciones</button>
        </form>
        <div style="margin-top:15px;">
            <a href="index.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>