<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Read input
$rawData = $_POST['data'] ?? null;
$threshold = floatval($_POST['threshold'] ?? 2.0);
$method = $_POST['method'] ?? 'zscore';
$window = intval($_POST['window'] ?? 5);

if (!$rawData) {
    http_response_code(400);
    echo json_encode(['error' => 'No data provided.']);
    exit;
}

$data = json_decode($rawData, true);

if (!is_array($data) || count($data) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide at least 3 numeric values.']);
    exit;
}

// Sanitise — keep only numeric values
$data = array_values(array_map('floatval', $data));
$n = count($data);

// === Statistics ===
$mean = array_sum($data) / $n;
$variance = array_sum(array_map(fn($x) => ($x - $mean) ** 2, $data)) / $n;
$std = sqrt($variance);

$results = [];

if ($method === 'moving_average') {
    // Moving-average threshold method
    for ($i = 0; $i < $n; $i++) {
        $start = max(0, $i - $window);
        $end = min($n - 1, $i + $window);
        $slice = array_slice($data, $start, $end - $start + 1);
        $ma = array_sum($slice) / count($slice);
        $dev = abs($data[$i] - $ma);
        // Use global std for threshold scaling
        $isAnomaly = ($std > 0) && ($dev >= $threshold * $std);
        $zScore = ($std > 0) ? ($data[$i] - $mean) / $std : 0;
        $results[] = [
            'index' => $i,
            'value' => $data[$i],
            'z' => round($zScore, 4),
            'ma' => round($ma, 4),
            'deviation' => round($dev, 4),
            'anomaly' => $isAnomaly
        ];
    }
} else {
    // Z-score method (default)
    for ($i = 0; $i < $n; $i++) {
        $z = ($std > 0) ? ($data[$i] - $mean) / $std : 0;
        $results[] = [
            'index' => $i,
            'value' => $data[$i],
            'z' => round($z, 4),
            'anomaly' => ($std > 0) && (abs($z) >= $threshold)
        ];
    }
}

$anomalyCount = count(array_filter($results, fn($r) => $r['anomaly']));

echo json_encode([
    'mean' => round($mean, 4),
    'std' => round($std, 4),
    'threshold' => $threshold,
    'method' => $method,
    'total' => $n,
    'anomaly_count' => $anomalyCount,
    'results' => $results
]);
