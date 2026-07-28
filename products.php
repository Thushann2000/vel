<?php
require_once __DIR__ . '/includes/functions.php';

/* -------------------- Read & sanitise filter inputs -------------------- */
$q          = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$gender     = $_GET['gender']  ?? '';
$size       = $_GET['size']    ?? '';
$minPrice   = isset($_GET['min']) && $_GET['min'] !== '' ? (float) $_GET['min'] : null;
$maxPrice   = isset($_GET['max']) && $_GET['max'] !== '' ? (float) $_GET['max'] : null;
$sort       = $_GET['sort']    ?? 'newest';

/* Keep the search term sane: trim to 80 chars */
if (mb_strlen($q) > 80) { $q = mb_substr($q, 0, 80); }

$allowedGenders = ['women', 'men', 'unisex'];
$allowedSizes   = ['XS', 'S', 'M', 'L', 'XL'];
if (!in_array($gender, $allowedGenders, true)) { $gender = ''; }
if (!in_array($size, $allowedSizes, true))     { $size = ''; }

/* -------------------- Build the query dynamically ---------------------- */
$where  = ['p.is_active = 1'];
$params = [];

/* Keyword search — matches the product name, its description or the
   category name. %, _ and \ are escaped so they are treated literally. */
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';
    $where[] = "(p.name LIKE ? ESCAPE '\\\\' OR p.description LIKE ? ESCAPE '\\\\' OR c.name LIKE ? ESCAPE '\\\\')";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId) { $where[] = 'p.category_id = ?'; $params[] = $categoryId; }
if ($gender)     { $where[] = 'p.gender = ?';      $params[] = $gender; }
if ($minPrice !== null) { $where[] = 'p.price >= ?'; $params[] = $minPrice; }
if ($maxPrice !== null) { $where[] = 'p.price <= ?'; $params[] = $maxPrice; }
if ($size) {
    $where[] = 'EXISTS (SELECT 1 FROM product_sizes ps WHERE ps.product_id = p.id AND ps.size = ?)';
    $params[] = $size;
}

$orderBy = match ($sort) {
    'price-asc'  => 'p.price ASC',
    'price-desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC, p.id DESC',
};

/* When searching with the default sort, put name matches first so the most
   obvious hits appear at the top of the grid. */
if ($q !== '' && $sort === 'newest') {
    $orderBy = 'nameHit DESC, ' . $orderBy;
}

$relevance = '0 AS nameHit';
if ($q !== '') {
    $relevance = "(CASE WHEN p.name LIKE ? ESCAPE '\\\\' THEN 1 ELSE 0 END) AS nameHit";
    array_unshift($params, $like);   // this placeholder sits in the SELECT list
}

$sql = 'SELECT p.id, p.name, p.price, p.image, p.tag, p.gender, c.name AS category, ' . $relevance . '
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . $orderBy;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* Categories for the filter sidebar */
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

/* Are any filters (search included) currently applied? */
$hasFilters = ($q !== '' || $categoryId || $gender || $size || $minPrice !== null || $maxPrice !== null);

