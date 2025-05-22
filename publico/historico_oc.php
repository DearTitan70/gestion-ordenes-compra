<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

// Obtener áreas para el filtro
$areas = $pdo->query("SELECT id, nombre FROM area ORDER BY nombre")->fetchAll();

// Obtener estados distintos para el filtro
$estados = $pdo->query("SELECT DISTINCT estado_actual FROM orden_compra ORDER BY estado_actual")->fetchAll();

// Leer filtros del formulario
$f_area = isset($_GET['area_id']) ? $_GET['area_id'] : '';
$f_estado = isset($_GET['estado_actual']) ? $_GET['estado_actual'] : '';
$f_fecha_ini = isset($_GET['fecha_ini']) ? $_GET['fecha_ini'] : '';
$f_fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Construir consulta dinámica
$where = [];
$params = [];

if ($f_area !== '') {
    $where[] = 'oc.area_id = ?';
    $params[] = $f_area;
}
if ($f_estado !== '') {
    $where[] = 'oc.estado_actual = ?';
    $params[] = $f_estado;
}
if ($f_fecha_ini !== '') {
    $where[] = 'oc.fecha_creacion >= ?';
    $params[] = $f_fecha_ini . ' 00:00:00';
}
if ($f_fecha_fin !== '') {
    $where[] = 'oc.fecha_creacion <= ?';
    $params[] = $f_fecha_fin . ' 23:59:59';
}

$sql = "SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido, a.nombre AS area_nombre
    FROM orden_compra oc
    JOIN usuario u ON oc.usuario_creador_id = u.id
    JOIN area a ON oc.area_id = a.id";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY oc.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ocs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Órdenes de Compra</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Histórico de Órdenes de Compra</h2>
        <form method="get" style="margin-bottom:20px;">
            <label>Área:
                <select name="area_id">
                    <option value="">Todas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['id'] ?>" <?= $f_area == $area['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($area['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Estado:
                <select name="estado_actual">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= htmlspecialchars($estado['estado_actual']) ?>" <?= $f_estado == $estado['estado_actual'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($estado['estado_actual']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Fecha inicio:
                <input type="date" name="fecha_ini" value="<?= htmlspecialchars($f_fecha_ini) ?>">
            </label>
            <label>Fecha fin:
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($f_fecha_fin) ?>">
            </label>
            <button type="submit">Filtrar</button>
            <a href="historico_oc.php" style="margin-left:10px;">Limpiar</a>
        </form>
        <?php if (count($ocs) === 0): ?>
            <p>No hay órdenes de compra registradas.</p>
        <?php else: ?>
            <table border="1" cellpadding="6" style="width:100%;margin-bottom:15px;">
                <tr>
                    <th>No. O.C.</th>
                    <th>Proveedor</th>
                    <th>No. Factura</th>
                    <th>Área</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Creador</th>
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
                    <td><?= htmlspecialchars($oc['creador_nombre'] . ' ' . $oc['creador_apellido']) ?></td>
                    <td>
                        <a href="historico_oc_detalle.php?id=<?= $oc['id'] ?>">Ver detalle</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php
        // Enlace dinámico al dashboard según rol
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
            case 'VISUALIZADOR':
                $dashboard = 'dashboard_visualizador.php';
                break;
            default:
                $dashboard = 'index.php';
        }
        ?>
        <p><a href="<?= $dashboard ?>">Volver al dashboard</a></p>
    </div>
</body>
</html>
