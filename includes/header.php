<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MASSCAN Web Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="./assets/style.css" rel="stylesheet">
    <script>
        // Apply saved theme before page renders to avoid flash
        (function () {
            var saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', saved);
        })();
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top brand-navbar">
    <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold" href="./">
            <i class="bi bi-radar me-1"></i> MASSCAN Web Interface
        </a>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <a href="./dashboard.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-bar-chart-line me-1"></i> Dashboard
            </a>
            <a href="./scan.php" class="btn btn-sm btn-outline-light">
                <i class="bi bi-play-circle me-1"></i> Scan
            </a>
            <a href="./" class="btn btn-sm btn-outline-light">
                <i class="bi bi-search me-1"></i> Search
            </a>
            <button id="theme-toggle" class="btn btn-sm btn-outline-light" title="Toggle dark mode">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
        </div>
    </div>
</nav>
