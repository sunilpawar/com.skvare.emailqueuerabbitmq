<?php
use CRM_EmailQueueRabbitMQ_ExtensionUtil as E;
use Civi\EmailQueueRabbitMQ\Config;

require E::path('vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

class CRM_EmailQueueRabbitMQ_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    // Check if this is an AJAX request for metrics data
    $action = CRM_Utils_Request::retrieve('refresh', 'String');
    if ($action === 'getMetrics') {
      $this->ajaxGetMetrics();
    }

    // Include Chart.js and custom metrics JavaScript
    $resources = CRM_Core_Resources::singleton();
    // Add CSS
    $resources->addStyleFile('com.skvare.emailqueuerabbitmq', 'css/metrics-dashboard.css');

    // Load custom JS after Chart.js libraries are loaded
    $resources->addScriptFile('com.skvare.emailqueuerabbitmq', 'js/metrics-dashboard.js', 100, 'page-footer');

    // Set template variables
    $this->assign('pageTitle', ts('RabbitMQ Metrics Dashboard'));
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    // Get initial metrics data
    $metrics = $this->getRabbitMQMetrics();
    $this->assign('initialMetrics', json_encode($metrics));

    parent::run();
  }

  public function ajaxGetMetrics() {
    $metrics = $this->getRabbitMQMetrics();
    header('Content-Type: application/json');
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
    }
    catch (Exception $e) {
      return ['error' => 'Failed to fetch metrics: ' . $e->getMessage()];
    }
  }

  private function fetchRabbitMQAPI($url, $settings) {
    $context = stream_context_create([
      'http' => [
        'header' => "Authorization: Basic " . base64_encode($settings['user'] . ':' . $settings['pass']),
        'timeout' => 10,
      ],
    ]);

    $result = @file_get_contents($url, FALSE, $context);
    if ($result === FALSE) {
      return NULL;
    }

    return json_decode($result, TRUE);
  }

}
