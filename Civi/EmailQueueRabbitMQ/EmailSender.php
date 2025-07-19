<?php
// Civi/EmailQueueRabbitMQ/EmailSender.php

namespace Civi\EmailQueueRabbitMQ;

require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
  private $method;
  private $config;

  public function __construct($method = 'smtp') {
    $this->method = $method;
    $this->config = Config::getSMTPSettings();
  }

  public function send($emailData) {
    $startTime = microtime(TRUE);

    try {
      switch ($this->method) {
        case 'smtp':
          $result = $this->sendSMTP($emailData);
          break;
        case 'sendmail':
          $result = $this->sendMail($emailData);
          break;
        case 'mail':
          $result = $this->sendPHPMail($emailData);
          break;
        default:
          throw new \Exception("Unsupported email method: " . $this->method);
      }

      $duration = round((microtime(TRUE) - $startTime) * 1000, 2);

      if (Config::getProcessingSettings()['enable_logging']) {
        \Civi::log('emailqueue_rabbitmq')->info("Email sent via {method}", [
          'method' => $this->method,
          'email_id' => $emailData['id'],
          'to' => $emailData['to_email'],
          'duration_ms' => $duration,
          'success' => $result['success']
        ]);
      }

      return $result;

    }
    catch (\Exception $e) {
      return ['success' => FALSE, 'message' => $e->getMessage()];
    }
  }

  private function sendSMTP($emailData) {
    $mail = new PHPMailer(TRUE);

    try {
      // Server settings
      $mail->isSMTP();
      $mail->Host = $this->config['host'];
      $mail->SMTPAuth = TRUE;
      $mail->Username = $this->config['user'];
      $mail->Password = $this->config['pass'];
      $mail->SMTPSecure = $this->config['encryption'];
      $mail->Port = $this->config['port'];

      // Set timeout
      $mail->Timeout = Config::getProcessingSettings()['consumer_timeout'];

      // Recipients
      $mail->setFrom($emailData['from_email'], $emailData['from_name'] ?? '');
      $mail->addAddress($emailData['to_email']);

      if (!empty($emailData['reply_to'])) {
        $mail->addReplyTo($emailData['reply_to']);
      }

      // CC and BCC
      if (!empty($emailData['cc'])) {
        $ccList = explode(',', $emailData['cc']);
        foreach ($ccList as $cc) {
          $mail->addCC(trim($cc));
        }
      }

      if (!empty($emailData['bcc'])) {
        $bccList = explode(',', $emailData['bcc']);
        foreach ($bccList as $bcc) {
          $mail->addBCC(trim($bcc));
        }
      }

      // Content
      $mail->isHTML(TRUE);
      $mail->Subject = $emailData['subject'];
      $mail->Body = $emailData['body_html'];
      $mail->AltBody = $emailData['body_text'];

      // Custom headers
      if (!empty($emailData['headers'])) {
        $headers = json_decode($emailData['headers'], TRUE);
        if ($headers) {
          foreach ($headers as $header => $value) {
            $mail->addCustomHeader($header, $value);
          }
        }
      }

      // Add tracking headers
      $mail->addCustomHeader('X-Skvare-EmailQueue-ID', $emailData['id']);
      $mail->addCustomHeader('X-Skvare-EmailQueue-Priority', $emailData['priority']);

      $mail->send();
      return ['success' => TRUE, 'message' => 'Email sent successfully via SMTP'];

    }
    catch (Exception $e) {
      return ['success' => FALSE, 'message' => "SMTP send failed: {$mail->ErrorInfo}"];
    }
  }

  private function sendMail($emailData) {
    $headers = "From: " . $emailData['from_email'] . "\r\n";
    $headers .= "Reply-To: " . ($emailData['reply_to'] ?: $emailData['from_email']) . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Skvare-EmailQueue-ID: " . $emailData['id'] . "\r\n";
    $headers .= "X-Skvare-EmailQueue-Priority: " . $emailData['priority'] . "\r\n";

    if (!empty($emailData['cc'])) {
      $headers .= "CC: " . $emailData['cc'] . "\r\n";
    }

    if (!empty($emailData['bcc'])) {
      $headers .= "BCC: " . $emailData['bcc'] . "\r\n";
    }

    $success = mail($emailData['to_email'], $emailData['subject'], $emailData['body_html'], $headers);

    return [
      'success' => $success,
      'message' => $success ? 'Email sent successfully via sendmail' : 'Sendmail send failed'
    ];
  }

  private function sendPHPMail($emailData) {
    // Similar to sendMail but with more basic implementation
    return $this->sendMail($emailData);
  }
}
