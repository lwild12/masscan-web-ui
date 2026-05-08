<?php
declare(strict_types=1);
define('DOC_ROOT', __DIR__ . '/');
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/data_validation.php';
include __DIR__ . '/includes/header.php';
?>
<div class="container-fluid mt-5 pt-3">
    <!-- Search card -->
    <div class="card mb-3 mx-2">
        <div class="card-header d-flex align-items-center" style="cursor:pointer;"
             data-bs-toggle="collapse" data-bs-target="#collapse" aria-expanded="true">
            <h5 class="mb-0 me-2"><i class="bi bi-search me-1"></i> Search</h5>
            <div id="search-params" class="me-auto"></div>
            <i class="bi bi-dash-lg" id="collapse-icon"></i>
        </div>
        <div id="collapse" class="collapse show">
            <div class="card-body">
                <form action="./index.php" onsubmit="return submitSearchForm();" id="form">
                    <input type="hidden" name="action" value="search">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label for="ipAddress" class="form-label">IP Address</label>
                            <input type="text" class="form-control form-control-sm" id="ipAddress" name="ip"
                                   value="<?php echo htmlentities($filter['ip']); ?>"
                                   placeholder="xxx.xxx.xxx.xxx">
                        </div>
                        <div class="col-md-2">
                            <label for="portN" class="form-label">Port Number</label>
                            <input type="text" class="form-control form-control-sm" id="portN" name="port"
                                   value="<?php if ($filter['port'] > 0): echo (int) $filter['port']; endif; ?>"
                                   placeholder="1–65535">
                        </div>
                        <div class="col-md-2">
                            <label for="serviceState" class="form-label">State</label>
                            <input type="text" class="form-control form-control-sm" id="serviceState" name="state"
                                   value="<?php echo htmlentities($filter['state']); ?>"
                                   placeholder="open/closed">
                        </div>
                        <div class="col-md-3">
                            <label for="pProtocol" class="form-label">Protocol</label>
                            <input type="text" class="form-control form-control-sm" id="pProtocol" name="protocol"
                                   value="<?php echo htmlentities($filter['protocol']); ?>"
                                   placeholder="tcp/udp">
                        </div>
                        <div class="col-md-3">
                            <label for="pService" class="form-label">Service</label>
                            <input type="text" class="form-control form-control-sm" id="pService" name="service"
                                   value="<?php echo htmlentities($filter['service']); ?>"
                                   placeholder="ftp/http/smtp">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-7">
                            <label for="pBanner" class="form-label">Service Banner / Title</label>
                            <input type="text" class="form-control form-control-sm" id="pBanner" name="banner"
                                   value="<?php echo htmlentities($filter['banner']); ?>"
                                   placeholder="IIS / Apache / ESMTP">
                        </div>
                        <div class="col-md-2 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="exact-match" value="1" id="exactMatch"
                                       <?php if ($filter['exact-match'] === 1): echo 'checked'; endif; ?>>
                                <label class="form-check-label" for="exactMatch">Exact match</label>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="bi bi-check-lg me-1"></i> Go
                            </button>
                            <span class="ajax-throbber-wrapper-form">
                                <div class="spinner-border spinner-border-sm text-primary d-none" id="ajax-loader-form" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="ajax-search-container" class="mx-2">
        <?php require __DIR__ . '/includes/res-wrapper.php'; ?>
        <?php if (empty($results['data'])): ?>
            <p class="text-end text-muted import-help">
                No data yet? Click <a href="javascript:void(0);" onclick="showImportHelp();">here</a> to learn how to import scan results.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal (shared by IP history & import help) -->
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

<?php include __DIR__ . '/includes/footer.php'; ?>
