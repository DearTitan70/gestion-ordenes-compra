<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'APROBADOR_AREA') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Aprobador de Área</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <img src="img/FDS_Logo.webp">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?> (Aprobador de Área)</h2>
        <ul>
            <li><a href="registrar_oc.php">Registrar Orden de Compra</a></li>
            <li><a href="aprobacion_oc.php">Aprobación de O.C.</a></li>
            <li><a href="historico_oc.php">Histórico de O.C.</a></li>
        </ul>
        <p><a href="logout.php">Cerrar sesión</a></p>
    </div>
</body>
</html>