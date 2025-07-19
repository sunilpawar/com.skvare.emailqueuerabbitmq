<?php
// Civi/EmailQueueRabbitMQ/Consumer.php

namespace Civi\EmailQueueRabbitMQ;

require_once 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Consumer {
  private $connection;
  private $channel;
  private $emailQueue;
  private $emailSender;
  private $config;
  private $queueName;
  private $priority;
  private $startTime;
  private $processedCount = 0;

  public function __construct($priority = NULL, $emailMethod = 'smtp') {
    global $skipAlterMailerHook;
    $skipAlterMailerHook = TRUE;
    $this->config = Config::getRabbitMQSettings();
    $this->priority = $priority;
    $this->startTime = time();
    $processingSettings = Config::getProcessingSettings();

    $this->connection = new AMQPStreamConnection(
      $this->config['host'],
      $this->config['port'],
      $this->config['user'],
      $this->config['pass'],
      $this->config['vhost']
    );

    $this->channel = $this->connection->channel();

    // Determine queue name based on priority
    if ($priority && $processingSettings['enable_priority_queues']) {
      $priorityQueues = Config::getPriorityQueues();
      if (!isset($priorityQueues[$priority])) {
        throw new \Exception("Invalid priority: $priority");
      }
      $this->queueName = $priorityQueues[$priority];
    }
    else {
      $this->queueName = Config::getDefaultQueue();
    }

    $this->channel->queue_declare($this->queueName, FALSE, TRUE, FALSE, FALSE);

    $this->emailQueue = new EmailQueue();
    $this->emailSender = \Civi::service('pear_mail');
  }

  public function run() {
    \Civi::log('emailqueue_rabbitmq')->info("Starting Skvare Email Queue Consumer for queue: {$this->queueName}");
    if ($this->priority) {
      // echo " (Priority: {$this->priority})";
    }

    $this->channel->basic_qos(NULL, 1, NULL);
    $this->channel->basic_consume(
      $this->queueName,
      '',
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      [$this, 'processEmail']
    );

    // Set up signal handlers for graceful shutdown
    if (function_exists('pcntl_signal')) {
      pcntl_signal(SIGTERM, [$this, 'shutdown']);
      pcntl_signal(SIGINT, [$this, 'shutdown']);
    }

    while ($this->channel->is_consuming()) {
      $this->channel->wait(NULL, FALSE, Config::getProcessingSettings()['consumer_timeout']);

      // Check for signals
      if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
      }
    }
  }

  public function processEmail($msg) {
    $startTime = microtime(TRUE);
    try {
      $message = json_decode($msg->body, TRUE);
      $emailData = NULL;
      if ($emailData['type'] === 'FULL_EMAIL' || empty($emailData['type'])) {
        \CRM_Core_Error::debug_log_message("Processing email as FULL_EMAIL type");
        $emailData = $message;
      }
      elseif ($message['type'] === 'EMAIL_ID') {
        // Fetch full email data from database
        $emailData = $this->emailQueue->getEmailById($message['id']);

        if (!$emailData) {
          throw new Exception("Email not found in database: {$message['id']}");
        }

        // Merge with message metadata
        $emailData = array_merge($emailData, $message);
      }
      else {
        throw new Exception("Unknown message type: {$message['type']}");
      }

      \Civi::log('emailqueue_rabbitmq')->info("Processing email ID: {$emailData['id']} (Priority: {$emailData['priority']}) for {$emailData['to_email']}");
      // Send email
      $header = json_decode($emailData['headers'], TRUE);
      $result = $this->emailSender->send($emailData['to_email'], $header, $emailData['body_html'] ?? '',);
      if ($result) {
        // Update status to sent
        $this->emailQueue->updateStatus($emailData['id'], 'sent');
        $this->processedCount++;

        $processingTime = round((microtime(TRUE) - $startTime) * 1000, 2);
        \Civi::log('emailqueue_rabbitmq')->info("Email sent successfully: {$emailData['id']} (took {$processingTime}ms)");

        $this->emailQueue->logAction($emailData['id'], 'sent', "Email sent successfully in {$processingTime}ms");
      }
      else {
        $message = '';  // Default message if not provided by send method
        if (is_a($result, 'PEAR_Error')) {
          $message = \CRM_Utils_Mail::errorMessage($this->emailSender, $result);
        }
        \CRM_Core_Error::debug_log_message("Email sending failed");
        // Handle failure
        $this->handleFailure($emailData, $message);
      }

      // Acknowledge message
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

      // Log performance statistics
      if (Config::getProcessingSettings()['enable_monitoring']) {
        $this->logPerformanceStats();
      }

    }
    catch (\Exception $e) {
      \Civi::log('emailqueue_rabbitmq')->error("Consumer error: " . $e->getMessage());

      // Handle failure if we have email data
      if (isset($emailData)) {
        $this->handleFailure($emailData, $e->getMessage());
      }

      // Acknowledge message to prevent reprocessing
      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }
  }

  private function handleFailure($emailData, $errorMessage) {
    $processingSettings = Config::getProcessingSettings();
    $this->emailQueue->incrementRetryCount($emailData['id']);

    // Check if we should retry
    if ($emailData['retry_count'] < $processingSettings['max_retries']) {
      $this->emailQueue->updateStatus($emailData['id'], 'pending', $errorMessage);
      \Civi::log('emailqueue_rabbitmq')->info("Email failed, will retry: {$emailData['id']} (attempt " . ($emailData['retry_count'] + 1) . "/{$processingSettings['max_retries']})");
    }
    else {
      $this->emailQueue->updateStatus($emailData['id'], 'failed', $errorMessage);
      \Civi::log('emailqueue_rabbitmq')->info("Email failed permanently: {$emailData['id']} - $errorMessage");
    }
  }

  private function logPerformanceStats() {
    $uptime = time() - $this->startTime;
    $rate = $uptime > 0 ? round($this->processedCount / ($uptime / 60), 2) : 0;

    if ($this->processedCount % 100 == 0) { // Log every 100 emails
      /*
      \Civi::log('emailqueue_rabbitmq')->info("Consumer performance stats", [
        'queue' => $this->queueName,
        'priority' => $this->priority,
        'processed_count' => $this->processedCount,
        'uptime_minutes' => round($uptime / 60, 2),
        'rate_per_minute' => $rate
      ]);
      */
    }
  }

  public function shutdown($signal = NULL) {
    \Civi::log('emailqueue_rabbitmq')->info("Received shutdown signal, stopping consumer gracefully...");
    \Civi::log('emailqueue_rabbitmq')->info("Processed {$this->processedCount} emails in total");

    if ($this->channel && $this->channel->is_consuming()) {
      $this->channel->basic_cancel('', FALSE, TRUE);
    }

    exit(0);
  }

  public function __destruct() {
    if ($this->channel) {
      $this->channel->close();
    }
    if ($this->connection) {
      $this->connection->close();
    }
  }
}
