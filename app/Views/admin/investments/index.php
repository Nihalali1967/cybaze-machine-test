<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('admin/investments/create') ?>" class="btn btn-primary">Assign investment</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?= form_open('admin/investments', ['method' => 'get', 'class' => 'row g-2 align-items-end']) ?>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="search">Search</label>
                <input type="text" class="form-control form-control-sm" id="search" name="search"
                       placeholder="Customer, email or package" value="<?= esc($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="user_id">Customer</label>
                <select class="form-select form-select-sm" id="user_id" name="user_id">
                    <option value="">All customers</option>
                    <?php foreach ($customers as $id => $label): ?>
                        <option value="<?= $id ?>" <?= (string) $filters['user_id'] === (string) $id ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= site_url('admin/investments') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                    <th>Customer</th>
                    <th>Package (frozen terms)</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Profit / period</th>
                    <th>Invested</th>
                    <th>Next profit</th>
                    <th class="text-end">Paid</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($investments as $investment): ?>
                <?php $isDue = $investment['status'] === 'active' && $investment['next_profit_date'] !== null && $investment['next_profit_date'] <= $today; ?>
                <tr>
                    <td class="text-secondary"><?= (int) $investment['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= esc($investment['customer_name']) ?></div>
                        <div class="small text-secondary"><?= esc($investment['customer_email']) ?></div>
                    </td>
                    <td>
                        <div><?= esc($investment['package_name']) ?></div>
                        <div class="small text-secondary">
                            <?= fpct($investment['return_percentage']) ?> p.a. ·
                            <?= schedule_label($investment['withdrawal_type']) ?>
                        </div>
                    </td>
                    <td class="text-end"><?= inr($investment['investment_amount']) ?></td>
                    <td class="text-end"><?= inr($investment['period_profit_amount']) ?></td>
                    <td><?= fdate($investment['investment_date']) ?></td>
                    <td>
                        <?= fdate($investment['next_profit_date']) ?>
                        <?php if ($isDue): ?>
                            <span class="badge text-bg-warning">due</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= (int) $investment['periods_paid'] ?></td>
                    <td><?= status_badge($investment['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/investments/' . $investment['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($investments === []): ?>
                <tr><td colspan="10" class="text-center text-secondary py-4">No investments match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
