<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: ../login_process.php');
    exit();
}
include '../db.php'; 

// 1. Get Total Users
$userCountQuery = "SELECT COUNT(*) as total FROM users";
$userCountResult = $conn->query($userCountQuery);
$totalUsers = $userCountResult->fetch_assoc()['total'];

// 2. Get Total Revenue
$revenueQuery = "SELECT SUM(totalAmount) as total FROM orders";
$revenueResult = $conn->query($revenueQuery);
$totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;

// 3. Get Active Orders count
$orderCountQuery = "SELECT COUNT(*) as total FROM orders";
$orderCountResult = $conn->query($orderCountQuery);
$totalOrders = $orderCountResult->fetch_assoc()['total'];

// users table does not contain status/joinDate in this schema; select available columns
$recentUsersQuery = "SELECT userID AS id, fullName, email, userType FROM users ORDER BY userID DESC LIMIT 5";
$recentUsersResult = $conn->query($recentUsersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* Existing styles kept for consistency */
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge.success { background-color: #d4edda; color: #155724; }
        .btn { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #27ae60; color: white; }
        footer { background: white; padding: 20px; text-align: center; color: #7f8c8d; margin-left: 250px; border-top: 1px solid #ecf0f1; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h1>Dashboard</h1>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">🏠 Home</a></li>
                    <li><a href="manage_members.php">👥 Members</a></li>
                    <li><a href="manage_products.php">📦 Products & Prices</a></li>
                    <li><a href="transaction_reports.php">📊 Transaction Reports</a></li>
                    <li><a href="logout.php">🚪 Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['fullName']); ?>!</h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo number_format($totalUsers); ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #27ae60;">
                    <h3>Total Revenue</h3>
                    <div class="number">RM <?php echo number_format($totalRevenue, 2); ?></div>
                </div>
                <div class="stat-card" style="border-left-color: #e67e22;">
                    <h3>Active Orders</h3>
                    <div class="number"><?php echo number_format($totalOrders); ?></div>
                </div>
            </div>

            <section class="section">
                <h3>Recent Registered Members</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $recentUsersResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['fullName']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge success"><?php echo htmlspecialchars($user['userType']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <footer>
        <p>&copy; 2025 Admin Dashboard. All rights reserved.</p>
    </footer>
</body>
</html>