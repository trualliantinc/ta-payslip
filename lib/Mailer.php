<?php
// lib/Mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {

  /**
   * Bootstrap a PHPMailer with SMTP settings.
   * You can optionally override From/Reply-To per send.
   */
  private static function base(
    ?string $fromEmail = null,
    ?string $fromName  = null,
    ?string $replyToEmail = null,
    ?string $replyToName  = null
  ): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // use ENCRYPTION_SMTPS if you’re on 465
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 15;

    // Defaults from config, but allow overrides
    $fromEmail   = $fromEmail   ?: MAIL_FROM;
    $fromName    = $fromName    ?: MAIL_FROM_NAME;
    $replyToEmail = $replyToEmail ?: $fromEmail;
    $replyToName  = $replyToName  ?: $fromName;

    $mail->setFrom($fromEmail, $fromName);
    $mail->addReplyTo($replyToEmail, $replyToName);

    // DEV-ONLY: relax cert checks if your local SMTP uses self-signed certs
    // $mail->SMTPOptions = ['ssl' => [
    //   'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true
    // ]];

    return $mail;
  }

  /**
   * Send HTML email + PDF attachment (payslip).
   * Optionally override From/Reply-To per send.
   */
  public static function sendWithAttachment(
    string $to,
    string $toName,
    string $subject,
    string $bodyHtml,
    string $pdfBytes,
    string $filename = 'payslip.pdf',
    ?string $fromEmail = null,
    ?string $fromName  = null,
    ?string $replyToEmail = null,
    ?string $replyToName  = null
  ): bool {
    try {
      $mail = self::base($fromEmail, $fromName, $replyToEmail, $replyToName);
      $mail->addAddress($to, $toName);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $bodyHtml;

      if ($pdfBytes !== '') {
        $mail->addStringAttachment($pdfBytes, $filename, 'base64', 'application/pdf');
      }

      return $mail->send();
    } catch (Exception $e) {
      error_log('Mailer sendWithAttachment error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Send HTML email (e.g., reset-password link).
   * Optionally override From/Reply-To per send.
   */
  public static function sendHtml(
    string $to,
    string $toName,
    string $subject,
    string $bodyHtml,
    ?string $fromEmail = null,
    ?string $fromName  = null,
    ?string $replyToEmail = null,
    ?string $replyToName  = null
  ): bool {
    try {
      $mail = self::base($fromEmail, $fromName, $replyToEmail, $replyToName);
      $mail->addAddress($to, $toName);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $bodyHtml;
      return $mail->send();
    } catch (Exception $e) {
      error_log('Mailer sendHtml error: ' . $e->getMessage());
      return false;
    }
  }
}
