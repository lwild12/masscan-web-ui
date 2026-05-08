<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
define('EXPORT', true);
require __DIR__ . '/includes/data_validation.php';

if (!empty($results)) {
    $xml  = new DOMDocument('1.0', 'UTF-8');
    $root = $xml->createElement('nmaprun');
    $root->setAttribute('scanner', 'masscan');
    $root->setAttribute('start', (string) time());
    $root->setAttribute('version', '1.0-BETA');
    $root->setAttribute('xmloutputversion', '1.03');
    $xml->appendChild($root);

    foreach ($results as $res) {
        $host    = $xml->createElement('host');
        $endtime = isset($res['scanned_ts']) ? (string) strtotime($res['scanned_ts']) : (string) time();
        $host->setAttribute('endtime', $endtime);

        $address = $xml->createElement('address');
        $address->setAttribute('addr', long2ip((int) $res['ipaddress']));
        $address->setAttribute('addrtype', 'ipv4');

        $ports = $xml->createElement('ports');
        $port  = $xml->createElement('port');
        $port->setAttribute('protocol',   (string) $res['protocol']);
        $port->setAttribute('portid',     (string) $res['port_id']);
        $port->setAttribute('state',      (string) $res['state']);
        $port->setAttribute('reason',     (string) $res['reason']);
        $port->setAttribute('reason_ttl', (string) $res['reason_ttl']);
        $ports->appendChild($port);

        $host->appendChild($address);
        $host->appendChild($ports);
        $root->appendChild($host);
    }

    $xml->formatOutput = true;
    $name = 'export_' . date('m_d_Y') . '.xml';
    header('Content-Disposition: attachment; filename=' . $name);
    header('Content-Type: text/xml; charset=UTF-8');
    echo $xml->saveXML();
}
