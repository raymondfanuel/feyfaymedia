<?php
/**
 * Site configuration - FeyFay Media
 * Constants used when settings table is not yet loaded
 */

// Session start with secure options (once per request)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    if (empty($_SESSION['created'])) {
        $_SESSION['created'] = true;
        session_regenerate_id(true);
    }
}

// Paths (use __FILE__ so path is correct when config is included from admin or root)
define('ROOT_PATH', rtrim(realpath(dirname(__DIR__)) ?: dirname(__DIR__), DIRECTORY_SEPARATOR) . '/');
define('UPLOAD_DIR', 'assets/images/uploads/posts');
define('UPLOAD_PATH', ROOT_PATH . UPLOAD_DIR . '/');
define('UPLOAD_URL', UPLOAD_DIR . '/');

// Pagination
define('POSTS_PER_PAGE', 12);
define('ADMIN_POSTS_PER_PAGE', 15);

// Defaults (overridden by settings table when available)
define('DEFAULT_SITE_NAME', 'FeyFay Media');
define('DEFAULT_SITE_DESCRIPTION', 'Your Trusted Source for News');

// Mail: set these to use MailHog (view at http://localhost:8025). Leave empty to use PHP mail().
define('MAIL_SMTP_HOST', 'localhost');  // e.g. 'localhost' for MailHog
define('MAIL_SMTP_PORT', 1025);          // MailHog SMTP port
