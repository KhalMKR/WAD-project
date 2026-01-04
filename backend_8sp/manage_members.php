<?php
session_start();
// Admin-only
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: ../login.html');
    exit();
}
// admin flag (no separate super-admin)
$isAdmin = isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin';
include '../includes/db.php';

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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; color: #333; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #2c3e50; color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h1 { font-size: 24px; margin-bottom: 30px; text-align: center; }
        .nav-menu { list-style: none; }
        .nav-menu li { margin-bottom: 15px; }
        .nav-menu a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px 15px; border-radius: 5px; transition: background-color 0.3s; }
        .nav-menu a:hover { background-color: #34495e; }
        .nav-menu a.active { background-color: #3498db; }
        .main-content { margin-left: 250px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .message { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 5px; margin-bottom: 20px; }
        .btn { padding: 10px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn-danger:hover { background-color: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background-color: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background-color: #f8f9fa; }
        select, input { padding: 8px; border-radius: 5px; border: 1px solid #ddd; }
        form.inline { display: inline-block; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h1>Dashboard</h1>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">🏠 Home</a></li>
                    <li><a href="manage_members.php" class="active">👥 Members</a></li>
                    <li><a href="../manage_products.php">📦 Products & Prices</a></li>
                    <li><a href="../transaction_reports.php">📊 Transaction Reports</a></li>
                    <li><a href="logout.php">🚪 Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <div class="header">
                <h1>Manage Members</h1>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="section">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo (int)$u['userID']; ?></td>
                            <td><?php echo htmlspecialchars($u['fullName']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['userType']); ?></td>
                            <td>
                                <form method="post" class="inline">
                                    <input type="hidden" name="action" value="setRole">
                                    <input type="hidden" name="userID" value="<?php echo (int)$u['userID']; ?>">
                                    <select name="role">
                                        <option value="user" <?php if ($u['userType']==='user') echo 'selected'; ?>>User</option>
                                        <option value="admin" <?php if ($u['userType']==='admin') echo 'selected'; ?>>Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </form>
                                <?php if ((int)$u['userID'] !== (int)$_SESSION['userID']): ?>
                                    <a href="manage_members.php?delete=<?php echo (int)$u['userID']; ?>" class="btn btn-danger" onclick="return confirm('Delete this user?');">Delete</a>
                                <?php else: ?>
                                    <span style="color:#666">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
