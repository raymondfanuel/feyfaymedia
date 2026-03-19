<?php
/**
 * Admin - Manage staff (admin only) - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role_admin();
$admin_title = 'Staff';

$error = '';
$success = '';
$current_user_id = (int)($_SESSION['user_id'] ?? 0);

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user']) && csrf_verify()) {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['staff', 'admin']) ? $_POST['role'] : 'staff';
    if (!$name || !$username || !$email || !$password) {
        $error = 'Name, username, email and password are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already in use.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)")->execute([$name, $username, $email, $hash, $role]);
            $success = 'User added.';
        }
    }
}

// Update user (role, active, password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user']) && csrf_verify()) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $role = in_array($_POST['role'] ?? '', ['staff', 'admin']) ? $_POST['role'] : 'staff';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = $_POST['new_password'] ?? '';
    if ($uid) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        if ($stmt->fetch()) {
            if ($new_password !== '') {
                if (strlen($new_password) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } else {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET role = ?, is_active = ?, password = ?, updated_at = NOW() WHERE id = ?")->execute([$role, $is_active, $hash, $uid]);
                    $success = 'User updated.';
                }
            } else {
                $pdo->prepare("UPDATE users SET role = ?, is_active = ?, updated_at = NOW() WHERE id = ?")->execute([$role, $is_active, $uid]);
                $success = 'User updated.';
            }
        }
    }
}

// Delete user (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && csrf_verify()) {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid <= 0) {
        $error = 'Invalid user selected.';
    } elseif ($uid === $current_user_id) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $target = $stmt->fetch();

        if (!$target) {
            $error = 'User not found.';
        } else {
            if (($target['role'] ?? '') === 'admin') {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $admin_count = (int)$stmt->fetchColumn();
                if ($admin_count <= 1) {
                    $error = 'Cannot delete the last admin account.';
                }
            }

            if ($error === '') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
                $stmt->execute([$uid]);
                $posts_count = (int)$stmt->fetchColumn();
                if ($posts_count > 0) {
                    $error = 'Cannot delete this user because they have posts. Reassign or delete their posts first.';
                } else {
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
                    $success = 'User deleted.';
                }
            }
        }
    }
}

$stmt = $pdo->query("SELECT id, name, username, email, role, is_active, created_at FROM users ORDER BY role DESC, name ASC");
$users = $stmt->fetchAll();

if ($error !== '') toast_add('error', $error);
if ($success !== '') toast_add('success', $success);

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Staff &amp; Admins</h1>

    <section class="users-add">
        <h2>Add user</h2>
        <form method="post" class="admin-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="add_user" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo e($_POST['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="Min 8 characters">
                </div>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add user</button>
        </form>
    </section>

    <h2>All users</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Active</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo e($u['name']); ?></td>
                <td><?php echo e($u['username']); ?></td>
                <td><?php echo e($u['email']); ?></td>
                <td><span class="status-badge status-<?php echo e($u['role']); ?>"><?php echo e($u['role']); ?></span></td>
                <td><?php echo (int)$u['is_active'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo format_date($u['created_at']); ?></td>
                <td>
                    <button type="button" class="btn btn-small" onclick="document.getElementById('edit-<?php echo $u['id']; ?>').style.display=document.getElementById('edit-<?php echo $u['id']; ?>').style.display==='none'?'block':'none'">Edit</button>
                    <?php if ((int)$u['id'] !== $current_user_id): ?>
                    <form method="post" class="form-inline" style="display:inline;" onsubmit="return confirm('Delete this user account?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="delete_user" value="1">
                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                        <button type="submit" class="btn btn-small link-delete">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <tr id="edit-<?php echo $u['id']; ?>" style="display:none;">
                <td colspan="7" class="edit-user-cell">
                    <form method="post" class="admin-form form-inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="update_user" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <label>Role <select name="role">
                            <option value="staff" <?php echo $u['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select></label>
                        <label><input type="checkbox" name="is_active" value="1" <?php echo (int)$u['is_active'] ? 'checked' : ''; ?>> Active</label>
                        <label>New password (leave blank to keep) <input type="password" name="new_password" minlength="8"></label>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
