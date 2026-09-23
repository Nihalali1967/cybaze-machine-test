<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Total paid out</div>
                <div class="stat-value text-success"><?= inr($totals['amount']) ?></div>
                <div class="small text-secondary"><?= (int) $totals['count'] ?> payout(s)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label text-secondary">Still outstanding</div>
                <div class="stat-value text-primary"><?= inr($outstanding) ?></div>
                <div class="small text-secondary">profit available but not yet withdrawn</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?= form_open('admin/withdrawals', ['method' => 'get', 'class' => 'row g-2 align-items-end']) ?>
            <div class="col-md-4">
                <label class="form-label small mb-1" for="search">Customer</label>
                <input type="text" class="form-control form-control-sm" id="search" name="search"
                       placeholder="Name or email" value="<?= esc($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All</option>
                    <?php foreach (['paid', 'pending', 'approved', 'rejected'] as $status): ?>
                        <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= site_url('admin/withdrawals') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        <?= form_close() ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Requested</th>
                    <th>Customer</th>
                    <th>Investment</th>
                    <th class="text-end">Entries</th>
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
                        <div><?= esc($withdrawal['customer_name']) ?></div>
                        <div class="small text-secondary"><?= esc($withdrawal['customer_email']) ?></div>
                    </td>
                    <td>
                        <?php if ($withdrawal['investment_id'] === null): ?>
                            <span class="text-secondary">All positions</span>
                        <?php else: ?>
                            #<?= (int) $withdrawal['investment_id'] ?> · <?= esc($withdrawal['package_name']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= (int) $withdrawal['profit_count'] ?></td>
                    <td class="text-end fw-semibold"><?= inr($withdrawal['amount']) ?></td>
                    <td><?= status_badge($withdrawal['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($withdrawals === []): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No withdrawals yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
