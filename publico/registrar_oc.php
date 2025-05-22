<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['GESTOR', 'APROBADOR_AREA', 'APROBADOR_GENERAL'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

// Mensajes de éxito o error
$mensaje = '';
if (isset($_GET['success'])) {
    $mensaje = '<p style="color:green;">Orden de Compra registrada correctamente.</p>';
} elseif (isset($_GET['error'])) {
    $mensaje = '<p style="color:red;">'.htmlspecialchars($_GET['error']).'</p>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Orden de Compra</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Registrar Orden de Compra</h2>
        <?= $mensaje ?>
        <form action="registrar_oc_process.php" method="post">
            <label for="proveedor">Proveedor:</label>
            <input type="text" id="proveedor" name="proveedor" required>
            <label for="no_factura">No. Factura:</label>
            <input type="text" id="no_factura" name="no_factura" required>
            <label for="no_oc">No. O.C.:</label>
            <input type="text" id="no_oc" name="no_oc" required>
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion" rows="3" style="width:100%;"></textarea>
            <label>Fecha:</label>
            <input type="text" value="<?= date('Y-m-d H:i') ?>" disabled>
            <label>Usuario:</label>
            <input type="text" value="<?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?>" disabled>
            <label>Área:</label>
            <input type="text" value="<?= htmlspecialchars($_SESSION['area']) ?>" disabled>
            <button type="submit">Registrar</button>
        </form>
        <?php
        $dashboard = '';
        switch ($_SESSION['rol']) {
            case 'GESTOR':
                $dashboard = 'dashboard_gestor.php';
                break;
            case 'APROBADOR_AREA':
                $dashboard = 'dashboard_aprobador_area.php';
                break;
            case 'APROBADOR_GENERAL':
                $dashboard = 'dashboard_aprobador_general.php';
                break;
            default:
                $dashboard = 'index.php';
        }
        ?>
        <p style="margin-top:15px;"><a href="<?= $dashboard ?>">Volver al dashboard</a></p>
    </div>
</body>
</html>