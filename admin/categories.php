<?php
/**
 * Admin - Manage categories - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$admin_title = 'Categories';
$categories = get_categories($pdo);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category']) && can_manage_categories()) {
    if (!csrf_verify()) { header('Location: categories.php'); exit; }
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $slug = slugify($name);
        $n = 0;
        $orig = $slug;
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) break;
            $slug = $orig . '-' . (++$n);
        }
        try {
            $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)")->execute([$name, $slug]);
            $success = 'Category added.';
            $categories = get_categories($pdo);
        } catch (PDOException $e) {
            $error = 'Could not add (duplicate?).';
        }
    } else {
        $error = 'Name is required.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category']) && can_manage_categories()) {
    if (!csrf_verify()) { header('Location: categories.php'); exit; }
    $del_id = (int) ($_POST['delete_category'] ?? 0);
    if ($del_id > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ?");
        $stmt->execute([$del_id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $error = 'Cannot delete: category has posts.';
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$del_id]);
            $success = 'Category deleted.';
            $categories = get_categories($pdo);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Categories</h1>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if (can_manage_categories()): ?>
    <div class="categories-add">
        <h2>Add Category</h2>
        <form method="post" class="admin-form form-inline">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="add_category" value="1">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required placeholder="Category name">
            </div>
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </div>
    <?php endif; ?>
    <h2>All Categories</h2>
    <?php if (empty($categories)): ?>
    <p>No categories yet.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><?php echo e($c['name']); ?></td>
                <td><?php echo e($c['slug']); ?></td>
                <td>
                    <?php if (can_manage_categories()): ?>
                    <form method="post" class="form-inline" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="delete_category" value="<?php echo (int)$c['id']; ?>">
                        <button type="submit" class="btn-link link-delete">Delete</button>
                    </form>
                    <?php else: ?>
                    —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
