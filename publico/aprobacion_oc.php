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

// Determinar filtro según rol
if ($rol === 'APROBADOR_AREA') {
    // Solo O.C. de su área y estado PENDIENTE
    $stmt = $pdo->prepare("SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido
        FROM orden_compra oc
        JOIN usuario u ON oc.usuario_creador_id = u.id
        WHERE oc.area_id = (SELECT area_id FROM usuario WHERE id = ?) AND oc.estado_actual = 'PENDIENTE'
        ORDER BY oc.fecha_creacion DESC");
    $stmt->execute([$usuario_id]);
} else {
    // APROBADOR_GENERAL: O.C. en estado LIBERADO POR APROBADOR DE AREA
    $stmt = $pdo->prepare("SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido
        FROM orden_compra oc
        JOIN usuario u ON oc.usuario_creador_id = u.id
        WHERE oc.estado_actual = 'LIBERADO POR APROBADOR DE AREA'
        ORDER BY oc.fecha_creacion DESC");
    $stmt->execute();
}
$ocs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aprobación de O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Órdenes de Compra Pendientes de Aprobación</h2>
        <?php if (count($ocs) === 0): ?>
            <p>No hay órdenes de compra pendientes.</p>
        <?php else: ?>
            <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
                <tr>
                    <th>No. O.C.</th>
                    <th>Proveedor</th>
                    <th>No. Factura</th>
                    <th>Fecha</th>
                    <th>Creador</th>
                    <th>Acción</th>
                </tr>
                <?php foreach ($ocs as $oc): ?>
                <tr>
                    <td><?= htmlspecialchars($oc['no_oc']) ?></td>
                    <td><?= htmlspecialchars($oc['proveedor']) ?></td>
                    <td><?= htmlspecialchars($oc['no_factura']) ?></td>
                    <td><?= htmlspecialchars($oc['fecha_creacion']) ?></td>
                    <td><?= htmlspecialchars($oc['creador_nombre'] . ' ' . $oc['creador_apellido']) ?></td>
                    <td>
                        <a href="aprobar_oc_detalle.php?id=<?= $oc['id'] ?>">Ver / Aprobar / Rechazar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <p><a href="<?= $rol === 'APROBADOR_AREA' ? 'dashboard_aprobador_area.php' : 'dashboard_aprobador_general.php' ?>">Volver al dashboard</a></p>
    </div>
</body>
</html>