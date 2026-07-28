<?php
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = db()->prepare(
    'SELECT p.*, c.name AS category, c.id AS category_id
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.is_active = 1'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Piece not found Velvet Vogue';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="wrap cart-empty"><h3>Piece not found</h3>'
       . '<p>This item may have been removed.</p><a class="btn" href="' . e(base_url('products.php')) . '">Back to shop</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$sizes  = db()->prepare('SELECT size FROM product_sizes WHERE product_id = ? ORDER BY FIELD(size,"XS","S","M","L","XL")');
$sizes->execute([$id]);
$sizes = $sizes->fetchAll(PDO::FETCH_COLUMN);

$colors = db()->prepare('SELECT color_name, color_hex FROM product_colors WHERE product_id = ?');
$colors->execute([$id]);
$colors = $colors->fetchAll();

/* "You may also like" — same category, excluding this piece */
$related = db()->prepare(
    'SELECT id, name, price, image, tag FROM products
     WHERE category_id = ? AND id <> ? AND is_active = 1 ORDER BY RAND() LIMIT 3'
);
$related->execute([$product['category_id'], $id]);
$related = $related->fetchAll();

$pageTitle  = $product['name'] . ' Velvet Vogue';   // header.php escapes it
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="wrap">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(base_url('index.php')) ?>">Home</a> /
      <a href="<?= e(base_url('products.php')) ?>">Shop</a> /
      <a href="<?= e(base_url('products.php?category=' . $product['category_id'])) ?>"><?= e($product['category']) ?></a> /
      <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
      <div class="product-detail__media">
        <?php if ($product['tag']): ?><span class="product-card__tag"><?= e($product['tag']) ?></span><?php endif; ?>
        <img src="<?= e(base_url($product['image'])) ?>" alt="<?= e($product['name']) ?>"
             onerror="this.src='https://placehold.co/900x1100/800020/f7f3ee?text=Velvet+Vogue'">
      </div>

      <div class="product-detail__body">
        <span class="eyebrow eyebrow--dark"><?= e($product['category']) ?> · <?= ucfirst(e($product['gender'])) ?></span>
        <h1><?= e($product['name']) ?></h1>
        <p class="product-detail__price"><?= money($product['price']) ?></p>
        <hr class="gold-rule">
        <p class="product-detail__desc"><?= nl2br(e($product['description'])) ?></p>

        <form method="post" action="<?= e(base_url('cart-action.php')) ?>" class="product-detail__form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

          <?php if ($colors): ?>
            <div class="detail-field">
              <label>Colour</label>
              <div class="color-chips">
                <?php foreach ($colors as $i => $col): ?>
                  <label class="color-chip" title="<?= e($col['color_name']) ?>">
                    <input type="radio" name="color" value="<?= e($col['color_name']) ?>" <?= $i===0?'checked':'' ?> hidden>
                    <span class="color-swatch" style="background:<?= e($col['color_hex']) ?>"></span>
                    <span class="color-name"><?= e($col['color_name']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php else: ?>
            <input type="hidden" name="color" value="One Colour">
          <?php endif; ?>

          <div class="detail-field">
            <label for="size">Size</label>
            <select id="size" name="size" class="size-select" required>
              <option value="">Choose a size</option>
              <?php foreach ($sizes as $s): ?>
                <option value="<?= e($s) ?>"><?= e($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="detail-field">
            <label for="qty">Quantity</label>
            <input type="number" id="qty" name="qty" class="size-select" value="1" min="1" max="10" style="max-width:120px">
          </div>

          <div class="card-actions" style="margin-top:1.4rem">
            <button class="btn btn--block" type="submit" name="buy" value="0">Add to Cart</button>
            <button class="btn btn--gold btn--block" type="submit" name="buy" value="1">Buy Now</button>
          </div>
        </form>

        <div class="product-detail__meta">
          <div><strong>Free delivery</strong><span>On orders over LKR 25,000</span></div>
          <div><strong>14-day returns</strong><span>Easy, no-quibble</span></div>
          <div><strong>Considered craft</strong><span>Ethically sourced cloth</span></div>
        </div>
      </div>
    </div>

    <?php if ($related): ?>
      <div style="margin-top:5rem">
        <header class="section-head section-head--center">
          <span class="eyebrow eyebrow--dark">Complete the look</span>
          <h2>You may also like</h2>
          <hr class="gold-rule gold-rule--center">
        </header>
        <div class="product-grid product-grid--three">
          <?php foreach ($related as $r): ?>
            <article class="product-card">
              <a class="product-card__media" href="<?= e(base_url('product.php?id=' . $r['id'])) ?>">
                <?php if ($r['tag']): ?><span class="product-card__tag"><?= e($r['tag']) ?></span><?php endif; ?>
                <img src="<?= e(base_url($r['image'])) ?>" alt="<?= e($r['name']) ?>" loading="lazy"
                     onerror="this.src='https://placehold.co/800x1000/800020/f7f3ee?text=Velvet+Vogue'">
              </a>
              <div class="product-card__body">
                <h3 class="product-card__name"><?= e($r['name']) ?></h3>
                <span class="product-card__price"><?= money($r['price']) ?></span>
                <div class="product-card__controls">
                  <a class="btn btn--sm btn--block" href="<?= e(base_url('product.php?id=' . $r['id'])) ?>">View Piece</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
