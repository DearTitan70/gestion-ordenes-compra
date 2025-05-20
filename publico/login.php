<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Gestión O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php
        if (isset($_GET['error'])) {
            echo '<p style="color:red;">'.htmlspecialchars($_GET['error']).'</p>';
        }
        ?>
        <form action="login_process.php" method="post">
            <label for="correo">Correo electrónico:</label>
            <input type="email" id="correo" name="correo" required>
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>