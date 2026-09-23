<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('admin/profits?customer=' . urlencode($investment['customer_name'])) ?>" class="btn btn-outline-secondary">Profit history</a>
    <a href="<?= site_url('admin/investments') ?>" class="btn btn-outline-secondary">Back to list</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Frozen package terms</div>
            <div class="card-body">
                <p class="small text-secondary">
                    These values were copied from the package when the investment was created.
                    Editing the package afterwards cannot change them.
                </p>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        <?= esc($investment['customer_name']) ?>
                        <span class="text-secondary small">(<?= esc($investment['customer_email']) ?>)</span>
                    </dd>

                    <dt class="col-sm-4">Package</dt>
                    <dd class="col-sm-8">
                        <?= esc($investment['package_name']) ?>
                        <?php if ($investment['package_id'] !== null): ?>
                            <span class="text-secondary small">· package #<?= (int) $investment['package_id'] ?></span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Package amount</dt>
                    <dd class="col-sm-8"><?= inr($investment['package_amount']) ?></dd>

                    <dt class="col-sm-4">Return (annual)</dt>
                    <dd class="col-sm-8">
                        <?= fpct($investment['return_percentage']) ?>
                        <span class="text-secondary small">· basis: <?= esc($investment['return_basis']) ?></span>
                    </dd>

                    <dt class="col-sm-4">Withdrawal type</dt>
                    <dd class="col-sm-8"><?= schedule_label($investment['withdrawal_type']) ?></dd>

                    <dt class="col-sm-4">Profit per period</dt>
                    <dd class="col-sm-8 fw-semibold"><?= inr($investment['period_profit_amount']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Position</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Invested</dt>
                    <dd class="col-sm-6 text-end"><?= inr($investment['investment_amount']) ?></dd>

                    <dt class="col-sm-6">Investment date</dt>
                    <dd class="col-sm-6 text-end"><?= fdate($investment['investment_date']) ?></dd>

                    <dt class="col-sm-6">Next profit date</dt>
                    <dd class="col-sm-6 text-end">
                        <?= fdate($investment['next_profit_date']) ?>
                        <?php if ($investment['next_profit_date'] !== null && $investment['next_profit_date'] <= $today): ?>
                            <span class="badge text-bg-warning">due</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-6">Periods paid</dt>
                    <dd class="col-sm-6 text-end"><?= (int) $investment['periods_paid'] ?></dd>

                    <dt class="col-sm-6">Maturity</dt>
                    <dd class="col-sm-6 text-end">
                        <?= $investment['maturity_periods'] === null ? 'Open ended' : (int) $investment['maturity_periods'] . ' payouts' ?>
                    </dd>

                    <dt class="col-sm-6">Status</dt>
                    <dd class="col-sm-6 text-end"><?= status_badge($investment['status']) ?></dd>
                </dl>

                <hr>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary">Profit available to withdraw</span>
                    <span class="fw-semibold"><?= inr($available['amount']) ?></span>
                </div>
                <div class="small text-secondary mt-1">
                    <?= $available['eligible']
                        ? $available['count'] . ' profit ' . ($available['count'] === 1 ? 'entry' : 'entries') . ' ready'
                        : esc($available['reason']) ?>
                </div>

                <hr>

                <?php foreach (['active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label): ?>
                    <?php if ($value === $investment['status']) { continue; } ?>
                    <?= form_open('admin/investments/' . $investment['id'] . '/status', ['class' => 'd-inline']) ?>
                        <input type="hidden" name="status" value="<?= $value ?>">
                        <button type="submit" class="btn btn-sm btn-outline-<?= $value === 'cancelled' ? 'danger' : 'secondary' ?> mb-1">
                            Mark <?= $label ?>
                        </button>
                    <?= form_close() ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Profit ledger for this investment</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Due date</th>
                    <th>Period</th>
                    <th>Period start</th>
                    <th class="text-end">Investment</th>
                    <th class="text-end">Profit</th>
                    <th>Status</th>
                    <th>Processed</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($profits as $profit): ?>
                <tr>
                    <td><?= fdate($profit['due_date']) ?></td>
                    <td><code><?= esc($profit['period_key']) ?></code></td>
                    <td class="text-secondary"><?= fdate($profit['period_start']) ?></td>
                    <td class="text-end"><?= inr($profit['investment_amount']) ?></td>
                    <td class="text-end fw-semibold"><?= inr($profit['profit_amount']) ?></td>
                    <td><?= status_badge($profit['status']) ?></td>
                    <td class="small text-secondary"><?= fdate($profit['processed_at'], 'd M Y H:i') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($profits === []): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">
                    No profit generated yet. Use <a href="<?= site_url('admin/profits/process') ?>">Process profit</a> once a period has elapsed.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
