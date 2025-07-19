<?php

use CRM_EmailQueueRabbitMQ_ExtensionUtil as E;
require E::path('vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_EmailQueueRabbitMQ_Form_Settings extends CRM_Core_Form {


  public function buildQuickForm() {
    $this->setTitle(ts('Email Queue RabbitMQ Settings'));

    // RabbitMQ Connection Settings
    $this->add('text', 'skvare_emailqueue_rabbitmq_host', ts('RabbitMQ Host'), ['class' => 'form-control'], TRUE);
    $this->add('text', 'skvare_emailqueue_rabbitmq_port', ts('RabbitMQ Port'), ['class' => 'form-control'], TRUE);
    $this->add('text', 'skvare_emailqueue_rabbitmq_user', ts('RabbitMQ Username'), ['class' => 'form-control'], TRUE);
    $this->add('password', 'skvare_emailqueue_rabbitmq_pass', ts('RabbitMQ Password'), ['class' => 'form-control'], TRUE);
    $this->add('text', 'skvare_emailqueue_rabbitmq_vhost', ts('RabbitMQ Virtual Host'), ['class' => 'form-control']);

    // Email Queue Database Settings
    $this->add('text', 'skvare_emailqueue_db_host', ts('Queue Database Host'), ['class' => 'form-control'], TRUE);
    $this->add('text', 'skvare_emailqueue_db_name', ts('Queue Database Name'), ['class' => 'form-control'], TRUE);
    $this->add('text', 'skvare_emailqueue_db_user', ts('Queue Database Username'), ['class' => 'form-control'], TRUE);
    $this->add('password', 'skvare_emailqueue_db_pass', ts('Queue Database Password'), ['class' => 'form-control'], TRUE);
    $this->add('text', 'skvare_emailqueue_db_port', ts('Queue Database Port'), ['class' => 'form-control']);

    // Processing Settings
    $this->add('select', 'skvare_emailqueue_batch_size', ts('Batch Size'), [
      25 => '25',
      50 => '50',
      100 => '100',
      200 => '200',
      500 => '500'
    ], TRUE);

    $this->add('select', 'skvare_emailqueue_max_retries', ts('Max Retries'), [
      1 => '1',
      3 => '3',
      5 => '5',
      10 => '10'
    ], TRUE);

    $this->add('text', 'skvare_emailqueue_retry_delay', ts('Retry Delay (seconds)'), ['class' => 'form-control']);

    // Priority Queue Settings
    $this->add('checkbox', 'skvare_emailqueue_enable_priority_queues', ts('Enable Priority-based Queues'));

    // Queue Naming Strategy
    $this->add('text', 'skvare_emailqueue_queue_prefix', ts('Queue Name Prefix'), ['class' => 'form-control']);

    // SMTP Settings
    $this->add('text', 'skvare_emailqueue_smtp_host', ts('SMTP Host'), ['class' => 'form-control']);
    $this->add('text', 'skvare_emailqueue_smtp_port', ts('SMTP Port'), ['class' => 'form-control']);
    $this->add('text', 'skvare_emailqueue_smtp_user', ts('SMTP Username'), ['class' => 'form-control']);
    $this->add('password', 'skvare_emailqueue_smtp_pass', ts('SMTP Password'), ['class' => 'form-control']);
    $this->add('select', 'skvare_emailqueue_smtp_encryption', ts('SMTP Encryption'), [
      'none' => 'None',
      'ssl' => 'SSL',
      'tls' => 'TLS'
    ]);

    // Advanced Settings
    $this->add('text', 'skvare_emailqueue_consumer_timeout', ts('Consumer Timeout (seconds)'), ['class' => 'form-control']);
    $this->add('checkbox', 'skvare_emailqueue_enable_logging', ts('Enable Debug Logging'));
    $this->add('checkbox', 'skvare_emailqueue_enable_monitoring', ts('Enable Performance Monitoring'));

    // Test Connection Buttons
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Save Settings'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'upload',
        'name' => ts('Test RabbitMQ Connection'),
        'subName' => 'test_rabbitmq',
      ],
      [
        'type' => 'upload',
        'name' => ts('Test Database Connection'),
        'subName' => 'test_database',
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  public function setDefaultValues() {
    $defaults = [];
    $settings = [
      'skvare_emailqueue_rabbitmq_host',
      'skvare_emailqueue_rabbitmq_port',
      'skvare_emailqueue_rabbitmq_user',
      'skvare_emailqueue_rabbitmq_pass',
      'skvare_emailqueue_rabbitmq_vhost',
      'skvare_emailqueue_db_host',
      'skvare_emailqueue_db_name',
      'skvare_emailqueue_db_user',
      'skvare_emailqueue_db_pass',
      'skvare_emailqueue_db_port',
      'skvare_emailqueue_batch_size',
      'skvare_emailqueue_max_retries',
      'skvare_emailqueue_retry_delay',
      'skvare_emailqueue_enable_priority_queues',
      'skvare_emailqueue_queue_prefix',
      'skvare_emailqueue_smtp_host',
      'skvare_emailqueue_smtp_port',
      'skvare_emailqueue_smtp_user',
      'skvare_emailqueue_smtp_pass',
      'skvare_emailqueue_smtp_encryption',
      'skvare_emailqueue_consumer_timeout',
      'skvare_emailqueue_enable_logging',
      'skvare_emailqueue_enable_monitoring',
    ];

    foreach ($settings as $setting) {
      $defaults[$setting] = Civi::settings()->get($setting);
    }

    // Set default values if not set
    $defaults['skvare_emailqueue_rabbitmq_host'] = $defaults['skvare_emailqueue_rabbitmq_host'] ?: 'localhost';
    $defaults['skvare_emailqueue_rabbitmq_port'] = $defaults['skvare_emailqueue_rabbitmq_port'] ?: '5672';
    $defaults['skvare_emailqueue_rabbitmq_user'] = $defaults['skvare_emailqueue_rabbitmq_user'] ?: 'guest';
    $defaults['skvare_emailqueue_rabbitmq_vhost'] = $defaults['skvare_emailqueue_rabbitmq_vhost'] ?: '/';
    $defaults['skvare_emailqueue_db_port'] = $defaults['skvare_emailqueue_db_port'] ?: '3306';
    $defaults['skvare_emailqueue_batch_size'] = $defaults['skvare_emailqueue_batch_size'] ?: 100;
    $defaults['skvare_emailqueue_max_retries'] = $defaults['skvare_emailqueue_max_retries'] ?: 3;
    $defaults['skvare_emailqueue_retry_delay'] = $defaults['skvare_emailqueue_retry_delay'] ?: 60;
    $defaults['skvare_emailqueue_queue_prefix'] = $defaults['skvare_emailqueue_queue_prefix'] ?: 'skvare_email_queue';
    $defaults['skvare_emailqueue_smtp_port'] = $defaults['skvare_emailqueue_smtp_port'] ?: '587';
    $defaults['skvare_emailqueue_smtp_encryption'] = $defaults['skvare_emailqueue_smtp_encryption'] ?: 'tls';
    $defaults['skvare_emailqueue_consumer_timeout'] = $defaults['skvare_emailqueue_consumer_timeout'] ?: 30;

    return $defaults;
  }

  public function postProcess() {
    $redirectUrl = CRM_Utils_System::url('civicrm/admin/emailqueue/rabbitmq/settings', 'reset=1');
    $values = $this->getSubmitValues();
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($redirectUrl);
    // Handle test connections
    if (!empty($values['_qf_Settings_upload_test_rabbitmq'])) {
      $this->testRabbitMQConnection($values);
      //CRM_Utils_System::redirect($redirectUrl);
      return;
    }

    if (!empty($values['_qf_Settings_upload_test_database'])) {
      $this->testDatabaseConnection($values);
      return;
    }

    $settings = [
      'skvare_emailqueue_rabbitmq_host',
      'skvare_emailqueue_rabbitmq_port',
      'skvare_emailqueue_rabbitmq_user',
      'skvare_emailqueue_rabbitmq_pass',
      'skvare_emailqueue_rabbitmq_vhost',
      'skvare_emailqueue_db_host',
      'skvare_emailqueue_db_name',
      'skvare_emailqueue_db_user',
      'skvare_emailqueue_db_pass',
      'skvare_emailqueue_db_port',
      'skvare_emailqueue_batch_size',
      'skvare_emailqueue_max_retries',
      'skvare_emailqueue_retry_delay',
      'skvare_emailqueue_enable_priority_queues',
      'skvare_emailqueue_queue_prefix',
      'skvare_emailqueue_smtp_host',
      'skvare_emailqueue_smtp_port',
      'skvare_emailqueue_smtp_user',
      'skvare_emailqueue_smtp_pass',
      'skvare_emailqueue_smtp_encryption',
      'skvare_emailqueue_consumer_timeout',
      'skvare_emailqueue_enable_logging',
      'skvare_emailqueue_enable_monitoring',
    ];

    foreach ($settings as $setting) {
      if (isset($values[$setting])) {
        Civi::settings()->set($setting, $values[$setting]);
      }
    }
    CRM_Core_Session::setStatus(ts('Email Queue RabbitMQ settings have been saved.'), ts('Settings Saved'), 'success');
  }

  private function testRabbitMQConnection($values) {
    try {

      $connection = new AMQPStreamConnection(
        $values['skvare_emailqueue_rabbitmq_host'],
        $values['skvare_emailqueue_rabbitmq_port'],
        $values['skvare_emailqueue_rabbitmq_user'],
        $values['skvare_emailqueue_rabbitmq_pass'],
        $values['skvare_emailqueue_rabbitmq_vhost']
      );

      $connection->close();
      CRM_Core_Session::setStatus(ts('RabbitMQ connection successful!'), ts('Connection Test'), 'success');

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(ts('RabbitMQ connection failed: %1', [1 => $e->getMessage()]), ts('Connection Test'), 'error');
    }
  }

  private function testDatabaseConnection($values) {
    try {
      $dsn = "mysql:host={$values['skvare_emailqueue_db_host']};port={$values['skvare_emailqueue_db_port']};dbname={$values['skvare_emailqueue_db_name']};charset=utf8mb4";
      $pdo = new PDO($dsn, $values['skvare_emailqueue_db_user'], $values['skvare_emailqueue_db_pass']);

      CRM_Core_Session::setStatus(ts('Database connection successful!'), ts('Connection Test'), 'success');

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(ts('Database connection failed: %1', [1 => $e->getMessage()]), ts('Connection Test'), 'error');
    }
  }

}
