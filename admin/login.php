<?php
/**
 * Admin login - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(base_url('admin/dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) {
            $error = 'Please enter username and password.';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && (int)($user['is_active'] ?? 1) === 1 && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'staff';
                $redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : 'dashboard.php';
                $redirect = preg_replace('#^admin/#', '', $redirect);
                if (!preg_match('#^[a-z0-9_\-\.]+\.php(\?.*)?$#i', $redirect)) $redirect = 'dashboard.php';
                redirect(base_url('admin/' . $redirect));
            }
            $error = 'Invalid username or password.';
        }
    }
}
$settings = get_settings($pdo);
$site_name = e($settings['site_name'] ?? DEFAULT_SITE_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $site_name; ?> Admin</title>
    <link rel="icon" href="<?php echo base_url('assets/images/feyfaylogo.png'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body admin-login-page">
    <div class="login-box">
        <h1><?php echo $site_name; ?></h1>
        <p class="login-subtitle">Admin Login</p>
        <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="post" action="" class="admin-form">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
        <p class="login-footer"><a href="<?php echo base_url(); ?>">&larr; Back to site</a></p>
    </div>
</body>
</html>
