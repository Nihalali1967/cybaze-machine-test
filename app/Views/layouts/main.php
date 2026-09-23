<?php
/**
 * Shared layout for the admin and customer areas.
 *
 * @var string $title
 */
$role = (string) session()->get('role');
$name = (string) session()->get('name');
$path = trim((string) service('request')->getUri()->getPath(), '/');

$links = $role === 'admin'
    ? [
        'Dashboard'      => 'admin',
        'Packages'       => 'admin/packages',
        'Customers'      => 'admin/customers',
        'Investments'    => 'admin/investments',
        'Process profit' => 'admin/profits/process',
        'Profit history' => 'admin/profits',
        'Withdrawals'    => 'admin/withdrawals',
        'Runs'           => 'admin/profits/runs',
    ]
    : [
        'Dashboard'      => 'customer',
        'Packages'       => 'customer/packages',
        'My investments' => 'customer/investments',
        'Profit history' => 'customer/profits',
        'Withdraw'       => 'customer/withdrawals',
    ];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Investment Manager') ?> · Investment Manager</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <style>
        body { background-color: #f4f6f9; }
        .stat-card { border: 0; border-radius: .65rem; }
        .stat-value { font-size: 1.5rem; font-weight: 600; line-height: 1.2; }
        .stat-label { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
        .nav-link.active { font-weight: 600; }
        .table > :not(caption) > * > * { padding: .6rem .75rem; }
        .card { border: 0; border-radius: .65rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="<?= site_url($role === 'admin' ? 'admin' : 'customer') ?>">
            <span class="text-warning">◆</span> Investment Manager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($links as $label => $link): ?>
                    <?php $isActive = $path === $link || str_starts_with($path, $link . '/'); ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= site_url($link) ?>"><?= esc($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small">
                    <?= esc($name) ?><?= $role !== '' ? ' · ' . esc(ucfirst($role)) : '' ?>
                </span>
                <a class="btn btn-sm btn-outline-light" href="<?= site_url('logout') ?>">Sign out</a>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0"><?= esc($title ?? '') ?></h1>
            <?php if (isset($subtitle)): ?>
                <p class="text-secondary mb-0 small"><?= esc($subtitle) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <?= $this->renderSection('actions') ?>
        </div>
    </div>

    <?= $this->include('partials/alerts') ?>
    <?= $this->renderSection('content') ?>
</main>

<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
