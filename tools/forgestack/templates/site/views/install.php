<?php
declare(strict_types=1);
$appName = (string)env('APP_NAME', 'Application');
$installed = (bool)($installed ?? false);
$error = (string)($error ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Install · <?= e($appName) ?></title>
  <style>
    :root{--ink:#14211d;--muted:#60716a;--line:#dbe4dd;--brand:#146b5a;--paper:#f5f3ec;--bad:#a33}
    *{box-sizing:border-box}
    body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.6 system-ui,"Segoe UI",sans-serif}
    .card{max-width:660px;margin:8vh auto;background:#fff;border:1px solid var(--line);border-radius:18px;padding:38px;box-shadow:0 18px 50px rgba(20,33,29,.08)}
    h1{font-size:1.7rem;margin:0 0 6px}
    .lede{color:var(--muted);margin:0 0 24px}
    label{display:block;font-weight:600;font-size:.86rem;margin:16px 0 6px}
    input[type=password]{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:10px;font:inherit}
    button{margin-top:22px;width:100%;padding:14px;border:0;border-radius:10px;background:var(--brand);color:#fff;font:700 1rem system-ui,sans-serif;cursor:pointer}
    .alert{padding:13px 16px;border-radius:10px;background:#fdecec;color:#8a1f1f;margin-bottom:18px}
    .ok{background:#e8f7f1;color:#0b5c46}
    .hint{color:var(--muted);font-size:.82rem}
    code{background:#eef3ef;padding:2px 6px;border-radius:5px}
    .check{display:flex;gap:9px;align-items:center;font-size:.9rem;color:var(--muted);margin-top:14px}
    .check input{width:auto}
    a{color:var(--brand)}
  </style>
</head>
<body>
<div class="card">
  <h1>Install <?= e($appName) ?></h1>
  <p class="lede">One-time setup. Creates the tables, seeds reference data and creates the administrator seeded in <code>.env</code>.</p>
  <?php foreach (($flashes ?? []) as $flash): ?>
    <div class="alert <?= $flash['type'] === 'success' ? 'ok' : '' ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>
  <?php if ($error !== ''): ?>
    <div class="alert"><?= e($error) ?></div>
  <?php endif; ?>
  <?php if ($installed): ?>
    <div class="alert ok">The application is already installed. Delete <code>public/install.php</code> from the server.</div>
    <p><a href="<?= e(url('/')) ?>">Open the site &rarr;</a></p>
  <?php else: ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="install_key">Installation key</label>
      <input id="install_key" name="install_key" type="password" required>
      <p class="hint">The value of <code>INSTALL_KEY</code> in your <code>.env</code> file.</p>

      <label class="check"><input type="checkbox" name="seed_demo" value="1" checked> Seed the reference/demo records from <code>database/seed.sql</code></label>
      <p class="hint">Administrator: <strong><?= e((string)env('ADMIN_EMAIL', 'not set')) ?></strong> — password comes from <code>ADMIN_PASSWORD</code> in <code>.env</code>.</p>

      <button type="submit">Run installation</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
