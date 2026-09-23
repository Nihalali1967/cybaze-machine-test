<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('customer/packages') ?>" class="btn btn-primary">Invest in a package</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Package</th>
                    <th>Locked-in terms</th>
                    <th class="text-end">Invested</th>
                    <th class="text-end">Profit / period</th>
                    <th>Invested on</th>
                    <th>Next profit</th>
                    <th class="text-end">Paid</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($investments as $investment): ?>
                <tr>
                    <td class="text-secondary"><?= (int) $investment['id'] ?></td>
                    <td class="fw-semibold"><?= esc($investment['package_name']) ?></td>
                    <td class="small text-secondary">
                        <?= fpct($investment['return_percentage']) ?> p.a. · <?= esc(schedule_label($investment['withdrawal_type'])) ?>
                    </td>
                    <td class="text-end"><?= inr($investment['investment_amount']) ?></td>
                    <td class="text-end"><?= inr($investment['period_profit_amount']) ?></td>
                    <td><?= fdate($investment['investment_date']) ?></td>
                    <td>
                        <?= fdate($investment['next_profit_date']) ?>
                        <?php if ($investment['status'] === 'active' && $investment['next_profit_date'] !== null && $investment['next_profit_date'] <= $today): ?>
                            <span class="badge text-bg-warning">due</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= (int) $investment['periods_paid'] ?></td>
                    <td><?= status_badge($investment['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('customer/investments/' . $investment['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($investments === []): ?>
                <tr><td colspan="10" class="text-center text-secondary py-4">
                    No investments yet. <a href="<?= site_url('customer/packages') ?>">Choose a package</a> to get started.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
