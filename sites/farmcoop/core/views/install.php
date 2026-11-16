<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install · <?= e(env('APP_NAME', 'Application')) ?></title>
  <style>
    :root{--ink:#10241f;--muted:#64746e;--line:#dbe4e0;--brand:#0f766e;--bg:#f2f6f4}
    *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 "Segoe UI",system-ui,sans-serif}
    /* The installer is a page of the product, so it sits on the product's own
       plate. The image is the app's, resolved through asset() for cache-busting;
       if it is missing the flat colour above is the fallback. */
    .bg-plate{position:fixed;inset:-8%;z-index:-2;pointer-events:none;
      background:url("<?= e(asset('assets/img/plate.webp')) ?>") 58% 44%/cover no-repeat;
      opacity:.5;filter:saturate(.6) brightness(1.05);
      animation:plate-drift 58s ease-in-out infinite alternate}
    .bg-plate::after{content:"";position:absolute;inset:0;
      background:linear-gradient(180deg,rgba(242,246,244,.74),rgba(242,246,244,.9) 34%,rgba(242,246,244,.95))}
    @keyframes plate-drift{from{transform:translate3d(-1.2%,0,0) scale(1.05)}to{transform:translate3d(1.2%,0,0) scale(1.15)}}
    .card{position:relative;max-width:640px;margin:8vh auto;background:#fff;border:1px solid var(--line);border-radius:18px;padding:38px;box-shadow:0 18px 50px rgba(16,36,31,.14);animation:rise .5s cubic-bezier(.16,1,.3,1) both}
    @keyframes rise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
    h1{font-size:1.85rem;margin:0 0 6px;letter-spacing:-.02em}p.lede{color:var(--muted);margin:0 0 26px}
    label{display:block;font-weight:600;font-size:.86rem;margin:16px 0 6px}
    input{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:10px;font:inherit;transition:border-color .2s ease,box-shadow .2s ease}
    input:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 4px rgba(15,118,110,.14)}
    button{margin-top:24px;width:100%;padding:14px;border:0;border-radius:10px;background:var(--brand);color:#fff;font-weight:700;font:inherit;cursor:pointer;transition:transform .18s ease,box-shadow .2s ease,filter .2s ease}
    button:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(15,118,110,.28);filter:brightness(1.06)}
    button:active{transform:translateY(0) scale(.99)}
    .alert{padding:13px 16px;border-radius:10px;background:#fdecec;color:#8a1f1f;margin-bottom:18px}
    .ok{background:#e8f7f1;color:#0b5c46}
    code{background:#eef3f1;padding:2px 6px;border-radius:5px}
    .hint{color:var(--muted);font-size:.82rem}
    @media(prefers-reduced-motion:reduce){*{animation-duration:.001ms!important;transition-duration:.001ms!important}}
  </style>
</head>
<body>
<div class="bg-plate" aria-hidden="true"></div>
<div class="card">
  <h1>Install <?= e(env('APP_NAME', 'Application')) ?></h1>
  <p class="lede">One-time setup. Creates tables, seeds reference data and creates your administrator account.</p>
  <?php foreach (($flashes ?? []) as $flash): ?>
    <div class="alert <?= $flash['type'] === 'success' ? 'ok' : '' ?>"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>
  <?php if ($installed ?? false): ?>
    <div class="alert ok">The application is already installed. Remove <code>public/install.php</code> from the server.</div>
    <p><a href="<?= e(url('/')) ?>">Open the application →</a></p>
  <?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <label for="install_key">Installation key</label>
      <input id="install_key" name="install_key" type="password" required autocomplete="off">
      <p class="hint">The value of <code>INSTALL_KEY</code> in your <code>.env</code> file.</p>

      <label for="admin_name">Administrator name</label>
      <input id="admin_name" name="admin_name" value="Daniel Abo" required>

      <label for="admin_email">Administrator email</label>
      <input id="admin_email" name="admin_email" type="email" value="<?= e(env('ADMIN_EMAIL', 'abodaniel435@gmail.com')) ?>" required>

      <label for="admin_password">Administrator password</label>
      <input id="admin_password" name="admin_password" type="password" minlength="10" required autocomplete="new-password">
      <p class="hint">At least 10 characters. Stored only as a password hash.</p>

      <label for="demo_password">Demo accounts password (optional)</label>
      <input id="demo_password" name="demo_password" type="password" minlength="10" autocomplete="new-password">
      <p class="hint">Used for the documented demo roles. Leave blank to skip demo accounts.</p>

      <button type="submit">Run installation</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
