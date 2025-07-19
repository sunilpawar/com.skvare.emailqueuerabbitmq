<div class="crm-form-block crm-emailqueue-rabbitmq-settings-form-block">

  <div class="help">
    <p>{ts}Configure the Skvare Email Queue RabbitMQ system for high-volume email processing with priority-based queues.{/ts}</p>
  </div>

  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {ts}RabbitMQ Connection Settings{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr>
          <td class="label">{$form.skvare_emailqueue_rabbitmq_host.label}</td>
          <td>{$form.skvare_emailqueue_rabbitmq_host.html}
            <div class="description">{ts}RabbitMQ server hostname or IP address{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_rabbitmq_port.label}</td>
          <td>{$form.skvare_emailqueue_rabbitmq_port.html}
            <div class="description">{ts}Default: 5672{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_rabbitmq_user.label}</td>
          <td>{$form.skvare_emailqueue_rabbitmq_user.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_rabbitmq_pass.label}</td>
          <td>{$form.skvare_emailqueue_rabbitmq_pass.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_rabbitmq_vhost.label}</td>
          <td>{$form.skvare_emailqueue_rabbitmq_vhost.html}
            <div class="description">{ts}Default: /{/ts}</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {ts}Email Queue Database Settings{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr>
          <td class="label">{$form.skvare_emailqueue_db_host.label}</td>
          <td>{$form.skvare_emailqueue_db_host.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_db_port.label}</td>
          <td>{$form.skvare_emailqueue_db_port.html}
            <div class="description">{ts}Default: 3306{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_db_name.label}</td>
          <td>{$form.skvare_emailqueue_db_name.html}
            <div class="description">{ts}Database containing email_queue tables{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_db_user.label}</td>
          <td>{$form.skvare_emailqueue_db_user.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_db_pass.label}</td>
          <td>{$form.skvare_emailqueue_db_pass.html}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {ts}Processing Configuration{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr>
          <td class="label">{$form.skvare_emailqueue_batch_size.label}</td>
          <td>{$form.skvare_emailqueue_batch_size.html}
            <div class="description">{ts}Number of emails to process per batch{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_max_retries.label}</td>
          <td>{$form.skvare_emailqueue_max_retries.html}
            <div class="description">{ts}Maximum retry attempts for failed emails{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_retry_delay.label}</td>
          <td>{$form.skvare_emailqueue_retry_delay.html}
            <div class="description">{ts}Delay between retry attempts in seconds{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_enable_priority_queues.label}</td>
          <td>{$form.skvare_emailqueue_enable_priority_queues.html}
            <div class="description">{ts}Enable separate queues for different priority levels (1=Highest, 4=Lowest){/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_queue_prefix.label}</td>
          <td>{$form.skvare_emailqueue_queue_prefix.html}
            <div class="description">{ts}Prefix for RabbitMQ queue names{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_consumer_timeout.label}</td>
          <td>{$form.skvare_emailqueue_consumer_timeout.html}
            <div class="description">{ts}Consumer timeout in seconds{/ts}</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {ts}SMTP Configuration{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr>
          <td class="label">{$form.skvare_emailqueue_smtp_host.label}</td>
          <td>{$form.skvare_emailqueue_smtp_host.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_smtp_port.label}</td>
          <td>{$form.skvare_emailqueue_smtp_port.html}
            <div class="description">{ts}Common ports: 25, 465 (SSL), 587 (TLS){/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_smtp_user.label}</td>
          <td>{$form.skvare_emailqueue_smtp_user.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_smtp_pass.label}</td>
          <td>{$form.skvare_emailqueue_smtp_pass.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_smtp_encryption.label}</td>
          <td>{$form.skvare_emailqueue_smtp_encryption.html}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {ts}Advanced Settings{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr>
          <td class="label">{$form.skvare_emailqueue_enable_logging.label}</td>
          <td>{$form.skvare_emailqueue_enable_logging.html}
            <div class="description">{ts}Enable detailed logging for debugging{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.skvare_emailqueue_enable_monitoring.label}</td>
          <td>{$form.skvare_emailqueue_enable_monitoring.html}
            <div class="description">{ts}Enable performance monitoring and statistics{/ts}</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

{literal}
  <script>
    CRM.$(function($) {
      // Initialize accordions
      $('.crm-accordion-wrapper').each(function() {
        $(this).crmAccordions();
      });

      // Add tooltips for better UX
      $('[title]').tooltip();
    });
  </script>
{/literal}

<style>
  {literal}
  .crm-emailqueue-rabbitmq-settings-form-block .description {
    font-size: 0.9em;
    color: #666;
    font-style: italic;
    margin-top: 2px;
  }

  .crm-emailqueue-rabbitmq-settings-form-block .help {
    background: #f0f8ff;
    border: 1px solid #b8daff;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 20px;
  }

  .crm-emailqueue-rabbitmq-settings-form-block .crm-accordion-header {
    background: #2c5282;
    color: white;
    font-weight: bold;
  }
  {/literal}
</style>
