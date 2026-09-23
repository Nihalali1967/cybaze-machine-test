<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('admin/investments/create') ?>" class="btn btn-outline-primary">Assign investment</a>
    <a href="<?= site_url('admin/profits/process') ?>" class="btn btn-primary">
        Process profit<?= $dueCount > 0 ? ' (' . $dueCount . ' due)' : '' ?>
    </a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php if ($dueCount > 0): ?>
    <div class="alert alert-warning shadow-sm">
        <strong><?= $dueCount ?></strong> active investment<?= $dueCount === 1 ? '' : 's' ?> have profit due as of <?= fdate($today) ?>.
        <a href="<?= site_url('admin/profits/process') ?>" class="alert-link">Review and process now</a>.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Active investments</div>
                <div class="stat-value"><?= (int) $summary['active_count'] ?></div>
                <div class="small text-secondary">of <?= (int) $summary['total_count'] ?> total</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Capital invested</div>
                <div class="stat-value"><?= inr($summary['invested']) ?></div>
                <div class="small text-secondary"><?= inr($summary['active_invested']) ?> active</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Profit generated</div>
                <div class="stat-value"><?= inr($profitTotals['total']) ?></div>
                <div class="small text-secondary"><?= (int) $profitTotals['rows'] ?> profit entries</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Awaiting withdrawal</div>
                <div class="stat-value text-primary"><?= inr($profitTotals['available']) ?></div>
                <div class="small text-secondary"><?= inr($withdrawalTotals['amount']) ?> already paid out</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Latest profit entries</span>
                <a href="<?= site_url('admin/profits') ?>" class="small">Full history</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th class="text-end">Investment</th>
                            <th class="text-end">Profit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($recentProfits === []): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">No profit has been generated yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentProfits as $profit): ?>
                        <tr>
                            <td><?= fdate($profit['due_date']) ?></td>
                            <td><?= esc($profit['customer_name']) ?></td>
                            <td class="text-end"><?= inr($profit['investment_amount']) ?></td>
                            <td class="text-end fw-semibold"><?= inr($profit['profit_amount']) ?></td>
                            <td><?= status_badge($profit['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent processing runs</span>
                <a href="<?= site_url('admin/profits/runs') ?>" class="small">All runs</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Run</th>
                            <th class="text-end">Created</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($recentRuns === []): ?>
                        <tr><td colspan="3" class="text-center text-secondary py-4">Profit has never been processed.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentRuns as $run): ?>
                        <tr>
                            <td>
                                <?= fdate($run['run_at'], 'd M Y H:i') ?>
                                <div class="small">
                                    <?= $run['is_dry_run'] ? '<span class="badge text-bg-secondary">Preview</span>' : '<span class="badge text-bg-success">Committed</span>' ?>
                                    <span class="text-secondary">up to <?= fdate($run['as_of']) ?></span>
                                </div>
                            </td>
                            <td class="text-end"><?= (int) $run['periods_created'] ?></td>
                            <td class="text-end"><?= inr($run['total_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
