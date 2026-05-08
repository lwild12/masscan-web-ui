<div class="card mb-3">
    <div class="card-header d-flex align-items-center">
        <h5 class="mb-0 me-auto"><i class="bi bi-globe me-1"></i> Results</h5>
        <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Save
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="javascript:void(0);"
                       onclick="return exportResultsToXML('<?php echo http_build_query($filter); ?>');"
                       id="export-link">
                        <i class="bi bi-file-earmark-code me-1"></i> Export to XML
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="export-csv.php" id="export-csv-link">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="card-body p-2" id="ajax-list-container">
        <?php require __DIR__ . '/list.php'; ?>
    </div>
</div>
