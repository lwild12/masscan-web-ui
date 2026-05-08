<?php
declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

// ----------------------------------------------------------------
// Input validation
// ----------------------------------------------------------------
$target  = trim($_POST['target']  ?? '');
$ports   = trim($_POST['ports']   ?? '');
$rate    = max(1, min(1000000, (int) ($_POST['rate'] ?? 1000)));
$banners = (int) ($_POST['banners'] ?? 0) === 1;

if (empty($target) || empty($ports)) {
    echo json_encode(['error' => 'Target and ports are required.']);
    exit;
}

// Validate target: allow IP, CIDR, or IP range (a.b.c.d-e.f.g.h)
// Also allow simple hostnames for local use (letters, digits, dots, hyphens)
if (!preg_match('/^[0-9a-zA-Z.\-\/]+$/', $target) || strlen($target) > 255) {
    echo json_encode(['error' => 'Invalid target. Use an IP address, CIDR, or range.']);
    exit;
}

// Validate ports: digits, commas, hyphens only — no shell metacharacters
if (!preg_match('/^[0-9,\-]+$/', $ports) && !in_array($ports, ['top100', 'top1000'], true)) {
    echo json_encode(['error' => 'Invalid port specification.']);
    exit;
}

// ----------------------------------------------------------------
// Check masscan is available
// ----------------------------------------------------------------
$masscan_bin = trim((string) shell_exec('which masscan 2>/dev/null'));
if (empty($masscan_bin)) {
    echo json_encode(['error' => 'masscan is not installed or not in PATH. Install it inside the Docker container.']);
    exit;
}

// ----------------------------------------------------------------
// Check for an already-running scan (one at a time)
// ----------------------------------------------------------------
$db = getPdo();
$running_check = $db->query("SELECT COUNT(*) FROM jobs WHERE status = 'running'");
if ($running_check && (int) $running_check->fetchColumn() > 0) {
    echo json_encode(['error' => 'A scan is already running. Please wait for it to finish.']);
    exit;
}

// ----------------------------------------------------------------
// Create job record
// ----------------------------------------------------------------
$job_id   = sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$xml_path  = sys_get_temp_dir() . '/masscan_' . $job_id . '.xml';
$log_path  = sys_get_temp_dir() . '/masscan_' . $job_id . '.log';

$insert = $db->prepare(
    'INSERT INTO jobs (id, status, target, ports, rate, banners) VALUES (:id, :status, :target, :ports, :rate, :banners)'
);
$insert->execute([
    ':id'      => $job_id,
    ':status'  => 'running',
    ':target'  => $target,
    ':ports'   => $ports,
    ':rate'    => $rate,
    ':banners' => $banners ? 1 : 0,
]);

// ----------------------------------------------------------------
// Build masscan command and launch in background
// ----------------------------------------------------------------
if (in_array($ports, ['top100', 'top1000'], true)) {
    $port_arg = '--top-ports ' . ($ports === 'top100' ? '100' : '1000');
} else {
    $port_arg = '-p' . escapeshellarg($ports);
}

$masscan_cmd = escapeshellarg($masscan_bin)
    . ' ' . escapeshellarg($target)
    . ' ' . $port_arg
    . ' --rate=' . $rate
    . ' -oX ' . escapeshellarg($xml_path);

if ($banners) {
    $masscan_cmd .= ' --banners';
}

// Write a small wrapper script so the background process can update job status.
// No set -e: always run scan_import.php so it can mark the job failed if masscan errored.
$wrapper_script = sys_get_temp_dir() . '/masscan_wrapper_' . $job_id . '.sh';
$php_bin        = PHP_BINARY;
$doc_root       = __DIR__;

$wrapper_content = "#!/bin/bash\n"
    . $masscan_cmd . ' > ' . escapeshellarg($log_path) . " 2>&1\n"
    . escapeshellarg($php_bin)
    . ' ' . escapeshellarg($doc_root . '/includes/scan_import.php')
    . ' ' . escapeshellarg($job_id)
    . ' ' . escapeshellarg($xml_path)
    . ' >> ' . escapeshellarg($log_path) . " 2>&1\n";

file_put_contents($wrapper_script, $wrapper_content);
chmod($wrapper_script, 0755);

// Launch wrapper in background, fully detached
$full_cmd = 'nohup bash ' . escapeshellarg($wrapper_script) . ' > ' . escapeshellarg($log_path) . ' 2>&1 &';
exec($full_cmd);

echo json_encode(['job_id' => $job_id]);
