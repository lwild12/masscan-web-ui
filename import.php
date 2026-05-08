<?php
declare(strict_types=1);
/**
 * CLI-only import script.
 * Usage: php import.php <path-to-masscan.xml>
 *
 * Docker example:
 *   docker compose exec web php /var/www/html/import.php /var/www/html/imports/scan.xml
 */
if (!str_starts_with(php_sapi_name(), 'cli')) {
    die('This script can only be run from the command line!');
}

include __DIR__ . '/includes/functions.php';

function seconds2human(int $ss): string
{
    $s      = $ss % 60;
    $mins   = (int) floor(($ss % 3600) / 60);
    $hours  = (int) floor(($ss % 86400) / 3600);
    $days   = (int) floor(($ss % 2592000) / 86400);
    $months = (int) floor($ss / 2592000);

    $parts = [];
    if ($months > 0) $parts[] = "$months months";
    if ($days   > 0) $parts[] = "$days days";
    if ($hours  > 0) $parts[] = "$hours hours";
    if ($mins   > 0) $parts[] = "$mins minutes";
    if ($s      > 0) $parts[] = "$s seconds";

    return implode(', ', $parts) ?: '0 seconds';
}

if (!extension_loaded('simplexml')) {
    echo 'This script requires the php-xml package.' . PHP_EOL;
    echo 'Install the package, restart your web server and run the script again.' . PHP_EOL;
    exit(1);
}

$start_ts = time();

require __DIR__ . '/config.php';

if (!isset($argv[1])) {
    die('Please provide a file path to import!' . PHP_EOL);
}

$tmp      = pathinfo($argv[1]);
$filepath = $tmp['dirname'] === '.' ? __DIR__ . '/' . $argv[1] : $argv[1];

if (!is_file($filepath)) {
    echo 'File: ' . $filepath . PHP_EOL;
    echo 'File does not exist!' . PHP_EOL;
    exit(1);
}

do {
    echo PHP_EOL . 'Do you want to clear the database before importing (yes/no)?: ';
    $input = trim((string) fgets(STDIN));
} while (!in_array($input, ['yes', 'no'], true));

$db = getPdo();

if ($input === 'yes') {
    echo PHP_EOL . 'Clearing the database...' . PHP_EOL;
    $db->exec('TRUNCATE TABLE data');
}

echo 'Reading file...' . PHP_EOL;
$raw = file_get_contents($filepath);
if ($raw === false) {
    die('Could not read file: ' . $filepath . PHP_EOL);
}
// Ensure UTF-8; masscan XML may contain Latin-1 banners
$content = mb_check_encoding($raw, 'UTF-8')
    ? $raw
    : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');

echo 'Parsing file...' . PHP_EOL;
$xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
if ($xml === false) {
    die('There is a problem with this XML file.' . PHP_EOL);
}

$total    = 0;
$inserted = 0;

echo 'Processing data (this may take some time for large files)...' . PHP_EOL;

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

foreach ($xml->host as $host) {
    foreach ($host->ports as $p) {
        $ip         = (int) sprintf('%u', ip2long((string) $host->address['addr']));
        $ts         = (int) $host['endtime'];
        $scanned_ts = date('Y-m-d H:i:s', $ts);
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
            $service = '';
            $banner  = '';
            $title   = '';
        }

        $state      = (string) $p->port->state['state'];
        $reason     = (string) $p->port->state['reason'];
        $reason_ttl = (int)    $p->port->state['reason_ttl'];

        $total++;
        if ($stmt->execute()) {
            $inserted++;
        }
    }
}

if (DB_DRIVER === 'pgsql') {
    echo PHP_EOL . 'Building full-text search index...' . PHP_EOL;
    $db->exec("UPDATE data SET searchtext = to_tsvector('english', title || ' ' || banner || ' ' || service || ' ' || protocol || ' ' || port_id)");
}

$secs = time() - $start_ts;

echo PHP_EOL . 'Summary:' . PHP_EOL;
echo 'Total records:    ' . $total    . PHP_EOL;
echo 'Inserted records: ' . $inserted . PHP_EOL;
echo 'Time taken:       ' . seconds2human($secs) . PHP_EOL;
