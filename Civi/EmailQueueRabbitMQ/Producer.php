<?php
// Civi/EmailQueueRabbitMQ/Producer.php

namespace Civi\EmailQueueRabbitMQ;

require_once 'vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Producer {
  private $connection;
  private $channel;
  private $emailQueue;
  private $config;
  private $priorityQueues;
  private $defaultQueue;
  private $maxMessageSize;

  public function __construct() {
    $this->config = Config::getRabbitMQSettings();
    $this->priorityQueues = Config::getPriorityQueues();
    $this->defaultQueue = Config::getDefaultQueue();
    $this->maxMessageSize = 50 * 1024; // 50KB threshold

    $this->connection = new AMQPStreamConnection(
      $this->config['host'],
      $this->config['port'],
      $this->config['user'],
      $this->config['pass'],
      $this->config['vhost']
    );

    $this->channel = $this->connection->channel();

    // Declare default queue
    $this->channel->queue_declare($this->defaultQueue, FALSE, TRUE, FALSE, FALSE);

    // Declare all priority queues if enabled
    $processingConfig = Config::getProcessingSettings();

    if ($processingConfig['enable_priority_queues']) {
      foreach ($this->priorityQueues as $queueName) {
        $this->channel->queue_declare($queueName, FALSE, TRUE, FALSE, FALSE);
      }
    }

    $this->emailQueue = new EmailQueue();
  }

  public function runPriorityBased($priority = NULL) {
    $processingSettings = Config::getProcessingSettings();

    if (!$processingSettings['enable_priority_queues']) {
      throw new \Exception("Priority-based queues are not enabled");
    }

    if ($priority && !isset($this->priorityQueues[$priority])) {
      throw new \Exception("Invalid priority: $priority");
    }

    $priorities = $priority ? [$priority] : array_keys($this->priorityQueues);

    \Civi::log('emailqueue_rabbitmq')->info("Starting Skvare Email Queue Producer for priorities: " . implode(', ', $priorities));

    while (TRUE) {
      try {
        foreach ($priorities as $currentPriority) {
          $emails = $this->emailQueue->getQueuedEmailsByPriority(
            $currentPriority,
            $processingSettings['batch_size']
          );

          if (!empty($emails)) {
            $this->processEmailsForPriority($emails, $currentPriority);
          }
        }

        \Civi::log('emailqueue_rabbitmq')->info("Completed priority scan, sleeping...");
        sleep(5);

      }
      catch (\Exception $e) {
        \Civi::log('emailqueue_rabbitmq')->error("Producer error: " . $e->getMessage());
        sleep(10);
      }
    }
  }

  public function run() {
    $processingSettings = Config::getProcessingSettings();

    \Civi::log('emailqueue_rabbitmq')->info("Starting Skvare Email Queue Producer");

    while (TRUE) {
      try {
        $emails = $this->emailQueue->getQueuedEmails($processingSettings['batch_size']);
        $count = count($emails);
        if (empty($emails)) {
          \Civi::log('emailqueue_rabbitmq')->info("No emails to process, sleeping...");
          sleep(10);
          continue;
        }
        else {
          \Civi::log('emailqueue_rabbitmq')->info("Processing batch of {$count} emails");
        }

        foreach ($emails as $email) {
          $this->emailQueue->updateStatus($email['id'], 'processing');

          $queueName = $this->getQueueForPriority($email['priority']);
          $message = new AMQPMessage(
            json_encode($email),
            [
              'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
              'timestamp' => time(),
              'app_id' => 'skvare_emailqueue_rabbitmq'
            ]
          );

          $this->channel->basic_publish($message, '', $queueName);

          \Civi::log('emailqueue_rabbitmq')->info("Queued email ID: {$email['id']} (Priority: {$email['priority']}) for {$email['to_email']}");
          $this->emailQueue->logAction($email['id'], 'queued', "Email queued for processing in queue: $queueName");
        }
      }
      catch (\Exception $e) {
        \Civi::log('emailqueue_rabbitmq')->error("Producer error: " . $e->getMessage());
        sleep(5);
      }
    }
  }

  private function processEmailsForPriority($emails, $priority) {
    $queueName = $this->priorityQueues[$priority];

    foreach ($emails as $emailData) {
      $this->emailQueue->updateStatus($emailData['id'], 'processing');
      $emailSize = strlen(json_encode($emailData));

      if ($emailSize <= $this->maxMessageSize) {
        // Small email: include full data
        $emailData['type'] = 'FULL_EMAIL';
        $emailData['timestamp'] = date('c');
        $emailData['size'] = $emailSize;
      }
      else {
        // Large email: store only ID and metadata
        unset($emailData['body_html'], $emailData['body_text']);
        $emailData['type'] = 'EMAIL_ID';
        $emailData['timestamp'] = date('c');
        $emailData['size'] = $emailSize;
      }
      $msgBody = json_encode($emailData);
      $message = new AMQPMessage(
        json_encode($msgBody),
        [
          'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
          'timestamp' => time(),
          'priority' => $priority,
          'app_id' => 'skvare_emailqueue_rabbitmq',
          'timestamp' => time(),
        ]
      );

      $this->channel->basic_publish($message, '', $queueName);

      \Civi::log('emailqueue_rabbitmq')->info("Queued email ID: {$email['id']} (Priority: $priority) for {$email['to_email']}");
      $this->emailQueue->logAction($email['id'], 'queued', "Email queued for processing in priority queue: $queueName");
    }

    \Civi::log('emailqueue_rabbitmq')->info("Processed {count} emails for priority {priority}", [
      'count' => count($emails),
      'priority' => $priority
    ]);
  }

  private function getQueueForPriority($priority) {
    $processingSettings = Config::getProcessingSettings();

    if ($processingSettings['enable_priority_queues'] && isset($this->priorityQueues[$priority])) {
      return $this->priorityQueues[$priority];
    }

    return $this->defaultQueue;
  }

  public function getQueueStatus() {
    // Return queue status for monitoring
    $status = [];

    try {
      $processingSettings = Config::getProcessingSettings();

      if ($processingSettings['enable_priority_queues']) {
        foreach ($this->priorityQueues as $priority => $queueName) {
          $status[$queueName] = [
            'priority' => $priority,
            'exists' => TRUE,
            'messages' => 'N/A' // RabbitMQ management API would be needed for actual count
          ];
        }
      }
      else {
        $status[$this->defaultQueue] = [
          'priority' => 'all',
          'exists' => TRUE,
          'messages' => 'N/A'
        ];
      }
    }
    catch (\Exception $e) {
      \Civi::log('emailqueue_rabbitmq')->error("Error getting queue status: " . $e->getMessage());
    }

    return $status;
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
