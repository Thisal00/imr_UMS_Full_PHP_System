<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . "/vendor/autoload.php";   // Composer autoload

function sendBillEmail($to, $subject, $body, $attachmentPath = null)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // use your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username = "thisalchathnuka80@gmail.com"; 
        $mail->Password = "zcpv fwss ykyv cnod ";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('thisalchathnuka80@gmail.com', 'UMS System');
        $mail->addAddress($to)
        // Attachment
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail->send();

    } catch (Exception $e) {
        error_log("Email Failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>
