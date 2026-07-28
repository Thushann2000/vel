<?php
/* =====================================================================
   VELVET VOGUE — Shared Helper Functions
   Session, authentication, CSRF protection, cart logic (session-based),
   flash messages, escaping and money formatting.
   Every page begins with:  require_once __DIR__ . '/includes/functions.php';
   ===================================================================== */

require_once __DIR__ . '/../config/db.php';

/* Start the session exactly once, everywhere. */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------------------------------------- Output escaping */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* -------------------------------------------------- Money formatting */
function money($amount): string
{
    return 'LKR ' . number_format((float) $amount, 0);
}

/* -------------------------------------------------- CSRF protection */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        die('Invalid or expired form token. Please go back and try again.');
    }
}

/* -------------------------------------------------- Flash messages */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* -------------------------------------------------- Authentication */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function is_logged_in(): bool { return current_user() !== null; }

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

/** Redirect to login unless an admin is signed in (used to guard /admin). */
function require_admin(): void
{
    if (!is_admin()) {
        flash('Please sign in with an administrator account.', 'error');
        header('Location: ' . base_url('account.php'));
        exit;
    }
}

/* -------------------------------------------------- URL helper
   Makes links work whether the project sits at the web root or in a
   sub-folder such as /velvet-vogue. */
function base_url(string $path = ''): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    // If we are inside /admin, step up one level to reach the site root.
    if (basename($dir) === 'admin') {
        $dir = dirname($dir);
    }
    $dir = ($dir === '/' || $dir === '.') ? '' : $dir;
    return $dir . '/' . ltrim($path, '/');
}

/* =====================================================================
   CART  — stored in the session as an array of line items.
   key    = productId|size|color   (so variants are separate lines)
   value  = [product_id, name, price, image, size, color, qty]
   ===================================================================== */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_key(int $productId, string $size, string $color): string
{
    return $productId . '|' . $size . '|' . $color;
}

function cart_add(int $productId, string $size, string $color, int $qty = 1): void
{
    $stmt = db()->prepare('SELECT id, name, price, image FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        return;
    }

    $cart = cart();
    $key  = cart_key($productId, $size, $color);

    if (isset($cart[$key])) {
        $cart[$key]['qty'] += $qty;
    } else {
        $cart[$key] = [
            'product_id' => (int) $product['id'],
            'name'       => $product['name'],
            'price'      => (float) $product['price'],
            'image'      => $product['image'],
            'size'       => $size,
            'color'      => $color,
            'qty'        => $qty,
        ];
    }
    $_SESSION['cart'] = $cart;
}

function cart_set_qty(string $key, int $qty): void
{
    $cart = cart();
    if (!isset($cart[$key])) {
        return;
    }
    if ($qty < 1) {
        unset($cart[$key]);
    } else {
        $cart[$key]['qty'] = $qty;
    }
    $_SESSION['cart'] = $cart;
}

function cart_remove(string $key): void
{
    $cart = cart();
    unset($cart[$key]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function cart_count(): int
{
    return array_sum(array_map(fn($i) => $i['qty'], cart()));
}

function cart_subtotal(): float
{
    $sum = 0.0;
    foreach (cart() as $item) {
        $sum += $item['price'] * $item['qty'];
    }
    return $sum;
}

function cart_shipping(): float
{
    $sub = cart_subtotal();
    return ($sub === 0.0 || $sub >= FREE_SHIP_THRESHOLD) ? 0.0 : (float) SHIPPING_FEE;
}

function cart_total(): float
{
    return cart_subtotal() + cart_shipping();
}
