<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: ../login_process.php');
    exit();
}
include '../db.php'; // Path to your connection file

// 1. Get Total Users
$userCountQuery = "SELECT COUNT(*) as total FROM users";
$userCountResult = $conn->query($userCountQuery);
$totalUsers = $userCountResult->fetch_assoc()['total'];

// 2. Get Total Revenue from Orders table
$revenueQuery = "SELECT SUM(totalAmount) as total FROM orders";
$revenueResult = $conn->query($revenueQuery);
$totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;

// 3. Get Active Orders count
$orderCountQuery = "SELECT COUNT(*) as total FROM orders";
$orderCountResult = $conn->query($orderCountQuery);
$totalOrders = $orderCountResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar h1 {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 15px;
        }

        .nav-menu a {
            color: #ecf0f1;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-menu a:hover {
            background-color: #34495e;
        }

        .nav-menu a.active {
            background-color: #3498db;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h2 {
            font-size: 28px;
            color: #2c3e50;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #3498db;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .stat-card.green {
            border-left-color: #27ae60;
        }

        .stat-card.orange {
            border-left-color: #e67e22;
        }

        .stat-card.red {
            border-left-color: #e74c3c;
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-card .change {
            font-size: 12px;
            color: #27ae60;
            margin-top: 5px;
        }

        .change.negative {
            color: #e74c3c;
        }

        /* Table Section */
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 20px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background-color: #f8f9fa;
        }

        table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge.danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .btn-primary {
            background-color: #27ae60;
            color: white;
            padding: 10px 20px;
            margin-bottom: 20px;
        }

        .btn-primary:hover {
            background-color: #229954;
        }

        /* Footer */
        footer {
            background: white;
            padding: 20px;
            text-align: center;
            color: #7f8c8d;
            margin-left: 250px;
            border-top: 1px solid #ecf0f1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            footer {
                margin-left: 0;
            }

            .nav-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .nav-menu li {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <h1>Dashboard</h1>
            <nav>
                <ul class="nav-menu">
                    <li><a href="#" class="active">🏠 Home</a></li>
                    <li><a href="#">👥 Users</a></li>
                    <li><a href="#">📊 Reports</a></li>
                    <li><a href="#">⚙️ Settings</a></li>
                    <li><a href="#">📝 Content</a></li>
                    <li><a href="#">💬 Messages</a></li>
                    <li><a href="#">📈 Analytics</a></li>
                    <li><a href="#">🚪 Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['fullName']); ?>!</h2>
                <div class="user-info">
                    <div>
                        <p><strong><?php echo htmlspecialchars($_SESSION['fullName']); ?></strong></p>
                        <p style="font-size: 12px; color: #7f8c8d;">Administrator</p>
                    </div>
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%233498db'/%3E%3Ctext x='50' y='55' font-size='60' fill='white' text-anchor='middle' font-family='Arial'%3EAD%3C/text%3E%3C/svg%3E" alt="User Avatar">
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo number_format($totalUsers); ?></div>
                    <div class="change">↑ 12% from last month</div>
                </div>

                <div class="stat-card green">
                    <h3>Total Revenue</h3>
                    <div class="number">RM <?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="change">↑ 8% from last month</div>
                </div>

                <div class="stat-card orange">
                    <h3>Active Orders</h3>
                    <div class="number"><?php echo number_format($totalOrders); ?></div>
                    <div class="change">↓ 3% from last month</div>
                </div>

                <div class="stat-card red">
                    <h3>Pending Tasks</h3>
                    <div class="number">42</div>
                    <div class="change negative">↑ 5% from last month</div>
                </div>
            </div>

            <!-- Recent Users Section -->
            <section class="section">
                <h3>Recent Users</h3>
                <button class="btn btn-primary">+ Add New User</button>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Join Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#001</td>
                                <td>Alice Johnson</td>
                                <td>alice@example.com</td>
                                <td><span class="badge success">Active</span></td>
                                <td>2024-01-15</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit">Edit</button>
                                        <button class="btn btn-delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#002</td>
                                <td>Bob Smith</td>
                                <td>bob@example.com</td>
                                <td><span class="badge success">Active</span></td>
                                <td>2024-02-20</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit">Edit</button>
                                        <button class="btn btn-delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#003</td>
                                <td>Carol Davis</td>
                                <td>carol@example.com</td>
                                <td><span class="badge pending">Pending</span></td>
                                <td>2024-03-10</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit">Edit</button>
                                        <button class="btn btn-delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#004</td>
                                <td>David Wilson</td>
                                <td>david@example.com</td>
                                <td><span class="badge danger">Inactive</span></td>
                                <td>2024-04-05</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit">Edit</button>
                                        <button class="btn btn-delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>#005</td>
                                <td>Emma Martinez</td>
                                <td>emma@example.com</td>
                                <td><span class="badge success">Active</span></td>
                                <td>2024-05-12</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-edit">Edit</button>
                                        <button class="btn btn-delete">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Quick Actions Section -->
            <section class="section">
                <h3>Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <button class="btn btn-primary" style="padding: 15px 20px;">Create Report</button>
                    <button class="btn btn-primary" style="padding: 15px 20px;">Send Notification</button>
                    <button class="btn btn-primary" style="padding: 15px 20px;">Backup Database</button>
                    <button class="btn btn-primary" style="padding: 15px 20px;">View Logs</button>
                </div>
            </section>
        </main>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Admin Dashboard. All rights reserved.</p>
    </footer>
</body>
</html>
