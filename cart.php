<?php
require_once __DIR__ . '/includes/functions.php';

$items    = cart();
$subtotal = cart_subtotal();
$shipping = cart_shipping();
$total    = cart_total();

$pageTitle  = 'Your Bag Velvet Vogue';
$activePage = '';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--slim">
  <div class="wrap">
    <span class="eyebrow">Checkout</span>
    <h1>Your Shopping Bag</h1>
    <p>Review your selections, sizes and colours below, then proceed when you're ready.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$items): ?>
      <div class="cart-empty">
        <h3>Your bag is empty</h3>
        <p>Nothing here yet. Explore the collection and add a few considered pieces.</p>
        <a href="<?= e(base_url('products.php')) ?>" class="btn">Shop the Collection</a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <section aria-label="Bag items">
          <div class="cart-items">
            <?php foreach ($items as $key => $item): $line = $item['price'] * $item['qty']; ?>
              <article class="cart-item">
                <img class="cart-item__media" src="<?= e(base_url($item['image'])) ?>" alt="<?= e($item['name']) ?>"
                     onerror="this.src='https://placehold.co/96x120/800020/f7f3ee?text=VV'">
                <div class="cart-item__info">
                  <h3><?= e($item['name']) ?></h3>
                  <div class="cart-item__meta">
                    <span class="cart-item__size">Size <?= e($item['size']) ?></span>
                    <span class="cart-item__size"><?= e($item['color']) ?></span>
                    <span class="cart-item__unit"><?= money($item['price']) ?> each</span>
                  </div>
                  <form method="post" action="<?= e(base_url('cart-action.php')) ?>" class="qty" style="margin-top:.8rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="key" value="<?= e($key) ?>">
                    <button type="submit" name="qty" value="<?= (int)$item['qty'] - 1 ?>" aria-label="Decrease quantity">&minus;</button>
                    <span><?= (int)$item['qty'] ?></span>
                    <button type="submit" name="qty" value="<?= (int)$item['qty'] + 1 ?>" aria-label="Increase quantity">+</button>
                  </form>
                </div>
                <div class="cart-item__right">
                  <span class="cart-item__line"><?= money($line) ?></span>
                  <form method="post" action="<?= e(base_url('cart-action.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="key" value="<?= e($key) ?>">
                    <button type="submit" class="cart-remove">Remove</button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <p style="margin-top:1.4rem">
            <form method="post" action="<?= e(base_url('cart-action.php')) ?>" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear">
              <button type="submit" class="cart-remove">Clear entire bag</button>
            </form>
          </p>
        </section>

        <aside class="cart-summary" aria-label="Order summary">
          <h3>Order Summary</h3>
          <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <div class="summary-row"><span>Delivery</span><span><?= $shipping === 0.0 ? 'Complimentary' : money($shipping) ?></span></div>
          <div class="summary-row summary-row--total"><span>Total</span><span><?= money($total) ?></span></div>
          <a href="<?= e(base_url('checkout.php')) ?>" class="btn btn--gold btn--block">Proceed to Checkout</a>
          <p class="cart-note">Free island-wide delivery on orders over LKR 25,000. This is a demonstration checkout — no payment is taken.</p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
