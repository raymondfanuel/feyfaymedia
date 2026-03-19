<?php
/**
 * Admin - Add new post - FeyFay Media
 * Title, slug, summary, content, image, category, tags, featured, status, meta
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$admin_title = 'Add Post';
$user = current_user($pdo);
$categories = get_categories($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Invalid request. Please try again.'; }
    else {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $publish_mode = $_POST['publish_mode'] ?? 'draft'; // 'now' | 'draft' | 'schedule'
    $scheduled_at = trim($_POST['scheduled_at'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_sponsored = isset($_POST['is_sponsored']) ? 1 : 0;
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $tags_input = trim($_POST['tags'] ?? '');

    if (!$title) $errors[] = 'Title is required.';
    if (!$category_id) $errors[] = 'Select a category.';
    if (trim($content) === '') $errors[] = 'Content is required.';
    if ($publish_mode === 'schedule' && $scheduled_at === '') $errors[] = 'Please choose a date and time for scheduling.';
    if ($publish_mode === 'schedule' && $scheduled_at !== '' && strtotime($scheduled_at) <= time()) $errors[] = 'Scheduled time must be in the future.';

    $status = 'draft';
    $published_at = null;
    if ($publish_mode === 'now') {
        $status = 'published';
        $published_at = null; // visible immediately
    } elseif ($publish_mode === 'schedule' && $scheduled_at !== '') {
        $status = 'published';
        $published_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
    }

    if (empty($errors)) {
        $slug = slugify($title);
        $n = 0;
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) break;
            $slug = slugify($title) . '-' . (++$n);
        }

        $image_path = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            if (in_array($mime, $allowed)) {
                $ext = mime_to_ext($mime);
                $filename = 'post-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                ensure_upload_dir();
                $full_path = rtrim(UPLOAD_PATH, '/') . '/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                    $image_path = rtrim(UPLOAD_URL, '/') . '/' . $filename;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, summary, content, image, category_id, author_id, status, published_at, is_featured, is_sponsored, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $summary, $content, $image_path, $category_id, $user['id'], $status, $published_at, $is_featured, $is_sponsored, $meta_title ?: null, $meta_description ?: null]);

        $post_id = (int) $pdo->lastInsertId();
        // Tags: comma-separated names, create tag if not exists, link in post_tags
        if ($tags_input !== '') {
            $tag_names = array_unique(array_map('trim', explode(',', $tags_input)));
            foreach ($tag_names as $name) {
                $name = trim($name);
                if ($name === '') continue;
                $tag_slug = slugify($name);
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                $stmt->execute([$tag_slug]);
                $tag = $stmt->fetch();
                if (!$tag) {
                    $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)")->execute([$name, $tag_slug]);
                    $tag_id = (int) $pdo->lastInsertId();
                } else {
                    $tag_id = (int) $tag['id'];
                }
                $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$post_id, $tag_id]);
            }
        }
        toast_add_flash('success', 'Post created successfully.');
        redirect(base_url('admin/posts.php'));
    }
    }
}

foreach ($errors as $e) {
    toast_add('error', $e);
}

$load_tinymce = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Add New Post</h1>
    <form method="post" enctype="multipart/form-data" class="admin-form post-form">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required value="<?php echo e($_POST['title'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="summary">Summary</label>
            <textarea id="summary" name="summary" rows="3"><?php echo e($_POST['summary'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="14"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Publish</label>
                <div class="publish-mode">
                    <label><input type="radio" name="publish_mode" value="now" <?php echo ($_POST['publish_mode'] ?? '') === 'now' ? 'checked' : ''; ?>> Publish Now</label>
                    <label><input type="radio" name="publish_mode" value="draft" <?php echo ($_POST['publish_mode'] ?? 'draft') === 'draft' ? 'checked' : ''; ?>> Save as Draft</label>
                    <label><input type="radio" name="publish_mode" value="schedule" id="publish_mode_schedule" <?php echo ($_POST['publish_mode'] ?? '') === 'schedule' ? 'checked' : ''; ?>> Schedule for Later</label>
                </div>
                <div class="form-group schedule-datetime" id="scheduleDatetimeWrap" style="display:<?php echo ($_POST['publish_mode'] ?? '') === 'schedule' ? 'block' : 'none'; ?>; margin-top: 0.5rem;">
                    <label for="scheduled_at">Date &amp; time</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?php echo e($_POST['scheduled_at'] ?? ''); ?>" min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_featured" value="1" <?php echo !empty($_POST['is_featured']) ? 'checked' : ''; ?>> Featured</label>
                <label><input type="checkbox" name="is_sponsored" value="1" <?php echo !empty($_POST['is_sponsored']) ? 'checked' : ''; ?>> Sponsored</label>
            </div>
        </div>
        <div class="form-group">
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" id="tags" name="tags" placeholder="tech, news, world" value="<?php echo e($_POST['tags'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="meta_title">Meta Title (SEO)</label>
                <input type="text" id="meta_title" name="meta_title" value="<?php echo e($_POST['meta_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description (SEO)</label>
                <input type="text" id="meta_description" name="meta_description" value="<?php echo e($_POST['meta_description'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="image">Featured Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <button type="submit" class="btn btn-primary">Save Post</button>
    </form>
</div>
<script>
(function() {
    var schedule = document.getElementById('publish_mode_schedule');
    var wrap = document.getElementById('scheduleDatetimeWrap');
    if (!schedule || !wrap) return;
    document.querySelectorAll('input[name="publish_mode"]').forEach(function(r) {
        r.addEventListener('change', function() { wrap.style.display = document.getElementById('publish_mode_schedule').checked ? 'block' : 'none'; });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
