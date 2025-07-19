#!/usr/bin/env php
<?php

require_once '/var/www/web/drupal7/sites/all/modules/civicrm/civicrm.config.php';
require_once 'CRM/Core/Config.php';
define('DRUPAL_ROOT', '/var/www/web/drupal7');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
// Bootstrap CiviCRM
/*
$civicrm_root = dirname(dirname(dirname(__DIR__)));
require_once $civicrm_root . '/civicrm.config.php';
require_once 'CRM/Core/Config.php';
*/
$config = CRM_Core_Config::singleton();

use Civi\EmailQueueRabbitMQ\Consumer;

try {
  $priority = isset($argv[1]) ? (int)$argv[1] : 1;
  $emailMethod = isset($argv[2]) ? $argv[2] : 'smtp';

  if ($priority && !in_array($priority, [1, 2, 3, 4])) {
    echo "Error: Priority must be 1, 2, 3, or 4\n";
    exit(1);
  }

  if (!in_array($emailMethod, ['smtp', 'sendmail', 'mail'])) {
    echo "Error: Email method must be smtp, sendmail, or mail\n";
    exit(1);
  }
  echo "Starting Skvare Email Queue Consumer...\n";
  $consumer = new Consumer($priority, $emailMethod);
  $consumer->run();

} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}

