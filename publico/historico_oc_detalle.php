
<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

$oc_id = $_GET['id'] ?? null;
if (!$oc_id) {
    header('Location: historico_oc.php');
    exit;
}

// Traer info principal de la O.C.
$stmt = $pdo->prepare("SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido, a.nombre AS area_nombre
    FROM orden_compra oc
    JOIN usuario u ON oc.usuario_creador_id = u.id
    JOIN area a ON oc.area_id = a.id
    WHERE oc.id = ?");
$stmt->execute([$oc_id]);
$oc = $stmt->fetch();
if (!$oc) {
    header('Location: historico_oc.php');
    exit;
}

// Traer historial de estados
$stmt = $pdo->prepare("SELECT eo.*, u.nombre, u.apellido
    FROM estado_oc eo
    JOIN usuario u ON eo.usuario_id = u.id
    WHERE eo.orden_compra_id = ?
    ORDER BY eo.fecha ASC");
$stmt->execute([$oc_id]);
$estados = $stmt->fetchAll();

// Traer comentarios adicionales
$stmt = $pdo->prepare("SELECT c.*, u.nombre, u.apellido
    FROM comentario c
    JOIN usuario u ON c.usuario_id = u.id
    WHERE c.orden_compra_id = ?
    ORDER BY c.fecha ASC");
$stmt->execute([$oc_id]);
$comentarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de O.C.</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Detalle de Orden de Compra</h2>
        <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
            <tr><th>No. O.C.</th><td><?= htmlspecialchars($oc['no_oc']) ?></td></tr>
            <tr><th>Descripción</th><td><?= htmlspecialchars($oc['descripcion'] ?? 'Sin descripción') ?></td></tr>
            <tr><th>Proveedor</th><td><?= htmlspecialchars($oc['proveedor']) ?></td></tr>
            <tr><th>No. Factura</th><td><?= htmlspecialchars($oc['no_factura']) ?></td></tr>
            <tr><th>Área</th><td><?= htmlspecialchars($oc['area_nombre']) ?></td></tr>
            <tr><th>Estado actual</th><td><?= htmlspecialchars($oc['estado_actual']) ?></td></tr>
            <tr><th>Fecha de creación</th><td><?= htmlspecialchars($oc['fecha_creacion']) ?></td></tr>
            <tr><th>Creador</th><td><?= htmlspecialchars($oc['creador_nombre'] . ' ' . $oc['creador_apellido']) ?></td></tr>
        </table>

        <h3>Historial de Estados</h3>
        <?php if (count($estados) === 0): ?>
            <p>No hay historial de estados.</p>
        <?php else: ?>
            <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
                <tr>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Comentario</th>
                </tr>
                <?php foreach ($estados as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['estado']) ?></td>
                    <td><?= htmlspecialchars($e['fecha']) ?></td>
                    <td><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></td>
                    <td><?= htmlspecialchars($e['comentario']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3>Comentarios Adicionales</h3>
        <?php if (count($comentarios) === 0): ?>
            <p>No hay comentarios adicionales.</p>
        <?php else: ?>
            <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Comentario</th>
                    <th>Etapa</th>
                </tr>
                <?php foreach ($comentarios as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['fecha']) ?></td>
                    <td><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                    <td><?= htmlspecialchars($c['texto']) ?></td>
                    <td><?= htmlspecialchars($c['etapa']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <p><a href="historico_oc.php">Volver al histórico</a></p>
    </div>
</body>
</html>
