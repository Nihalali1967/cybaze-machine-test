<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('customer/investments') ?>" class="btn btn-outline-secondary">Back to investments</a>
    <a href="<?= site_url('customer/withdrawals') ?>" class="btn btn-outline-primary">Withdraw profit</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Locked-in package terms</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Package</dt>
                    <dd class="col-sm-6 text-end"><?= esc($investment['package_name']) ?></dd>

                    <dt class="col-sm-6">Package amount</dt>
                    <dd class="col-sm-6 text-end"><?= inr($investment['package_amount']) ?></dd>

                    <dt class="col-sm-6">Return (annual)</dt>
                    <dd class="col-sm-6 text-end"><?= fpct($investment['return_percentage']) ?></dd>

                    <dt class="col-sm-6">Withdrawal type</dt>
                    <dd class="col-sm-6 text-end"><?= esc(schedule_label($investment['withdrawal_type'])) ?></dd>

                    <dt class="col-sm-6">Profit per period</dt>
                    <dd class="col-sm-6 text-end fw-semibold"><?= inr($investment['period_profit_amount']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Position</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Invested amount</dt>
                    <dd class="col-sm-6 text-end"><?= inr($investment['investment_amount']) ?></dd>

                    <dt class="col-sm-6">Investment date</dt>
                    <dd class="col-sm-6 text-end"><?= fdate($investment['investment_date']) ?></dd>

                    <dt class="col-sm-6">Next profit date</dt>
                    <dd class="col-sm-6 text-end"><?= fdate($investment['next_profit_date']) ?></dd>

                    <dt class="col-sm-6">Payouts received</dt>
                    <dd class="col-sm-6 text-end"><?= (int) $investment['periods_paid'] ?></dd>

                    <dt class="col-sm-6">Maturity</dt>
                    <dd class="col-sm-6 text-end">
                        <?= $investment['maturity_periods'] === null ? 'Open ended' : (int) $investment['maturity_periods'] . ' payouts' ?>
                    </dd>

                    <dt class="col-sm-6">Status</dt>
                    <dd class="col-sm-6 text-end"><?= status_badge($investment['status']) ?></dd>

                    <dt class="col-sm-6">Profit available</dt>
                    <dd class="col-sm-6 text-end fw-semibold"><?= inr($available['amount']) ?></dd>
                </dl>

                <?php if ($available['eligible']): ?>
                    <?= form_open('customer/withdrawals', ['class' => 'mt-3']) ?>
                        <input type="hidden" name="investment_id" value="<?= (int) $investment['id'] ?>">
                        <button type="submit" class="btn btn-success w-100">
                            Withdraw <?= inr($available['amount']) ?> from this position
                        </button>
                    <?= form_close() ?>
                <?php else: ?>
                    <div class="alert alert-secondary small mt-3 mb-0"><?= esc($available['reason']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Profit history for this investment</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Period</th>
                    <th class="text-end">Investment</th>
                    <th class="text-end">Profit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($profits as $profit): ?>
                <tr>
                    <td><?= fdate($profit['due_date']) ?></td>
                    <td><code><?= esc($profit['period_key']) ?></code></td>
                    <td class="text-end"><?= inr($profit['investment_amount']) ?></td>
                    <td class="text-end fw-semibold"><?= inr($profit['profit_amount']) ?></td>
                    <td>
                        <?= status_badge($profit['status']) ?>
                        <?php if ($profit['withdrawal_id'] !== null): ?>
                            <span class="small text-secondary">payout #<?= (int) $profit['withdrawal_id'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($profits === []): ?>
                <tr><td colspan="5" class="text-center text-secondary py-4">
                    No profit yet. Your first payout is due on <?= fdate($investment['next_profit_date']) ?>.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
