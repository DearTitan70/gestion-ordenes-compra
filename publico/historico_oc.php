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
$f_no_oc = isset($_GET['no_oc']) ? trim($_GET['no_oc']) : '';
$f_no_factura = isset($_GET['no_factura']) ? trim($_GET['no_factura']) : '';

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
if ($f_no_oc !== '') {
    $where[] = 'oc.no_oc LIKE ?';
    $params[] = '%' . $f_no_oc . '%';
}
if ($f_no_factura !== '') {
    $where[] = 'oc.no_factura LIKE ?';
    $params[] = '%' . $f_no_factura . '%';
}

$sql = "SELECT oc.*, u.nombre AS creador_nombre, u.apellido AS creador_apellido, a.nombre AS area_nombre
    FROM orden_compra oc
    JOIN users u ON oc.usuario_creador_id = u.id
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Órdenes de Compra</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Histórico de Órdenes de Compra</h2>
        
        <div class="filter-container">
            <form method="get" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="area_id">Área:</label>
                        <select id="area_id" name="area_id" class="filter-select">
                            <option value="">Todas</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id'] ?>" <?= $f_area == $area['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($area['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="estado_actual">Estado:</label>
                        <select id="estado_actual" name="estado_actual" class="filter-select">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= htmlspecialchars($estado['estado_actual']) ?>" <?= $f_estado == $estado['estado_actual'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['estado_actual']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="fecha_ini">Fecha inicio:</label>
                        <input type="date" id="fecha_ini" name="fecha_ini" class="date-input" value="<?= htmlspecialchars($f_fecha_ini) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="fecha_fin">Fecha fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" class="date-input" value="<?= htmlspecialchars($f_fecha_fin) ?>">
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="no_oc">No. O.C.:</label>
                        <input type="text" id="no_oc" name="no_oc" class="text-input" value="<?= htmlspecialchars($f_no_oc) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="no_factura">No. Factura:</label>
                        <input type="text" id="no_factura" name="no_factura" class="text-input" value="<?= htmlspecialchars($f_no_factura) ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="filter-button">
                        <span class="button-icon">🔍</span> Filtrar
                    </button>
                    <a href="historico_oc.php" class="clear-button">
                        <span class="button-icon">↺</span> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <?php if (count($ocs) === 0): ?>
            <p class="info-message">No hay órdenes de compra registradas.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
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
                    </thead>
                    <tbody>
                        <?php foreach ($ocs as $oc): ?>
                        <tr>
                            <td data-label="No. O.C."><?= htmlspecialchars($oc['no_oc']) ?></td>
                            <td data-label="Proveedor"><?= htmlspecialchars($oc['proveedor']) ?></td>
                            <td data-label="No. Factura"><?= htmlspecialchars($oc['no_factura']) ?></td>
                            <td data-label="Área"><?= htmlspecialchars($oc['area_nombre']) ?></td>
                            <td data-label="Estado"><?= htmlspecialchars($oc['estado_actual']) ?></td>
                            <td data-label="Fecha"><?= htmlspecialchars($oc['fecha_creacion']) ?></td>
                            <td data-label="Creador"><?= htmlspecialchars($oc['creador_nombre'] . ' ' . $oc['creador_apellido']) ?></td>
                            <td data-label="Acción">
                                <a href="historico_oc_detalle.php?id=<?= $oc['id'] ?>" class="action-button">Ver detalle</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
        <p class="navigation-link">
            <a href="<?= $dashboard ?>" class="back-button">Volver al dashboard</a>
        </p>
    </div>
</body>
</html>
