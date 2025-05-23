
<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['GESTOR', 'APROBADOR_AREA', 'APROBADOR_GENERAL'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';
require_once '../src/funciones/mail.php'; // Asegúrate de que la ruta sea correcta
require_once '../src/funciones/generar_cuerpo_correo_oc.php'; // NUEVO: incluir generador de HTML

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor = trim($_POST['proveedor'] ?? '');
    $no_factura = trim($_POST['no_factura'] ?? '');
    $no_oc = trim($_POST['no_oc'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!$proveedor || !$no_factura || !$no_oc) {
        header('Location: registrar_oc.php?error=Todos los campos son obligatorios');
        exit;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $area_id = null;

    // Obtener el área del usuario
    $stmt = $pdo->prepare("SELECT area_id, nombre FROM users WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $row = $stmt->fetch();
    if ($row) {
        $area_id = $row['area_id'];
        $usuario_creador_nombre = $row['nombre'];
    } else {
        header('Location: registrar_oc.php?error=Error al obtener el área del usuario');
        exit;
    }

    // Obtener el nombre del área
    $stmt_area = $pdo->prepare("SELECT nombre FROM area WHERE id = ?");
    $stmt_area->execute([$area_id]);
    $row_area = $stmt_area->fetch();
    $area_nombre = $row_area ? $row_area['nombre'] : '';


    // Insertar la orden de compra (ahora con descripción)
    $stmt = $pdo->prepare("INSERT INTO orden_compra (fecha_creacion, usuario_creador_id, proveedor, no_factura, no_oc, descripcion, area_id, estado_actual) VALUES (NOW(), ?, ?, ?, ?, ?, ?, 'PENDIENTE')");
    try {
        $stmt->execute([$usuario_id, $proveedor, $no_factura, $no_oc, $descripcion, $area_id]);
        $oc_id = $pdo->lastInsertId();

        // Registrar el primer estado en la tabla estado_oc
        $stmt_estado = $pdo->prepare("INSERT INTO estado_oc (orden_compra_id, estado, fecha, usuario_id, comentario) VALUES (?, 'PENDIENTE', NOW(), ?, NULL)");
        $stmt_estado->execute([$oc_id, $usuario_id]);

        // Buscar el aprobador de área para notificar
        $stmt_aprobador = $pdo->prepare("SELECT id, correo, nombre FROM users WHERE role_id_oc = (SELECT id FROM roles WHERE name = 'APROBADOR_AREA') AND area_id = ? LIMIT 1");
        $stmt_aprobador->execute([$area_id]);
        $aprobador = $stmt_aprobador->fetch();

        if ($aprobador) {
            // Preparar datos para el correo HTML
            $oc = [
                'id' => $oc_id,
                'fecha' => date('Y-m-d'),
                'proveedor' => $proveedor,
                'no_factura' => $no_factura,
                'no_oc' => $no_oc,
                'descripcion' => $descripcion,
                'area_nombre' => $area_nombre, // <-- Aquí ya llega el nombre
                'usuario_creador_nombre' => $usuario_creador_nombre,
            ];
            $estado = 'PENDIENTE';
            $mensaje = "Tienes una nueva Orden de Compra pendiente de aprobación.";
            $comentario = '';
            $nombre_destinatario = $aprobador['nombre'];
            $rol_destinatario = 'Aprobador de Área';

            // Generar cuerpo HTML
            $cuerpo_html = generarCuerpoCorreoOC($oc, $estado, $mensaje, $comentario, $nombre_destinatario, $rol_destinatario);

            // Enviar correo HTML
            enviarCorreo($aprobador['correo'], "Nueva Orden de Compra pendiente de aprobación", $cuerpo_html, $aprobador['nombre'], true);

            // También guardar la notificación en la tabla notificacion (puede ser texto plano)
            $mensaje_notif = "Tienes una nueva Orden de Compra pendiente de aprobación. No. O.C.: $no_oc, Proveedor: $proveedor";
            if (!empty($descripcion)) {
                $mensaje_notif .= ", Descripción: $descripcion";
            }
            $stmt_notif = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
            $stmt_notif->execute([$aprobador['id'], $mensaje_notif, $oc_id]);
        }

        header('Location: registrar_oc.php?success=1');
        exit;
    } catch (Exception $e) {
        die("Error al registrar O.C.: " . $e->getMessage());
        exit;
    }
} else {
    header('Location: registrar_oc.php');
    exit;
}
?>
