#!/usr/bin/env php
<?php

// Bootstrap CiviCRM
$civicrm_root = dirname(dirname(__DIR__), 3);
require_once $civicrm_root . '/civicrm.settings.php';

require_once 'CRM/Core/Config.php';
define('DRUPAL_ROOT', '/var/www/web/drupal7');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
$config = CRM_Core_Config::singleton();

use Civi\EmailQueueRabbitMQ\Producer;

try {
  $priority = isset($argv[1]) ? (int)$argv[1] : null;

  if ($priority && !in_array($priority, [1, 2, 3, 4])) {
    echo "Error: Priority must be 1, 2, 3, or 4\n";
    exit(1);
  }

  $producer = new Producer();
  if ($priority) {
    echo "Starting Skvare Email Queue Producer for Priority $priority\n";
    $producer->runPriorityBased($priority);
  } else {
    echo "Starting Skvare Email Queue Producer for All Priorities\n";
    $producer->run();
  }

} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}
