<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['APROBADOR_AREA', 'APROBADOR_GENERAL'])) {
    header('Location: index.php');
    exit;
}
require_once '../config/db.php';
require_once '../src/funciones/mail.php';
require_once '../src/funciones/generar_cuerpo_correo_oc.php';

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

    // Obtener datos de la O.C. incluyendo el nombre del área y del creador
    $stmt = $pdo->prepare("
        SELECT oc.*, a.nombre AS area_nombre, u.nombre AS usuario_creador_nombre, u.apellido AS usuario_creador_apellido
        FROM orden_compra oc
        JOIN area a ON oc.area_id = a.id
        JOIN users u ON oc.usuario_creador_id = u.id
        WHERE oc.id = ?
    ");
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
    $stmt = $pdo->prepare("SELECT u.id, u.correo, u.nombre FROM users u WHERE u.id = ?");
    $stmt->execute([$oc['usuario_creador_id']]);
    $gestor = $stmt->fetch();

    // Obtener aprobador de área
    $stmt = $pdo->prepare("SELECT u.id, u.correo, u.nombre FROM users u WHERE u.role_id_oc = (SELECT id FROM roles WHERE name = 'APROBADOR_AREA') AND u.area_id = ? LIMIT 1");
    $stmt->execute([$oc['area_id']]);
    $aprobador_area = $stmt->fetch();

    // Obtener aprobador general
    $stmt = $pdo->prepare("SELECT u.id, u.correo, u.nombre FROM users u WHERE u.role_id_oc = (SELECT id FROM roles WHERE name = 'APROBADOR_GENERAL') LIMIT 1");
    $stmt->execute();
    $aprobador_general = $stmt->fetch();

    // Notificar según el flujo
    if ($accion === 'aprobar') {
        if ($rol === 'APROBADOR_AREA') {
            // Notificar a aprobador general y gestor
            if ($aprobador_general) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$aprobador_general['id'], $mensaje_aprobador_general, $oc_id]);
                $cuerpo_html_aprobador_general = generarCuerpoCorreoOC($oc, $nuevo_estado, $mensaje_aprobador_general, $comentario, $aprobador_general['nombre'], 'Aprobador General');
                enviarCorreo($aprobador_general['correo'], "O.C. pendiente de liberación", $cuerpo_html_aprobador_general, $aprobador_general['nombre'], true);
            }
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                $cuerpo_html_gestor = generarCuerpoCorreoOC($oc, $nuevo_estado, $mensaje_gestor, $comentario, $gestor['nombre'], 'Gestor');
                enviarCorreo($gestor['correo'], "O.C. pendiente de liberación", $cuerpo_html_gestor, $gestor['nombre'], true);
            }
        } else {
            // APROBADOR_GENERAL: Notificar solo al gestor
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                $cuerpo_html_gestor = generarCuerpoCorreoOC($oc, $nuevo_estado, $mensaje_gestor, $comentario, $gestor['nombre'], 'Gestor');
                enviarCorreo($gestor['correo'], "O.C. aprobada y finalizada", $cuerpo_html_gestor, $gestor['nombre'], true);
            }
        }
    } else {
        // Rechazo
        if ($rol === 'APROBADOR_AREA') {
            // Notificar solo al gestor
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                $cuerpo_html_gestor = generarCuerpoCorreoOC($oc, $nuevo_estado, $mensaje_gestor, $comentario, $gestor['nombre'], 'Gestor');
                enviarCorreo($gestor['correo'], "O.C. rechazada", $cuerpo_html_gestor, $gestor['nombre'], true);
            }
        } else {
            // APROBADOR_GENERAL: Notificar a gestor y aprobador de área
            if ($gestor) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$gestor['id'], $mensaje_gestor, $oc_id]);
                $cuerpo_html_gestor = generarCuerpoCorreoOC($oc, $nuevo_estado, $mensaje_gestor, $comentario, $gestor['nombre'], 'Gestor');
                enviarCorreo($gestor['correo'], "O.C. rechazada", $cuerpo_html_gestor, $gestor['nombre'], true);
            }
            if ($aprobador_area) {
                $stmt = $pdo->prepare("INSERT INTO notificacion (destinatario_id, mensaje, orden_compra_id) VALUES (?, ?, ?)");
                $stmt->execute([$aprobador_area['id'], $mensaje_aprobador_area, $oc_id]);
                $cuerpo_html_aprobador_area = generarCuerpoCorreoOC($oc, $nuevo_estado, $mensaje_aprobador_area, $comentario, $aprobador_area['nombre'], 'Aprobador de Área');
                enviarCorreo($aprobador_area['correo'], "O.C. rechazada", $cuerpo_html_aprobador_area, $aprobador_area['nombre'], true);
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