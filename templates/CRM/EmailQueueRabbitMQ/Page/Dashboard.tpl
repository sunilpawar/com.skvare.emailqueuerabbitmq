<div class="rabbitmq-metrics-dashboard">
  <div class="crm-container">
    {* Time Range Controls *}
    <div class="metrics-controls">
      <div class="control-group">
        <label for="timeRange">{ts}Time Range:{/ts}</label>
        <select id="timeRange" class="form-control">
          <option value="5m">{ts}Last 5 minutes{/ts}</option>
          <option value="15m">{ts}Last 15 minutes{/ts}</option>
          <option value="1h" selected>{ts}Last hour{/ts}</option>
          <option value="6h">{ts}Last 6 hours{/ts}</option>
          <option value="24h">{ts}Last 24 hours{/ts}</option>
          <option value="7d">{ts}Last 7 days{/ts}</option>
        </select>
      </div>

      <div class="control-group">
        <label for="refreshRate">{ts}Refresh Rate:{/ts}</label>
        <select id="refreshRate" class="form-control">
          <option value="5">{ts}5 seconds{/ts}</option>
          <option value="10" selected>{ts}10 seconds{/ts}</option>
          <option value="30">{ts}30 seconds{/ts}</option>
          <option value="60">{ts}1 minute{/ts}</option>
          <option value="300">{ts}5 minutes{/ts}</option>
        </select>
      </div>

      <div class="control-group">
        <button id="pauseRefresh" class="btn btn-secondary">{ts}Pause{/ts}</button>
        <button id="refreshNow" class="btn btn-primary">{ts}Refresh Now{/ts}</button>
      </div>
    </div>

    {* Health Status Cards *}
    <div class="metrics-grid">
      <div class="metric-card status-card">
        <div class="metric-header">
          <h3>{ts}System Health{/ts}</h3>
          <div id="healthStatus" class="status-indicator">
            <span class="status-dot"></span>
            <span class="status-text">Loading...</span>
          </div>
        </div>
        <div class="metric-details">
          <div class="detail-item">
            <span class="label">{ts}Nodes:{/ts}</span>
            <span id="nodeCount" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Connections:{/ts}</span>
            <span id="connectionCount" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Channels:{/ts}</span>
            <span id="channelCount" class="value">-</span>
          </div>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-header">
          <h3>{ts}Message Rates{/ts}</h3>
        </div>
        <div class="metric-details">
          <div class="detail-item">
            <span class="label">{ts}Publish Rate:{/ts}</span>
            <span id="publishRate" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Deliver Rate:{/ts}</span>
            <span id="deliverRate" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Ack Rate:{/ts}</span>
            <span id="ackRate" class="value">-</span>
          </div>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-header">
          <h3>{ts}Queue Summary{/ts}</h3>
        </div>
        <div class="metric-details">
          <div class="detail-item">
            <span class="label">{ts}Total Queues:{/ts}</span>
            <span id="queueCount" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Messages Ready:{/ts}</span>
            <span id="messagesReady" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Messages Unacked:{/ts}</span>
            <span id="messagesUnacked" class="value">-</span>
          </div>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-header">
          <h3>{ts}Memory Usage{/ts}</h3>
        </div>
        <div class="metric-details">
          <div class="detail-item">
            <span class="label">{ts}Used Memory:{/ts}</span>
            <span id="memoryUsed" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Memory Limit:{/ts}</span>
            <span id="memoryLimit" class="value">-</span>
          </div>
          <div class="detail-item">
            <span class="label">{ts}Usage %:{/ts}</span>
            <span id="memoryPercent" class="value">-</span>
          </div>
        </div>
      </div>
    </div>

    {* Charts Section *}
    <div class="charts-grid">
      <div class="chart-container">
        <h3>{ts}Message Flow{/ts}</h3>
        <canvas id="messageFlowChart"></canvas>
      </div>

      <div class="chart-container">
        <h3>{ts}Queue Depths{/ts}</h3>
        <canvas id="queueDepthChart"></canvas>
      </div>

      <div class="chart-container">
        <h3>{ts}Memory Usage{/ts}</h3>
        <canvas id="memoryChart"></canvas>
      </div>

      <div class="chart-container">
        <h3>{ts}Connection Count{/ts}</h3>
        <canvas id="connectionChart"></canvas>
      </div>
    </div>

    {* Node Details Table *}
    <div class="node-details">
      <h3>{ts}Node Details{/ts}</h3>
      <div class="table-responsive">
        <table id="nodeTable" class="table table-striped">
          <thead>
            <tr>
              <th>{ts}Node{/ts}</th>
              <th>{ts}Status{/ts}</th>
              <th>{ts}Type{/ts}</th>
              <th>{ts}Memory{/ts}</th>
              <th>{ts}Disk Free{/ts}</th>
              <th>{ts}Uptime{/ts}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="6" class="text-center">{ts}Loading...{/ts}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    {* Queue Details Table *}
    <div class="queue-details">
      <h3>{ts}Queue Details{/ts}</h3>
      <div class="table-responsive">
        <table id="queueTable" class="table table-striped">
          <thead>
            <tr>
              <th>{ts}Queue{/ts}</th>
              <th>{ts}Messages{/ts}</th>
              <th>{ts}Ready{/ts}</th>
              <th>{ts}Unacked{/ts}</th>
              <th>{ts}Publish Rate{/ts}</th>
              <th>{ts}Deliver Rate{/ts}</th>
              <th>{ts}Memory{/ts}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="7" class="text-center">{ts}Loading...{/ts}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    {* Last Update Info *}
    <div class="last-update">
      <small>{ts}Last updated:{/ts} <span id="lastUpdate">{$currentTime}</span></small>
    </div>
  </div>
</div>

<script type="text/javascript">
  {literal}
  // Pass initial data to JavaScript
  window.rabbitmqMetrics = {
    initialData: {/literal}{$initialMetrics}{literal},
    ajaxUrl: '{crmURL p="civicrm/emailqueue/rabbitmq-dashboard" q="action=getMetrics"}',
    translations: {
      loading: 'Loading...',
      error: 'Error loading data',
      healthy: 'Healthy',
      warning: 'Warning',
      critical: 'Critical',
      offline: 'Offline',
      paused: 'Paused',
      running: 'Running'
    }
  };
  {/literal}
</script>
