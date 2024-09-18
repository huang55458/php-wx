<?php

namespace app\utils;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use think\facade\Log;

class SendEmail
{
    public function run($subject = '', $body = 'empty'): void
    {
        if (empty($subject)) {
            return;
        }
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
            //            $mail->Debugoutput = static function ($v) {
            //                dump($v);
            //            };
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host = env('MAIL_HOST', '');                     //Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   //Enable SMTP authentication
            $mail->Username = env('MAIL_USER', '');                     //SMTP username
            $mail->Password = env('MAIL_PASS', '');                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom(env('MAIL_USER', ''), 'test');
            //            $mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
            $mail->addAddress(env('MAIL_USER', ''));               //Name is optional
            //            $mail->addReplyTo('info@example.com', 'Information');
            //            $mail->addCC('cc@example.com');
            //            $mail->addBCC('bcc@example.com');

            //Attachments
            //            $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
            //            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML();                                  //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;
            //            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            Log::write('Message has been sent');
        } catch (Exception $e) {
            Log::write("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }
}
