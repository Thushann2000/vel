<?php
require_once __DIR__ . '/includes/functions.php';

$sent = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General enquiry');
    $message = trim($_POST['message'] ?? '');

    if ($name === '')                               { $errors[] = 'Please enter your name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email.'; }
    if ($message === '')                            { $errors[] = 'Please enter a message.'; }

    if (!$errors) {
        $ins = db()->prepare('INSERT INTO messages (name, email, subject, body) VALUES (?,?,?,?)');
        $ins->execute([$name, $email, $subject, $message]);
        $sent = true;
    }
}

$pageTitle  = 'Contact Velvet Vogue';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--slim">
  <div class="wrap">
    <span class="eyebrow">We'd love to hear from you</span>
    <h1>Get in Touch</h1>
    <p>Questions on sizing, orders, or a private appointment? Our client care team replies within one business day.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="split" style="align-items:start">
      <div class="split__body">
        <span class="eyebrow eyebrow--dark">Send a Message</span>
        <h2>Write to us</h2>
        <hr class="gold-rule">

        <?php if ($sent): ?>
          <div class="form-note" style="margin-top:1.6rem">Thank you — your message has been received. We'll be in touch shortly.</div>
        <?php elseif ($errors): ?>
          <div class="form-note" style="margin-top:1.6rem;background:rgba(128,0,32,.1)">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="form-card" style="margin-top:1.8rem" novalidate>
          <?= csrf_field() ?>
          <div class="field">
            <label for="cf-name">Full name</label>
            <input type="text" id="cf-name" name="name" placeholder="Your name" value="<?= e($_POST['name'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label for="cf-email">Email address</label>
            <input type="email" id="cf-email" name="email" placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label for="cf-subject">Subject</label>
            <select id="cf-subject" name="subject">
              <option>General enquiry</option>
              <option>Order &amp; delivery</option>
              <option>Sizing &amp; fit</option>
              <option>Private appointment</option>
            </select>
          </div>
          <div class="field">
            <label for="cf-message">Message</label>
            <textarea id="cf-message" name="message" placeholder="How can we help?" required><?= e($_POST['message'] ?? '') ?></textarea>
          </div>
          <button class="btn btn--block" type="submit">Send Message</button>
        </form>
      </div>

      <div class="split__media" style="align-self:stretch">
        <div class="form-card" style="height:100%">
          <span class="eyebrow eyebrow--dark">Visit &amp; Reach Us</span>
          <h2 style="margin-bottom:1.6rem">The Studio</h2>
          <div class="info-list">
            <div class="info-item"><h4>Address</h4><p>42 Horton Place, Colombo 07,<br>Western Province, Sri Lanka</p></div>
            <div class="info-item"><h4>Opening Hours</h4><p>Monday – Saturday · 10:00 – 19:00<br>Sunday · By appointment</p></div>
            <div class="info-item"><h4>Call</h4><p>+94 11 234 5678</p></div>
            <div class="info-item"><h4>Email</h4><p>care@velvetvogue.lk</p></div>
          </div>
          <div class="form-note" style="margin-top:1.8rem">
            Prefer a personal fitting? Book a private styling appointment and we'll set aside the studio just for you.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
