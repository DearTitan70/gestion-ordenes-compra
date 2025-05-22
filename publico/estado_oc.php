
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gestor</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h2>
        <p class="user-role">Gestor</p>
        
        <div class="dashboard-menu">
            <a href="registrar_oc.php" class="menu-item">
                <span class="menu-icon">📝</span>
                <span class="menu-text">Registrar Orden de Compra</span>
            </a>
        </div>
        
        <h3>Estado de mis Órdenes de Compra</h3>
        
        <div class="filter-container">
            <form method="get" class="filter-form">
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
                
                <div class="filter-actions">
                    <button type="submit" class="filter-button">
                        <span class="button-icon">🔍</span> Filtrar
                    </button>
                    <a href="dashboard_gestor.php" class="clear-button">
                        <span class="button-icon">↺</span> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <?php if (count($ocs) === 0): ?>
            <p class="info-message">No has registrado órdenes de compra en este periodo.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. O.C.</th>
                            <th>Proveedor</th>
                            <th>No. Factura</th>
                            <th>Área</th>
                            <th>Estado actual</th>
                            <th>Fecha de creación</th>
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
                            <td data-label="Estado actual"><?= htmlspecialchars($oc['estado_actual']) ?></td>
                            <td data-label="Fecha de creación"><?= htmlspecialchars($oc['fecha_creacion']) ?></td>
                            <td data-label="Acción">
                                <a href="historico_oc_detalle.php?id=<?= $oc['id'] ?>" class="action-button">Ver detalle</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <p class="logout-container">
            <a href="logout.php" class="logout-button">Cerrar sesión</a>
        </p>
    </div>
</body>
</html>