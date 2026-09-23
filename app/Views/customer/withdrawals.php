<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="stat-label text-secondary">Available to withdraw now</div>
            <div class="stat-value text-primary"><?= inr($available['amount']) ?></div>
            <div class="small text-secondary">
                <?= $available['eligible']
                    ? (int) $available['count'] . ' profit period(s) ready'
                    : esc($available['reason']) ?>
            </div>
        </div>
        <?php if ($available['eligible']): ?>
            <?= form_open('customer/withdrawals') ?>
                <input type="hidden" name="investment_id" value="all">
                <button type="submit" class="btn btn-success">
                    Withdraw all <?= inr($available['amount']) ?>
                </button>
            <?= form_close() ?>
        <?php else: ?>
            <button type="button" class="btn btn-secondary" disabled>Nothing available</button>
        <?php endif; ?>
    </div>
</div>

<p class="small text-secondary">
    A payout is only possible when the investment is not cancelled, the profit period has been reached
    and the profit has not already been withdrawn. Every check below is applied again when you submit.
</p>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Position by position</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Package</th>
                    <th>Investment status</th>
                    <th>Next profit</th>
                    <th class="text-end">Available profit</th>
                    <th>Eligibility</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($investments as $investment): ?>
                <?php $position = $breakdown[(int) $investment['id']]; ?>
                <tr>
                    <td class="text-secondary"><?= (int) $investment['id'] ?></td>
                    <td>
                        <div><?= esc($investment['package_name']) ?></div>
                        <div class="small text-secondary">
                            <?= fpct($investment['return_percentage']) ?> p.a. ·
                            <?= esc(schedule_label($investment['withdrawal_type'])) ?>
                        </div>
                    </td>
                    <td><?= status_badge($investment['status']) ?></td>
                    <td><?= fdate($investment['next_profit_date']) ?></td>
                    <td class="text-end <?= $position['eligible'] ? 'fw-semibold' : 'text-secondary' ?>">
                        <?= inr($position['amount']) ?>
                    </td>
                    <td class="small">
                        <?= $position['eligible']
                            ? '<span class="text-success">Ready — ' . (int) $position['count'] . ' period(s)</span>'
                            : '<span class="text-secondary">' . esc($position['reason']) . '</span>' ?>
                    </td>
                    <td class="text-end">
                        <?php if ($position['eligible']): ?>
                            <?= form_open('customer/withdrawals', ['class' => 'd-inline']) ?>
                                <input type="hidden" name="investment_id" value="<?= (int) $investment['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Withdraw</button>
                            <?= form_close() ?>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Not ready</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($investments === []): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">You have no investments yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Total withdrawn</div>
                <div class="stat-value text-success"><?= inr($totals['amount']) ?></div>
                <div class="small text-secondary"><?= (int) $totals['count'] ?> payout(s)</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">My payouts</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Requested</th>
                            <th>Scope</th>
                            <th class="text-end">Periods</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td class="text-secondary"><?= (int) $withdrawal['id'] ?></td>
                            <td><?= fdate($withdrawal['requested_at'], 'd M Y H:i') ?></td>
                            <td>
                                <?= $withdrawal['investment_id'] === null
                                    ? '<span class="text-secondary">All positions</span>'
                                    : '#' . (int) $withdrawal['investment_id'] . ' · ' . esc((string) $withdrawal['package_name']) ?>
                            </td>
                            <td class="text-end"><?= (int) $withdrawal['profit_count'] ?></td>
                            <td class="text-end fw-semibold"><?= inr($withdrawal['amount']) ?></td>
                            <td><?= status_badge($withdrawal['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($withdrawals === []): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">No payouts yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
