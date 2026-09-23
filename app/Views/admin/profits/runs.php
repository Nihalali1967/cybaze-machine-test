<?= $this->extend('layouts/main') ?>

<?= $this->section('actions') ?>
    <a href="<?= site_url('admin/profits/process') ?>" class="btn btn-primary">Process profit</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<p class="text-secondary small">
    Every preview and every committed run is recorded here, so it is always possible to answer
    "has this week's profit already been processed?".
</p>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Run at</th>
                    <th>Type</th>
                    <th>By</th>
                    <th>Processed up to</th>
                    <th class="text-end">Scanned</th>
                    <th class="text-end">Created</th>
                    <th class="text-end">Skipped</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($runs as $run): ?>
                <tr>
                    <td class="text-secondary"><?= (int) $run['id'] ?></td>
                    <td><?= fdate($run['run_at'], 'd M Y H:i') ?></td>
                    <td>
                        <?= $run['is_dry_run']
                            ? '<span class="badge text-bg-secondary">Preview</span>'
                            : '<span class="badge text-bg-success">Committed</span>' ?>
                    </td>
                    <td><?= esc($run['run_by_name'] ?? 'system') ?></td>
                    <td><?= fdate($run['as_of']) ?></td>
                    <td class="text-end"><?= (int) $run['investments_scanned'] ?></td>
                    <td class="text-end fw-semibold"><?= (int) $run['periods_created'] ?></td>
                    <td class="text-end"><?= (int) $run['periods_skipped'] ?></td>
                    <td class="text-end"><?= inr($run['total_amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($runs === []): ?>
                <tr><td colspan="9" class="text-center text-secondary py-4">No runs recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
