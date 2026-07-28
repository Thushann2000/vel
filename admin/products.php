<?php
/* =====================================================================
   ADMIN — PRODUCTS  (single-page CRUD)
   This one page handles the full product lifecycle:
     • LIST    every product
     • INSERT  a new product           (POST action=save, no id)
     • UPDATE  an existing product     (POST action=save, with id)
     • DELETE  a product               (POST action=delete)
   Add/Edit share the same form; Edit pre-fills it via ?edit=<id>.
   ===================================================================== */
require_once __DIR__ . '/includes/admin-guard.php';

$pdo      = db();
$allSizes = ['XS', 'S', 'M', 'L', 'XL'];

/* ---------------------------------------------------------------------
   1. HANDLE POST — save (insert/update) and delete
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* -------- DELETE -------- */
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            // Grab the image path first so we can remove the physical file too
            $imgStmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
            $imgStmt->execute([$id]);
            $imgRow = $imgStmt->fetch();

            // product_sizes / product_colors cascade via FK ON DELETE CASCADE
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);

            if ($imgRow && $imgRow['image']) {
                $imgAbs = __DIR__ . '/../' . $imgRow['image'];
                if (is_file($imgAbs)) { @unlink($imgAbs); }
            }
            header('Location: ' . base_url('admin/products.php?ok=deleted'));
            exit;
        }
        header('Location: ' . base_url('admin/products.php'));
        exit;
    }

    /* -------- INSERT or UPDATE -------- */
    if ($action === 'save') {
        $errors      = [];                       // declared FIRST — nothing below can silently wipe it
        $id          = (int) ($_POST['id'] ?? 0);
        $editing     = $id > 0;
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float) ($_POST['price'] ?? 0);
        $categoryId  = (int) ($_POST['category_id'] ?? 0);
        $gender      = in_array($_POST['gender'] ?? '', ['women','men','unisex'], true) ? $_POST['gender'] : 'women';

        // Image comes ONLY from the uploaded file now. On edit, if no new
        // file is chosen, keep whatever image the product already had
        // (passed through as a hidden field so we don't lose it).
        $oldImage = trim($_POST['current_image'] ?? '');
        $image    = $oldImage;

        if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $up = $_FILES['image_file'];

            if (!is_uploaded_file($up['tmp_name']) || $up['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Image upload failed (error code ' . (int) $up['error'] . '). Please try again.';
            } else {
                $maxBytes = 50 * 1024 * 1024; // 50MB
                $allowed  = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif'];

                // Detect the REAL mime type from file content — never trust
                // the client-supplied $_FILES[...]['type'], it's spoofable.
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($up['tmp_name']);

                if ($up['size'] > $maxBytes) {
                    $errors[] = 'Uploaded image is too large (max 50MB).';
                } elseif (!array_key_exists($realMime, $allowed)) {
                    $errors[] = 'Unsupported image type. Use JPEG, PNG or GIF.';
                } else {
                    $ext      = $allowed[$realMime];
                    $baseName = bin2hex(random_bytes(8));
                    $destRel  = 'images/' . $baseName . $ext;
                    $destAbs  = __DIR__ . '/../' . $destRel;
                    $imagesDir = dirname($destAbs);
                    if (!is_dir($imagesDir) && !@mkdir($imagesDir, 0755, true) && !is_dir($imagesDir)) {
                        $errors[] = 'The images/ folder could not be created. Check folder permissions.';
                    } elseif (!is_writable($imagesDir)) {
                        $errors[] = 'The images/ folder is not writable. Check folder permissions.';
                    } elseif (!move_uploaded_file($up['tmp_name'], $destAbs)) {
                        $errors[] = 'Failed to save the uploaded image.';
                    } else {
                        $image = $destRel;
                        // Clean up the old file once the new one is safely saved
                        if ($oldImage !== '' && $oldImage !== $image) {
                            $oldAbs = __DIR__ . '/../' . $oldImage;
                            if (is_file($oldAbs)) { @unlink($oldAbs); }
                        }
                    }
                }
            }
        }

        if ($image === '') {
            $errors[] = 'Please upload a product image.';
        }

        $tag         = trim($_POST['tag'] ?? '');
        $isActive    = !empty($_POST['is_active']) ? 1 : 0;
        $sizes       = array_values(array_intersect($_POST['sizes'] ?? [], $allSizes));

        /* Validate the rest of the fields */
        if ($name === '')        { $errors[] = 'Name is required.'; }
        if ($description === '') { $errors[] = 'Description is required.'; }
        if ($price <= 0)         { $errors[] = 'Price must be greater than zero.'; }
        if (!$categoryId)        { $errors[] = 'Please choose a category.'; }

        if ($errors) {
            // Keep the form on screen: ?edit=<id> when amending, ?new=1 when creating
            $qs = ($editing ? 'edit=' . $id : 'new=1')
                . '&err=' . rawurlencode(implode(' ', $errors));
            header('Location: ' . base_url('admin/products.php?' . $qs . '#product-form'));
            exit;
        }

        /* Parse colours "Name|#hex" per line */
        $colors = [];
        foreach (preg_split('/\r\n|\r|\n/', $_POST['colors'] ?? '') as $line) {
            $line = trim($line);
            if ($line === '') { continue; }
            [$cName, $cHex] = array_pad(explode('|', $line, 2), 2, '#000000');
            $colors[] = [trim($cName), trim($cHex) ?: '#000000'];
        }

        $pdo->beginTransaction();
        try {
            if ($editing) {
                $pdo->prepare(
                    'UPDATE products SET name=?, description=?, price=?, category_id=?, gender=?, image=?, tag=?, is_active=? WHERE id=?'
                )->execute([$name, $description, $price, $categoryId, $gender, $image, $tag, $isActive, $id]);
            } else {
                $pdo->prepare(
                    'INSERT INTO products (name, description, price, category_id, gender, image, tag, is_active) VALUES (?,?,?,?,?,?,?,?)'
                )->execute([$name, $description, $price, $categoryId, $gender, $image, $tag, $isActive]);
                $id = (int) $pdo->lastInsertId();
            }

            /* Replace child rows */
            $pdo->prepare('DELETE FROM product_sizes  WHERE product_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM product_colors WHERE product_id = ?')->execute([$id]);

            $sizeIns = $pdo->prepare('INSERT INTO product_sizes (product_id, size) VALUES (?,?)');
            foreach ($sizes as $s) { $sizeIns->execute([$id, $s]); }

            $colIns = $pdo->prepare('INSERT INTO product_colors (product_id, color_name, color_hex) VALUES (?,?,?)');
            foreach ($colors as $c) { $colIns->execute([$id, $c[0], $c[1]]); }

            $pdo->commit();
            header('Location: ' . base_url('admin/products.php?ok=' . ($editing ? 'updated' : 'created')));
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $msg = 'Could not save the product: ' . substr($ex->getMessage(), 0, 150);
            $qs  = ($editing ? 'edit=' . $id : 'new=1') . '&err=' . rawurlencode($msg);
            header('Location: ' . base_url('admin/products.php?' . $qs . '#product-form'));
            exit;
        }
    }
}

