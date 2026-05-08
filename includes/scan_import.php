<?php
declare(strict_types=1);
/**
 * Called automatically by the scan wrapper script after masscan finishes.
 * Usage: php includes/scan_import.php <job_id> <xml_path>
 */
if (!str_starts_with(php_sapi_name(), 'cli')) {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/includes/functions.php';

$job_id  = $argv[1] ?? '';
$xml_path = $argv[2] ?? '';

if (!$job_id || !$xml_path) {
    exit('Usage: php scan_import.php <job_id> <xml_path>' . PHP_EOL);
}

$db = getPdo();

function fail_job(PDO $db, string $job_id, string $msg): never
{
    $stmt = $db->prepare("UPDATE jobs SET status = 'failed', finished_at = NOW(), error_msg = :msg WHERE id = :id");
    $stmt->execute([':msg' => $msg, ':id' => $job_id]);
    echo 'FAILED: ' . $msg . PHP_EOL;
    exit(1);
}

if (!is_file($xml_path)) {
    fail_job($db, $job_id, 'XML output file not found: ' . $xml_path);
}

if (!extension_loaded('simplexml')) {
    fail_job($db, $job_id, 'php-xml extension not loaded.');
}

echo 'Importing scan results for job ' . $job_id . PHP_EOL;

$raw  = file_get_contents($xml_path);
if ($raw === false) {
    fail_job($db, $job_id, 'Could not read XML file.');
}

$content = mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
$xml     = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
if ($xml === false) {
    fail_job($db, $job_id, 'Failed to parse XML.');
}

$q    = 'INSERT INTO data (ip, port_id, scanned_ts, protocol, state, reason, reason_ttl, service, banner, title) '
      . 'VALUES (:ip, :port, :scanned_ts, :protocol, :state, :reason, :reason_ttl, :service, :banner, :title)';
$stmt = $db->prepare($q);

$stmt->bindParam(':ip',         $ip,         PDO::PARAM_INT);
$stmt->bindParam(':port',       $port,       PDO::PARAM_INT);
$stmt->bindParam(':scanned_ts', $scanned_ts);
$stmt->bindParam(':protocol',   $protocol,   PDO::PARAM_STR);
$stmt->bindParam(':state',      $state,      PDO::PARAM_STR);
$stmt->bindParam(':reason',     $reason,     PDO::PARAM_STR);
$stmt->bindParam(':reason_ttl', $reason_ttl, PDO::PARAM_INT);
$stmt->bindParam(':service',    $service,    PDO::PARAM_STR);
$stmt->bindParam(':banner',     $banner,     PDO::PARAM_STR);
$stmt->bindParam(':title',      $title,      PDO::PARAM_STR);

$inserted = 0;
foreach ($xml->host as $host) {
    foreach ($host->ports as $p) {
        $ip         = (int) sprintf('%u', ip2long((string) $host->address['addr']));
        $ts         = (int) $host['endtime'];
        $scanned_ts = date('Y-m-d H:i:s', $ts ?: time());
        $port       = (int) $p->port['portid'];
        $protocol   = (string) $p->port['protocol'];

        if (isset($p->port->service)) {
            $service = (string) $p->port->service['name'];
            if ($service === 'title') {
                $title  = isset($p->port->service['banner']) ? (string) $p->port->service['banner'] : '';
                $banner = '';
            } else {
                $banner = isset($p->port->service['banner']) ? (string) $p->port->service['banner'] : '';
                $title  = '';
            }
        } else {
            $service = $banner = $title = '';
        }

        $state      = (string) $p->port->state['state'];
        $reason     = (string) $p->port->state['reason'];
        $reason_ttl = (int)    $p->port->state['reason_ttl'];

        if ($stmt->execute()) {
            $inserted++;
        }
    }
}

if (DB_DRIVER === 'pgsql') {
    echo 'Rebuilding full-text index…' . PHP_EOL;
    $db->exec("UPDATE data SET searchtext = to_tsvector('english', title || ' ' || banner || ' ' || service || ' ' || protocol || ' ' || port_id)");
}

// Update job as done
$update = $db->prepare("UPDATE jobs SET status = 'done', finished_at = NOW(), record_count = :count WHERE id = :id");
$update->execute([':count' => $inserted, ':id' => $job_id]);

// Clean up temp files
@unlink($xml_path);

echo 'Done — inserted ' . $inserted . ' records.' . PHP_EOL;
