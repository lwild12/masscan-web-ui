<?php
$base = 'action=search'
    . '&rec_per_page=' . (int) $filter['rec_per_page']
    . '&ip='          . htmlentities($filter['ip'])
    . '&port='        . (int) $filter['port']
    . '&state='       . htmlentities($filter['state'])
    . '&protocol='    . htmlentities($filter['protocol'])
    . '&service='     . htmlentities($filter['service'])
    . '&banner='      . htmlentities($filter['banner'])
    . '&exact-match=' . $filter['exact-match']
    . '&text='        . htmlentities($filter['text']);

$pager_data = $base . '&page=';
$data_prev  = $base . '&page=' . ($results['pagination']['page'] - 1);
$data_next  = $base . '&page=' . ($results['pagination']['page'] + 1);
$rpp_data   = 'action=search&ip=' . htmlentities($filter['ip'])
    . '&port='        . (int) $filter['port']
    . '&state='       . htmlentities($filter['state'])
    . '&protocol='    . htmlentities($filter['protocol'])
    . '&service='     . htmlentities($filter['service'])
    . '&banner='      . htmlentities($filter['banner'])
    . '&exact-match=' . $filter['exact-match']
    . '&text='        . htmlentities($filter['text'])
    . '&page=1&rec_per_page=';
$data_search = 'action=search&rec_per_page=' . (int) $filter['rec_per_page']
    . '&ip='          . htmlentities($filter['ip'])
    . '&port='        . (int) $filter['port']
    . '&state='       . htmlentities($filter['state'])
    . '&protocol='    . htmlentities($filter['protocol'])
    . '&service='     . htmlentities($filter['service'])
    . '&banner='      . htmlentities($filter['banner'])
    . '&exact-match=' . $filter['exact-match']
    . '&page=1&text=';
?>
<div class="row align-items-center mb-2 g-2">
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm w-auto"
                    onchange="searchData('<?php echo $rpp_data; ?>' + this.value)">
                <?php foreach ([10, 20, 40, 50, 100] as $n): ?>
                    <option value="<?php echo $n; ?>"<?php if ($filter['rec_per_page'] == $n): echo ' selected'; endif; ?>>
                        <?php echo $n; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="text-muted small">records per page</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="d-flex align-items-center justify-content-end gap-2">
            <span class="ajax-throbber-wrapper">
                <div class="spinner-border spinner-border-sm text-secondary d-none" id="ajax-loader" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </span>
            <label class="text-muted small mb-0">Search:</label>
            <input class="form-control form-control-sm" style="max-width:220px;" type="text"
                   onkeyup="searchDataText('<?php echo $data_search; ?>' + this.value);"
                   value="<?php echo htmlentities($filter['text']); ?>">
        </div>
    </div>
</div>

<table class="table table-bordered table-hover table-sm mb-2">
    <thead>
        <tr>
            <th class="ip text-center">IP</th>
            <th class="banner">Banner / Title</th>
            <th class="port text-center">Port</th>
            <th class="service text-center">Service</th>
            <th class="protocol text-center">Protocol</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($results['data'])): ?>
        <?php foreach ($results['data'] as $r): ?>
            <tr>
                <td class="ip text-center">
                    <a href="javascript:void(0);"
                       onclick="return showIpHistory('<?php echo long2ip((int) $r['ipaddress']); ?>', '<?php echo $r['ipaddress']; ?>')">
                        <?php echo long2ip((int) $r['ipaddress']); ?>
                    </a>
                </td>
                <td class="banner">
                    <?php if (!empty($r['banner'])): ?>
                        <strong>Banner:</strong> <?php echo htmlentities($r['banner']); ?>
                    <?php endif; ?>
                    <?php if (!empty($r['title'])): ?>
                        <strong>Title:</strong> <?php echo htmlentities($r['title']); ?>
                    <?php endif; ?>
                </td>
                <td class="port text-center"><?php echo (int) $r['port_id']; ?></td>
                <td class="service text-center">
                    <?php if ($r['service'] !== 'title'): ?>
                        <?php echo htmlentities($r['service']); ?>
                        <?php if ($r['service'] === 'http'): ?>
                            <a href="http://<?php echo long2ip((int) $r['ipaddress']); ?><?php echo ((int) $r['port_id'] > 0 && (int) $r['port_id'] !== 80) ? ':' . $r['port_id'] : ''; ?>"
                               target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td class="protocol text-center"><?php echo htmlentities($r['protocol']); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" class="text-center">
                <div class="alert alert-warning mb-0">No results found.</div>
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<?php if ($results['pagination']['records'] > 0): ?>
<div class="row align-items-center">
    <div class="col-md-6">
        <p class="text-muted small mb-0">
            Showing <?php echo $results['pagination']['from']; ?>
            to <?php echo $results['pagination']['to']; ?>
            of <?php echo $results['pagination']['records']; ?> entries
        </p>
    </div>
    <div class="col-md-6 d-flex justify-content-end align-items-center gap-2">
        <div class="spinner-border spinner-border-sm text-secondary d-none" id="ajax-loader-pagination" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php if ($results['pagination']['page'] == 1): echo 'disabled'; endif; ?>">
                    <a class="page-link" href="javascript:void(0);"
                       onclick="searchData('<?php echo $data_prev; ?>', 'ajax-loader-pagination');">
                        &laquo; Prev
                    </a>
                </li>
                <?php for ($i = 1; $i <= $results['pagination']['pages']; $i++): ?>
                    <?php if (($results['pagination']['page'] - 3) < $i && ($results['pagination']['page'] + 3) > $i): ?>
                        <li class="page-item <?php if ($results['pagination']['page'] == $i): echo 'active'; endif; ?>">
                            <a class="page-link" href="javascript:void(0);"
                               onclick="searchData('<?php echo $pager_data . $i; ?>', 'ajax-loader-pagination');">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?php if ($results['pagination']['page'] == $results['pagination']['pages']): echo 'disabled'; endif; ?>">
                    <a class="page-link" href="javascript:void(0);"
                       onclick="searchData('<?php echo $data_next; ?>');">
                        Next &raquo;
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>
<?php endif; ?>
