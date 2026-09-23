<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$periodsPerYear = \App\Libraries\ProfitCalculator::periodsPerYear((string) $package['withdrawal_type']);
$packageData    = [
    'name'   => $package['name'],
    'amount' => (float) $package['amount'],
    'rate'   => (float) $package['return_percentage'],
    'type'   => (string) $package['withdrawal_type'],
];
?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <?= form_open('customer/invest') ?>
                    <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Package</label>
                        <input type="text" class="form-control" value="<?= esc($package['name']) ?>" disabled>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="amount">Investment amount (₹)</label>
                            <input type="number" step="0.01" min="<?= esc((string) $package['amount']) ?>" class="form-control"
                                   id="amount" name="amount" value="<?= esc(old('amount') ?? (string) $package['amount']) ?>">
                            <div class="form-text">Minimum <?= inr($package['amount']) ?>.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="investment_date">Investment date</label>
                            <input type="date" class="form-control" id="investment_date" name="investment_date"
                                   max="<?= $today ?>" value="<?= esc(old('investment_date') ?? $today) ?>">
                        </div>
                    </div>

                    <div class="alert alert-secondary" id="profit-hint"></div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Confirm investment</button>
                        <a href="<?= site_url('customer/packages') ?>" class="btn btn-outline-secondary">Back</a>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Package terms you are accepting</h2>
                <dl class="row mb-0 small">
                    <dt class="col-7 fw-normal text-secondary">Package amount</dt>
                    <dd class="col-5 text-end"><?= inr($package['amount']) ?></dd>

                    <dt class="col-7 fw-normal text-secondary">Return (annual)</dt>
                    <dd class="col-5 text-end"><?= fpct($package['return_percentage']) ?></dd>

                    <dt class="col-7 fw-normal text-secondary">Withdrawal</dt>
                    <dd class="col-5 text-end"><?= esc(schedule_label($package['withdrawal_type'])) ?></dd>

                    <dt class="col-7 fw-normal text-secondary">Profit per payout</dt>
                    <dd class="col-5 text-end fw-semibold">
                        <?= inr(\App\Libraries\ProfitCalculator::periodProfit((float) $package['amount'], (float) $package['return_percentage'], (string) $package['withdrawal_type'])) ?>
                    </dd>
                </dl>
                <hr>
                <p class="small text-secondary mb-0">
                    Your first payout falls due one full <?= esc(str_replace('ly', '', (string) $package['withdrawal_type'])) ?>
                    (<?= $periodsPerYear ?> per year) after the investment date.
                    These terms are copied onto your investment and cannot be changed by later package edits.
                </p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var pkg     = <?= json_encode($packageData) ?>;
    var perYear = <?= $periodsPerYear ?>;

    var amountInput = document.getElementById('amount');
    var hint        = document.getElementById('profit-hint');

    function refresh() {
        var amount = parseFloat(amountInput.value) || pkg.amount;
        var profit = amount * (pkg.rate / 100) / perYear;

        hint.innerHTML = 'At <strong>' + pkg.rate + '% p.a.</strong> paid <strong>' + pkg.type + '</strong>, '
            + 'this position returns <strong>₹' + profit.toFixed(2) + '</strong> per payout '
            + '(' + perYear + ' payouts per year).';
    }

    amountInput.addEventListener('input', refresh);
    refresh();
})();
</script>
<?= $this->endSection() ?>
