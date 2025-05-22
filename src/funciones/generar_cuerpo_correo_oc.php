
<?php
/**
 * Genera el cuerpo HTML para notificaciones de O.C. compatible con Outlook clásico.
 * 
 * @param array $oc Datos de la orden de compra (id, fecha, monto, proveedor, etc.)
 * @param string $estado Estado actual de la O.C.
 * @param string $mensaje Mensaje principal (aprobación/rechazo)
 * @param string $comentario Comentario del aprobador (si aplica)
 * @param string $nombre_destinatario Nombre del destinatario
 * @param string $rol_destinatario Rol del destinatario
 * @return string HTML listo para enviar por correo
 */
function generarCuerpoCorreoOC($oc, $estado, $mensaje, $comentario, $nombre_destinatario, $rol_destinatario) {
    $logo_url = "https://fds.com.co/oc/publico/img/FDS_Logo.png";
    $color_principal = "#879683";
    $color_secundario = "#F9F3E5";
    $color_estado = $estado === 'FINALIZADO' ? "#28a745" : ($estado === 'RECHAZADO POR APROBADOR DE AREA' || $estado === 'RECHAZADO POR APROBADOR GENERAL' ? "#dc3545" : "#ffc107");

    $oc_id = htmlspecialchars($oc['no_oc']);
    $oc_fecha = htmlspecialchars(date('d/m/Y', strtotime($oc['fecha'] ?? $oc['fecha_creacion'] ?? '')));
    $oc_monto = isset($oc['monto_total']) ? number_format($oc['monto_total'], 2, ',', '.') : 'N/A';
    $oc_proveedor = htmlspecialchars($oc['proveedor'] ?? 'N/A');
    $oc_descripcion = htmlspecialchars($oc['descripcion'] ?? '');
    $oc_area = htmlspecialchars($oc['area_nombre'] ?? '');
    $oc_creador = htmlspecialchars($oc['usuario_creador_nombre'] ?? '');

    ob_start();
    ?>
    <table width="100%" bgcolor="<?=$color_secundario?>" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; padding: 0; margin: 0;">
      <tr>
        <td align="center">
          <table width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="border:1px solid #e0e0e0;">
            <!-- Header con logo -->
            <tr>
              <td align="left" style="padding: 20px 30px 10px 30px; border-bottom: 1px solid #eee;">
                <img src="<?=$logo_url?>" alt="Logo" style="height: 50px; display: block;">
              </td>
            </tr>
            <!-- Título y saludo -->
            <tr>
              <td style="padding: 30px 30px 0 30px;">
                <h2 style="color: <?=$color_principal?>; margin: 0 0 10px 0; font-size: 22px; font-weight: bold;">
                  Notificación de Orden de Compra
                </h2>
                <p style="margin: 0 0 10px 0; font-size: 16px;">
                  Hola <b><?=htmlspecialchars($nombre_destinatario)?></b> (<?=$rol_destinatario?>),
                </p>
                <p style="font-size: 15px; margin: 0 0 20px 0;"><?=$mensaje?></p>
              </td>
            </tr>
            <!-- Tabla de detalles -->
            <tr>
              <td style="padding: 0 30px 0 30px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0 20px 0;">
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px; width: 160px;">N° O.C.:</td>
                    <td style="padding: 8px; font-size: 14px;"><b><?=$oc_id?></b></td>
                  </tr>
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px;">Fecha de creación:</td>
                    <td style="padding: 8px; font-size: 14px;"><?=$oc_fecha?></td>
                  </tr>
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px;">Proveedor:</td>
                    <td style="padding: 8px; font-size: 14px;"><?=$oc_proveedor?></td>
                  </tr>
                  <?php if($oc_area): ?>
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px;">Área:</td>
                    <td style="padding: 8px; font-size: 14px;"><?=$oc_area?></td>
                  </tr>
                  <?php endif; ?>
                  <?php if($oc_creador): ?>
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px;">Solicitante:</td>
                    <td style="padding: 8px; font-size: 14px;"><?=$oc_creador?></td>
                  </tr>
                  <?php endif; ?>
                  <?php if($oc_descripcion): ?>
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px;">Descripción:</td>
                    <td style="padding: 8px; font-size: 14px;"><?=$oc_descripcion?></td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                    <td style="padding: 8px; color: #888; font-size: 14px;">Estado actual:</td>
                    <td style="padding: 8px; font-size: 14px; color: #fff; background: <?=$color_estado?>;">
                      <?=$estado?>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <!-- Comentario del aprobador -->
            <?php if($comentario): ?>
            <tr>
              <td style="padding: 0 30px 0 30px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                  <tr>
                    <td style="font-size: 14px; font-weight: bold; padding-bottom: 4px;">Comentario del aprobador:</td>
                  </tr>
                  <tr>
                    <td style="background: #f8f9fa; border-left: 4px solid <?=$color_principal?>; padding: 12px 18px; font-size: 14px;">
                      <?=nl2br(htmlspecialchars($comentario))?>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <?php endif; ?>
            <!-- Pie -->
            <tr>
              <td style="padding: 0 30px 30px 30px;">
                <p style="color: #888; font-size: 13px; margin: 30px 0 0 0;">
                  Para ver más detalles, ingresa al sistema de gestión de O.C.
                </p>
              </td>
            </tr>
            <tr>
              <td bgcolor="<?=$color_principal?>" style="color: #fff; text-align: center; padding: 12px; font-size: 13px;">
                &copy; <?=date('Y')?> Fuera De Serie FDS - Sistema de Gestión de O.C.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <?php
    return ob_get_clean();
}
?>
