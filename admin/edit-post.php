<?php
/**
 * Admin - Edit or delete post - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(base_url('admin/posts.php'));

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) redirect(base_url('admin/posts.php'));

$admin_title = 'Edit Post';
$categories = get_categories($pdo);
$tags_list = get_tags_for_post($pdo, $id);
$tags_str = implode(', ', array_column($tags_list, 'name'));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { header('Location: edit-post.php?id=' . $id); exit; }
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $publish_mode = $_POST['publish_mode'] ?? 'draft';
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
        $published_at = null;
    } elseif ($publish_mode === 'schedule' && $scheduled_at !== '') {
        $status = 'published';
        $published_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
    }

    if (empty($errors)) {
        $slug = slugify($title);
        $n = 0;
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if (!$stmt->fetch()) break;
            $slug = slugify($title) . '-' . (++$n);
        }

        $image_path = $post['image'];
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

        $stmt = $pdo->prepare("UPDATE posts SET title=?, slug=?, summary=?, content=?, image=?, category_id=?, status=?, published_at=?, is_featured=?, is_sponsored=?, meta_title=?, meta_description=? WHERE id=?");
        $stmt->execute([$title, $slug, $summary, $content, $image_path, $category_id, $status, $published_at, $is_featured, $is_sponsored, $meta_title ?: null, $meta_description ?: null, $id]);

        $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
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
                $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$id, $tag_id]);
            }
        }
        toast_add_flash('success', 'Post updated successfully.');
        redirect(base_url('admin/posts.php'));
    }
} else {
    $_POST = $post;
    $_POST['tags'] = $tags_str;
    $_POST['meta_title'] = $post['meta_title'];
    $_POST['meta_description'] = $post['meta_description'];
    $_POST['is_featured'] = $post['is_featured'];
    if ($post['status'] === 'draft') {
        $_POST['publish_mode'] = 'draft';
        $_POST['scheduled_at'] = '';
    } elseif (!empty($post['published_at']) && strtotime($post['published_at']) > time()) {
        $_POST['publish_mode'] = 'schedule';
        $_POST['scheduled_at'] = date('Y-m-d\TH:i', strtotime($post['published_at']));
    } else {
        $_POST['publish_mode'] = 'now';
        $_POST['scheduled_at'] = '';
    }
}

foreach ($errors as $e) {
    toast_add('error', $e);
}

$load_tinymce = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Edit Post</h1>
    <form method="post" enctype="multipart/form-data" class="admin-form post-form">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required value="<?php echo e($post['title']); ?>">
        </div>
        <div class="form-group">
            <label for="summary">Summary</label>
            <textarea id="summary" name="summary" rows="3"><?php echo e($post['summary']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="14"><?php echo htmlspecialchars(($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['content'] ?? '') : ($post['content'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $post['category_id'] == $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Publish</label>
                <div class="publish-mode">
                    <label><input type="radio" name="publish_mode" value="now" <?php echo ($_POST['publish_mode'] ?? '') === 'now' ? 'checked' : ''; ?>> Publish Now</label>
                    <label><input type="radio" name="publish_mode" value="draft" <?php echo ($_POST['publish_mode'] ?? '') === 'draft' ? 'checked' : ''; ?>> Save as Draft</label>
                    <label><input type="radio" name="publish_mode" value="schedule" id="publish_mode_schedule" <?php echo ($_POST['publish_mode'] ?? '') === 'schedule' ? 'checked' : ''; ?>> Schedule for Later</label>
                </div>
                <div class="form-group schedule-datetime" id="scheduleDatetimeWrap" style="display:<?php echo ($_POST['publish_mode'] ?? '') === 'schedule' ? 'block' : 'none'; ?>; margin-top: 0.5rem;">
                    <label for="scheduled_at">Date &amp; time</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?php echo e($_POST['scheduled_at'] ?? ''); ?>" min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_featured" value="1" <?php echo $post['is_featured'] ? 'checked' : ''; ?>> Featured</label>
                <label><input type="checkbox" name="is_sponsored" value="1" <?php echo !empty($post['is_sponsored']) ? 'checked' : ''; ?>> Sponsored</label>
            </div>
        </div>
        <div class="form-group">
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" id="tags" name="tags" value="<?php echo e($tags_str); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="meta_title">Meta Title</label>
                <input type="text" id="meta_title" name="meta_title" value="<?php echo e($post['meta_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <input type="text" id="meta_description" name="meta_description" value="<?php echo e($post['meta_description'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Featured Image</label>
            <?php if (!empty($post['image'])): ?>
            <p class="current-image"><img src="<?php echo base_url($post['image']); ?>" alt="" style="max-width:200px;height:auto;"></p>
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <small>Leave empty to keep current.</small>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Post</button>
            <a href="<?php echo base_url('admin/posts.php'); ?>" class="btn btn-secondary">Cancel</a>
            <?php if (can_delete_posts()): ?><a href="<?php echo base_url('admin/delete-post.php?id=' . $id); ?>" class="btn btn-danger">Delete</a><?php endif; ?>
        </div>
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