/* ---------------------------------------------------------------------
   2. LOAD FORM STATE  — blank for "add", pre-filled for "edit"

   The page now serves TWO distinct views from one controller:
     • LIST view  — admin/products.php            (catalogue table only)
     • FORM view  — admin/products.php?new=1      (blank Add form)
                  — admin/products.php?edit=<id>  (pre-filled Edit form)
   ?err=... also forces the form open so a rejected submission is not lost.
   --------------------------------------------------------------------- */
$editId   = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing  = false;
$showForm = isset($_GET['new']) || $editId || isset($_GET['err']);

$product = [
    'id' => 0, 'name' => '', 'description' => '', 'price' => '',
    'category_id' => 0, 'gender' => 'women', 'image' => '', 'tag' => '', 'is_active' => 1,
];
$prodSizes  = [];
$prodColors = '';

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $row = $stmt->fetch();
    if ($row) {
        $editing = true;
        $product = $row;

        $s = $pdo->prepare('SELECT size FROM product_sizes WHERE product_id = ?');
        $s->execute([$editId]);
        $prodSizes = $s->fetchAll(PDO::FETCH_COLUMN);

        $c = $pdo->prepare('SELECT color_name, color_hex FROM product_colors WHERE product_id = ?');
        $c->execute([$editId]);
        $prodColors = implode("\n", array_map(fn($r) => $r['color_name'] . '|' . $r['color_hex'], $c->fetchAll()));
    } else {
        flash('Product not found.', 'error');
    }
}

