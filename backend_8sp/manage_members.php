<?php
session_start();
// Admin-only
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: ../login.html');
    exit();
}
// admin flag (no separate super-admin)
$isAdmin = isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin';
include '../db.php';

$message = '';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setRole') {
    $target = intval($_POST['userID']);
    $newRole = $_POST['role'] === 'admin' ? 'admin' : 'user';

    // Only admins can change roles (this page already requires admin access)
    if ($newRole === 'admin' && !$isAdmin) {
        $message = 'Permission denied: only admins can grant admin role.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET userType = ? WHERE userID = ?");
        if ($stmt) {
            $stmt->bind_param('si', $newRole, $target);
            if ($stmt->execute()) $message = 'Role updated.'; else $message = 'Update failed: '.$stmt->error;
            $stmt->close();
        } else {
            $message = 'Prepare failed: '.$conn->error;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delid = intval($_GET['delete']);
    // Prevent self-delete
    if ($delid === intval($_SESSION['userID'])) {
        $message = 'You cannot delete your own account.';
    } else {
        // Check target role
        $q = $conn->prepare("SELECT userType FROM users WHERE userID = ? LIMIT 1");
        $q->bind_param('i', $delid);
        $q->execute();
        $res = $q->get_result();
        $row = $res->fetch_assoc();
        $q->close();
        $targetRole = $row['userType'] ?? 'user';
        // allow admins to delete other accounts (except self)
        if ($targetRole === 'admin' && !$isAdmin) {
            $message = 'Permission denied: only admins can delete admin accounts.';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE userID = ?");
            if ($stmt) {
                $stmt->bind_param('i', $delid);
                if ($stmt->execute()) $message = 'User deleted.'; else $message = 'Delete failed: '.$stmt->error;
                $stmt->close();
            } else {
                $message = 'Prepare failed: '.$conn->error;
            }
        }
    }
}

// Fetch users
$users = [];
$r = $conn->query("SELECT userID, fullName, email, userType FROM users ORDER BY userID DESC");
if ($r) while ($u = $r->fetch_assoc()) $users[] = $u;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Members</title>
    <link rel="stylesheet" href="../style.css">
    <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #ddd}</style>
</head>
<body>
    <h1>Manage Members</h1>
    <?php if ($message): ?><p><strong><?php echo htmlspecialchars($message); ?></strong></p><?php endif; ?>

    <table>
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo (int)$u['userID']; ?></td>
                <td><?php echo htmlspecialchars($u['fullName']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['userType']); ?></td>
                <td>
                    <form method="post" class="inline" style="display:inline-block;margin-right:8px;">
                        <input type="hidden" name="action" value="setRole">
                        <input type="hidden" name="userID" value="<?php echo (int)$u['userID']; ?>">
                        <select name="role">
                            <option value="user" <?php if ($u['userType']==='user') echo 'selected'; ?>>User</option>
                            <option value="admin" <?php if ($u['userType']==='admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <button type="submit">Save</button>
                    </form>
                    <?php if ((int)$u['userID'] !== (int)$_SESSION['userID']): ?>
                        <a href="manage_members.php?delete=<?php echo (int)$u['userID']; ?>" onclick="return confirm('Delete this user?');">Delete</a>
                    <?php else: ?>
                        <span style="color:#666">(You)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
