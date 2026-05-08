<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/data_validation.php';

if (isset($_GET['form'])) {
    require __DIR__ . '/includes/res-wrapper.php';
} else {
    require __DIR__ . '/includes/list.php';
}
