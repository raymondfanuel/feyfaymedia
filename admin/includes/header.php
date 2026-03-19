<?php
/**
 * Admin layout header - FeyFay Media
 */
$settings = get_settings($pdo);
$site_name = e($settings['site_name'] ?? DEFAULT_SITE_NAME);
$user = current_user($pdo);
$admin_title = $admin_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($admin_title); ?> | <?php echo $site_name; ?> Admin</title>
    <link rel="icon" href="<?php echo base_url('assets/images/feyfaylogo.png'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-inner">
            <a href="<?php echo base_url('admin/dashboard.php'); ?>" class="admin-logo"><?php echo $site_name; ?> Admin</a>
            <button type="button" class="admin-nav-toggle" id="adminNavToggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <nav class="admin-nav" id="adminNav">
                <a href="<?php echo base_url('admin/dashboard.php'); ?>">Dashboard</a>
                <a href="<?php echo base_url('admin/posts.php'); ?>">Posts</a>
                <a href="<?php echo base_url('admin/add-post.php'); ?>">Add Post</a>
                <a href="<?php echo base_url('admin/categories.php'); ?>">Categories</a>
                <a href="<?php echo base_url('admin/comments.php'); ?>">Comments</a>
                <?php if (can_manage_settings()): ?><a href="<?php echo base_url('admin/settings.php'); ?>">Settings</a><?php endif; ?>
                <?php if (can_manage_settings()): ?><a href="<?php echo base_url('admin/radio.php'); ?>">Live Radio</a><?php endif; ?>
                <?php if (can_manage_users()): ?><a href="<?php echo base_url('admin/users.php'); ?>">Staff</a><?php endif; ?>
                <a href="<?php echo base_url(); ?>" target="_blank">View Site</a>
                <a href="<?php echo base_url('admin/logout.php'); ?>">Logout</a>
            </nav>
            <span class="admin-user"><?php echo e($user['name'] ?? $user['username'] ?? 'User'); ?> <em>(<?php echo e($user['role'] ?? 'staff'); ?>)</em></span>
        </div>
    </header>
    <main class="admin-main">
