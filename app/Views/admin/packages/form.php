<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php $isEdit = $package !== null; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <?= form_open($isEdit ? 'admin/packages/' . $package['id'] : 'admin/packages') ?>

                    <div class="mb-3">
                        <label class="form-label" for="name">Package name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= $isEdit ? old_value($package, 'name') : esc(old('name') ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="amount">Package amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" required
                                   value="<?= $isEdit ? old_value($package, 'amount') : esc(old('amount') ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="return_percentage">Return percentage (annual %)</label>
                            <input type="number" step="0.01" min="0.01" max="100" class="form-control"
                                   id="return_percentage" name="return_percentage" required
                                   value="<?= $isEdit ? old_value($package, 'return_percentage') : esc(old('return_percentage') ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="withdrawal_type">Withdrawal type</label>
                            <select class="form-select" id="withdrawal_type" name="withdrawal_type" required>
                                <?php $selectedType = $isEdit ? old('withdrawal_type', $package['withdrawal_type']) : old('withdrawal_type'); ?>
                                <?php foreach (['weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $selectedType === $value ? 'selected' : '' ?>>
                                        <?= $label ?> (<?= \App\Libraries\ProfitCalculator::periodsPerYear($value) ?> payouts / year)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="status">Status</label>
                            <?php $selectedStatus = $isEdit ? old('status', $package['status']) : old('status', 'active'); ?>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create package' ?></button>
                        <a href="<?= site_url('admin/packages') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">How the return percentage is applied</h2>
                <p class="small text-secondary mb-2">
                    The percentage is treated as an <strong>annual</strong> rate and divided by the number of
                    payout periods in a year:
                </p>
                <ul class="small mb-3">
                    <li>Weekly &rarr; 52 periods</li>
                    <li>Monthly &rarr; 12 periods</li>
                    <li>Quarterly &rarr; 4 periods</li>
                    <li>Yearly &rarr; 1 period</li>
                </ul>
                <div class="alert alert-secondary small mb-0">
                    Example: <?= inr(50000) ?> at 12% paid monthly gives
                    <?= inr(50000) ?> &times; 12% &divide; 12 = <strong><?= inr(500) ?></strong> per month.
                </div>
            </div>
        </div>

        <?php if ($isEdit): ?>
            <div class="alert alert-warning shadow-sm small mt-3 mb-0">
                Editing this package does <strong>not</strong> change any investment that already exists.
                Each investment keeps its own frozen copy of the package name, amount, rate and withdrawal type.
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
