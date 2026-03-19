<?php
/**
 * Contact page - FeyFay Media
 * Contact form + newsletter subscription
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Contact';
$message = '';
$settings = get_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['newsletter'])) {
        // Newsletter signup
        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO subscribers (email) VALUES (?)");
                $stmt->execute([$email]);
                $message = $stmt->rowCount() > 0 ? 'Thank you for subscribing.' : 'You are already subscribed.';
            } catch (Exception $e) {
                $message = 'Subscription failed. Please try again.';
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['message'] ?? '');
        if ($name && $email && $body && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $to = trim($settings['contact_email'] ?? '') ?: 'tricksrayy@gmail.com';
            $subject_line = $subject ?: 'Contact form – ' . ($settings['site_name'] ?? 'FeyFay Media');
            $email_body = "Name: $name\nEmail: $email\n\nMessage:\n$body";
            $site_name = $settings['site_name'] ?? 'FeyFay Media';
            $sent = send_site_email($to, $subject_line, $email_body, $to, $site_name, $name . ' <' . $email . '>');
            $message = $sent ? 'Thank you. We will get back to you soon.' : 'Your message could not be sent. Please try again or email us directly.';
        } else {
            $message = 'Please fill all required fields correctly.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap">
    <div class="content-main static-page">
        <h1 class="page-title">Contact</h1>
        <?php if ($message): ?>
        <p class="form-message"><?php echo e($message); ?></p>
        <?php endif; ?>
        <form class="contact-form" method="post" action="">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="<?php echo e($_POST['subject'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="5" required><?php echo e($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
