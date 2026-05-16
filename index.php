<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Anomaly detection tool — upload CSV data, detect outliers with Z-Score or Moving Average methods, and visualize results with interactive D3.js charts.">
  <title>Anomaly Detector</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- D3.js v7 -->
  <script src="https://d3js.org/d3.v7.min.js"></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- ===== NAVBAR ===== -->
  <nav class="navbar navbar-custom sticky-top">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="bi bi-activity"></i> Anomaly Detector
      </a>
      <span class="text-secondary" style="font-size:0.8rem;">
      </span>
    </div>
  </nav>

  <!-- ===== MAIN CONTENT ===== -->
  <div class="container py-4">
    <div class="row g-4">

      <!-- ===== LEFT PANEL — CONTROLS ===== -->
      <div class="col-lg-4">

        <!-- Data Input Card -->
        <div class="glass-card fade-up mb-4" id="input-card">
          <div class="card-title-custom">
            <i class="bi bi-cloud-arrow-up"></i> Data Input
          </div>

          <!-- Tab nav for upload vs paste -->
          <ul class="nav nav-pills nav-fill mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-upload" data-bs-toggle="pill" data-bs-target="#pane-upload" type="button" role="tab" style="font-size:0.82rem;">
                <i class="bi bi-file-earmark-arrow-up"></i> Upload CSV
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-paste" data-bs-toggle="pill" data-bs-target="#pane-paste" type="button" role="tab" style="font-size:0.82rem;">
                <i class="bi bi-clipboard-data"></i> Paste Data
              </button>
            </li>
          </ul>

          <div class="tab-content">
            <!-- Upload Tab -->
            <div class="tab-pane fade show active" id="pane-upload" role="tabpanel">
              <div class="mb-3">
                <label for="csvFile" class="form-label">Choose CSV file</label>
                <input class="form-control" type="file" id="csvFile" accept=".csv">
              </div>
              <div id="file-info" class="mb-2" style="font-size:0.8rem;color:var(--text-secondary);"></div>
              <div class="mb-3" id="column-select-wrap" style="display:none;">
                <label for="columnSelect" class="form-label">Numeric Column</label>
                <select class="form-select" id="columnSelect"></select>
              </div>
            </div>

            <!-- Paste Tab -->
            <div class="tab-pane fade" id="pane-paste" role="tabpanel">
              <div class="mb-3">
                <label for="pasteData" class="form-label">Comma-separated values</label>
                <textarea class="form-control" id="pasteData" rows="5" placeholder="e.g. 120, 135, 128, 900, 140, 132, 3, 138..."></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Configuration Card -->
        <div class="glass-card fade-up mb-4" id="config-card">
          <div class="card-title-custom">
            <i class="bi bi-sliders"></i> Detection Settings
          </div>

          <div class="mb-3">
            <label for="methodSelect" class="form-label">Algorithm</label>
            <select class="form-select" id="methodSelect">
              <option value="zscore" selected>Z-Score (Standard Deviation)</option>
              <option value="moving_average">Moving Average</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="thresholdInput" class="form-label">
              Threshold: <strong id="thresholdDisplay">2.0</strong>
            </label>
            <input type="range" class="threshold-slider" id="thresholdInput" min="0.5" max="5" step="0.1" value="2.0">
            <div class="d-flex justify-content-between" style="font-size:0.7rem;color:var(--text-secondary);">
              <span>More sensitive</span><span>Less sensitive</span>
            </div>
          </div>

          <div class="mb-3" id="window-wrap">
            <label for="windowInput" class="form-label">Moving Avg Window</label>
            <input type="number" class="form-control" id="windowInput" value="5" min="2" max="20">
          </div>

          <button class="btn btn-accent w-100" id="runBtn" disabled>
            <i class="bi bi-play-fill"></i> Run Detection
          </button>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3" id="stats-row" style="display:none;">
          <div class="col-6">
            <div class="stat-card fade-up">
              <div class="stat-value text-cyan" id="stat-total">—</div>
              <div class="stat-label">Total Points</div>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-card fade-up">
              <div class="stat-value text-rose" id="stat-anomalies">—</div>
              <div class="stat-label">Anomalies</div>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-card fade-up">
              <div class="stat-value text-indigo" id="stat-mean">—</div>
              <div class="stat-label">Mean</div>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-card fade-up">
              <div class="stat-value text-amber" id="stat-std">—</div>
              <div class="stat-label">Std Dev</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== RIGHT PANEL — VISUALIZATION ===== -->
      <div class="col-lg-8">

        <!-- Chart Card -->
        <div class="glass-card fade-up mb-4" id="chart-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="card-title-custom mb-0">
              <i class="bi bi-graph-up"></i> Anomaly Visualization
            </div>
            <div id="chart-legend" style="display:none;font-size:0.78rem;">
              <span style="color:var(--normal-point);">&#9679;</span> Normal&nbsp;&nbsp;
              <span style="color:var(--anomaly-point);">&#9679;</span> Anomaly&nbsp;&nbsp;
              <span style="color:var(--warning);">- -</span> Mean
            </div>
          </div>

          <div id="chart-container">
            <!-- Live threshold slider inside chart -->
            <div id="live-slider-wrap" style="display:none;" class="mb-2">
              <div class="d-flex align-items-center gap-2" style="font-size:0.82rem;">
                <span class="text-secondary">Threshold:</span>
                <input type="range" class="threshold-slider flex-grow-1" id="liveThreshold" min="0.5" max="5" step="0.1" value="2.0">
                <strong id="liveThresholdVal" style="min-width:2.2rem;">2.0</strong>
              </div>
            </div>

            <div id="chart">
              <div class="empty-state">
                <i class="bi bi-bar-chart-line"></i>
                <p>Upload or paste data to begin anomaly detection</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Data Table Card -->
        <div class="glass-card fade-up" id="table-card" style="display:none;">
          <div class="card-title-custom">
            <i class="bi bi-table"></i> Detection Results
          </div>
          <div class="table-responsive" style="max-height:350px;overflow-y:auto;">
            <table class="table table-dark-custom table-sm" id="results-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Value</th>
                  <th>Z-Score</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="results-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- D3 Tooltip -->
  <div class="d3-tooltip" id="tooltip"></div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- App JS -->
  <script src="app.js"></script>
</body>
</html>
