<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['GESTOR', 'APROBADOR_AREA', 'APROBADOR_GENERAL'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor = trim($_POST['proveedor'] ?? '');
    $no_factura = trim($_POST['no_factura'] ?? '');
    $no_oc = trim($_POST['no_oc'] ?? '');

    if (!$proveedor || !$no_factura || !$no_oc) {
        header('Location: registrar_oc.php?error=Todos los campos son obligatorios');
        exit;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $area_id = null;

    // Obtener el área del usuario
    $stmt = $pdo->prepare("SELECT area_id FROM usuario WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $row = $stmt->fetch();
    if ($row) {
        $area_id = $row['area_id'];
    } else {
        header('Location: registrar_oc.php?error=Error al obtener el área del usuario');
        exit;
    }

    // Insertar la orden de compra
    $stmt = $pdo->prepare("INSERT INTO orden_compra (fecha_creacion, usuario_creador_id, proveedor, no_factura, no_oc, area_id, estado_actual) VALUES (NOW(), ?, ?, ?, ?, ?, 'PENDIENTE')");
    try {
        $stmt->execute([$usuario_id, $proveedor, $no_factura, $no_oc, $area_id]);
        $oc_id = $pdo->lastInsertId();

        // Registrar el primer estado en la tabla estado_oc
        $stmt_estado = $pdo->prepare("INSERT INTO estado_oc (orden_compra_id, estado, fecha, usuario_id, comentario) VALUES (?, 'PENDIENTE', NOW(), ?, NULL)");
        $stmt_estado->execute([$oc_id, $usuario_id]);

        // Buscar el aprobador de área para notificar (primer usuario con rol APROBADOR_AREA y misma área)
        $stmt_aprobador = $pdo->prepare("SELECT correo, nombre FROM usuario WHERE rol_id = (SELECT id FROM rol WHERE nombre = 'APROBADOR_AREA') AND area_id = ? LIMIT 1");
        $stmt_aprobador->execute([$area_id]);
        $aprobador = $stmt_aprobador->fetch();

        if ($aprobador) {
            // Preparar envío de correo (puedes configurar mail() o PHPMailer)
            $to = $aprobador['correo'];
            $subject = "Nueva Orden de Compra pendiente de aprobación";
            $message = "Hola " . $aprobador['nombre'] . ",\n\nTienes una nueva Orden de Compra pendiente de aprobación.\n\nNo. O.C.: $no_oc\nProveedor: $proveedor\n\nPor favor ingresa al sistema para revisarla.";
            // mail($to, $subject, $message); // Descomenta y configura según tu servidor

            // También puedes guardar la notificación en la tabla notificacion
            $stmt_notif = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES ((SELECT id FROM usuario WHERE correo = ?), ?, ?)");
            $stmt_notif->execute([$to, $message, $oc_id]);
        }

        header('Location: registrar_oc.php?success=1');
        exit;
    } catch (Exception $e) {
        header('Location: registrar_oc.php?error=Error al registrar la Orden de Compra');
        exit;
    }
} else {
    header('Location: registrar_oc.php');
    exit;
}
?>