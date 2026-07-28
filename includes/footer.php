  </main>

  <footer class="site-footer">
    <div class="wrap">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="<?= e(base_url('index.php')) ?>" class="brand">Velvet<span>Vogue</span></a>
          <p>Considered clothing for a life well dressed. Cut, sourced, and finished for those who value the quiet confidence of true craft.</p>
        </div>
        <div class="footer-col">
          <h4>Explore</h4>
          <ul>
            <li><a href="<?= e(base_url('index.php')) ?>">Home</a></li>
            <li><a href="<?= e(base_url('products.php')) ?>">Shop</a></li>
            <li><a href="<?= e(base_url('cart.php')) ?>">Cart</a></li>
            <li><a href="<?= e(base_url('account.php')) ?>">Account</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Client Care</h4>
          <ul>
            <li><a href="<?= e(base_url('contact.php')) ?>">Contact Us</a></li>
            <li><a href="<?= e(base_url('contact.php')) ?>">Shipping &amp; Returns</a></li>
            <li><a href="<?= e(base_url('contact.php')) ?>">Size Guide</a></li>
            <li><a href="<?= e(base_url('contact.php')) ?>">FAQ</a></li>
          </ul>
        </div>
        <div class="footer-col footer-brand">
          <h4>The Atelier Letter</h4>
          <p>New arrivals, private previews, and styling notes — direct to you.</p>
          <form class="newsletter" novalidate>
            <input type="email" placeholder="Email address" aria-label="Email address" required>
            <button class="btn btn--gold btn--sm" type="submit">Join</button>
          </form>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; <span class="js-year">2025</span> Velvet Vogue · Colombo, Sri Lanka. All rights reserved.</span>
        <div class="socials">
          <a href="#">Instagram</a>
          <a href="#">Pinterest</a>
          <a href="#">Journal</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- SweetAlert2 (https://sweetalert2.github.io) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if (!empty($flash)): ?>
  <script>
    // Show the server-side flash message as a SweetAlert2 toast.
    // $flash['type'] is 'success' or 'error'; anything else falls back to 'info'.
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: <?= json_encode(
                in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)
                    ? $flash['type'] : 'info',
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
             ) ?>,
      title: <?= json_encode($flash['message'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true
    });
  </script>
  <?php endif; ?>

  <script src="<?= e(base_url('assets/js/script.js')) ?>"></script>
</body>
</html>
