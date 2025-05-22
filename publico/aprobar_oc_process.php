<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['APROBADOR_AREA', 'APROBADOR_GENERAL'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oc_id = $_POST['oc_id'] ?? null;
    $accion = $_POST['accion'] ?? null;
    $comentario = trim($_POST['comentario'] ?? '');

    if (!$oc_id || !$accion) {
        header("Location: aprobar_oc_detalle.php?id=$oc_id&error=Datos incompletos");
        exit;
    }

    // Obtener datos de la O.C.
    $stmt = $pdo->prepare("SELECT * FROM orden_compra WHERE id = ?");
    $stmt->execute([$oc_id]);
    $oc = $stmt->fetch();
    if (!$oc) {
        header('Location: aprobacion_oc.php');
        exit;
    }

    // Validar estado actual
    if ($rol === 'APROBADOR_AREA' && $oc['estado_actual'] !== 'PENDIENTE') {
        header("Location: aprobar_oc_detalle.php?id=$oc_id&error=La O.C. ya fue procesada");
        exit;
    }
    if ($rol === 'APROBADOR_GENERAL' && $oc['estado_actual'] !== 'LIBERADO POR APROBADOR DE AREA') {
        header("Location: aprobar_oc_detalle.php?id=$oc_id&error=La O.C. ya fue procesada");
        exit;
    }

    // Procesar acción
    if ($accion === 'aprobar') {
        if ($rol === 'APROBADOR_AREA') {
            // Cambiar estado a LIBERADO POR APROBADOR DE AREA
            $nuevo_estado = 'LIBERADO POR APROBADOR DE AREA';
            $mensaje_gestor = "Tu O.C. ha sido liberada por el aprobador de área.";
            $mensaje_aprobador_general = "Tienes una O.C. pendiente de liberación.";
        } else {
            // APROBADOR_GENERAL
            $nuevo_estado = 'FINALIZADO';
            $mensaje_gestor = "Tu O.C. ha sido aprobada y finalizada.";
            $mensaje_aprobador_area = null;
        }
    } elseif ($accion === 'rechazar') {
        if (empty($comentario)) {
            header("Location: aprobar_oc_detalle.php?id=$oc_id&error=Debes ingresar un comentario para rechazar");
            exit;
        }
        if ($rol === 'APROBADOR_AREA') {
            $nuevo_estado = 'RECHAZADO POR APROBADOR DE AREA';
            $mensaje_gestor = "Tu O.C. fue rechazada por el aprobador de área. Motivo: $comentario";
        } else {
            $nuevo_estado = 'RECHAZADO POR APROBADOR GENERAL';
            $mensaje_gestor = "Tu O.C. fue rechazada por el aprobador general. Motivo: $comentario";
            $mensaje_aprobador_area = "La O.C. fue rechazada por el aprobador general. Motivo: $comentario";
        }
    } else {
        header("Location: aprobar_oc_detalle.php?id=$oc_id&error=Acción no válida");
        exit;
    }

    // Actualizar estado en orden_compra
    $stmt = $pdo->prepare("UPDATE orden_compra SET estado_actual = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $oc_id]);

    // Registrar en estado_oc
    $stmt = $pdo->prepare("INSERT INTO estado_oc (orden_compra_id, estado, fecha, usuario_id, comentario) VALUES (?, ?, NOW(), ?, ?)");
    $stmt->execute([$oc_id, $nuevo_estado, $usuario_id, $comentario]);

    // Guardar comentario en tabla comentario si hay texto
    if (!empty($comentario)) {
        $stmt = $pdo->prepare("INSERT INTO comentario (orden_compra_id, usuario_id, fecha, texto, etapa) VALUES (?, ?, NOW(), ?, ?)");
        $stmt->execute([
            $oc_id,
            $usuario_id,
            $comentario,
            $nuevo_estado 
        ]);
    }

    // Notificaciones y correos
    // Obtener gestor (creador)
    $stmt = $pdo->prepare("SELECT u.id, u.correo, u.nombre FROM usuario u WHERE u.id = ?");
    $stmt->execute([$oc['usuario_creador_id']]);
    $gestor = $stmt->fetch();

    // Obtener aprobador de área
    $stmt = $pdo->prepare("SELECT u.id, u.correo, u.nombre FROM usuario u WHERE u.rol_id = (SELECT id FROM rol WHERE nombre = 'APROBADOR_AREA') AND u.area_id = ? LIMIT 1");
    $stmt->execute([$oc['area_id']]);
    $aprobador_area = $stmt->fetch();

    // Obtener aprobador general
    $stmt = $pdo->prepare("SELECT u.id, u.correo, u.nombre FROM usuario u WHERE u.rol_id = (SELECT id FROM rol WHERE nombre = 'APROBADOR_GENERAL') LIMIT 1");
    $stmt->execute();
    $aprobador_general = $stmt->fetch();

    // Notificar según el flujo
    if ($accion === 'aprobar') {
        if ($rol === 'APROBADOR_AREA') {
            // Notificar a aprobador general y gestor
            if ($aprobador_general) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$aprobador_general['id'], $mensaje_aprobador_general, $oc_id]);
                // mail($aprobador_general['correo'], "O.C. pendiente de liberación", $mensaje_aprobador_general);
            }
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                // mail($gestor['correo'], "O.C. liberada", $mensaje_gestor);
            }
        } else {
            // APROBADOR_GENERAL: Notificar solo al gestor
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                // mail($gestor['correo'], "O.C. finalizada", $mensaje_gestor);
            }
        }
    } else {
        // Rechazo
        if ($rol === 'APROBADOR_AREA') {
            // Notificar solo al gestor
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                // mail($gestor['correo'], "O.C. rechazada", $mensaje_gestor);
            }
        } else {
            // APROBADOR_GENERAL: Notificar a gestor y aprobador de área
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                // mail($gestor['correo'], "O.C. rechazada", $mensaje_gestor);
            }
            if ($aprobador_area) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$aprobador_area['id'], $mensaje_aprobador_area, $oc_id]);
                // mail($aprobador_area['correo'], "O.C. rechazada", $mensaje_aprobador_area);
            }
        }
    }

    header('Location: aprobacion_oc.php');
    exit;
} else {
    header('Location: aprobacion_oc.php');
    exit;
}
?>