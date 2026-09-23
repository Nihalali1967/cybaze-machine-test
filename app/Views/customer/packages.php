<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="text-secondary small">
    Only active packages are shown. The return percentage is an annual rate, prorated by how often the package pays out.
</p>

<div class="row g-3">
    <?php foreach ($packages as $package): ?>
        <?php
        $periodProfit = \App\Libraries\ProfitCalculator::periodProfit(
            (float) $package['amount'],
            (float) $package['return_percentage'],
            (string) $package['withdrawal_type'],
        );
        $periodsPerYear = \App\Libraries\ProfitCalculator::periodsPerYear((string) $package['withdrawal_type']);
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h5 mb-0"><?= esc($package['name']) ?></h2>
                        <?= status_badge($package['status']) ?>
                    </div>
                    <p class="text-secondary small mb-3">
                        <?= esc(schedule_label($package['withdrawal_type'])) ?> payout ·
                        <?= $periodsPerYear ?> payout(s) per year
                    </p>

                    <dl class="row mb-3 small">
                        <dt class="col-7 fw-normal text-secondary">Package amount</dt>
                        <dd class="col-5 text-end fw-semibold"><?= inr($package['amount']) ?></dd>

                        <dt class="col-7 fw-normal text-secondary">Return (annual)</dt>
                        <dd class="col-5 text-end"><?= fpct($package['return_percentage']) ?></dd>

                        <dt class="col-7 fw-normal text-secondary">Profit per payout</dt>
                        <dd class="col-5 text-end fw-semibold text-success"><?= inr($periodProfit) ?></dd>
                    </dl>

                    <a href="<?= site_url('customer/invest/' . $package['id']) ?>" class="btn btn-primary mt-auto">Invest now</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($packages === []): ?>
        <div class="col-12">
            <div class="alert alert-info shadow-sm mb-0">No package is currently open for investment.</div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
