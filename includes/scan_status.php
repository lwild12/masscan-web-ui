<?php
declare(strict_types=1);
header('Content-Type: application/json');

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/includes/functions.php';

$job_id = trim($_GET['job_id'] ?? '');

// Validate UUID-ish format to avoid path traversal
if (!preg_match('/^[0-9a-f\-]{36}$/', $job_id)) {
    echo json_encode(['error' => 'Invalid job ID.']);
    exit;
}

$db   = getPdo();
$stmt = $db->prepare('SELECT status, error_msg, record_count FROM jobs WHERE id = :id');
$stmt->execute([':id' => $job_id]);
$job  = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(['error' => 'Job not found.']);
    exit;
}

// Read live output from the log file
$log_path = sys_get_temp_dir() . '/masscan_' . $job_id . '.log';
$output   = is_file($log_path) ? file_get_contents($log_path) : '';

echo json_encode([
    'status'       => $job['status'],
    'output'       => $output,
    'record_count' => $job['record_count'],
    'error'        => $job['error_msg'],
]);
