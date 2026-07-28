<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('cart.php'));
    exit;
}
csrf_check();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $size      = trim($_POST['size'] ?? '');
        $color     = trim($_POST['color'] ?? 'One Colour');
        $qty       = max(1, min(10, (int) ($_POST['qty'] ?? 1)));

        if (!$productId || $size === '') {
            flash('Please choose a size before adding to your bag.', 'error');
            header('Location: ' . base_url('product.php?id=' . $productId));
            exit;
        }
        cart_add($productId, $size, $color, $qty);

        if (!empty($_POST['buy'])) {                 // Buy Now → straight to cart
            header('Location: ' . base_url('cart.php'));
        } else {
            flash('Added to your bag.', 'success');
            header('Location: ' . base_url('product.php?id=' . $productId));
        }
        exit;

    case 'update':
        $key = $_POST['key'] ?? '';
        $qty = (int) ($_POST['qty'] ?? 1);
        cart_set_qty($key, $qty);
        header('Location: ' . base_url('cart.php'));
        exit;

    case 'remove':
        cart_remove($_POST['key'] ?? '');
        flash('Item removed from your bag.', 'success');
        header('Location: ' . base_url('cart.php'));
        exit;

    case 'clear':
        cart_clear();
        flash('Your bag has been cleared.', 'success');
        header('Location: ' . base_url('cart.php'));
        exit;

    default:
        header('Location: ' . base_url('cart.php'));
        exit;
}
