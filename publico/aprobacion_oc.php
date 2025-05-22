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
        JOIN users u ON oc.usuario_creador_id = u.id
        WHERE oc.area_id = (SELECT area_id FROM users WHERE id = ?) AND oc.estado_actual = 'PENDIENTE'
        ORDER BY oc.fecha_creacion DESC");
    $stmt->execute([$usuario_id]);
} else {
    // APROBADOR_GENERAL: O.C. en estado LIBERADO POR APROBADOR DE AREA
    $stmt = $pdo->prepare("SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido
        FROM orden_compra oc
        JOIN users u ON oc.usuario_creador_id = u.id
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobación de O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Órdenes de Compra Pendientes de Aprobación</h2>
        <?php if (count($ocs) === 0): ?>
            <p class="info-message">No hay órdenes de compra pendientes.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. O.C.</th>
                            <th>Proveedor</th>
                            <th>No. Factura</th>
                            <th>Fecha</th>
                            <th>Creador</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocs as $oc): ?>
                        <tr>
                            <td data-label="No. O.C."><?= htmlspecialchars($oc['no_oc']) ?></td>
                            <td data-label="Proveedor"><?= htmlspecialchars($oc['proveedor']) ?></td>
                            <td data-label="No. Factura"><?= htmlspecialchars($oc['no_factura']) ?></td>
                            <td data-label="Fecha"><?= htmlspecialchars($oc['fecha_creacion']) ?></td>
                            <td data-label="Creador"><?= htmlspecialchars($oc['creador_nombre'] . ' ' . $oc['creador_apellido']) ?></td>
                            <td data-label="Acción">
                                <a href="aprobar_oc_detalle.php?id=<?= $oc['id'] ?>" class="action-button">Ver / Aprobar / Rechazar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="navigation-link">
            <a href="<?= $rol === 'APROBADOR_AREA' ? 'dashboard_aprobador_area.php' : 'dashboard_aprobador_general.php' ?>" class="back-button">
                Volver al dashboard
            </a>
        </p>
    </div>
</body>
</html>

