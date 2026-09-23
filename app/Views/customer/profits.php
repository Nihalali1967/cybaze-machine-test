<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('customer/withdrawals') ?>" class="btn btn-primary">Withdraw profit</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Total profit earned</div>
                <div class="stat-value"><?= inr($totals['total']) ?></div>
                <div class="small text-secondary"><?= (int) $totals['rows'] ?> entries</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Available</div>
                <div class="stat-value text-primary"><?= inr($totals['available']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Withdrawn</div>
                <div class="stat-value text-success"><?= inr($totals['withdrawn']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?= form_open('customer/profits', ['method' => 'get', 'class' => 'row g-2 align-items-end']) ?>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All</option>
                    <option value="available" <?= $filters['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="withdrawn" <?= $filters['status'] === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="from">Due from</label>
                <input type="date" class="form-control form-control-sm" id="from" name="from" value="<?= esc($filters['from']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="to">Due to</label>
                <input type="date" class="form-control form-control-sm" id="to" name="to" value="<?= esc($filters['to']) ?>">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= site_url('customer/profits') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        <?= form_close() ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Investment</th>
                    <th>Period</th>
                    <th class="text-end">Investment amount</th>
                    <th class="text-end">Profit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($profits as $profit): ?>
                <tr>
                    <td><?= fdate($profit['due_date']) ?></td>
                    <td>
                        <div><?= esc($profit['package_name']) ?></div>
                        <div class="small text-secondary">
                            #<?= (int) $profit['investment_id'] ?> · <?= esc(schedule_label($profit['withdrawal_type'])) ?>
                        </div>
                    </td>
                    <td><code><?= esc($profit['period_key']) ?></code></td>
                    <td class="text-end"><?= inr($profit['investment_amount']) ?></td>
                    <td class="text-end fw-semibold <?= $profit['status'] === 'available' ? 'text-primary' : '' ?>">
                        <?= inr($profit['profit_amount']) ?>
                    </td>
                    <td><?= status_badge($profit['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($profits === []): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No profit entries match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
