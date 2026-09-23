<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Step 1 — choose the processing date</h2>
                <p class="small text-secondary mb-3">
                    Every active investment whose next profit date has arrived on or before this date is processed.
                    If processing was skipped for a while, all missed periods are created in this one run.
                </p>
                <?= form_open('admin/profits/process', ['method' => 'get', 'class' => 'row g-2 align-items-end']) ?>
                    <div class="col-sm-5">
                        <label class="form-label small mb-1" for="as_of">Process up to</label>
                        <input type="date" class="form-control form-control-sm" id="as_of" name="as_of" value="<?= esc($asOf) ?>">
                    </div>
                    <div class="col-sm-7 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Refresh preview</button>
                        <a href="<?= site_url('admin/profits/process') ?>" class="btn btn-sm btn-outline-secondary">Today</a>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Last committed run</h2>
                <?php if ($lastRun === null): ?>
                    <p class="text-secondary small mb-0">Profit has never been processed.</p>
                <?php else: ?>
                    <p class="mb-1 small">
                        <?= fdate($lastRun['run_at'], 'd M Y H:i') ?> by <?= esc($lastRun['run_by_name'] ?? 'system') ?>
                    </p>
                    <p class="small text-secondary mb-0">
                        Created <?= (int) $lastRun['periods_created'] ?> period(s) worth <?= inr($lastRun['total_amount']) ?>
                        up to <?= fdate($lastRun['as_of']) ?>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Step 2 — review what this run will do</span>
        <span class="badge text-bg-secondary">Read-only preview</span>
    </div>
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3">
                    <div class="stat-label text-secondary">Investments scanned</div>
                    <div class="stat-value"><?= (int) $preview['investments_scanned'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3">
                    <div class="stat-label text-secondary">Periods to create</div>
                    <div class="stat-value text-success"><?= (int) $preview['periods_created'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3">
                    <div class="stat-label text-secondary">Already present</div>
                    <div class="stat-value text-secondary"><?= (int) $preview['periods_skipped'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3">
                    <div class="stat-label text-secondary">Total profit</div>
                    <div class="stat-value"><?= inr($preview['total_amount']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($preview['details'] === []): ?>
    <div class="alert alert-info shadow-sm">
        Nothing is due as of <?= fdate($asOf) ?>. No active investment has reached its next profit date —
        this run would create no entries at all.
    </div>
<?php else: ?>
    <?php foreach ($preview['details'] as $detail): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2">
                <span>
                    <span class="fw-semibold"><?= esc($detail['customer_name']) ?></span>
                    <span class="text-secondary small">
                        · <?= esc($detail['package_name']) ?> · <?= inr($detail['investment_amount']) ?>                        · <?= esc(schedule_label($detail['withdrawal_type'])) ?>
                    </span>
                </span>
                <span class="small">
                    <?= (int) $detail['created'] ?> new period(s) worth <span class="fw-semibold"><?= inr($detail['amount']) ?></span>
                    <?= (int) $detail['skipped'] > 0 ? ' · ' . (int) $detail['skipped'] . ' already present' : '' ?>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Due date</th>
                            <th>Period</th>
                            <th class="text-end">Profit</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detail['periods'] as $period): ?>
                        <tr>
                            <td><?= fdate($period['due_date']) ?></td>
                            <td><code><?= esc($period['period_key']) ?></code></td>
                            <td class="text-end"><?= inr($period['amount']) ?></td>
                            <td>
                                <?= $period['state'] === 'created'
                                    ? '<span class="badge text-bg-success">will be created</span>'
                                    : '<span class="badge text-bg-secondary">already exists — skipped</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-secondary">
                After this run the next profit date becomes <strong><?= fdate($detail['next_profit_date']) ?></strong>
                and the investment status becomes <strong><?= esc($detail['status']) ?></strong>.
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h6 mb-1">Step 3 — commit</h2>
            <p class="small text-secondary mb-0">
                Running this again for the same date creates nothing new: each period is protected by a
                <code>UNIQUE (investment_id, due_date)</code> constraint.
            </p>
        </div>
        <?php if ((int) $preview['periods_created'] > 0): ?>
            <?= form_open('admin/profits/process') ?>
                <input type="hidden" name="as_of" value="<?= esc($asOf) ?>">
                <button type="submit" class="btn btn-success">
                    Process <?= (int) $preview['periods_created'] ?> period(s) worth <?= inr($preview['total_amount']) ?>
                </button>
            <?= form_close() ?>
        <?php else: ?>
            <button type="button" class="btn btn-secondary" disabled>Nothing to process</button>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between mt-3">
    <a href="<?= site_url('admin/profits') ?>" class="small">View profit history</a>
    <a href="<?= site_url('admin/profits/runs') ?>" class="small">View processing runs</a>
</div>

<?= $this->endSection() ?>
