<?php
require_once __DIR__ . '/includes/functions.php';

/* Featured edit — newest four active products */
$featured = db()->query(
    'SELECT id, name, price, image, tag FROM products WHERE is_active = 1 ORDER BY created_at DESC, id DESC LIMIT 4'
)->fetchAll();

/* Category cards — first three categories with a product count */
$categories = db()->query(
    'SELECT c.id, c.name, c.slug, COUNT(p.id) AS n
     FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
     GROUP BY c.id ORDER BY c.id LIMIT 3'
)->fetchAll();

$pageTitle  = 'Velvet Vogue Modern Luxury Clothing';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap hero__inner">
    <span class="eyebrow">Autumn / Winter Collection</span>
    <h1>Dressed in quiet confidence.</h1>
    <p>Considered pieces cut from the finest cloth — tailored, timeless, and made to be lived in. Discover the wardrobe that speaks softly and lasts.</p>
    <div class="hero__actions">
      <a href="<?= e(base_url('products.php')) ?>" class="btn btn--gold">Shop the Collection</a>
      <a href="#featured" class="btn btn--ghost">Featured Pieces</a>
    </div>
  </div>
</section>

<section class="benefits">
  <div class="wrap">
    <div class="benefits__grid">
      <div class="benefit"><strong>Island-wide</strong><span>Delivery in 2–4 Days</span></div>
      <div class="benefit"><strong>Free Shipping</strong><span>Orders over LKR 25,000</span></div>
      <div class="benefit"><strong>Considered Craft</strong><span>Ethically Sourced Cloth</span></div>
      <div class="benefit"><strong>14-Day</strong><span>Easy Returns</span></div>
    </div>
  </div>
</section>

<section class="section" id="featured">
  <div class="wrap">
    <header class="section-head section-head--center">
      <span class="eyebrow eyebrow--dark">Handpicked</span>
      <h2>The Featured Edit</h2>
      <hr class="gold-rule gold-rule--center">
      <p>A tightly curated selection from the new season — explore each piece in full.</p>
    </header>

    <div class="product-grid">
      <?php foreach ($featured as $p): ?>
        <article class="product-card">
          <a class="product-card__media" href="<?= e(base_url('product.php?id=' . $p['id'])) ?>">
            <?php if ($p['tag']): ?><span class="product-card__tag"><?= e($p['tag']) ?></span><?php endif; ?>
            <img src="<?= e(base_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                 onerror="this.src='https://placehold.co/800x1000/800020/f7f3ee?text=Velvet+Vogue'">
          </a>
          <div class="product-card__body">
            <h3 class="product-card__name"><?= e($p['name']) ?></h3>
            <span class="product-card__price"><?= money($p['price']) ?></span>
            <div class="product-card__controls">
              <a class="btn btn--sm btn--block" href="<?= e(base_url('product.php?id=' . $p['id'])) ?>">View Piece</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:3rem">
      <a href="<?= e(base_url('products.php')) ?>" class="btn">View All Pieces</a>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="wrap">
    <header class="section-head">
      <span class="eyebrow eyebrow--dark">Shop by Category</span>
      <h2>Find your form</h2>
      <hr class="gold-rule">
    </header>
    <div class="category-grid">
      <?php $i = 1; foreach ($categories as $c): ?>
        <a class="category-card" href="<?= e(base_url('products.php?category=' . $c['id'])) ?>">
          <img src="<?= e(base_url('images/category-' . $c['slug'] . '.jpg')) ?>" alt="<?= e($c['name']) ?> category" loading="lazy"
               onerror="this.src='https://placehold.co/800x1000/1a1614/c9a24b?text=<?= e($c['name']) ?>'">
          <div class="category-card__label">
            <span><?= sprintf('%02d', $i++) ?> — <?= (int)$c['n'] ?> pieces</span>
            <h3><?= e($c['name']) ?></h3>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--dark">
  <div class="wrap">
    <div class="split">
      <div class="split__media">
        <img src="<?= e(base_url('images/editorial.jpg')) ?>" alt="Atelier detail" loading="lazy"
             onerror="this.src='https://placehold.co/900x1100/800020/f7f3ee?text=The+Atelier'">
      </div>
      <div class="split__body">
        <span class="eyebrow">Our Philosophy</span>
        <h2>Cloth chosen with care, made to last a decade.</h2>
        <p>Velvet Vogue began with a simple conviction: that a wardrobe should be small, deliberate, and beautifully made. Every seam is finished by hand, every fabric traced to its source. We design fewer things, better — so you buy less and wear it longer.</p>
        <a href="<?= e(base_url('contact.php')) ?>" class="btn btn--gold">Visit the Studio</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
