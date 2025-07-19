<?php

require_once 'emailqueuerabbitmq.civix.php';

use CRM_EmailQueueRabbitMQ_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function emailqueuerabbitmq_civicrm_config(&$config): void {
  _emailqueuerabbitmq_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function emailqueuerabbitmq_civicrm_install(): void {
  _emailqueuerabbitmq_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function emailqueuerabbitmq_civicrm_enable(): void {
  _emailqueuerabbitmq_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function emailqueuerabbitmq_civicrm_navigationMenu(&$menu) {
  _emailqueuerabbitmq_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => E::ts('Email Queue RabbitMQ Settings'),
    'name' => 'emailqueue_rabbitmq_settings',
    'url' => 'civicrm/admin/emailqueue/rabbitmq/settings',
    'permission' => 'administer CiviCRM',
  ]);
}

/**
 * Implements hook_civicrm_permission().
 */
function emailqueuerabbitmq_civicrm_permission(&$permissions) {
  $permissions['manage email queue rabbitmq'] = [
    'label' => E::ts('Email Queue RabbitMQ: Manage Settings'),
    'description' => E::ts('Manage Email Queue RabbitMQ configuration and monitoring'),
  ];
}
