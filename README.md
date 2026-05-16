# Anomaly Detector

An interactive web-based anomaly detection tool built with **HTML, Bootstrap 5, JavaScript, D3.js** (front-end) and **PHP** (back-end). It allows users to upload CSV datasets or paste numeric values, detects outliers, and visualizes results with an interactive scatter-plot chart.

---

## Objective

Detect anomalies (unusual values, outliers, or unexpected patterns) in tabular data and present them visually so a stakeholder can quickly identify suspicious entries.

## Algorithm

### Z-Score Method (Default)

The Z-score measures how many standard deviations a data point is from the mean:

```
z_i = (x_i - μ) / σ
```

A point is flagged as an **anomaly** when `|z_i| >= threshold` (default threshold = 2.0).

### Moving Average Method

Computes a sliding-window average and flags points whose deviation from the local average exceeds `threshold × σ`:

```
MA_i = avg(x_{i-w}, ..., x_{i+w})
anomaly if |x_i - MA_i| >= threshold × σ
```

## Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| Threshold | 2.0 | Z-score cutoff for anomaly flagging |
| Method | Z-Score | Detection algorithm (Z-Score or Moving Average) |
| Window | 5 | Window size for Moving Average method |

## Sample Datasets

- **`data/clean_data.csv`** — 50 normal transaction records (range ~198–318)
- **`data/outlier_data.csv`** — 50 records with 7 injected outliers (spikes to 3950–7500 and negatives)

## How to Run

1. Place the project folder inside your XAMPP `htdocs` directory.
2. Start **Apache** from the XAMPP Control Panel.
3. Open a browser and navigate to: `http://localhost/DELETE/index.php`
4. Upload a CSV file or paste comma-separated values.
5. Adjust the threshold and detection method, then click **Run Detection**.
6. Interact with the chart — hover for tooltips, scroll to zoom, drag to pan.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Structure | HTML5, PHP |
| Styling | CSS3, Bootstrap 5.3 |
| Visualization | D3.js v7 |
| Logic (client) | JavaScript ES6 |
| Logic (server) | PHP 7.4+ |

## Features

- CSV file upload with column detection
- Paste numeric data directly
- Two detection algorithms: Z-Score and Moving Average
- Interactive D3.js scatter plot with animated data points
- Hover tooltips showing value, z-score, and anomaly status
- Live threshold slider for real-time re-detection
- Summary statistics cards (total, anomalies, mean, std dev)
- Color-coded results table
- Zoom and pan on the chart
- Dark glassmorphism UI with smooth animations

## Reflection

1. **Z-Score method** was chosen as the default because it is simple, well-understood, and effective for normally distributed data. It requires no training data and produces immediately interpretable results.

2. **Threshold sensitivity**: Lowering the threshold (e.g., 1.0) flags more points as anomalies, increasing recall but also false positives. Raising it (e.g., 3.0) only catches extreme outliers. The live slider makes this trade-off easy to explore.

3. **Real-world enhancements**: For production deployment, additional features could include user authentication, persistent storage (MySQL), historical trend analysis, email/SMS alerts for detected anomalies, and support for multivariate anomaly detection.

4. **Challenges**: Integrating D3.js zoom behavior with dynamically rendered axes required careful coordinate transforms. The solution was to use D3's `rescaleX`/`rescaleY` to keep axes synchronized with the zoomed view.
