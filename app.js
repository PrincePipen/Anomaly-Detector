/**
 * Anomaly Detector — Front-End Application
 * D3.js v7 scatter-plot visualization with interactive anomaly detection.
 */

(() => {
  'use strict';

  // ── DOM refs ──
  const csvFileInput     = document.getElementById('csvFile');
  const pasteDataInput   = document.getElementById('pasteData');
  const columnSelect     = document.getElementById('columnSelect');
  const columnWrap       = document.getElementById('column-select-wrap');
  const fileInfo         = document.getElementById('file-info');
  const methodSelect     = document.getElementById('methodSelect');
  const thresholdInput   = document.getElementById('thresholdInput');
  const thresholdDisplay = document.getElementById('thresholdDisplay');
  const windowInput      = document.getElementById('windowInput');
  const windowWrap       = document.getElementById('window-wrap');
  const runBtn           = document.getElementById('runBtn');
  const statsRow         = document.getElementById('stats-row');
  const chartContainer   = document.getElementById('chart');
  const liveSliderWrap   = document.getElementById('live-slider-wrap');
  const liveThreshold    = document.getElementById('liveThreshold');
  const liveThresholdVal = document.getElementById('liveThresholdVal');
  const chartLegend      = document.getElementById('chart-legend');
  const tableCard        = document.getElementById('table-card');
  const resultsTbody     = document.getElementById('results-tbody');
  const tooltip          = document.getElementById('tooltip');

  // ── State ──
  let uploadedData = null;   // full parsed CSV rows
  let numericData  = null;   // current numeric array for detection
  let lastResults  = null;   // last detection response

  // ── Init ──
  windowWrap.style.display = 'none';

  // ── Event listeners ──
  thresholdInput.addEventListener('input', () => {
    thresholdDisplay.textContent = parseFloat(thresholdInput.value).toFixed(1);
  });

  methodSelect.addEventListener('change', () => {
    windowWrap.style.display = methodSelect.value === 'moving_average' ? '' : 'none';
  });

  csvFileInput.addEventListener('change', handleFileUpload);
  pasteDataInput.addEventListener('input', handlePasteInput);
  runBtn.addEventListener('click', runDetection);

  liveThreshold.addEventListener('input', () => {
    liveThresholdVal.textContent = parseFloat(liveThreshold.value).toFixed(1);
    if (numericData) runDetection();
  });

  // ── File upload handler ──
  async function handleFileUpload() {
    const file = csvFileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('csvfile', file);

    fileInfo.innerHTML = '<i class="bi bi-arrow-repeat spin-icon"></i> Parsing...';

    try {
      const res  = await fetch('upload.php', { method: 'POST', body: formData });
      const json = await res.json();

      if (json.error) { fileInfo.textContent = json.error; return; }

      uploadedData = json.data;
      fileInfo.innerHTML = `<i class="bi bi-check-circle" style="color:var(--success);"></i> ${json.row_count} rows · ${json.columns.length} columns`;

      // Populate numeric column selector
      columnSelect.innerHTML = '';
      json.numeric_columns.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c; opt.textContent = c;
        columnSelect.appendChild(opt);
      });

      columnWrap.style.display = json.numeric_columns.length ? '' : 'none';
      runBtn.disabled = !json.numeric_columns.length;

      if (json.numeric_columns.length) {
        extractNumericColumn();
        columnSelect.addEventListener('change', extractNumericColumn);
      }
    } catch (err) {
      fileInfo.textContent = 'Upload failed — is XAMPP running?';
      console.error(err);
    }
  }

  function extractNumericColumn() {
    const col = columnSelect.value;
    if (!uploadedData || !col) return;
    numericData = uploadedData.map(row => parseFloat(row[col])).filter(v => !isNaN(v));
  }

  // ── Paste handler ──
  function handlePasteInput() {
    const raw = pasteDataInput.value.trim();
    if (!raw) { runBtn.disabled = true; return; }
    const vals = raw.split(/[\s,;|\n]+/).map(Number).filter(v => !isNaN(v));
    numericData = vals.length >= 3 ? vals : null;
    runBtn.disabled = !numericData;
    uploadedData = null;
    columnWrap.style.display = 'none';
  }

  // ── Run detection ──
  async function runDetection() {
    if (!numericData || numericData.length < 3) return;

    const th = parseFloat(liveSliderWrap.style.display !== 'none' ? liveThreshold.value : thresholdInput.value);

    runBtn.disabled = true;
    showChartSpinner();

    const formData = new FormData();
    formData.append('data', JSON.stringify(numericData));
    formData.append('threshold', th);
    formData.append('method', methodSelect.value);
    formData.append('window', windowInput.value);

    try {
      const res  = await fetch('detect.php', { method: 'POST', body: formData });
      const json = await res.json();

      if (json.error) { alert(json.error); return; }

      lastResults = json;
      updateStats(json);
      renderChart(json);
      renderTable(json);

      // Show live slider & legend
      liveSliderWrap.style.display = '';
      liveThreshold.value = th;
      liveThresholdVal.textContent = th.toFixed(1);
      chartLegend.style.display = '';
      tableCard.style.display = '';
      statsRow.style.display = '';

    } catch (err) {
      alert('Detection failed. Make sure XAMPP/Apache is running.');
      console.error(err);
    } finally {
      runBtn.disabled = false;
    }
  }

  // ── Stats panel ──
  function updateStats(json) {
    document.getElementById('stat-total').textContent     = json.total;
    document.getElementById('stat-anomalies').textContent = json.anomaly_count;
    document.getElementById('stat-mean').textContent      = json.mean.toFixed(2);
    document.getElementById('stat-std').textContent       = json.std.toFixed(2);
  }

  // ── Table ──
  function renderTable(json) {
    resultsTbody.innerHTML = '';
    json.results.forEach(r => {
      const tr = document.createElement('tr');
      if (r.anomaly) tr.classList.add('row-anomaly');
      tr.innerHTML = `
        <td>${r.index + 1}</td>
        <td>${r.value}</td>
        <td>${r.z}</td>
        <td>${r.anomaly
          ? '<span class="badge badge-anomaly">ANOMALY</span>'
          : '<span class="badge badge-normal">Normal</span>'}</td>`;
      resultsTbody.appendChild(tr);
    });
  }

  // ── Chart ──
  function showChartSpinner() {
    chartContainer.innerHTML = `<div class="spinner-overlay"><div class="spinner-ring"></div></div>`;
  }

  function renderChart(json) {
    chartContainer.innerHTML = '';

    const data = json.results;
    const margin = { top: 24, right: 30, bottom: 48, left: 60 };
    const containerWidth = chartContainer.clientWidth || 700;
    const width  = containerWidth - margin.left - margin.right;
    const height = 380 - margin.top - margin.bottom;

    const svg = d3.select('#chart')
      .append('svg')
      .attr('width', width + margin.left + margin.right)
      .attr('height', height + margin.top + margin.bottom)
      .append('g')
      .attr('transform', `translate(${margin.left},${margin.top})`);

    // Scales
    const x = d3.scaleLinear().domain([0, data.length - 1]).range([0, width]);
    const yMin = d3.min(data, d => d.value);
    const yMax = d3.max(data, d => d.value);
    const yPad = (yMax - yMin) * 0.12 || 10;
    const y = d3.scaleLinear().domain([yMin - yPad, yMax + yPad]).range([height, 0]);

    // Gridlines
    svg.append('g').attr('class', 'grid')
      .call(d3.axisLeft(y).ticks(6).tickSize(-width).tickFormat(''));

    // Axes
    svg.append('g').attr('class', 'axis')
      .attr('transform', `translate(0,${height})`)
      .call(d3.axisBottom(x).ticks(Math.min(data.length, 15)).tickFormat(d => d + 1));

    svg.append('g').attr('class', 'axis').call(d3.axisLeft(y).ticks(6));

    // Axis labels
    svg.append('text')
      .attr('x', width / 2).attr('y', height + 40)
      .attr('text-anchor', 'middle')
      .attr('fill', '#94a3b8').attr('font-size', '12px')
      .text('Data Point Index');

    svg.append('text')
      .attr('transform', 'rotate(-90)')
      .attr('x', -height / 2).attr('y', -45)
      .attr('text-anchor', 'middle')
      .attr('fill', '#94a3b8').attr('font-size', '12px')
      .text('Value');

    // Threshold band
    const upperBound = json.mean + json.threshold * json.std;
    const lowerBound = json.mean - json.threshold * json.std;
    svg.append('rect').attr('class', 'threshold-band')
      .attr('x', 0).attr('width', width)
      .attr('y', y(Math.min(upperBound, yMax + yPad)))
      .attr('height', Math.abs(y(lowerBound) - y(upperBound)));

    // Mean line
    svg.append('line').attr('class', 'mean-line')
      .attr('x1', 0).attr('x2', width)
      .attr('y1', y(json.mean)).attr('y2', y(json.mean));

    // Upper/lower threshold lines
    [upperBound, lowerBound].forEach(val => {
      svg.append('line')
        .attr('x1', 0).attr('x2', width)
        .attr('y1', y(val)).attr('y2', y(val))
        .attr('stroke', '#f43f5e').attr('stroke-width', 1)
        .attr('stroke-dasharray', '4 3').attr('opacity', 0.45);
    });

    // Line connecting points
    const line = d3.line().x(d => x(d.index)).y(d => y(d.value)).curve(d3.curveMonotoneX);
    svg.append('path')
      .datum(data)
      .attr('fill', 'none')
      .attr('stroke', 'rgba(99,102,241,0.25)')
      .attr('stroke-width', 1.5)
      .attr('d', line);

    // Normal dots
    svg.selectAll('.dot-normal')
      .data(data.filter(d => !d.anomaly))
      .join('circle')
      .attr('class', 'dot-normal')
      .attr('cx', d => x(d.index))
      .attr('cy', d => y(d.value))
      .attr('r', 0)
      .on('mouseenter', showTooltip)
      .on('mousemove', moveTooltip)
      .on('mouseleave', hideTooltip)
      .transition().duration(500).delay((d, i) => i * 12)
      .attr('r', 5);

    // Anomaly dots
    svg.selectAll('.dot-anomaly')
      .data(data.filter(d => d.anomaly))
      .join('circle')
      .attr('class', 'dot-anomaly')
      .attr('cx', d => x(d.index))
      .attr('cy', d => y(d.value))
      .attr('r', 0)
      .on('mouseenter', showTooltip)
      .on('mousemove', moveTooltip)
      .on('mouseleave', hideTooltip)
      .transition().duration(600).delay((d, i) => i * 40)
      .attr('r', 8);

    // Zoom
    const zoom = d3.zoom()
      .scaleExtent([1, 8])
      .translateExtent([[0, 0], [width, height]])
      .extent([[0, 0], [width, height]])
      .on('zoom', (event) => {
        const newX = event.transform.rescaleX(x);
        const newY = event.transform.rescaleY(y);
        svg.selectAll('.dot-normal, .dot-anomaly')
          .attr('cx', d => newX(d.index))
          .attr('cy', d => newY(d.value));
        svg.selectAll('.axis').remove();
        svg.append('g').attr('class', 'axis')
          .attr('transform', `translate(0,${height})`)
          .call(d3.axisBottom(newX).ticks(Math.min(data.length, 15)).tickFormat(d => d + 1));
        svg.append('g').attr('class', 'axis').call(d3.axisLeft(newY).ticks(6));
      });

    d3.select('#chart svg').call(zoom);
  }

  // ── Tooltip ──
  function showTooltip(event, d) {
    tooltip.innerHTML = `
      <span class="label">Index:</span> ${d.index + 1}<br>
      <span class="label">Value:</span> <strong>${d.value}</strong><br>
      <span class="label">Z-Score:</span> ${d.z}
      ${d.anomaly ? '<span class="anomaly-badge">ANOMALY</span>' : ''}`;
    tooltip.classList.add('show');
  }

  function moveTooltip(event) {
    tooltip.style.left = (event.pageX + 14) + 'px';
    tooltip.style.top  = (event.pageY - 20) + 'px';
  }

  function hideTooltip() {
    tooltip.classList.remove('show');
  }

})();
