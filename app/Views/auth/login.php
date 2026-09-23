<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Investment Manager</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <style> body { background-color: #212529; } </style>
</head>
<body>
<div class="container" style="max-width: 460px; padding-top: 6vh;">
    <div class="text-center text-white mb-4">
        <h1 class="h4 mb-1"><span class="text-warning">◆</span> Investment Manager</h1>
        <p class="text-white-50 small mb-0">Investment &amp; profit management system</p>
    </div>

    <div class="card shadow">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Sign in</h2>

            <?= $this->include('partials/alerts') ?>

            <?= form_open('login') ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= esc(old('email') ?? '') ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            <?= form_close() ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body small">
            <div class="fw-semibold mb-1">Demo accounts</div>
            <div class="text-secondary">Admin: <code>admin@invest.com</code> / <code>admin123</code></div>
            <div class="text-secondary">Customer: <code>john@invest.com</code> / <code>customer123</code></div>
            <div class="text-secondary">Customer: <code>mary@invest.com</code> / <code>customer123</code></div>
        </div>
    </div>
</div>
</body>
</html>
