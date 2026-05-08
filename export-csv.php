<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
define('EXPORT', true);
require __DIR__ . '/includes/data_validation.php';

if (!empty($results)) {
    $name = 'export_' . date('m_d_Y') . '.csv';
    header('Content-Disposition: attachment; filename=' . $name);
    header('Content-Type: text/csv; charset=UTF-8');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['IP Address', 'Port', 'Service', 'Protocol', 'State']);
    foreach ($results as $res) {
        fputcsv($out, [
            long2ip((int) $res['ipaddress']),
            (int) $res['port_id'],
            $res['service'],
            $res['protocol'],
            $res['state'],
        ]);
    }
    fclose($out);
}
