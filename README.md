# Skvare Email Queue RabbitMQ Extension - Installation & Usage Guide

## Extension Overview

The **Skvare Email Queue RabbitMQ Extension** (`com.skvare.emailqueuerabbitmq`) provides an advanced email queue system with:
- **UI-based configuration** for RabbitMQ and email settings
- **Priority-based queue processing** (1=Highest, 4=Lowest)
- **Multiple consumer/producer instances** for different priorities
- **Comprehensive monitoring and logging**
- **Integration with CiviCRM settings system**
- **Connection testing capabilities**
- **Performance monitoring**

## Flow Chart

![Screenshot](/images/flow_chart.png)


## Directory Structure

```
extensions/com.skvare.emailqueuerabbitmq/
├── info.xml
├── emailqueuerabbitmq.php
├── emailqueuerabbitmq.civix.php
├── CRM/
│   └── EmailQueueRabbitMQ/
│       ├── Form/
│       │   └── Settings.php
│       └── Page/
│           └── Settings.php
├── Civi/
│   └── EmailQueueRabbitMQ/
│       ├── Config.php
│       ├── Database.php
│       ├── EmailQueue.php
│       ├── Producer.php
│       ├── Consumer.php
│       └── EmailSender.php
├── bin/
│   ├── producer.php
│   ├── consumer.php
│   ├── monitor.php
│   └── retry_failed.php
├── templates/
│   └── CRM/
│       └── EmailQueueRabbitMQ/
│           └── Form/
│               └── Settings.tpl
├── composer.json
└── vendor/
```

## Installation

### 1. Download and Install Extension

```bash
cd /path/to/civicrm/extensions
git clone https://github.com/skvare/com.skvare.emailqueuerabbitmq.git
# or extract zip file to com.skvare.emailqueuerabbitmq directory
```

### 2. Install PHP Dependencies

```bash
cd com.skvare.emailqueuerabbitmq
composer install
```

**Required Composer Dependencies:**
```json
{
  "require": {
    "phpmailer/phpmailer": "^6.6",
    "php-amqplib/php-amqplib": "^3.2"
  }
}
```

### 3. Enable Extension in CiviCRM

1. Go to **Administer** → **System Settings** → **Extensions**
2. Find "Email Queue RabbitMQ" extension by Skvare
3. Click **Install**

### 4. Configure Extension Settings

1. Navigate to **Administer** → **System Settings** → **Email Queue RabbitMQ Settings**
2. Configure all sections:

#### RabbitMQ Connection Settings
- **Host**: localhost (or your RabbitMQ server)
- **Port**: 5672
- **Username**: your_rabbitmq_user
- **Password**: your_rabbitmq_password
- **Virtual Host**: / (default)

#### Email Queue Database Settings
- **Host**: your_queue_db_host
- **Port**: 3306 (default)
- **Database Name**: QueueDatabase
- **Username**: your_queue_db_user
- **Password**: your_queue_db_password

#### Processing Configuration
- **Batch Size**: 100 (emails processed per batch)
- **Max Retries**: 3
- **Retry Delay**: 60 seconds
- **Enable Priority-based Queues**: ✓ (for priority processing)
- **Queue Name Prefix**: skvare_email_queue
- **Consumer Timeout**: 30 seconds

#### SMTP Configuration
- **Host**: smtp.gmail.com
- **Port**: 587
- **Username**: your_email@gmail.com
- **Password**: your_app_password
- **Encryption**: TLS

#### Advanced Settings
- **Enable Debug Logging**: ✓ (for troubleshooting)
- **Enable Performance Monitoring**: ✓ (for statistics)

### 5. Test Connections

Use the built-in connection test buttons:
- **Test RabbitMQ Connection**: Verifies RabbitMQ connectivity
- **Test Database Connection**: Verifies email queue database access

## Priority-Based Queue System

### Queue Structure

When priority queues are enabled, the system creates separate queues:

- **Priority 1**: `skvare_email_queue_priority_1` (Highest - Critical emails)
- **Priority 2**: `skvare_email_queue_priority_2` (High - Important emails)
- **Priority 3**: `skvare_email_queue_priority_3` (Normal - Regular emails)
- **Priority 4**: `skvare_email_queue_priority_4` (Low - Bulk emails)
- **Default**: `skvare_email_queue_default` (When priority queues disabled)

### Email Priority Values

In your email_queue table, set the `priority` column:
- **1**: Critical (password resets, account alerts, system notifications)
- **2**: High (receipts, confirmations, transactional emails)
- **3**: Normal (newsletters, updates, notifications)
- **4**: Low (marketing emails, promotional content, bulk campaigns)

