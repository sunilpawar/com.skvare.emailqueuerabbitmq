#!/usr/bin/env php
<?php

// Bootstrap CiviCRM
$civicrm_root = dirname(dirname(dirname(__DIR__)));
require_once $civicrm_root . '/civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

use Civi\EmailQueueRabbitMQ\EmailQueue;

$emailQueue = new EmailQueue();
$failedEmails = $emailQueue->getFailedEmails();

echo "Skvare Email Queue RabbitMQ - Retry Failed Emails\n";
echo "===============================================\n\n";

echo "Found " . count($failedEmails) . " failed emails to retry\n\n";

foreach ($failedEmails as $email) {
  $emailQueue->updateStatus($email['id'], 'pending', null);
  echo "Reset email ID: {$email['id']} (Priority: {$email['priority']}) for retry - {$email['to_email']}\n";
  $emailQueue->logAction($email['id'], 'retry_reset', 'Email reset for retry by admin script');
}

echo "\nRetry process completed at: " . date('Y-m-d H:i:s') . "\n";
