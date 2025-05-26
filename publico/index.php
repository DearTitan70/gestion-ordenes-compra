
<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    // Redirigir al dashboard correspondiente si ya está logueado
    switch ($_SESSION['rol']) {
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
            // Si el rol no es reconocido, destruir sesión
            session_destroy();
            header('Location: index.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestión O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <img src="img/FDS_Logo.webp">
        <h2>Iniciar Sesión</h2>
        <h3>Sistema de Gestion de O.C.</h3>
        <?php
        if (isset($_GET['error'])) {
            echo '<p style="color:red;">'.htmlspecialchars($_GET['error']).'</p>';
        }
        if (isset($_GET['success'])) {
            echo '<p style="color:green;">'.htmlspecialchars($_GET['success']).'</p>';
        }
        ?>
        <form action="login_process.php" method="post">
            <label for="correo">Correo electrónico:</label>
            <input type="email" id="correo" name="correo" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Ingresar</button>
            <div style="margin-top:10px;">
                <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
        <p style="margin-top:15px;">
            ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
        </p>
    </div>
</body>
</html>
