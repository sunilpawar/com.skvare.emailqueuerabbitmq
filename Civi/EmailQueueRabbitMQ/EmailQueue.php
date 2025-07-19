<?php
// Civi/EmailQueueRabbitMQ/EmailQueue.php

namespace Civi\EmailQueueRabbitMQ;

class EmailQueue {
  private $db;
  private $config;

  public function __construct() {
    $this->db = Database::getInstance()->getPDO();
    $this->config = Config::getProcessingSettings();
  }

  public function getQueuedEmailsByPriority($priority, $limit = 100) {
    $sql = "SELECT * FROM email_queue
                WHERE status = 'pending'
                AND priority = :priority
                AND (scheduled_date IS NULL OR scheduled_date <= NOW())
                ORDER BY created_date ASC
                LIMIT :limit";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':priority', $priority, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetchAll();

    if ($this->config['enable_logging']) {
      \Civi::log('emailqueue_rabbitmq')->info("Retrieved {count} emails for priority {priority}", [
        'count' => count($result),
        'priority' => $priority,
      ]);
    }

    return $result;
  }

  public function getEmailById($id) {
    try {
      $stmt = $this->db->prepare("
                SELECT *
                FROM email_queue
                WHERE id = :id
            ");

      $stmt->execute(['id' => $id]);
      $email = $stmt->fetch();

      if ($email) {
        // Decode JSON fields
        $email['headers'] = json_decode($email['headers'] ?? '[]', TRUE);
        return $email;
      }

      return null;
    } catch (PDOException $e) {
      error_log("Database error in getEmailById: " . $e->getMessage());
      throw new Exception("Failed to retrieve email from database");
    }
  }

  public function getQueuedEmails($limit = 100) {
    $limit = 2;
    $sql = "SELECT * FROM email_queue
                WHERE status = 'pending'
                AND (scheduled_date IS NULL OR scheduled_date <= NOW())
                ORDER BY priority DESC, created_date ASC
                LIMIT :limit";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  public function updateStatus($id, $status, $errorMessage = NULL) {
    $sql = "UPDATE email_queue SET status = :status, error_message = :error_message";

    if ($status === 'sent') {
      $sql .= ", sent_date = NOW()";
    }
    elseif ($status === 'processing') {
      $sql .= ", sent_date = NULL";
    }

    $sql .= " WHERE id = :id";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      ':id' => $id,
      ':status' => $status,
      ':error_message' => $errorMessage,
    ]);

    $this->logAction($id, "status_updated", "Status changed to: $status" . ($errorMessage ? " - Error: $errorMessage" : ""));

    if ($this->config['enable_logging']) {
      \Civi::log('emailqueue_rabbitmq')->info("Email {$id} status updated to {$status}");
    }
  }

  public function incrementRetryCount($id) {
    $sql = "UPDATE email_queue SET retry_count = retry_count + 1 WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $id]);

    $this->logAction($id, "retry_incremented", "Retry count incremented");
  }

  public function logAction($queueId, $action, $message) {
    $sql = "INSERT INTO email_queue_log (queue_id, action, message, created_date)
                VALUES (:queue_id, :action, :message, NOW())";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      ':queue_id' => $queueId,
      ':action' => $action,
      ':message' => $message,
    ]);
  }

  public function getFailedEmails() {
    $sql = "SELECT * FROM email_queue
                WHERE status = 'failed'
                AND retry_count < max_retries
                AND created_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  public function getStatistics() {
    $sql = "SELECT
                    status,
                    priority,
                    COUNT(*) as count,
                    AVG(retry_count) as avg_retries,
                    MIN(created_date) as oldest_email,
                    MAX(created_date) as newest_email
                FROM email_queue
                WHERE created_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY status, priority
                ORDER BY priority, status";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
  }

  public function getProcessingStatistics() {
    $sql = "SELECT
                    priority,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
                    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                    AVG(CASE WHEN sent_date IS NOT NULL THEN
                        TIMESTAMPDIFF(SECOND, created_date, sent_date) END) as avg_processing_time
                FROM email_queue
                WHERE created_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY priority
                ORDER BY priority";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
  }
}
