<?php
/**
 * Helper functions - FeyFay Media News Blog CMS
 */

/**
 * Generate URL-friendly slug
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'post');
}

/**
 * Truncate text safely for excerpts
 */
function excerpt($text, $length = 160) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Format date for display
 */
function format_date($date) {
    return date('F j, Y', strtotime($date));
}

function format_datetime($date) {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Escape for HTML (XSS protection)
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Base URL of the site (works from root and from /admin)
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = dirname($script);
    if (strpos($base, '/admin') !== false) {
        $base = dirname($base);
    }
    $base = rtrim($protocol . '://' . $host . $base, '/');
    return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
}

/**
 * Ensure upload directory exists and is writable (returns true if ok)
 */
function ensure_upload_dir() {
    $path = defined('UPLOAD_PATH') ? UPLOAD_PATH : (dirname(__DIR__) . '/assets/images/uploads/posts/');
    if (!is_dir($path)) {
        $parent = dirname($path);
        if (!is_dir($parent)) {
            @mkdir(dirname($parent), 0755, true);
        }
        return @mkdir($path, 0755, true);
    }
    return is_writable($path);
}

/**
 * Get safe file extension from MIME type for uploads
 */
function mime_to_ext($mime) {
    $map = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    return $map[$mime] ?? 'jpg';
}

/**
 * Redirect and exit
 */
function redirect($url, $code = 302) {
    header('Location: ' . $url, true, $code);
    exit;
}

/**
 * Toast notifications (current request + flash across redirects)
 */
function toast_add($type, $message, $flash = false) {
    $message = trim((string)$message);
    if ($message === '') return;

    $type = strtolower(trim((string)$type));
    $allowed = ['success', 'error', 'info', 'warning'];
    if (!in_array($type, $allowed, true)) $type = 'info';

    $toast = ['type' => $type, 'message' => $message];
    if ($flash) {
        if (!isset($_SESSION['toast_notifications']) || !is_array($_SESSION['toast_notifications'])) {
            $_SESSION['toast_notifications'] = [];
        }
        $_SESSION['toast_notifications'][] = $toast;
        return;
    }

    if (!isset($GLOBALS['toast_notifications']) || !is_array($GLOBALS['toast_notifications'])) {
        $GLOBALS['toast_notifications'] = [];
    }
    $GLOBALS['toast_notifications'][] = $toast;
}

function toast_add_flash($type, $message) {
    toast_add($type, $message, true);
}

function toast_consume_all() {
    $toasts = [];

    if (!empty($_SESSION['toast_notifications']) && is_array($_SESSION['toast_notifications'])) {
        foreach ($_SESSION['toast_notifications'] as $t) {
            if (!is_array($t)) continue;
            $msg = trim((string)($t['message'] ?? ''));
            if ($msg === '') continue;
            $type = strtolower((string)($t['type'] ?? 'info'));
            if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) $type = 'info';
            $toasts[] = ['type' => $type, 'message' => $msg];
        }
    }
    unset($_SESSION['toast_notifications']);

    if (!empty($GLOBALS['toast_notifications']) && is_array($GLOBALS['toast_notifications'])) {
        foreach ($GLOBALS['toast_notifications'] as $t) {
            if (!is_array($t)) continue;
            $msg = trim((string)($t['message'] ?? ''));
            if ($msg === '') continue;
            $type = strtolower((string)($t['type'] ?? 'info'));
            if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) $type = 'info';
            $toasts[] = ['type' => $type, 'message' => $msg];
        }
    }
    $GLOBALS['toast_notifications'] = [];

    return $toasts;
}

function render_toasts_script() {
    $toasts = toast_consume_all();
    if (empty($toasts)) return '';

    $json = json_encode($toasts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || $json === 'null') return '';

    return '<script>(function(){var toasts=' . $json . ';function run(){if(!window.FeyFayToast||typeof window.FeyFayToast.show!=="function")return;toasts.forEach(function(t){window.FeyFayToast.show(t.message,t.type);});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",run);}else{run();}})();</script>';
}

/**
 * Send email via SMTP (e.g. MailHog on localhost:1025). Returns true on success.
 */
