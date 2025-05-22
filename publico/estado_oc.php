
<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'GESTOR') {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

$usuario_id = $_SESSION['usuario_id'];

// Leer filtros del formulario
$f_fecha_ini = isset($_GET['fecha_ini']) ? $_GET['fecha_ini'] : '';
$f_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Construir consulta dinámica
$where = ["oc.usuario_creador_id = ?"];
$params = [$usuario_id];

if ($f_fecha_ini !== '') {
    $where[] = 'oc.fecha_creacion >= ?';
    $params[] = $f_fecha_ini . ' 00:00:00';
}
if ($f_fecha_fin !== '') {
    $where[] = 'oc.fecha_creacion <= ?';
    $params[] = $f_fecha_fin . ' 23:59:59';
}

$sql = "SELECT oc.*, a.nombre AS area_nombre
    FROM orden_compra oc
    JOIN area a ON oc.area_id = a.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY oc.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ocs = $stmt->fetchAll();
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
        </ul>
        <h3>Estado de mis Órdenes de Compra</h3>
        <form method="get" style="margin-bottom:20px;">
            <label>Fecha inicio:
                <input type="date" name="fecha_ini" value="<?= htmlspecialchars($f_fecha_ini) ?>">
            </label>
            <label>Fecha fin:
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($f_fecha_fin) ?>">
            </label>
            <button type="submit">Filtrar</button>
            <a href="dashboard_gestor.php" style="margin-left:10px;">Limpiar</a>
        </form>
        <?php if (count($ocs) === 0): ?>
            <p>No has registrado órdenes de compra en este periodo.</p>
        <?php else: ?>
            <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
                <tr>
                    <th>No. O.C.</th>
                    <th>Proveedor</th>
                    <th>No. Factura</th>
                    <th>Área</th>
                    <th>Estado actual</th>
                    <th>Fecha de creación</th>
                    <th>Acción</th>
                </tr>
                <?php foreach ($ocs as $oc): ?>
                <tr>
                    <td><?= htmlspecialchars($oc['no_oc']) ?></td>
                    <td><?= htmlspecialchars($oc['proveedor']) ?></td>
                    <td><?= htmlspecialchars($oc['no_factura']) ?></td>
                    <td><?= htmlspecialchars($oc['area_nombre']) ?></td>
                    <td><?= htmlspecialchars($oc['estado_actual']) ?></td>
                    <td><?= htmlspecialchars($oc['fecha_creacion']) ?></td>
                    <td>
                        <a href="historico_oc_detalle.php?id=<?= $oc['id'] ?>">Ver detalle</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <p><a href="logout.php">Cerrar sesión</a></p>
    </div>
</body>
</html>
