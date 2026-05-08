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

// Read the tail of the live log file (last 50 KB) to avoid loading huge files
$log_path = sys_get_temp_dir() . '/masscan_' . $job_id . '.log';
$output   = '';
if (is_file($log_path)) {
    $fp = fopen($log_path, 'r');
    if ($fp !== false) {
        $size = filesize($log_path);
        if ($size > 51200) {
            fseek($fp, -51200, SEEK_END);
        }
        $output = (string) stream_get_contents($fp);
        fclose($fp);
    }
}

echo json_encode([
    'status'       => $job['status'],
    'output'       => $output,
    'record_count' => $job['record_count'],
    'error'        => $job['error_msg'],
]);