/* ---------------------------------------------------------------------
   3. LOAD LIST DATA
   --------------------------------------------------------------------- */
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$products   = $pdo->query(
    'SELECT p.id, p.name, p.price, p.gender, p.tag, p.is_active, c.name AS category
     FROM products p JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC, p.id DESC'
)->fetchAll();

$pageTitle  = 'Manage Products Velvet Vogue';
$activePage = 'admin';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_nav.php';
?>

<section class="section">
  <div class="wrap">

    <!-- ============ ADD / EDIT FORM  (form view only) ============ -->
    <?php if ($showForm): ?>
    <div class="form-card" id="product-form" style="margin-bottom:2.5rem">
      <div class="admin-card-head">
        <div>
          <span class="eyebrow eyebrow--dark">Catalogue</span>
          <h2><?= $editing ? 'Edit Product #' . (int)$product['id'] : 'Add New Product' ?></h2>
        </div>
        <div class="table-actions">
          <?php if ($editing): ?>
            <a class="btn btn--sm" href="<?= e(base_url('admin/products.php?new=1')) ?>#product-form">+ New instead</a>
          <?php endif; ?>
          <a class="btn btn--sm" href="<?= e(base_url('admin/products.php')) ?>">Cancel</a>
        </div>
      </div>

      <form method="post" action="<?= e(base_url('admin/products.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><?php endif; ?>

        <div class="field">
          <label for="name">Product name</label>
          <input type="text" id="name" name="name" value="<?= e($product['name']) ?>" required>
        </div>

        <div class="field">
          <label for="description">Description</label>
          <textarea id="description" name="description" required><?= e($product['description']) ?></textarea>
        </div>

        <div class="form-two">
          <div class="field">
            <label for="price">Price (LKR)</label>
            <input type="number" id="price" name="price" min="0" step="0.01" value="<?= e((string)$product['price']) ?>" required>
          </div>
          <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
              <option value="">Choose…</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$product['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-two">
          <div class="field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
              <?php foreach (['women','men','unisex'] as $g): ?>
                <option value="<?= $g ?>" <?= $product['gender']===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="tag">Tag (optional)</label>
            <input type="text" id="tag" name="tag" value="<?= e($product['tag']) ?>" placeholder="New / Icon">
          </div>
        </div>

        <input type="hidden" name="current_image" value="<?= e($product['image']) ?>">

        <div class="field">
          <label for="image_file">Product image<?= $editing ? '' : ' <span style="color:var(--velvet-deep)">*</span>' ?></label>
          <?php if ($editing && $product['image']): ?>
            <div style="margin-bottom:.6rem">
              <img src="<?= e(base_url($product['image'])) ?>" alt="Current image" style="width:80px;height:100px;object-fit:cover;border-radius:6px;border:1px solid var(--line)"
                   onerror="this.src='https://placehold.co/80x100/800020/f7f3ee?text=VV'">
            </div>
          <?php endif; ?>
          <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif" <?= $editing ? '' : 'required' ?>>
          <small style="color:var(--ink-soft);display:block;margin-top:.25rem">
            <?= $editing ? 'Choose a file only if you want to replace the current image.' : 'Required.' ?> Max 50MB. Allowed: JPG, PNG, GIF.
          </small>
        </div>

        <div class="field">
          <label>Available sizes</label>
          <div class="size-chips">
            <?php foreach ($allSizes as $s): ?>
              <label class="size-chip <?= in_array($s,$prodSizes,true)?'is-active':'' ?>">
                <input type="checkbox" name="sizes[]" value="<?= $s ?>" <?= in_array($s,$prodSizes,true)?'checked':'' ?> hidden>
                <?= $s ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field">
          <label for="colors">Colours — one per line as <code>Name|#hex</code></label>
          <textarea id="colors" name="colors" placeholder="Burgundy|#800020&#10;Charcoal|#2a2320"><?= e($prodColors) ?></textarea>
        </div>

        <div class="field">
          <label class="filter-radio">
            <input type="checkbox" name="is_active" value="1" <?= $product['is_active']?'checked':'' ?>>
            <span>Active (visible in store)</span>
          </label>
        </div>

        <button class="btn btn--gold btn--block" type="submit"><?= $editing ? 'Save Changes' : 'Create Product' ?></button>
      </form>
    </div>
    <?php endif; ?>

    <!-- ============ PRODUCT LIST ============ -->
    <div class="form-card">
      <div class="admin-card-head">
        <div>
          <span class="eyebrow eyebrow--dark">Catalogue</span>
          <h3>All Products (<?= count($products) ?>)</h3>
        </div>
        <a class="btn btn--sm btn--gold" href="<?= e(base_url('admin/products.php?new=1')) ?>#product-form">+ Add New Product</a>
      </div>
      <?php if (!$products): ?>
        <p style="color:var(--ink-soft)">No products yet — use Add New Product to create your first one.</p>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr><th>Name</th><th>Category</th><th>Gender</th><th>Price</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <tr>
                <td><?= e($p['name']) ?> <?php if($p['tag']): ?><span class="pill pill--tag"><?= e($p['tag']) ?></span><?php endif; ?></td>
                <td><?= e($p['category']) ?></td>
                <td><?= ucfirst(e($p['gender'])) ?></td>
                <td><?= money($p['price']) ?></td>
                <td><span class="pill pill--<?= $p['is_active']?'paid':'cancelled' ?>"><?= $p['is_active']?'Active':'Hidden' ?></span></td>
                <td class="table-actions">
                  <a class="btn btn--sm" href="<?= e(base_url('admin/products.php?edit=' . $p['id'])) ?>#product-form">Edit</a>
                  <form method="post" action="<?= e(base_url('admin/products.php')) ?>"
                        class="admin-delete-form" data-name="<?= e($p['name']) ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn--sm" type="submit" style="background:var(--velvet-deep);border-color:var(--velvet-deep)">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- ============ SweetAlert2 — success / error popups + delete confirm ============ -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
  var VV_COLOR = '#800020';

  /* 1. Show a popup for the result of the last create/update/delete action */
  var params = new URLSearchParams(window.location.search);
  var ok  = params.get('ok');
  var err = params.get('err');

  if (ok) {
    var okMessages = {
      created: 'Product created successfully.',
      updated: 'Product updated successfully.',
      deleted: 'Product deleted successfully.'
    };
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: okMessages[ok] || 'Done.',
      confirmButtonColor: VV_COLOR
    });
  } else if (err) {
    Swal.fire({
      icon: 'error',
      title: 'Please check the form',
      text: decodeURIComponent(err),
      confirmButtonColor: VV_COLOR
    });
  }

  if (ok || err) {
    params.delete('ok');
    params.delete('err');
    var cleanUrl = window.location.pathname
      + (params.toString() ? '?' + params.toString() : '')
      + window.location.hash;
    window.history.replaceState({}, '', cleanUrl);
  }

  /* 2. Replace the plain confirm() on delete with a SweetAlert2 dialog */
  document.querySelectorAll('.admin-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = form.dataset.name || 'this product';
      Swal.fire({
        icon: 'warning',
        title: 'Delete "' + name + '"?',
        text: 'This cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        confirmButtonColor: VV_COLOR,
        cancelButtonColor: '#6c757d'
      }).then(function (result) {
        if (result.isConfirmed) {
          HTMLFormElement.prototype.submit.call(form);
        }
      });
    });
  });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
