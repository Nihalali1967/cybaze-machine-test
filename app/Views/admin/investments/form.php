<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$packageData = [];

foreach ($packages as $package) {
    $packageData[(string) $package['id']] = [
        'name'   => $package['name'],
        'amount' => (float) $package['amount'],
        'rate'   => (float) $package['return_percentage'],
        'type'   => (string) $package['withdrawal_type'],
    ];
}
?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <?= form_open('admin/investments') ?>

                    <div class="mb-3">
                        <label class="form-label" for="user_id">Customer</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Select a customer…</option>
                            <?php foreach ($customers as $id => $label): ?>
                                <option value="<?= $id ?>" <?= (string) old('user_id') === (string) $id ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="package_id">Investment package</label>
                        <select class="form-select" id="package_id" name="package_id" required>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?= (int) $package['id'] ?>" <?= (string) old('package_id') === (string) $package['id'] ? 'selected' : '' ?>>
                                    <?= esc($package['name']) ?> — <?= inr($package['amount']) ?> at <?= fpct($package['return_percentage']) ?>,
                                    <?= schedule_label($package['withdrawal_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only active packages are listed.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="amount">Investment amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount"
                                   value="<?= esc(old('amount') ?? '') ?>">
                            <div class="form-text">Leave as the package amount, or invest more on the same terms.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="investment_date">Investment date</label>
                            <input type="date" class="form-control" id="investment_date" name="investment_date"
                                   max="<?= $today ?>" value="<?= esc(old('investment_date') ?? $today) ?>">
                            <div class="form-text">Past dates are allowed so a payout cycle can be demonstrated.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="maturity_periods">Maturity (number of payouts, optional)</label>
                        <input type="number" min="1" class="form-control" id="maturity_periods" name="maturity_periods"
                               value="<?= esc(old('maturity_periods') ?? '') ?>">
                        <div class="form-text">Leave empty for an open ended investment.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="notes">Notes (optional)</label>
                        <input type="text" class="form-control" id="notes" name="notes" maxlength="255" value="<?= esc(old('notes') ?? '') ?>">
                    </div>

                    <div class="alert alert-secondary" id="profit-hint"></div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create investment</button>
                        <a href="<?= site_url('admin/investments') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">What happens on save</h2>
                <ol class="small text-secondary mb-0">
                    <li>The package is validated (it must be active, and the amount must be at least the package amount).</li>
                    <li>The package name, amount, rate and withdrawal type are <strong>copied onto the investment</strong>.</li>
                    <li>The profit per period is calculated once and frozen onto the investment.</li>
                    <li>The first payout date is set to one full period after the investment date.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var packages = <?= json_encode($packageData) ?>;
    var perYear  = <?= json_encode(\App\Libraries\ProfitCalculator::PERIODS_PER_YEAR) ?>;

    var packageSelect = document.getElementById('package_id');
    var amountInput   = document.getElementById('amount');
    var hint          = document.getElementById('profit-hint');

    function refresh(usePackageAmount) {
        var pkg = packages[packageSelect.value];

        if (!pkg) {
            hint.textContent = '';
            return;
        }

        if (usePackageAmount || amountInput.value === '') {
            amountInput.value = pkg.amount.toFixed(2);
        }

        var amount = parseFloat(amountInput.value) || pkg.amount;
        var profit = amount * (pkg.rate / 100) / perYear[pkg.type];

        hint.innerHTML = 'Frozen terms: <strong>' + pkg.name + '</strong> at <strong>' + pkg.rate + '% p.a.</strong>, '
            + 'paid <strong>' + pkg.type + '</strong> (' + perYear[pkg.type] + ' payouts / year). '
            + 'Profit per period: <strong>₹' + profit.toFixed(2) + '</strong>, starting one '
            + pkg.type.replace('ly', '') + ' after the investment date.';
    }

    packageSelect.addEventListener('change', function () { refresh(true); });
    amountInput.addEventListener('input', function () { refresh(false); });

    refresh(true);
})();
</script>
<?= $this->endSection() ?>
