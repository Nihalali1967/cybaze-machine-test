<?php
$flashes = [
    'success' => 'success',
    'error'   => 'danger',
    'info'    => 'info',
    'warning' => 'warning',
];
?>
<?php foreach ($flashes as $key => $class): ?>
    <?php if (session()->getFlashdata($key)): ?>
        <div class="alert alert-<?= $class ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= esc((string) session()->getFlashdata($key)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <strong>Please check the following:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ((array) session()->getFlashdata('errors') as $error): ?>
                <li><?= esc((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
