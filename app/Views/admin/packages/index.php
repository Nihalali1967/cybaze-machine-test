<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('admin/packages/create') ?>" class="btn btn-primary">New package</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="alert alert-info shadow-sm small">
    <strong>Return percentage is an annual rate</strong>, prorated by the withdrawal frequency.
    Gold at 20% paid quarterly therefore returns <strong><?= inr(2500) ?></strong> per quarter on a <?= inr(50000) ?> investment.
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Package</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Return (p.a.)</th>
                    <th>Withdrawal</th>
                    <th class="text-end">Profit / period</th>
                    <th>Status</th>
                    <th class="text-end">Investments</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($packages as $package): ?>
                <?php
                $periodProfit = \App\Libraries\ProfitCalculator::periodProfit(
                    (float) $package['amount'],
                    (float) $package['return_percentage'],
                    (string) $package['withdrawal_type'],
                );
                ?>
                <tr>
                    <td class="fw-semibold"><?= esc($package['name']) ?></td>
                    <td class="text-end"><?= inr($package['amount']) ?></td>
                    <td class="text-end"><?= fpct($package['return_percentage']) ?></td>
                    <td><?= schedule_label($package['withdrawal_type']) ?></td>
                    <td class="text-end"><?= inr($periodProfit) ?></td>
                    <td><?= status_badge($package['status']) ?></td>
                    <td class="text-end">
                        <?= (int) $package['investment_count'] ?>
                        <?php if ((int) $package['active_count'] > 0): ?>
                            <span class="text-secondary small">(<?= (int) $package['active_count'] ?> active)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/packages/' . $package['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <?= form_open('admin/packages/' . $package['id'] . '/toggle', ['class' => 'd-inline']) ?>
                            <button type="submit" class="btn btn-sm btn-outline-<?= $package['status'] === 'active' ? 'warning' : 'success' ?>">
                                <?= $package['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        <?= form_close() ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($packages === []): ?>
                <tr><td colspan="8" class="text-center text-secondary py-4">No packages yet. Create your first one.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-secondary small mt-3 mb-0">
    Deactivating a package blocks new investments only. Existing investments always keep the terms they were created with,
    so editing a package here can never change a running investment.
</p>

<?= $this->endSection() ?>
