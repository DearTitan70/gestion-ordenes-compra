<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['APROBADOR_AREA', 'APROBADOR_GENERAL'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$area = $_SESSION['area'];
$area_id = null;

// Obtener el id de área del usuario (para validación)
$stmt_area = $pdo->prepare("SELECT area_id FROM usuario WHERE id = ?");
$stmt_area->execute([$usuario_id]);
$row_area = $stmt_area->fetch();
if ($row_area) {
    $area_id = $row_area['area_id'];
}

$oc_id = $_GET['id'] ?? null;
if (!$oc_id) {
    header('Location: aprobacion_oc.php');
    exit;
}

// Obtener datos de la O.C.
$stmt = $pdo->prepare("SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido, a.nombre AS area_nombre
    FROM orden_compra oc
    JOIN usuario u ON oc.usuario_creador_id = u.id
    JOIN area a ON oc.area_id = a.id
    WHERE oc.id = ?");
$stmt->execute([$oc_id]);
$oc = $stmt->fetch();

if (!$oc) {
    header('Location: aprobacion_oc.php');
    exit;
}

// Validar que el aprobador puede ver esta O.C.
if ($rol === 'APROBADOR_AREA' && ($oc['estado_actual'] !== 'PENDIENTE' || $oc['area_id'] != $area_id)) {
    header('Location: aprobacion_oc.php');
    exit;
}
if ($rol === 'APROBADOR_GENERAL' && $oc['estado_actual'] !== 'LIBERADO POR APROBADOR DE AREA') {
    header('Location: aprobacion_oc.php');
    exit;
}

// Mensajes
$mensaje = '';
if (isset($_GET['error'])) {
    $mensaje = '<p style="color:red;">'.htmlspecialchars($_GET['error']).'</p>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle O.C. - Aprobar/Rechazar</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Detalle de Orden de Compra</h2>
        <?= $mensaje ?>
        <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
            <tr><th>No. O.C.</th><td><?= htmlspecialchars($oc['no_oc']) ?></td></tr>
            <tr><th>Proveedor</th><td><?= htmlspecialchars($oc['proveedor']) ?></td></tr>
            <tr><th>No. Factura</th><td><?= htmlspecialchars($oc['no_factura']) ?></td></tr>
            <tr><th>Fecha</th><td><?= htmlspecialchars($oc['fecha_creacion']) ?></td></tr>
            <tr><th>Creador</th><td><?= htmlspecialchars($oc['creador_nombre'] . ' ' . $oc['creador_apellido']) ?></td></tr>
            <tr><th>Área</th><td><?= htmlspecialchars($oc['area_nombre']) ?></td></tr>
        </table>
        <form action="aprobar_oc_process.php" method="post">
            <input type="hidden" name="oc_id" value="<?= $oc['id'] ?>">
            <label>Comentario (obligatorio si rechaza):</label>
            <textarea name="comentario" rows="3" style="width:100%;"></textarea>
            <button type="submit" name="accion" value="aprobar"><?= $rol === 'APROBADOR_AREA' ? 'Liberar' : 'Aprobar' ?></button>
            <button type="submit" name="accion" value="rechazar" style="background:#d9534f;">Rechazar</button>
        </form>
        <p><a href="aprobacion_oc.php">Volver al listado</a></p>
    </div>
</body>
</html>