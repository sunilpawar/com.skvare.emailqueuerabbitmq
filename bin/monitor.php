#!/usr/bin/env php
<?php

// Bootstrap CiviCRM
$civicrm_root = dirname(dirname(dirname(__DIR__)));
require_once $civicrm_root . '/civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

use Civi\EmailQueueRabbitMQ\EmailQueue;
use Civi\EmailQueueRabbitMQ\Producer;

$emailQueue = new EmailQueue();

echo "Skvare Email Queue RabbitMQ - System Monitor\n";
echo "==========================================\n\n";

// Basic Statistics
$stats = $emailQueue->getStatistics();
echo "Email Queue Statistics (Last 24 hours):\n";
echo str_repeat("-", 70) . "\n";
echo sprintf("%-12s %-10s %-8s %-12s %-20s\n", "Status", "Priority", "Count", "Avg Retries", "Oldest Email");
echo str_repeat("-", 70) . "\n";

foreach ($stats as $stat) {
  echo sprintf("%-12s %-10s %-8d %-12.2f %-20s\n",
    ucfirst($stat['status']),
    $stat['priority'],
    $stat['count'],
    $stat['avg_retries'],
    $stat['oldest_email'] ? date('Y-m-d H:i', strtotime($stat['oldest_email'])) : 'N/A'
  );
}

echo str_repeat("-", 70) . "\n\n";

// Processing Statistics
$processingStats = $emailQueue->getProcessingStatistics();
echo "Processing Performance (Last Hour):\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-10s %-8s %-10s %-8s %-8s %-15s\n", "Priority", "Pending", "Processing", "Sent", "Failed", "Avg Time (sec)");
echo str_repeat("-", 80) . "\n";

foreach ($processingStats as $stat) {
  echo sprintf("%-10s %-8d %-10d %-8d %-8d %-15s\n",
    $stat['priority'],
    $stat['pending_count'],
    $stat['processing_count'],
    $stat['sent_count'],
    $stat['failed_count'],
    $stat['avg_processing_time'] ? round($stat['avg_processing_time'], 2) : 'N/A'
  );
}

echo str_repeat("-", 80) . "\n\n";

// Queue Status
try {
  $producer = new Producer();
  $queueStatus = $producer->getQueueStatus();

  echo "RabbitMQ Queue Status:\n";
  echo str_repeat("-", 50) . "\n";
  echo sprintf("%-30s %-10s %-10s\n", "Queue Name", "Priority", "Status");
  echo str_repeat("-", 50) . "\n";

  foreach ($queueStatus as $queueName => $status) {
    echo sprintf("%-30s %-10s %-10s\n",
      $queueName,
      $status['priority'],
      $status['exists'] ? 'Active' : 'Inactive'
    );
  }

  echo str_repeat("-", 50) . "\n";

} catch (Exception $e) {
  echo "Error getting queue status: " . $e->getMessage() . "\n";
}

echo "\nMonitoring completed at: " . date('Y-m-d H:i:s') . "\n";
