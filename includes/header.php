<?php
$pageTitle  = $pageTitle  ?? 'Velvet Vogue';
$activePage = $activePage ?? '';
$user       = current_user();
$flash      = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Velvet Vogue — considered clothing and modern luxury essentials, crafted for a life well dressed. Colombo, Sri Lanka.">
  <title><?= e($pageTitle) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
</head>
<body>

  <header class="site-header">
    <nav class="nav wrap" aria-label="Primary">
      <a href="<?= e(base_url('index.php')) ?>" class="brand">Velvet<span>Vogue</span></a>
      <ul class="nav-links" id="nav-links">
        <li><a href="<?= e(base_url('index.php')) ?>"    class="<?= $activePage==='home'?'active':'' ?>">Home</a></li>
        <li><a href="<?= e(base_url('products.php')) ?>" class="<?= $activePage==='shop'?'active':'' ?>">Shop</a></li>
        <li><a href="<?= e(base_url('contact.php')) ?>"  class="<?= $activePage==='contact'?'active':'' ?>">Contact</a></li>
        <li><a href="<?= e(base_url('account.php')) ?>"  class="<?= $activePage==='account'?'active':'' ?>"><?= $user ? 'My Account' : 'Account' ?></a></li>
        <?php if (is_admin()): ?>
          <li><a href="<?= e(base_url('admin/index.php')) ?>" class="<?= $activePage==='admin'?'active':'' ?>">Admin</a></li>
        <?php endif; ?>
      </ul>
      <div class="nav-actions">
        <a href="<?= e(base_url('cart.php')) ?>" class="cart-link">Cart
          <span class="cart-count" <?= cart_count() ? '' : 'style="display:none"' ?>><?= cart_count() ?></span>
        </a>
        <?php if ($user): ?>
          <a href="<?= e(base_url('logout.php')) ?>" class="cart-link" title="Sign out">Sign out</a>
        <?php endif; ?>
        <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="nav-links">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
  </header>

  <?php if ($flash): ?>
    <noscript>
      <div class="site-alert site-alert--<?= e($flash['type']) ?>">
        <div class="wrap"><?= e($flash['message']) ?></div>
      </div>
    </noscript>
  <?php endif; ?>

  <main>
