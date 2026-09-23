<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('admin/customers/create') ?>" class="btn btn-primary">New customer</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th class="text-end">Investments</th>
                    <th class="text-end">Active capital</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($customer['name']) ?></td>
                    <td>
                        <div><?= esc($customer['email']) ?></div>
                        <?php if (! empty($customer['phone'])): ?>
                            <div class="small text-secondary"><?= esc($customer['phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= (int) $customer['investment_count'] ?></td>
                    <td class="text-end"><?= inr($customer['active_amount']) ?></td>
                    <td><?= status_badge($customer['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/investments?user_id=' . $customer['id']) ?>" class="btn btn-sm btn-outline-secondary">Investments</a>
                        <?= form_open('admin/customers/' . $customer['id'] . '/toggle', ['class' => 'd-inline']) ?>
                            <button type="submit" class="btn btn-sm btn-outline-<?= $customer['status'] === 'active' ? 'warning' : 'success' ?>">
                                <?= $customer['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        <?= form_close() ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($customers === []): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No customers yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
