<?php
require_once __DIR__ . '/includes/admin-guard.php';

$orders = db()->query(
    'SELECT id, full_name, email, total, status, created_at FROM orders ORDER BY created_at DESC'
)->fetchAll();

/* Preload items grouped by order for a lightweight expandable view */
$itemsByOrder = [];
foreach (db()->query('SELECT order_id, name, size, color, price, qty FROM order_items ORDER BY order_id')->fetchAll() as $it) {
    $itemsByOrder[$it['order_id']][] = $it;
}

$pageTitle  = 'Orders Velvet Vogue';
$activePage = 'admin';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<section class="section">
  <div class="wrap">
    <header class="section-head">
      <span class="eyebrow eyebrow--dark">Administration</span>
      <h2>Orders</h2>
      <hr class="gold-rule">
    </header>

    <?php if (!$orders): ?>
      <div class="cart-empty"><h3>No orders yet</h3><p>Orders placed through checkout will appear here.</p></div>
    <?php else: ?>
      <div class="form-card">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= e($o['full_name']) ?><br><small style="color:var(--ink-soft)"><?= e($o['email']) ?></small></td>
                <td>
                  <?php foreach ($itemsByOrder[$o['id']] ?? [] as $it): ?>
                    <div style="font-size:.82rem;color:var(--ink-soft)">
                      <?= e($it['name']) ?> · <?= e($it['size']) ?>/<?= e($it['color']) ?> × <?= (int)$it['qty'] ?>
                    </div>
                  <?php endforeach; ?>
                </td>
                <td><?= money($o['total']) ?></td>
                <td><span class="pill pill--<?= e($o['status']) ?>"><?= ucfirst(e($o['status'])) ?></span></td>
                <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
