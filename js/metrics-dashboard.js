(function($) {
  'use strict';

  let dashboard = {
    charts: {},
    refreshInterval: null,
    isPaused: false,
    currentRefreshRate: 10,
    currentTimeRange: '1h',
    historicalData: {
      messageFlow: [],
      queueDepth: [],
      memory: [],
      connections: []
    },

    init: function() {
      this.setupEventHandlers();
      this.initializeCharts();
      this.loadInitialData();
      this.startAutoRefresh();
    },

    setupEventHandlers: function() {
      const self = this;

      $('#timeRange').on('change', function() {
        self.currentTimeRange = $(this).val();
        self.refreshData();
      });

      $('#refreshRate').on('change', function() {
        self.currentRefreshRate = parseInt($(this).val());
        self.restartAutoRefresh();
      });

      $('#pauseRefresh').on('click', function() {
        self.togglePause();
      });

      $('#refreshNow').on('click', function() {
        self.refreshData();
      });
    },

    initializeCharts: function() {
      if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        this.showError('Chart.js library not loaded. Please refresh the page.');
        return;
      }
      Chart.defaults.color = '#666';
      Chart.defaults.borderColor = '#e0e0e0';
      Chart.defaults.backgroundColor = 'rgba(52, 152, 219, 0.1)';

      // Message Flow Chart
      this.charts.messageFlow = new Chart(document.getElementById('messageFlowChart'), {
        type: 'line',
        data: {
          labels: [],
          datasets: [
            {
              label: 'Publish Rate',
              data: [],
              borderColor: '#3498db',
              backgroundColor: 'rgba(52, 152, 219, 0.1)',
              tension: 0.4
            },
            {
              label: 'Deliver Rate',
              data: [],
              borderColor: '#2ecc71',
              backgroundColor: 'rgba(46, 204, 113, 0.1)',
              tension: 0.4
            },
            {
              label: 'Ack Rate',
              data: [],
              borderColor: '#e74c3c',
              backgroundColor: 'rgba(231, 76, 60, 0.1)',
              tension: 0.4
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              type: 'time',
              time: {
                unit: 'minute'
              },
              title: {
                display: true,
                text: 'Time'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Messages/sec'
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' msg/sec';
                }
              }
            }
          }
        }
      });

      // Queue Depth Chart
      this.charts.queueDepth = new Chart(document.getElementById('queueDepthChart'), {
        type: 'bar',
        data: {
          labels: [],
          datasets: [
            {
              label: 'Messages Ready',
              data: [],
              backgroundColor: '#3498db'
            },
            {
              label: 'Messages Unacked',
              data: [],
              backgroundColor: '#e74c3c'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: {
                display: true,
                text: 'Queues'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Message Count'
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                }
              }
            }
          }
        }
      });

      // Memory Chart
      this.charts.memory = new Chart(document.getElementById('memoryChart'), {
        type: 'line',
        data: {
          labels: [],
          datasets: [
            {
              label: 'Memory Used (MB)',
              data: [],
              borderColor: '#9b59b6',
              backgroundColor: 'rgba(155, 89, 182, 0.1)',
              tension: 0.4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              type: 'time',
              time: {
                unit: 'minute'
              },
              title: {
                display: true,
                text: 'Time'
              }
            },
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Memory (MB)'
              }
            }
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  return 'Memory: ' + context.parsed.y.toFixed(1) + ' MB';
                }
              }
            }
          }
        }
      });

      // Connection Chart
      this.charts.connections = new Chart(document.getElementById('connectionChart'), {
        type: 'doughnut',
        data: {
          labels: ['Active Connections', 'Available'],
          datasets: [{
            data: [0, 100],
            backgroundColor: ['#3498db', '#ecf0f1'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.label + ': ' + context.parsed;
                }
              }
            }
          }
        }
      });
    },

    loadInitialData: function() {
      if (window.rabbitmqMetrics && window.rabbitmqMetrics.initialData) {
        this.updateDashboard(window.rabbitmqMetrics.initialData);
      }
    },

    refreshData: function() {
      const self = this;

      $.ajax({
        url: window.rabbitmqMetrics.ajaxUrl,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
          self.updateDashboard(data);
        },
        error: function(xhr, status, error) {
          console.error('Failed to fetch metrics:', error);
          self.showError('Failed to fetch metrics data');
        }
      });
    },

    updateDashboard: function(data) {
      if (data.error) {
        this.showError(data.error);
        return;
      }

      this.updateStatusCards(data);
      this.updateCharts(data);
      this.updateTables(data);
      this.updateTimestamp();
    },

    updateStatusCards: function(data) {
      const overview = data.overview || {};
      const nodes = data.nodes || [];
      const connections = data.connections || [];
      const channels = data.channels || [];
      const queues = data.queues || [];

      // System Health
      const healthyNodes = nodes.filter(n => n.running).length;
      const totalNodes = nodes.length;
      let healthStatus = 'healthy';

      if (totalNodes === 0 || healthyNodes === 0) {
        healthStatus = 'offline';
      } else if (healthyNodes < totalNodes) {
        healthStatus = 'warning';
      }

      this.updateStatusIndicator('healthStatus', healthStatus);
      this.updateValue('nodeCount', totalNodes);
      this.updateValue('connectionCount', connections.length);
      this.updateValue('channelCount', channels.length);

      // Message Rates
      const messageStats = overview.message_stats || {};
      this.updateValue('publishRate', this.formatRate(messageStats.publish_details));
      this.updateValue('deliverRate', this.formatRate(messageStats.deliver_details));
      this.updateValue('ackRate', this.formatRate(messageStats.ack_details));

      // Queue Summary
      const totalMessages = queues.reduce((sum, q) => sum + (q.messages || 0), 0);
      const totalReady = queues.reduce((sum, q) => sum + (q.messages_ready || 0), 0);
      const totalUnacked = queues.reduce((sum, q) => sum + (q.messages_unacknowledged || 0), 0);

      this.updateValue('queueCount', queues.length);
      this.updateValue('messagesReady', totalReady.toLocaleString());
      this.updateValue('messagesUnacked', totalUnacked.toLocaleString());

      // Memory Usage
      const totalMemory = nodes.reduce((sum, n) => sum + (n.mem_used || 0), 0);
      const totalMemoryLimit = nodes.reduce((sum, n) => sum + (n.mem_limit || 0), 0);
      const memoryPercent = totalMemoryLimit > 0 ? (totalMemory / totalMemoryLimit * 100) : 0;

      this.updateValue('memoryUsed', this.formatBytes(totalMemory));
      this.updateValue('memoryLimit', this.formatBytes(totalMemoryLimit));
      this.updateValue('memoryPercent', memoryPercent.toFixed(1) + '%');
    },

    updateCharts: function(data) {
      // If charts are not initialized, skip chart updates
      if (!this.charts.messageFlow) {
        console.log('Charts not initialized, skipping chart updates');
        return;
      }

      const timestamp = new Date(data.timestamp || Date.now());
      const overview = data.overview || {};
      const messageStats = overview.message_stats || {};
      const queues = data.queues || [];
      const nodes = data.nodes || [];

      // Update historical data
      this.historicalData.messageFlow.push({
        x: timestamp,
        publish: this.getRate(messageStats.publish_details),
        deliver: this.getRate(messageStats.deliver_details),
        ack: this.getRate(messageStats.ack_details)
      });

      // Keep only recent data based on time range
      this.trimHistoricalData();

      // Update Message Flow Chart
      const messageFlowData = this.historicalData.messageFlow;
      this.charts.messageFlow.data.labels = messageFlowData.map(d => d.x);
      this.charts.messageFlow.data.datasets[0].data = messageFlowData.map(d => ({x: d.x, y: d.publish}));
      this.charts.messageFlow.data.datasets[1].data = messageFlowData.map(d => ({x: d.x, y: d.deliver}));
      this.charts.messageFlow.data.datasets[2].data = messageFlowData.map(d => ({x: d.x, y: d.ack}));
      this.charts.messageFlow.update('none');

      // Update Queue Depth Chart
      const topQueues = queues.slice(0, 10); // Show top 10 queues
      this.charts.queueDepth.data.labels = topQueues.map(q => q.name || 'Unknown');
      this.charts.queueDepth.data.datasets[0].data = topQueues.map(q => q.messages_ready || 0);
      this.charts.queueDepth.data.datasets[1].data = topQueues.map(q => q.messages_unacknowledged || 0);
      this.charts.queueDepth.update('none');

      // Update Memory Chart
      const totalMemory = nodes.reduce((sum, n) => sum + (n.mem_used || 0), 0) / (1024 * 1024); // Convert to MB
      this.historicalData.memory.push({
        x: timestamp,
        y: totalMemory
      });

      const memoryData = this.historicalData.memory;
      this.charts.memory.data.labels = memoryData.map(d => d.x);
      this.charts.memory.data.datasets[0].data = memoryData;
      this.charts.memory.update('none');

      // Update Connection Chart
      const totalConnections = (data.connections || []).length;
      const maxConnections = Math.max(100, totalConnections + 50); // Assume max connections
      this.charts.connections.data.datasets[0].data = [totalConnections, maxConnections - totalConnections];
      this.charts.connections.update('none');
    },

    updateTables: function(data) {
      const nodes = data.nodes || [];
      const queues = data.queues || [];

      // Update Node Table
      const nodeTableBody = $('#nodeTable tbody');
      nodeTableBody.empty();

      if (nodes.length === 0) {
        nodeTableBody.append('<tr><td colspan="6" class="text-center">No nodes found</td></tr>');
      } else {
        nodes.forEach(node => {
          const status = node.running ? 'running' : 'disc';
          const statusBadge = `<span class="status-badge ${status}">${status}</span>`;
          const uptime = this.formatUptime(node.uptime || 0);

          nodeTableBody.append(`
            <tr>
              <td>${node.name || 'Unknown'}</td>
              <td>${statusBadge}</td>
              <td>${node.type || 'disc'}</td>
              <td>${this.formatBytes(node.mem_used || 0)}</td>
              <td>${this.formatBytes(node.disk_free || 0)}</td>
              <td>${uptime}</td>
            </tr>
          `);
        });
      }

      // Update Queue Table
      const queueTableBody = $('#queueTable tbody');
      queueTableBody.empty();

      if (queues.length === 0) {
        queueTableBody.append('<tr><td colspan="7" class="text-center">No queues found</td></tr>');
      } else {
        queues.forEach(queue => {
          const messageStats = queue.message_stats || {};

          queueTableBody.append(`
            <tr>
              <td>${queue.name || 'Unknown'}</td>
              <td>${(queue.messages || 0).toLocaleString()}</td>
              <td>${(queue.messages_ready || 0).toLocaleString()}</td>
              <td>${(queue.messages_unacknowledged || 0).toLocaleString()}</td>
              <td>${this.formatRate(messageStats.publish_details)}</td>
              <td>${this.formatRate(messageStats.deliver_details)}</td>
              <td>${this.formatBytes(queue.memory || 0)}</td>
            </tr>
          `);
        });
      }
    },

    trimHistoricalData: function() {
      const now = Date.now();
      const timeRanges = {
        '5m': 5 * 60 * 1000,
        '15m': 15 * 60 * 1000,
        '1h': 60 * 60 * 1000,
        '6h': 6 * 60 * 60 * 1000,
        '24h': 24 * 60 * 60 * 1000,
        '7d': 7 * 24 * 60 * 60 * 1000
      };

      const cutoff = now - (timeRanges[this.currentTimeRange] || timeRanges['1h']);

      Object.keys(this.historicalData).forEach(key => {
        this.historicalData[key] = this.historicalData[key].filter(item =>
          new Date(item.x).getTime() > cutoff
        );
      });
    },

    updateStatusIndicator: function(elementId, status) {
      const element = $('#' + elementId);
      const dot = element.find('.status-dot');
      const text = element.find('.status-text');

      dot.removeClass('healthy warning critical offline').addClass(status);
      text.text(window.rabbitmqMetrics.translations[status] || status);
    },

    updateValue: function(elementId, value) {
      const element = $('#' + elementId);
      if (element.text() !== value.toString()) {
        element.text(value).addClass('value-updated');
        setTimeout(() => element.removeClass('value-updated'), 500);
      }
    },

    updateTimestamp: function() {
      $('#lastUpdate').text(new Date().toLocaleTimeString());
    },

    startAutoRefresh: function() {
      const self = this;
      this.stopAutoRefresh();

      if (!this.isPaused) {
        this.refreshInterval = setInterval(function() {
          self.refreshData();
        }, this.currentRefreshRate * 1000);
      }
    },

    stopAutoRefresh: function() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval);
        this.refreshInterval = null;
      }
    },

    restartAutoRefresh: function() {
      this.startAutoRefresh();
    },

    togglePause: function() {
      this.isPaused = !this.isPaused;
      const button = $('#pauseRefresh');

      if (this.isPaused) {
        button.text(window.rabbitmqMetrics.translations.running || 'Resume')
              .removeClass('btn-secondary').addClass('btn-secondary paused');
        this.stopAutoRefresh();
      } else {
        button.text(window.rabbitmqMetrics.translations.paused || 'Pause')
              .removeClass('paused');
        this.startAutoRefresh();
      }
    },

    showError: function(message) {
      const errorHtml = `<div class="error-message">${message}</div>`;
      $('.rabbitmq-metrics-dashboard').prepend(errorHtml);
      setTimeout(() => $('.error-message').fadeOut(), 5000);
    },

    formatRate: function(rateDetails) {
      if (!rateDetails || !rateDetails.rate) {
        return '0.0/s';
      }
      return rateDetails.rate.toFixed(1) + '/s';
    },

    getRate: function(rateDetails) {
      return (rateDetails && rateDetails.rate) || 0;
    },

    formatBytes: function(bytes) {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    formatUptime: function(milliseconds) {
      const seconds = Math.floor(milliseconds / 1000);
      const days = Math.floor(seconds / (3600 * 24));
      const hours = Math.floor((seconds % (3600 * 24)) / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);

      if (days > 0) {
        return `${days}d ${hours}h ${minutes}m`;
      } else if (hours > 0) {
        return `${hours}h ${minutes}m`;
      } else {
        return `${minutes}m`;
      }
    }
  };

  // Function to check if Chart.js is loaded and initialize
  let initializationAttempts = 0;
  const maxAttempts = 50; // Maximum 5 seconds wait time

  function initializeDashboard() {
    initializationAttempts++;

    if (typeof Chart !== 'undefined') {
      dashboard.init();
    } else if (initializationAttempts < maxAttempts) {
      // Chart.js not loaded yet, wait a bit and try again
      setTimeout(initializeDashboard, 100);
    } else {
      // Timeout reached, show error message
      console.error('Chart.js failed to load after 5 seconds');
      dashboard.showError('Chart.js library failed to load. Please check your internet connection and refresh the page.');

      // Initialize dashboard without charts
      dashboard.setupEventHandlers();
      dashboard.loadInitialData();
      dashboard.startAutoRefresh();
    }
  }

  // Initialize dashboard when DOM is ready
  $(document).ready(function() {
    initializeDashboard();
  });

})(CRM.$);
