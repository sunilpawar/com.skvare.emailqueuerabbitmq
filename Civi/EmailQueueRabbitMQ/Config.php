<?php
// This file is part of CiviCRM RabbitMQ Email Queue Extension

namespace Civi\EmailQueueRabbitMQ;

class Config {

  public static function getRabbitMQSettings() {
    return [
      'host' => \Civi::settings()->get('skvare_emailqueue_rabbitmq_host') ?: 'localhost',
      'port' => \Civi::settings()->get('skvare_emailqueue_rabbitmq_port') ?: 5672,
      'user' => \Civi::settings()->get('skvare_emailqueue_rabbitmq_user') ?: 'guest',
      'pass' => \Civi::settings()->get('skvare_emailqueue_rabbitmq_pass') ?: 'guest',
      'vhost' => \Civi::settings()->get('skvare_emailqueue_rabbitmq_vhost') ?: '/',
    ];
  }

  public static function getDatabaseSettings() {
    return [
      'host' => \Civi::settings()->get('skvare_emailqueue_db_host'),
      'name' => \Civi::settings()->get('skvare_emailqueue_db_name'),
      'user' => \Civi::settings()->get('skvare_emailqueue_db_user'),
      'pass' => \Civi::settings()->get('skvare_emailqueue_db_pass'),
      'port' => \Civi::settings()->get('skvare_emailqueue_db_port') ?: 3306,
    ];
  }

  public static function getSMTPSettings() {
    return [
      'host' => \Civi::settings()->get('skvare_emailqueue_smtp_host'),
      'port' => \Civi::settings()->get('skvare_emailqueue_smtp_port') ?: 587,
      'user' => \Civi::settings()->get('skvare_emailqueue_smtp_user'),
      'pass' => \Civi::settings()->get('skvare_emailqueue_smtp_pass'),
      'encryption' => \Civi::settings()->get('skvare_emailqueue_smtp_encryption') ?: 'tls',
    ];
  }

  public static function getProcessingSettings() {
    return [
      'batch_size' => \Civi::settings()->get('skvare_emailqueue_batch_size') ?: 100,
      'max_retries' => \Civi::settings()->get('skvare_emailqueue_max_retries') ?: 3,
      'retry_delay' => \Civi::settings()->get('skvare_emailqueue_retry_delay') ?: 60,
      'enable_priority_queues' => \Civi::settings()->get('skvare_emailqueue_enable_priority_queues') ?: FALSE,
      'queue_prefix' => \Civi::settings()->get('skvare_emailqueue_queue_prefix') ?: 'skvare_email_queue',
      'consumer_timeout' => \Civi::settings()->get('skvare_emailqueue_consumer_timeout') ?: 300,
      'enable_logging' => \Civi::settings()->get('skvare_emailqueue_enable_logging') ?: FALSE,
      'enable_monitoring' => \Civi::settings()->get('skvare_emailqueue_enable_monitoring') ?: FALSE,
    ];
  }

  public static function getPriorityQueues() {
    $prefix = \Civi::settings()->get('skvare_emailqueue_queue_prefix') ?: 'skvare_email_queue';
    return [
      1 => $prefix . '_priority_1', // Highest priority
      2 => $prefix . '_priority_2',
      3 => $prefix . '_priority_3',
      4 => $prefix . '_priority_4', // Lowest priority
    ];
  }

  public static function getDefaultQueue() {
    $prefix = \Civi::settings()->get('skvare_emailqueue_queue_prefix') ?: 'skvare_email_queue';
    return $prefix . '_default';
  }
}
