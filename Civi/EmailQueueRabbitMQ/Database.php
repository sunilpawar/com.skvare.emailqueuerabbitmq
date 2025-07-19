<?php
// Civi/EmailQueueRabbitMQ/Database.php

namespace Civi\EmailQueueRabbitMQ;

class Database {
  private static $instance = NULL;
  private $pdo;

  private function __construct() {
    $config = Config::getDatabaseSettings();

    try {
      $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
      $this->pdo = new \PDO($dsn, $config['user'], $config['pass'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => FALSE,
        \PDO::ATTR_PERSISTENT => TRUE,
      ]);
    }
    catch (\PDOException $e) {
      \Civi::log('emailqueue_rabbitmq')->error("Email Queue Database connection failed: " . $e->getMessage());
      throw $e;
    }
  }

  public static function getInstance() {
    if (self::$instance === NULL) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function getPDO() {
    return $this->pdo;
  }

  public function testConnection() {
    try {
      $this->pdo->query('SELECT 1');
      return TRUE;
    }
    catch (\PDOException $e) {
      return FALSE;
    }
  }
}