function send_mail_smtp($to, $subject, $body, $from_email, $from_name = '', $reply_to = '') {
    if (!defined('MAIL_SMTP_HOST') || MAIL_SMTP_HOST === '' || !defined('MAIL_SMTP_PORT')) {
        return false;
    }
    $host = MAIL_SMTP_HOST;
    $port = (int) MAIL_SMTP_PORT;
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 5);
    if (!$fp) return false;
    $read = function () use ($fp) {
        $line = @fgets($fp, 512);
        return $line !== false ? trim($line) : '';
    };
    $write = function ($cmd) use ($fp) { @fwrite($fp, $cmd . "\r\n"); };
    if (strpos($read(), '220') !== 0) { fclose($fp); return false; }
    $write('HELO localhost');
    if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
    $write('MAIL FROM:<' . $from_email . '>');
    if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
    $write('RCPT TO:<' . $to . '>');
    if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
    $write('DATA');
    if (strpos($read(), '354') !== 0) { fclose($fp); return false; }
    $headers = 'From: ' . ($from_name ? $from_name . ' <' . $from_email . '>' : $from_email) . "\r\n";
    if ($reply_to) $headers .= 'Reply-To: ' . $reply_to . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
    $headers .= 'Subject: ' . $subject . "\r\n";
    $write($headers . "\r\n" . $body);
    $write('.');
    $last = $read();
    fclose($fp);
    return strpos($last, '250') === 0;
}

/**
 * Send contact/notification email. Uses SMTP if MAIL_SMTP_HOST is set, else PHP mail().
 */
function send_site_email($to, $subject, $body, $from_email, $from_name = '', $reply_to = '') {
    if (defined('MAIL_SMTP_HOST') && MAIL_SMTP_HOST !== '') {
        return send_mail_smtp($to, $subject, $body, $from_email, $from_name, $reply_to);
    }
    $headers = [
        'From: ' . ($from_name ? $from_name . ' <' . $from_email . '>' : $from_email),
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
    ];
    if ($reply_to) $headers[] = 'Reply-To: ' . $reply_to;
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Get site settings (single row id=1). Pass true as second arg to force reload (e.g. after update).
 */
function get_settings($pdo, $reload = false) {
    static $settings = null;
    if ($reload) $settings = null;
    if ($settings === null) {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch();
    }
    return $settings ?: [];
}

/**
 * Get all categories (cached per request to reduce queries)
 */
function get_categories($pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $cache = $stmt->fetchAll();
    return $cache;
}

/**
 * SQL condition for posts visible on the public site (published and, if scheduled, published_at in the past)
 * $alias = table alias used in the query (e.g. 'p')
 */
function posts_public_visibility_sql($alias = 'p') {
    return "($alias.status = 'published' AND ($alias.published_at IS NULL OR $alias.published_at <= NOW()))";
}

/**
 * ORDER BY for public posts (scheduled posts sort by when they go live)
 */
function posts_public_order_sql($alias = 'p') {
    return "COALESCE($alias.published_at, $alias.created_at) DESC";
}

/**
 * Get display status for admin: 'draft', 'scheduled', or 'published'
 */
function post_display_status($post) {
    if (empty($post['status']) || $post['status'] === 'draft') return 'draft';
    if (!empty($post['published_at']) && strtotime($post['published_at']) > time()) return 'scheduled';
    return 'published';
}

/**
 * Get category by id or slug
 */
function get_category($pdo, $id_or_slug) {
    $col = is_numeric($id_or_slug) ? 'id' : 'slug';
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE $col = ?");
    $stmt->execute([$id_or_slug]);
    return $stmt->fetch();
}

/**
 * Get published posts with pagination (only posts visible on public site: published + scheduled in past)
 */
function get_posts($pdo, $limit = 10, $offset = 0, $category_id = null, $featured_only = false) {
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
            FROM posts p 
            JOIN categories c ON p.category_id = c.id 
            JOIN users u ON p.author_id = u.id 
            WHERE " . posts_public_visibility_sql('p');
    $params = [];
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }
    if ($featured_only) {
        $sql .= " AND p.is_featured = 1";
    }
    $sql .= " ORDER BY " . posts_public_order_sql('p') . " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get single post by id or slug (public: published and visible, i.e. scheduled time in past or publish now)
 */
function get_post($pdo, $id_or_slug, $public_only = true) {
    $col = is_numeric($id_or_slug) ? 'p.id' : 'p.slug';
    $val = $id_or_slug;
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
            FROM posts p 
            JOIN categories c ON p.category_id = c.id 
            JOIN users u ON p.author_id = u.id 
            WHERE $col = ?";
    if ($public_only) $sql .= " AND " . posts_public_visibility_sql('p');
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$val]);
    return $stmt->fetch();
}

