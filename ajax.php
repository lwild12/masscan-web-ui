<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

$ip = (int) ($_GET['ip'] ?? 0);

if ($ip > 0) {
    $db  = getPdo();
    $q   = 'SELECT ip AS ipaddress, port_id, service, protocol, banner, title '
         . 'FROM data WHERE ip = :ip ORDER BY scanned_ts DESC';
    $stmt = $db->prepare($q);
    $stmt->bindParam(':ip', $ip, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $results = [];
}

if (!empty($results)) { ?>
<table class="table table-bordered table-hover table-sm">
    <thead>
        <tr>
            <th class="banner">Banner/Title</th>
            <th class="port">Port</th>
            <th class="service">Service</th>
            <th class="protocol">Protocol</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $r): ?>
        <tr>
            <td>
                <?php if (!empty($r['banner'])): ?>
                    <strong>Banner:</strong> <?php echo htmlentities($r['banner']); ?>
                <?php endif; ?>
                <?php if (!empty($r['title'])): ?>
                    <strong>Title:</strong> <?php echo htmlentities($r['title']); ?>
                <?php endif; ?>
            </td>
            <td><?php echo (int) $r['port_id']; ?></td>
            <td>
                <?php if ($r['service'] !== 'title'): echo htmlentities($r['service']); endif; ?>
                <?php if ($r['service'] === 'http'): ?>
                    <a href="http://<?php echo long2ip((int) $r['ipaddress']); ?>" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                <?php endif; ?>
            </td>
            <td><?php echo htmlentities($r['protocol']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php } ?>
