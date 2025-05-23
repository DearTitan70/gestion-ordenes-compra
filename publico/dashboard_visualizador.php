<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'VISUALIZADOR') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Visualizador</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?> (Visualizador)</h2>
        <ul>
            <li><a href="historico_oc.php">Histórico de O.C.</a></li>
        </ul>
        <p><a href="logout.php">Cerrar sesión</a></p>
    </div>
</body>
</html>