$pageTitle  = ($q !== '' ? 'Search: ' . $q . ' — ' : '') . 'Shop the Collection Velvet Vogue';
$activePage = 'shop';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--slim">
  <div class="wrap">
    <span class="eyebrow">The Collection</span>
    <h1><?= $q !== '' ? 'Search Results' : 'Shop Every Piece' ?></h1>
    <p>
      <?= $q !== ''
            ? 'Showing pieces matching “' . e($q) . '”. Narrow it further with the filters.'
            : 'Search, or filter by category, gender, size and price to find exactly what you\'re looking for.' ?>
    </p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="shop-layout">

      <!-- ---------------- Filter sidebar (GET form) ---------------- -->
      <aside class="filters" aria-label="Product filters">
        <form method="get" action="<?= e(base_url('products.php')) ?>" role="search">
          <div class="filters__head">
            <h3>Filter</h3>
            <a href="<?= e(base_url('products.php')) ?>" class="filters__clear">Clear all</a>
          </div>

          <!-- ---- Keyword search ---- -->
          <div class="filter-group" style="border-top:0; padding-top:0">
            <h4>Search</h4>
            <div class="search-field">
              <input type="search" name="q" id="q" value="<?= e($q) ?>"
                     placeholder="e.g. blazer, silk, coat" aria-label="Search products">
              <button type="submit" class="search-field__btn" aria-label="Search">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                  <circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.6-3.6"></path>
                </svg>
              </button>
            </div>
            <?php if ($q !== ''): ?>
              <a class="filters__clear" style="display:inline-block;margin-top:.6rem"
                 href="<?= e(base_url('products.php?' . http_build_query(array_filter([
                        'category' => $categoryId ?: null,
                        'gender'   => $gender ?: null,
                        'size'     => $size ?: null,
                        'min'      => $minPrice,
                        'max'      => $maxPrice,
                        'sort'     => $sort,
                     ], fn($v) => $v !== null && $v !== '')))) ?>">&times; Clear search</a>
            <?php endif; ?>
          </div>

          <div class="filter-group">
            <h4>Category</h4>
            <div class="field">
              <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= $categoryId===(int)$c['id']?'selected':'' ?>>
                    <?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="filter-group">
            <h4>Gender</h4>
            <?php foreach ($allowedGenders as $g): ?>
              <label class="filter-radio">
                <input type="radio" name="gender" value="<?= e($g) ?>" <?= $gender===$g?'checked':'' ?>>
                <span><?= ucfirst($g) ?></span>
              </label>
            <?php endforeach; ?>
            <label class="filter-radio">
              <input type="radio" name="gender" value="" <?= $gender===''?'checked':'' ?>>
              <span>All</span>
            </label>
          </div>

          <div class="filter-group">
            <h4>Size</h4>
            <div class="size-chips">
              <?php foreach ($allowedSizes as $s): ?>
                <label class="size-chip <?= $size===$s?'is-active':'' ?>">
                  <input type="radio" name="size" value="<?= e($s) ?>" <?= $size===$s?'checked':'' ?> hidden>
                  <?= e($s) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="filter-group">
            <h4>Price range (LKR)</h4>
            <div class="price-row">
              <input type="number" name="min" min="0" step="500" placeholder="Min" value="<?= e($minPrice!==null?(string)$minPrice:'') ?>">
              <span>—</span>
              <input type="number" name="max" min="0" step="500" placeholder="Max" value="<?= e($maxPrice!==null?(string)$maxPrice:'') ?>">
            </div>
          </div>

          <input type="hidden" name="sort" value="<?= e($sort) ?>">
          <button class="btn btn--block" type="submit">Apply Filters</button>
        </form>
      </aside>

      <!-- ---------------- Results ---------------- -->
      <div class="shop-results">
        <div class="shop-toolbar">
          <span class="shop-count">
            <?= count($products) ?> piece<?= count($products)===1?'':'s' ?><?= $q !== '' ? ' for “' . e($q) . '”' : '' ?>
          </span>
          <form method="get" action="<?= e(base_url('products.php')) ?>" class="sort-form">
            <?php foreach (['q'=>$q,'category'=>$categoryId,'gender'=>$gender,'size'=>$size,'min'=>$minPrice,'max'=>$maxPrice] as $k=>$v): ?>
              <?php if ($v!==''&&$v!==null&&$v!==0): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>"><?php endif; ?>
            <?php endforeach; ?>
            <label for="sort">Sort</label>
            <select id="sort" name="sort" onchange="this.form.submit()">
              <option value="newest"     <?= $sort==='newest'?'selected':'' ?>>Newest</option>
              <option value="price-asc"  <?= $sort==='price-asc'?'selected':'' ?>>Price: Low to High</option>
              <option value="price-desc" <?= $sort==='price-desc'?'selected':'' ?>>Price: High to Low</option>
              <option value="name"       <?= $sort==='name'?'selected':'' ?>>Name A–Z</option>
            </select>
          </form>
        </div>

        <?php if (!$products): ?>
          <div class="cart-empty">
            <h3><?= $q !== '' ? 'Nothing matched “' . e($q) . '”' : 'No pieces match those filters' ?></h3>
            <p><?= $q !== ''
                  ? 'Check the spelling, try a shorter word, or clear a filter or two.'
                  : 'Try widening your price range or clearing a filter.' ?></p>
            <a href="<?= e(base_url('products.php')) ?>" class="btn"><?= $hasFilters ? 'Clear everything' : 'Back to shop' ?></a>
          </div>
        <?php else: ?>
          <div class="product-grid product-grid--three">
            <?php foreach ($products as $p): ?>
              <article class="product-card">
                <a class="product-card__media" href="<?= e(base_url('product.php?id=' . $p['id'])) ?>">
                  <?php if ($p['tag']): ?><span class="product-card__tag"><?= e($p['tag']) ?></span><?php endif; ?>
                  <img src="<?= e(base_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                       onerror="this.src='https://placehold.co/800x1000/800020/f7f3ee?text=Velvet+Vogue'">
                </a>
                <div class="product-card__body">
                  <span class="product-card__cat"><?= e($p['category']) ?></span>
                  <h3 class="product-card__name"><?= e($p['name']) ?></h3>
                  <span class="product-card__price"><?= money($p['price']) ?></span>
                  <div class="product-card__controls">
                    <a class="btn btn--sm btn--block" href="<?= e(base_url('product.php?id=' . $p['id'])) ?>">View Piece</a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
