<?php
use CRM_EmailQueueRabbitMQ_ExtensionUtil as E;
use Civi\EmailQueueRabbitMQ\Config;

require E::path('vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

class CRM_EmailQueueRabbitMQ_Page_Dashboard extends CRM_Core_Page {
  /**
   * @var AMQPStreamConnection
   */
  private $connection;
  private $channel;
  private $emailQueue;
  private $emailSender;
  private $config;
  public function run() {
    // Check if this is an AJAX request for metrics data
    $action = CRM_Utils_Request::retrieve('action', 'String');
    if ($action === 'getMetrics') {
      $this->ajaxGetMetrics();
      return;
    }

    // Include Chart.js and custom metrics JavaScript
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.emailqueuerabbitmq', 'css/metrics-dashboard.css')
      ->addScriptFile('com.skvare.emailqueuerabbitmq', 'js/metrics-dashboard.js')
      ->addScriptUrl('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js')
      ->addScriptUrl('https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js')
      ->addScriptUrl('https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js');

    // Set template variables
    $this->assign('pageTitle', ts('RabbitMQ Metrics Dashboard'));
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    // Get initial metrics data
    $metrics = $this->getRabbitMQMetrics();
    $this->assign('initialMetrics', json_encode($metrics));

    parent::run();
  }

  public function ajaxGetMetrics() {
    header('Content-Type: application/json');
    $metrics = $this->getRabbitMQMetrics();
    echo json_encode($metrics);
    CRM_Utils_System::civiExit();
  }

  private function getRabbitMQMetrics() {
    $settings = Config::getRabbitMQSettings();

    if (empty($settings['host']) || empty($settings['port'])) {
      return ['error' => 'RabbitMQ connection settings not configured'];
    }

    try {
      // Management API typically runs on port 15672
      $managementPort = 15672;
      $baseUrl = "http://{$settings['host']}:{$managementPort}/api";
      $metrics = [
        'timestamp' => time() * 1000, // JavaScript timestamp
        'overview' => $this->fetchRabbitMQAPI($baseUrl . '/overview', $settings),
        'nodes' => $this->fetchRabbitMQAPI($baseUrl . '/nodes', $settings),
        'queues' => $this->fetchRabbitMQAPI($baseUrl . '/queues', $settings),
        'channels' => $this->fetchRabbitMQAPI($baseUrl . '/channels', $settings),
        'connections' => $this->fetchRabbitMQAPI($baseUrl . '/connections', $settings),
      ];

      return $metrics;
    } catch (Exception $e) {
      return ['error' => 'Failed to fetch metrics: ' . $e->getMessage()];
    }
  }

  private function fetchRabbitMQAPI($url, $settings) {
    $context = stream_context_create([
      'http' => [
        'header' => "Authorization: Basic " . base64_encode($settings['user'] . ':' . $settings['pass']),
        'timeout' => 10,
      ]
    ]);

    $result = @file_get_contents($url, false, $context);
    if ($result === FALSE) {
      return null;
    }

    return json_decode($result, true);
  }

  private function getRabbitMQSettings() {
    return [
      'host' => Civi::settings()->get('skvare_emailqueue_rabbitmq_host') ?: 'localhost',
      'port' => Civi::settings()->get('skvare_emailqueue_rabbitmq_port') ?: '5672',
      'user' => Civi::settings()->get('skvare_emailqueue_rabbitmq_user') ?: 'guest',
      'pass' => Civi::settings()->get('skvare_emailqueue_rabbitmq_pass') ?: 'guest',
      'vhost' => Civi::settings()->get('skvare_emailqueue_rabbitmq_vhost') ?: '/',
    ];
  }

}
