<?php
/* =====================================================================
   VELVET VOGUE — Database Connection (PDO)
   Central connection used by every server-side script.
   Edit the four constants below to match your environment (XAMPP /
   WAMP / MAMP defaults are already set).
   ===================================================================== */

define('DB_HOST', 'k3xio06abqa902qt.cbetxkdyhwsb.us-east-1.rds.amazonaws.com');
define('DB_NAME', 'fampf13twp7pt5up');
define('DB_USER', 'ykxdzml6ndxekk0t');
define('DB_PASS', 'e0kkcrpigz83if3g');          

/* Site-wide business rules (kept in one place) */
define('SHIPPING_FEE',        850);     // flat LKR delivery fee
define('FREE_SHIP_THRESHOLD', 25000);   // free delivery over this subtotal

/**
 * Return a shared PDO instance (singleton).
 * Errors throw exceptions so we can fail loudly in development.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Friendly message in production; detailed while developing.
            die('Database connection failed. Check config/db.php — ' . $e->getMessage());
        }
    }
    return $pdo;
}
