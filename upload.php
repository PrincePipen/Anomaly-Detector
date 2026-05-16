<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only.']);
    exit;
}

if (!isset($_FILES['csvfile']) || $_FILES['csvfile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['csvfile']['tmp_name'];
$ext = strtolower(pathinfo($_FILES['csvfile']['name'], PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['error' => 'Only CSV files are accepted.']);
    exit;
}

$handle = fopen($file, 'r');
if (!$handle) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot read file.']);
    exit;
}

$headers = fgetcsv($handle);
if (!$headers) {
    fclose($handle);
    http_response_code(400);
    echo json_encode(['error' => 'CSV appears to be empty.']);
    exit;
}

// Clean BOM from first header
$headers[0] = preg_replace('/\x{FEFF}/u', '', $headers[0]);
$headers = array_map('trim', $headers);

$rows = [];
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) === count($headers)) {
        $assoc = array_combine($headers, $row);
        $rows[] = $assoc;
    }
}
fclose($handle);

// Identify numeric columns
$numericCols = [];
foreach ($headers as $h) {
    $isNumeric = true;
    $sampleCount = 0;
    foreach ($rows as $r) {
        if ($sampleCount >= 20) break;
        $val = trim($r[$h] ?? '');
        if ($val !== '' && !is_numeric($val)) {
            $isNumeric = false;
            break;
        }
        $sampleCount++;
    }
    if ($isNumeric) $numericCols[] = $h;
}

// Save to data/ for reference
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
copy($file, $dataDir . '/' . basename($_FILES['csvfile']['name']));

echo json_encode([
    'columns' => $headers,
    'numeric_columns' => $numericCols,
    'row_count' => count($rows),
    'preview' => array_slice($rows, 0, 5),
    'data' => $rows
]);
