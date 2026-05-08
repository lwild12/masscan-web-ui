<?php
declare(strict_types=1);
define('DOC_ROOT', __DIR__ . '/');
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
include __DIR__ . '/includes/header.php';
?>
<div class="container-fluid mt-5 pt-3">
    <h4 class="mx-2 mb-3"><i class="bi bi-play-circle me-2"></i>Run a Scan</h4>

    <div class="row g-3 mx-0">
        <!-- Scan form -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Scan Configuration</div>
                <div class="card-body">
                    <div id="scan-alerts"></div>
                    <form id="scan-form" onsubmit="return startScan();">
                        <div class="mb-3">
                            <label for="scan-target" class="form-label fw-semibold">
                                Target <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="scan-target" name="target"
                                   placeholder="192.168.1.0/24  or  10.0.0.1  or  10.0.0.1-10.0.0.50"
                                   required>
                            <div class="form-text">IP address, CIDR range, or dash-separated range.</div>
                        </div>
                        <div class="mb-3">
                            <label for="scan-ports" class="form-label fw-semibold">
                                Ports <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="scan-ports" name="ports"
                                   placeholder="80,443,22  or  1-1024  or  top100"
                                   value="80,443,22,21,25,53,8080,8443"
                                   required>
                            <div class="form-text">Comma-separated ports, ranges, or <code>top100</code> / <code>top1000</code>.</div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="scan-rate" class="form-label fw-semibold">Rate (pkt/s)</label>
                                <input type="number" class="form-control" id="scan-rate" name="rate"
                                       value="1000" min="1" max="1000000">
                                <div class="form-text">1000 is safe; be cautious on production networks.</div>
                            </div>
                            <div class="col-6 d-flex align-items-end pb-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="scan-banners" name="banners" value="1">
                                    <label class="form-check-label" for="scan-banners">
                                        Grab banners <span class="text-muted small">(slower)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <strong>Only scan networks you own or have written permission to scan.</strong>
                            Unauthorised scanning may be illegal.
                        </div>

                        <button type="submit" class="btn btn-danger w-100" id="scan-submit-btn">
                            <i class="bi bi-play-fill me-1"></i> Start Scan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Live output / status -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <span><i class="bi bi-terminal me-1"></i>Scan Output</span>
                    <span class="ms-auto" id="scan-status-badge"></span>
                </div>
                <div class="card-body p-0">
                    <div id="scan-output"
                         style="min-height:320px; max-height:520px; overflow-y:auto;
                                font-family:monospace; font-size:0.82rem; padding:12px;
                                white-space:pre-wrap; word-break:break-all;">
                        <span class="text-muted">Waiting for scan to start…</span>
                    </div>
                </div>
                <div class="card-footer d-none" id="scan-results-footer">
                    <a href="./" class="btn btn-success btn-sm">
                        <i class="bi bi-search me-1"></i> Browse scan results
                    </a>
                    <a href="./dashboard.php" class="btn btn-outline-secondary btn-sm ms-2">
                        <i class="bi bi-bar-chart-line me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var scanPollTimer = null;
var currentJobId  = null;

function startScan() {
    var target  = document.getElementById('scan-target').value.trim();
    var ports   = document.getElementById('scan-ports').value.trim();
    var rate    = parseInt(document.getElementById('scan-rate').value) || 1000;
    var banners = document.getElementById('scan-banners').checked ? 1 : 0;

    document.getElementById('scan-alerts').innerHTML = '';
    document.getElementById('scan-output').textContent = 'Starting scan…\n';
    document.getElementById('scan-submit-btn').disabled = true;
    document.getElementById('scan-results-footer').classList.add('d-none');
    setScanBadge('running');

    $.ajax({
        url:  './ajax-scan.php',
        type: 'post',
        data: { target: target, ports: ports, rate: rate, banners: banners },
        dataType: 'json',
        success: function (res) {
            if (res.error) {
                showScanAlert(res.error);
                document.getElementById('scan-submit-btn').disabled = false;
                setScanBadge('');
                return;
            }
            currentJobId = res.job_id;
            pollScanStatus();
        },
        error: function () {
            showScanAlert('Request failed. Is the server running?');
            document.getElementById('scan-submit-btn').disabled = false;
            setScanBadge('');
        }
    });
    return false;
}

function pollScanStatus() {
    if (!currentJobId) return;
    $.ajax({
        url:      './includes/scan_status.php',
        type:     'get',
        data:     { job_id: currentJobId },
        dataType: 'json',
        success: function (res) {
            var out = document.getElementById('scan-output');
            out.textContent = res.output || '';
            out.scrollTop   = out.scrollHeight;

            setScanBadge(res.status);

            if (res.status === 'running') {
                scanPollTimer = setTimeout(pollScanStatus, 2000);
            } else {
                document.getElementById('scan-submit-btn').disabled = false;
                currentJobId = null;
                if (res.status === 'done') {
                    document.getElementById('scan-results-footer').classList.remove('d-none');
                }
                if (res.status === 'failed') {
                    showScanAlert('Scan failed: ' + (res.error || 'unknown error'));
                }
            }
        },
        error: function () {
            scanPollTimer = setTimeout(pollScanStatus, 5000);
        }
    });
}

function setScanBadge(status) {
    var el  = document.getElementById('scan-status-badge');
    var map = {
        running: ['bg-primary',   'Running…'],
        done:    ['bg-success',   'Done'],
        failed:  ['bg-danger',    'Failed'],
        '':      ['',             '']
    };
    var s = map[status] || map[''];
    el.innerHTML = s[0] ? '<span class="badge ' + s[0] + '">' + s[1] + '</span>' : '';
}

function showScanAlert(msg) {
    document.getElementById('scan-alerts').innerHTML =
        '<div class="alert alert-danger alert-dismissible py-2 small">' + msg +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
