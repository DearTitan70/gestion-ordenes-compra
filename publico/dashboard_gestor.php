<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'GESTOR') {
    header('Location: index.php');
    exit;
}
mysqli_set_charset($con, "utf8mb4");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gestor</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?> (Gestor)</h2>
        <ul>
            <li><a href="registrar_oc.php">Registrar Orden de Compra</a></li>
            <li><a href="estado_oc.php">Estado de mis O.C.</a></li>
        </ul>
        <p><a href="logout.php">Cerrar sesión</a></p>
    </div>
</body>
</html>