## Running Producers and Consumers

### Option 1: Single Producer/Consumer (All Priorities)

**Start General Producer:**
```bash
cd /path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
php bin/producer.php
```

**Start General Consumer:**
```bash
php bin/consumer.php
```

### Option 2: Priority-Specific Producers/Consumers

**Start Priority-Specific Producers:**
```bash
# Priority 1 (Critical) - High frequency monitoring
php bin/producer.php 1

# Priority 2 (High) - Medium frequency monitoring
php bin/producer.php 2

# Priority 3 (Normal) - Normal frequency monitoring
php bin/producer.php 3

# Priority 4 (Low) - Low frequency monitoring
php bin/producer.php 4
```

**Start Priority-Specific Consumers:**
```bash
# Priority 1 Consumer (Multiple instances for critical emails)
php bin/consumer.php 1 smtp &  # Instance 1
php bin/consumer.php 1 smtp &  # Instance 2
php bin/consumer.php 1 smtp &  # Instance 3

# Priority 2 Consumer (Multiple instances for high priority)
php bin/consumer.php 2 smtp &  # Instance 1
php bin/consumer.php 2 smtp &  # Instance 2

# Priority 3 Consumer (Standard processing)
php bin/consumer.php 3 smtp &  # Instance 1
php bin/consumer.php 3 smtp &  # Instance 2

# Priority 4 Consumer (Single instance for bulk)
php bin/consumer.php 4 smtp &  # Instance 1
```

### Email Methods

Specify email method as second parameter:
```bash
php bin/consumer.php 1 smtp     # SMTP (recommended)
php bin/consumer.php 2 sendmail # Sendmail
php bin/consumer.php 3 mail     # PHP mail() function
```

## Recommended Production Setup

### Supervisor Configuration for Skvare Extension

Create separate supervisor configs for each priority:

**Critical Priority (Priority 1) - `/etc/supervisor/conf.d/skvare-email-priority-1.conf`:**
```ini
[program:skvare-email-priority-1-producer]
command=php bin/producer.php 1
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true
stderr_logfile=/var/log/skvare-email-priority-1-producer.err.log
stdout_logfile=/var/log/skvare-email-priority-1-producer.out.log

[program:skvare-email-priority-1-consumer]
command=php bin/consumer.php 1 smtp
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true
numprocs=3
process_name=%(program_name)s_%(process_num)02d
stderr_logfile=/var/log/skvare-email-priority-1-consumer.err.log
stdout_logfile=/var/log/skvare-email-priority-1-consumer.out.log
```

**High Priority (Priority 2) - `/etc/supervisor/conf.d/skvare-email-priority-2.conf`:**
```ini
[program:skvare-email-priority-2-producer]
command=php bin/producer.php 2
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true

[program:skvare-email-priority-2-consumer]
command=php bin/consumer.php 2 smtp
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d
```

**Normal Priority (Priority 3) - `/etc/supervisor/conf.d/skvare-email-priority-3.conf`:**
```ini
[program:skvare-email-priority-3-producer]
command=php bin/producer.php 3
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true

[program:skvare-email-priority-3-consumer]
command=php bin/consumer.php 3 smtp
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d
```

**Low Priority (Priority 4) - `/etc/supervisor/conf.d/skvare-email-priority-4.conf`:**
```ini
[program:skvare-email-priority-4-producer]
command=php bin/producer.php 4
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true

[program:skvare-email-priority-4-consumer]
command=php bin/consumer.php 4 smtp
directory=/path/to/civicrm/extensions/com.skvare.emailqueuerabbitmq
user=www-data
autostart=true
autorestart=true
numprocs=1
process_name=%(program_name)s_%(process_num)02d
```

### Start Services

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start skvare-email-priority-1-producer:*
sudo supervisorctl start skvare-email-priority-1-consumer:*
sudo supervisorctl start skvare-email-priority-2-producer:*
sudo supervisorctl start skvare-email-priority-2-consumer:*
sudo supervisorctl start skvare-email-priority-3-producer:*
sudo supervisorctl start skvare-email-priority-3-consumer:*
sudo supervisorctl start skvare-email-priority-4-producer:*
sudo supervisorctl start skvare-email-priority-4-consumer:*
```

## Monitoring and Management

### Real-time Monitoring

```bash
# View comprehensive statistics
php bin/monitor.php

# Monitor specific priority queue in RabbitMQ
rabbitmqctl list_queues name messages consumers | grep skvare

