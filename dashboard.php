<?php
declare(strict_types=1);
define('DOC_ROOT', __DIR__ . '/');
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

// Fetch stats — if db is empty or jobs table doesn't exist yet, degrade gracefully
try {
    $stats = getStats();
} catch (Throwable $e) {
    $stats = [
        'total_ips'     => 0,
        'total_records' => 0,
        'top_ports'     => [],
        'top_services'  => [],
        'timeline'      => [],
        'recent_jobs'   => [],
    ];
}

include __DIR__ . '/includes/header.php';
?>
<div class="container-fluid mt-5 pt-3">
    <h4 class="mx-2 mb-3"><i class="bi bi-bar-chart-line me-2"></i>Dashboard</h4>

    <!-- Stat cards -->
    <div class="row g-3 mx-0 mb-4">
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="display-6 fw-bold text-danger"><?php echo number_format($stats['total_ips']); ?></div>
                    <div class="text-muted mt-1"><i class="bi bi-hdd-network me-1"></i>Unique Hosts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="display-6 fw-bold text-danger"><?php echo number_format($stats['total_records']); ?></div>
                    <div class="text-muted mt-1"><i class="bi bi-list-ul me-1"></i>Total Records</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="display-6 fw-bold text-danger"><?php echo count($stats['top_ports']); ?></div>
                    <div class="text-muted mt-1"><i class="bi bi-door-open me-1"></i>Distinct Open Ports (top 10)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="display-6 fw-bold text-danger"><?php echo count($stats['recent_jobs']); ?></div>
                    <div class="text-muted mt-1"><i class="bi bi-play-circle me-1"></i>Recent Scans</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mx-0 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-door-open me-1"></i>Top 10 Open Ports</div>
                <div class="card-body">
                    <canvas id="portsChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-gear me-1"></i>Top 10 Services</div>
                <div class="card-body">
                    <canvas id="servicesChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan timeline -->
    <?php if (!empty($stats['timeline'])): ?>
    <div class="row g-3 mx-0 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-calendar3 me-1"></i>Scan Activity (last 30 days)</div>
                <div class="card-body">
                    <canvas id="timelineChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent jobs -->
    <?php if (!empty($stats['recent_jobs'])): ?>
    <div class="row g-3 mx-0 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-1"></i>Recent Scans</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Target</th>
                                <th>Ports</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Finished</th>
                                <th>Records</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stats['recent_jobs'] as $job): ?>
                            <tr>
                                <td><?php echo htmlentities($job['target']); ?></td>
                                <td><?php echo htmlentities($job['ports']); ?></td>
                                <td>
                                    <?php
                                    $badge = match($job['status']) {
                                        'done'    => 'bg-success',
                                        'running' => 'bg-primary',
                                        'failed'  => 'bg-danger',
                                        default   => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo htmlentities($job['status']); ?></span>
                                </td>
                                <td><?php echo htmlentities($job['started_at']); ?></td>
                                <td><?php echo $job['finished_at'] ? htmlentities($job['finished_at']) : '—'; ?></td>
                                <td><?php echo $job['record_count'] !== null ? number_format((int) $job['record_count']) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($stats['total_records'] === 0): ?>
    <div class="alert alert-info mx-2">
        <i class="bi bi-info-circle me-2"></i>
        No scan data yet.
        <a href="./scan.php">Run a scan</a> or
        <a href="javascript:void(0);" onclick="showImportHelp()">import an existing XML file</a> to get started.
    </div>
    <?php endif; ?>
</div>

<!-- Modal (for import help) -->
<div id="myModal" class="modal fade" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<script>
var portsData    = <?php echo json_encode(array_column($stats['top_ports'],    'port_id')); ?>;
var portsCounts  = <?php echo json_encode(array_map('intval', array_column($stats['top_ports'],    'c'))); ?>;
var servicesData = <?php echo json_encode(array_column($stats['top_services'], 'service')); ?>;
var servicesCounts = <?php echo json_encode(array_map('intval', array_column($stats['top_services'], 'c'))); ?>;
var timelineDays   = <?php echo json_encode(array_reverse(array_column($stats['timeline'], 'day'))); ?>;
var timelineCounts = <?php echo json_encode(array_map('intval', array_reverse(array_column($stats['timeline'], 'c')))); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="./assets/dashboard.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
