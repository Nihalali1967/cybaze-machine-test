<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('customer/packages') ?>" class="btn btn-primary">Invest in a package</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Total invested</div>
                <div class="stat-value"><?= inr($summary['invested']) ?></div>
                <div class="small text-secondary"><?= (int) $summary['active_count'] ?> active position(s)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Profit available</div>
                <div class="stat-value text-primary"><?= inr($profitTotals['available']) ?></div>
                <div class="small text-secondary"><?= (int) $profitTotals['available_count'] ?> entry(s) ready</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Profit withdrawn</div>
                <div class="stat-value text-success"><?= inr($profitTotals['withdrawn']) ?></div>
                <div class="small text-secondary"><?= (int) $withdrawalTotals['count'] ?> payout(s)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Next profit date</div>
                <div class="stat-value"><?= fdate($summary['next_profit_date'] ?? null, 'd M Y') ?></div>
                <div class="small text-secondary">earliest across active positions</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h6 mb-1">Profit ready to withdraw</h2>
            <?php if ($available['eligible']): ?>
                <p class="mb-0">
                    <span class="fw-semibold"><?= inr($available['amount']) ?></span>
                    <span class="text-secondary small">from <?= (int) $available['count'] ?> profit period(s)</span>
                </p>
            <?php else: ?>
                <p class="mb-0 text-secondary small"><?= esc($available['reason']) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($available['eligible']): ?>
            <?= form_open('customer/withdrawals') ?>
                <input type="hidden" name="investment_id" value="all">
                <button type="submit" class="btn btn-success">Withdraw <?= inr($available['amount']) ?></button>
            <?= form_close() ?>
        <?php else: ?>
            <a href="<?= site_url('customer/withdrawals') ?>" class="btn btn-outline-secondary">Withdrawal details</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">My investments</span>
        <a href="<?= site_url('customer/investments') ?>" class="small">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Package</th>
                    <th>Terms</th>
                    <th class="text-end">Invested</th>
                    <th class="text-end">Profit / period</th>
                    <th>Next profit</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($investments as $investment): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($investment['package_name']) ?></td>
                    <td class="small text-secondary">
                        <?= fpct($investment['return_percentage']) ?> p.a. · <?= esc(schedule_label($investment['withdrawal_type'])) ?>
                    </td>
                    <td class="text-end"><?= inr($investment['investment_amount']) ?></td>
                    <td class="text-end"><?= inr($investment['period_profit_amount']) ?></td>
                    <td>
                        <?= fdate($investment['next_profit_date']) ?>
                        <?php if ($investment['status'] === 'active' && $investment['next_profit_date'] !== null && $investment['next_profit_date'] <= $today): ?>
                            <span class="badge text-bg-warning">due</span>
                        <?php endif; ?>
                    </td>
                    <td><?= status_badge($investment['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('customer/investments/' . $investment['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($investments === []): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">
                    You have no investments yet. <a href="<?= site_url('customer/packages') ?>">Browse the packages</a>.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