# Check supervisor status
sudo supervisorctl status | grep skvare

# View live logs
tail -f /var/log/skvare-email-priority-1-consumer.out.log
```

### Example Monitor Output

```
Skvare Email Queue RabbitMQ - System Monitor
==========================================

Email Queue Statistics (Last 24 hours):
----------------------------------------------------------------------
Status       Priority   Count    Avg Retries Oldest Email
----------------------------------------------------------------------
pending      1          5        0.00        2025-07-18 14:30
processing   1          2        0.00        2025-07-18 14:32
sent         1          1247     0.15        2025-07-17 14:30
sent         2          892      0.22        2025-07-17 14:30
sent         3          15631    0.31        2025-07-17 14:30
sent         4          45123    0.18        2025-07-17 14:30
failed       3          12       2.50        2025-07-18 13:45
----------------------------------------------------------------------

Processing Performance (Last Hour):
--------------------------------------------------------------------------------
Priority   Pending  Processing Sent     Failed   Avg Time (sec)
--------------------------------------------------------------------------------
1          0        1          156      0        2.34
2          3        0          89       1        3.12
3          45       2          567      2        4.56
4          234      1          1234     5        6.78
--------------------------------------------------------------------------------

RabbitMQ Queue Status:
--------------------------------------------------
Queue Name                     Priority   Status
--------------------------------------------------
skvare_email_queue_priority_1  1          Active
skvare_email_queue_priority_2  2          Active
skvare_email_queue_priority_3  3          Active
skvare_email_queue_priority_4  4          Active
--------------------------------------------------

Monitoring completed at: 2025-07-18 15:45:32
```

### Retry Failed Emails

```bash
# Reset failed emails for retry
php bin/retry_failed.php
```

## CiviCRM Integration Points

### In your existing CiviCRM extension that captures emails:

```php
// Example integration in your email capture extension
use Civi\EmailQueueRabbitMQ\Config;

public function interceptEmail($mailer) {
    $emailData = [
        'to_email' => $mailer->getTo(),
        'subject' => $mailer->getSubject(),
        'from_email' => $mailer->getFrom(),
        'body_html' => $mailer->getBodyHtml(),
        'body_text' => $mailer->getBodyText(),
        'priority' => $this->determinePriority($mailer), // 1-4
        'status' => 'pending',
        'created_date' => date('Y-m-d H:i:s'),
        'max_retries' => Config::getProcessingSettings()['max_retries']
    ];

    $this->insertIntoQueue($emailData);

    // Prevent CiviCRM from sending immediately
    return FALSE;
}

private function determinePriority($mailer) {
    $subject = strtolower($mailer->getSubject());

    // Critical emails (Priority 1)
    if (stripos($subject, 'password reset') !== false ||
        stripos($subject, 'account locked') !== false ||
        stripos($subject, 'security alert') !== false) {
        return 1;
    }

    // High priority emails (Priority 2)
    if (stripos($subject, 'receipt') !== false ||
        stripos($subject, 'confirmation') !== false ||
        stripos($subject, 'payment') !== false) {
        return 2;
    }

    // Low priority emails (Priority 4)
    if (stripos($subject, 'newsletter') !== false ||
        stripos($subject, 'promotion') !== false ||
        stripos($subject, 'marketing') !== false ||
        stripos($subject, 'bulk') !== false) {
        return 4;
    }

    // Default to normal priority (Priority 3)
    return 3;
}
```

## Configuration Management

### Access Extension Settings Programmatically

```php
use Civi\EmailQueueRabbitMQ\Config;

// Get RabbitMQ settings
$rabbitMQConfig = Config::getRabbitMQSettings();

// Get processing settings
$processingConfig = Config::getProcessingSettings();

// Check if priority queues are enabled
if ($processingConfig['enable_priority_queues']) {
    $priorityQueues = Config::getPriorityQueues();
    // Use priority-based processing
} else {
    $defaultQueue = Config::getDefaultQueue();
    // Use single queue processing
}
```

## Performance Tuning

### For Different Volumes

**Low Volume (< 1,000 emails/day):**
- 1 Producer (all priorities)
- 2 Consumers (all priorities)
- Batch size: 50

**Medium Volume (1,000 - 10,000 emails/day):**
- 2 Producers (Priority 1-2, Priority 3-4)
- 4 Consumers (2 for Priority 1-2, 2 for Priority 3-4)
- Batch size: 100

**High Volume (10,000 - 100,000 emails/day):**
- 4 Producers (one per priority)
- 8+ Consumers (3 for Priority 1, 2 for Priority 2, 2 for Priority 3, 1 for Priority 4)
- Batch size: 200

**Enterprise Volume (> 100,000 emails/day):**
- Multiple servers with load balancing
- RabbitMQ clustering
- Database read replicas
- Horizontal scaling of consumers
- Batch size: 500

### Database Optimization

```sql
-- Optimize email_queue table
CREATE INDEX idx_skvare_priority_status ON email_queue(priority, status);
CREATE INDEX idx_skvare_status_scheduled ON email_queue(status, scheduled_date);
CREATE INDEX idx_skvare_priority_created ON email_queue(priority, created_date);

