<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (in_array($page, ['setup', 'login', 'register_clinic'], true)) {
    $setup = $page === 'setup';
    require __DIR__ . '/views/auth.php';
    exit;
}

require __DIR__ . '/views/layout.php';
