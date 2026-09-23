<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <?= form_open('admin/customers') ?>

                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?= esc(old('name') ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= esc(old('email') ?? '') ?>">
                        <div class="form-text">Used to sign in to the customer portal.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="text" class="form-control" id="password" name="password" required minlength="6" value="<?= esc(old('password') ?? '') ?>">
                            <div class="form-text">Minimum 6 characters.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="phone">Phone (optional)</label>
                            <input type="text" class="form-control" id="phone" name="phone" maxlength="15" value="<?= esc(old('phone') ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create customer</button>
                        <a href="<?= site_url('admin/customers') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