-- Partition table by date for large volumes
ALTER TABLE email_queue PARTITION BY RANGE (TO_DAYS(created_date)) (
    PARTITION p_before_2025 VALUES LESS THAN (TO_DAYS('2025-01-01')),
    PARTITION p_2025_q1 VALUES LESS THAN (TO_DAYS('2025-04-01')),
    PARTITION p_2025_q2 VALUES LESS THAN (TO_DAYS('2025-07-01')),
    PARTITION p_2025_q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
    PARTITION p_2025_q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## Troubleshooting

### Common Issues

**1. Extension Not Installing:**
```bash
# Check CiviCRM requirements
cv api System.check
# Verify extension structure
ls -la extensions/com.skvare.emailqueuerabbitmq/
```

**2. Configuration Not Saving:**
- Check CiviCRM permissions for admin user
- Verify settings table access
- Check PHP error logs

**3. Connection Test Failures:**
```bash
# Test RabbitMQ manually
telnet localhost 5672

# Test database manually
mysql -h host -P port -u user -p database_name
```

**4. Consumers Not Processing:**
```bash
# Check RabbitMQ queues
rabbitmqctl list_queues | grep skvare

# Check consumer processes
ps aux | grep "consumer.php"

# Test database connection from extension
php -r "
require_once 'vendor/autoload.php';
require_once 'civicrm.config.php';
require_once 'CRM/Core/Config.php';
CRM_Core_Config::singleton();
use Civi\EmailQueueRabbitMQ\Database;
try {
    \$db = Database::getInstance();
    echo 'Database connection successful';
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage();
}
"
```

**5. Emails Stuck in Processing:**
```sql
-- Reset stuck emails (run from email queue database)
UPDATE email_queue
SET status = 'pending'
WHERE status = 'processing'
AND created_date < DATE_SUB(NOW(), INTERVAL 30 MINUTE);
```

### Debug Mode

Enable debug logging in extension settings:
- **Enable Debug Logging**: ✓
- **Enable Performance Monitoring**: ✓

Check CiviCRM logs:
```bash
tail -f /path/to/civicrm/ConfigAndLog/CiviCRM.emailqueue_rabbitmq.log
```

## Security Considerations

1. **Database Access**: Use dedicated user with minimal privileges
2. **SMTP Credentials**: Use app passwords, not account passwords
3. **File Permissions**: Restrict config file access (`chmod 600`)
4. **Network Security**: Use SSL/TLS for all connections
5. **RabbitMQ Security**: Use dedicated vhost and user
6. **Monitoring**: Set up alerts for failed emails and system issues

## Updates and Maintenance

### Updating Extension

```bash
cd extensions/com.skvare.emailqueuerabbitmq
git pull origin main
composer update
# Clear CiviCRM cache
cv flush
```

### Database Maintenance

```sql
-- Clean old logs (run monthly)
DELETE FROM email_queue_log
WHERE created_date < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Archive old sent emails (run quarterly)
CREATE TABLE email_queue_archive LIKE email_queue;

INSERT INTO email_queue_archive
SELECT * FROM email_queue
WHERE status = 'sent'
AND sent_date < DATE_SUB(NOW(), INTERVAL 365 DAY);

DELETE FROM email_queue
WHERE status = 'sent'
AND sent_date < DATE_SUB(NOW(), INTERVAL 365 DAY);
```

### Performance Monitoring

Set up monitoring alerts for:
- Queue depth exceeding thresholds
- Failed email rates above 5%
- Consumer processes down
- Database connection issues
- RabbitMQ connection issues

## Support and Community

- **Extension Author**: Skvare
- **Email**: info@skvare.com
- **Website**: https://skvare.com
- **Documentation**: https://skvare.com/extensions
- **Issues**: Submit via CiviCRM extension directory or GitHub

This Skvare Email Queue RabbitMQ extension provides a robust, scalable email queue solution that integrates seamlessly with CiviCRM while offering priority-based processing for optimal email delivery performance and reliability.