/**
 * Get featured post (one featured article for hero; only if visible on public site)
 */
function get_featured_post($pdo) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
                           FROM posts p 
                           JOIN categories c ON p.category_id = c.id 
                           JOIN users u ON p.author_id = u.id 
                           WHERE " . posts_public_visibility_sql('p') . " AND p.is_featured = 1 
                           ORDER BY " . posts_public_order_sql('p') . " LIMIT 1");
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Breaking news: latest 5 published posts (for ticker; only visible on public site)
 */
function get_breaking_news($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT id, title, slug, created_at, published_at FROM posts WHERE " . posts_public_visibility_sql('posts') . " ORDER BY " . posts_public_order_sql('posts') . " LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Trending: most viewed published posts (only visible on public site)
 */
function get_trending_posts($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.views FROM posts p WHERE " . posts_public_visibility_sql('p') . " ORDER BY p.views DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Related posts (same category, exclude current; only visible on public site)
 */
function get_related_posts($pdo, $post_id, $category_id, $limit = 3) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                          FROM posts p JOIN categories c ON p.category_id = c.id 
                          WHERE p.category_id = ? AND p.id != ? AND " . posts_public_visibility_sql('p') . " 
                          ORDER BY " . posts_public_order_sql('p') . " LIMIT ?");
    $stmt->execute([$category_id, $post_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Search published posts (with optional pagination; only visible on public site)
 */
function search_posts($pdo, $q, $limit = 20, $offset = 0) {
    $term = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
                           FROM posts p 
                           JOIN categories c ON p.category_id = c.id 
                           JOIN users u ON p.author_id = u.id 
                           WHERE " . posts_public_visibility_sql('p') . " 
                           AND (p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?) 
                           ORDER BY " . posts_public_order_sql('p') . " LIMIT ? OFFSET ?");
    $stmt->execute([$term, $term, $term, $limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Count search results (for pagination; only visible on public site)
 */
function count_search_posts($pdo, $q) {
    $term = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts p 
                           WHERE " . posts_public_visibility_sql('p') . " 
                           AND (p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ?)");
    $stmt->execute([$term, $term, $term]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get latest posts for multiple categories (one query for homepage category sections)
 */
function get_posts_by_category_ids($pdo, $category_ids, $per_category = 3) {
    if (empty($category_ids)) return [];
    $placeholders = implode(',', array_fill(0, count($category_ids), '?'));
    $limit = count($category_ids) * $per_category;
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                           FROM posts p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE " . posts_public_visibility_sql('p') . " AND p.category_id IN ($placeholders) 
                           ORDER BY p.category_id, " . posts_public_order_sql('p'));
    $stmt->execute(array_values($category_ids));
    $all = $stmt->fetchAll();
    $grouped = [];
    foreach ($all as $row) {
        $cid = $row['category_id'];
        if (!isset($grouped[$cid])) $grouped[$cid] = [];
        if (count($grouped[$cid]) < $per_category) $grouped[$cid][] = $row;
    }
    return $grouped;
}

/**
 * Count published posts (only visible on public site)
 */
function count_posts($pdo, $category_id = null) {
    $sql = "SELECT COUNT(*) FROM posts WHERE " . posts_public_visibility_sql('posts');
    $params = [];
    if ($category_id) {
        $sql .= " AND category_id = ?";
        $params[] = $category_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Get tags for a post
 */
function get_tags_for_post($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT t.id, t.name, t.slug FROM tags t INNER JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

/**
 * Get approved comments for a post
 */
function get_post_comments($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

/**
 * Count comments (all or by status)
 */
function count_comments($pdo, $status = null) {
    $sql = "SELECT COUNT(*) FROM comments";
    $params = [];
    if ($status) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Increment post view count
 */
function increment_post_views($pdo, $post_id) {
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
}
