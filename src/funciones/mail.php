
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function enviarCorreo($to, $subject, $body, $toName = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.fds.com.co';
        $mail->SMTPAuth = true;
        $mail->Username = 'notificaciones@fds.com.co';
        $mail->Password = 'S1ST3NFDS-';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // UTF-8 settings
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64'; // Recommended for UTF-8 with PHPMailer

        $mail->setFrom('notificaciones@fds.com.co', 'Sistema de Cargas');
        $mail->addAddress($to, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error enviando correo: {$mail->ErrorInfo}");
        return false;
    }
}
?>
