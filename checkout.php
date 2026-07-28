<?php
require_once __DIR__ . '/includes/functions.php';

$items = cart();
if (!$items) {
    flash('Your bag is empty.', 'error');
    header('Location: ' . base_url('cart.php'));
    exit;
}

$user   = current_user();
$errors = [];
$confirmedOrderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '')                                   { $errors[] = 'Please enter your full name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $errors[] = 'Please enter a valid email address.'; }
    if ($address === '')                                { $errors[] = 'Please enter a delivery address.'; }

    if (!$errors) {
        $subtotal = cart_subtotal();
        $shipping = cart_shipping();
        $total    = cart_total();

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare(
                'INSERT INTO orders (user_id, full_name, email, address, subtotal, shipping, total, status)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $ins->execute([
                $user['id'] ?? null, $name, $email, $address,
                $subtotal, $shipping, $total, 'paid',   // demo: mark as paid
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $line = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, name, size, color, price, qty)
                 VALUES (?,?,?,?,?,?,?)'
            );
            foreach ($items as $item) {
                $line->execute([
                    $orderId, $item['product_id'], $item['name'],
                    $item['size'], $item['color'], $item['price'], $item['qty'],
                ]);
            }
            $pdo->commit();

            cart_clear();
            $confirmedOrderId = $orderId;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong placing your order. Please try again.';
        }
    }
}

$pageTitle  = 'Checkout Velvet Vogue';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--slim">
  <div class="wrap">
    <span class="eyebrow">Almost there</span>
    <h1><?= $confirmedOrderId ? 'Order Confirmed' : 'Checkout' ?></h1>
    <p><?= $confirmedOrderId ? 'Thank you — your order is on its way.' : 'Enter your delivery details to complete your order.' ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($confirmedOrderId): ?>
      <div class="cart-empty">
        <span class="eyebrow eyebrow--dark">Reference #<?= (int)$confirmedOrderId ?></span>
        <h3>Thank you for your order</h3>
        <p>A confirmation has been noted against your account. This is a demonstration store, so no payment was taken and no goods will ship.</p>
        <a href="<?= e(base_url('products.php')) ?>" class="btn">Continue Shopping</a>
        <?php if ($user): ?>
          <a href="<?= e(base_url('account.php')) ?>" class="btn btn--gold">View My Orders</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div>
          <?php if ($errors): ?>
            <div class="form-note" style="background:rgba(128,0,32,.1)">
              <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post" class="form-card" style="margin-top:1.4rem" novalidate>
            <?= csrf_field() ?>
            <span class="eyebrow eyebrow--dark">Delivery Details</span>
            <h2 style="margin-bottom:1.6rem">Where should we send it?</h2>
            <div class="field">
              <label for="name">Full name</label>
              <input type="text" id="name" name="name" required
                     value="<?= e($_POST['name'] ?? ($user['name'] ?? '')) ?>">
            </div>
            <div class="field">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" required
                     value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>">
            </div>
            <div class="field">
              <label for="address">Delivery address</label>
              <textarea id="address" name="address" required><?= e($_POST['address'] ?? '') ?></textarea>
            </div>
            <button class="btn btn--gold btn--block" type="submit">Place Order</button>
          </form>
        </div>

        <aside class="cart-summary" aria-label="Order summary">
          <h3>Order Summary</h3>
          <?php foreach ($items as $item): ?>
            <div class="summary-row">
              <span><?= e($item['name']) ?> · <?= e($item['size']) ?> × <?= (int)$item['qty'] ?></span>
              <span><?= money($item['price'] * $item['qty']) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="summary-row"><span>Delivery</span><span><?= cart_shipping()===0.0?'Complimentary':money(cart_shipping()) ?></span></div>
          <div class="summary-row summary-row--total"><span>Total</span><span><?= money(cart_total()) ?></span></div>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
