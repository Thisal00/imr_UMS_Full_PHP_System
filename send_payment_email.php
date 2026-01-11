<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

function sendPaymentEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

   try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // use your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username = "shimalrashmitha@gmail.com"; 
        $mail->Password = "ikbp jpyp znng wzck ";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('thisalchathnuka80@gmail.com', 'UMS System');
        $mail->addAddress($to);

        // TO
        $mail->addAddress($to);

        // CONTENT
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($body);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
