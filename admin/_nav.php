<?php
/* Admin sub-navigation strip.
   "Products" and "Add Product" now resolve to two different views of
   admin/products.php: the plain URL lists the catalogue, ?new=1 opens a
   blank creation form. The active entry is highlighted accordingly. */
$navFile = basename($_SERVER['SCRIPT_NAME']);
$navForm = isset($_GET['new']) || isset($_GET['edit']);
?>
<div class="admin-subnav">
  <div class="wrap">
    <a href="<?= e(base_url('admin/index.php')) ?>"
       class="<?= $navFile === 'index.php' ? 'is-active' : '' ?>">Dashboard</a>

    <a href="<?= e(base_url('admin/products.php')) ?>"
       class="<?= ($navFile === 'products.php' && !$navForm) ? 'is-active' : '' ?>">Products</a>

    <a href="<?= e(base_url('admin/products.php?new=1')) ?>#product-form"
       class="<?= $navForm ? 'is-active' : '' ?>">Add Product</a>

    <a href="<?= e(base_url('admin/orders.php')) ?>"
       class="<?= $navFile === 'orders.php' ? 'is-active' : '' ?>">Orders</a>

    <a href="<?= e(base_url('index.php')) ?>">View Store &#8599;</a>
  </div>
</div>
