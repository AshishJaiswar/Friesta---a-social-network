<?php
require_once('PHPMailer/PHPMailerAutoload.php');
class Mail
{
    public static function sendMail($subject, $body, $address)
    {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'ssl';
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = '465';
        $mail->isHTML();
        $mail->Username = 'friesta.socialnetwork@gmail.com';
        $mail->Password = 'Aj8655021090';
        $mail->Subject = $subject;
        $mail->setFrom('friesta.socialnetwork@gmail.com', 'Ashish Jaiswar');
        $mail->Body = $body;
        $mail->addAddress($address);
        $mail->send();
    }
}
