<?php
declare(strict_types=1);

function getPdo(): PDO
{
    try {
        $db = new PDO(DB_DRIVER . ':host=' . DB_HOST . ';dbname=' . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        try {
            $stmt = $db->query('SELECT 1 FROM data');
            $stmt->fetch();
            return $db;
        } catch (PDOException $pdoe) {
            include DOC_ROOT . 'includes/install.php';
            exit();
        }
    } catch (PDOException $pdoe) {
        if (str_starts_with(php_sapi_name(), 'cli')) {
            echo $pdoe->getMessage();
            exit(1);
        }
        $msg = $pdoe->getMessage();
        if (str_contains($msg, 'Access denied for user') ||
            str_contains($msg, 'getaddrinfo failed') ||
            str_contains($msg, 'not find driver') ||
            str_contains($msg, 'does not exist')) {
            include DOC_ROOT . 'includes/setup.php';
        } else {
            include DOC_ROOT . 'includes/error.php';
        }
        exit();
    }
}

function browse(array $filter, bool $export = false): array
{
    $db = getPdo();
    $records_per_page = (int) $filter['rec_per_page'];
    $page = isset($filter['page']) && $filter['page'] > 1 ? (int) $filter['page'] : 1;
    $from = ($page - 1) * $records_per_page;

    $select_cols = 'ip AS ipaddress, port_id, protocol, state, reason, service, banner, title';
    if ($export) {
        $select_cols .= ', scanned_ts';
    }
    $q1 = "SELECT $select_cols";
    $q2 = 'SELECT COUNT(*) as total_records';
    $q  = ' FROM data WHERE 1 = 1';

    $params = [];

    // IP address range (integers from ip2long — safe to interpolate directly)
    if (!empty($filter['ip'])) {
        [$start_ip, $end_ip] = getStartAndEndIps($filter['ip']);
        $q .= " AND (ip >= $start_ip AND ip <= $end_ip)";
    }

    // Port (cast to int — safe to interpolate)
    if (isset($filter['port']) && (int) $filter['port'] > 0 && (int) $filter['port'] <= 65535) {
        $q .= ' AND port_id = ' . (int) $filter['port'];
    }

    // Protocol
    if (!empty($filter['protocol'])) {
        $q .= ' AND protocol = :protocol';
        $params[':protocol'] = $filter['protocol'];
    }

    // State
    if (!empty($filter['state'])) {
        $q .= ' AND state = :state';
        $params[':state'] = $filter['state'];
    }

    // Service
    if (!empty($filter['service'])) {
        $q .= ' AND service = :service';
        $params[':service'] = $filter['service'];
    }

    // Banner / title search
    if (!empty($filter['banner'])) {
        if ((int) $filter['exact-match'] === 1) {
            if (DB_DRIVER === 'pgsql') {
                $q .= ' AND (banner LIKE :banner_like OR title LIKE :title_like)';
                $params[':banner_like'] = '%' . $filter['banner'] . '%';
                $params[':title_like']  = '%' . $filter['banner'] . '%';
            } else {
                $q .= ' AND (banner LIKE BINARY :banner_like OR title LIKE BINARY :title_like)';
                $params[':banner_like'] = '%' . $filter['banner'] . '%';
                $params[':title_like']  = '%' . $filter['banner'] . '%';
            }
        } else {
            if (DB_DRIVER === 'pgsql') {
                $tsquery = implode(' | ', preg_split('/\s+/', trim($filter['banner'])));
                $q .= ' AND searchtext @@ to_tsquery(:banner_tsquery)';
                $params[':banner_tsquery'] = $tsquery;
            } else {
                $q .= ' AND MATCH(title, banner) AGAINST (:banner_fts IN NATURAL LANGUAGE MODE)';
                $params[':banner_fts'] = $filter['banner'];
            }
        }
    }

    // Text / quick search across banner, service, protocol, port
    if (!empty($filter['text'])) {
        if (DB_DRIVER === 'pgsql') {
            $q .= ' AND searchtext @@ websearch_to_tsquery(:text_tsquery)';
            $params[':text_tsquery'] = $filter['text'];
        } else {
            $q .= ' AND (MATCH(title, banner) AGAINST (:text_fts IN NATURAL LANGUAGE MODE)'
                . ' OR service LIKE :text_service'
                . ' OR protocol LIKE :text_protocol'
                . ' OR port_id = :text_port)';
            $params[':text_fts']      = $filter['text'];
            $params[':text_service']  = '%' . $filter['text'] . '%';
            $params[':text_protocol'] = '%' . $filter['text'] . '%';
            $params[':text_port']     = (int) $filter['text'];
        }
    }

    $q3 = isset($start_ip) ? ' ORDER BY ip ASC' : ' ORDER BY scanned_ts DESC';
    $q4 = $export ? '' : " LIMIT $records_per_page OFFSET $from";

    try {
        $stmt = $db->prepare($q1 . $q . $q3 . $q4);
        $stmt->execute($params);
    } catch (PDOException $ex) {
        echo 'A database error occurred: ' . htmlentities($ex->getMessage());
        exit(1);
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($export) {
        return $data;
    }

    $count_stmt = $db->prepare($q2 . $q);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC);

    $to    = $from + $records_per_page < $total['total_records'] ? $from + $records_per_page : $total['total_records'];
    $pages = $total['total_records'] > 1 ? (int) ceil($total['total_records'] / $records_per_page) : 0;

    return [
        'data' => $data,
        'pagination' => [
            'page'    => $page,
            'pages'   => $pages,
            'records' => $total['total_records'],
            'from'    => ++$from,
            'to'      => $to,
        ],
    ];
}

function getStats(): array
{
    $db = getPdo();

    $total_ips     = (int) $db->query('SELECT COUNT(DISTINCT ip) FROM data')->fetchColumn();
    $total_records = (int) $db->query('SELECT COUNT(*) FROM data')->fetchColumn();

    $top_ports_stmt = $db->query(
        "SELECT port_id, COUNT(*) AS c FROM data WHERE state = 'open' GROUP BY port_id ORDER BY c DESC LIMIT 10"
    );
    $top_ports = $top_ports_stmt->fetchAll(PDO::FETCH_ASSOC);

    $top_services_stmt = $db->query(
        "SELECT service, COUNT(*) AS c FROM data WHERE service != '' GROUP BY service ORDER BY c DESC LIMIT 10"
    );
    $top_services = $top_services_stmt->fetchAll(PDO::FETCH_ASSOC);

    $timeline_stmt = $db->query(
        'SELECT DATE(scanned_ts) AS day, COUNT(*) AS c FROM data GROUP BY day ORDER BY day DESC LIMIT 30'
    );
    $timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);

    $jobs_stmt = $db->query(
        "SELECT id, status, target, ports, started_at, finished_at, record_count FROM jobs ORDER BY started_at DESC LIMIT 10"
    );
    $recent_jobs = $jobs_stmt ? $jobs_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    return [
        'total_ips'     => $total_ips,
        'total_records' => $total_records,
        'top_ports'     => $top_ports,
        'top_services'  => $top_services,
        'timeline'      => $timeline,
        'recent_jobs'   => $recent_jobs,
    ];
}

function getStartAndEndIps(string $ip): array
{
    $start_ip = '';
    $end_ip   = '';
    $ip       = trim($ip, '.');
    $p        = explode('.', trim($ip));
    for ($i = 0; $i < 4; $i++) {
        if ($i > 0) {
            $start_ip .= '.';
            $end_ip   .= '.';
        }
        if (isset($p[$i])) {
            $start_ip .= $p[$i];
            $end_ip   .= $p[$i];
        } else {
            $start_ip .= '0';
            $end_ip   .= '255';
        }
    }
    return [ip2long($start_ip), ip2long($end_ip)];
}
