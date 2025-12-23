<?php
session_start();
// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}
// admin role flag
$isAdmin = isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin';
include 'db.php';

$message = '';

// Handle POST actions (add, update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $imagePath = trim($_POST['imagePath'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);

        $stmt = $conn->prepare("INSERT INTO products (name, price, category, imagePath, stockQuantity) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sdssi', $name, $price, $category, $imagePath, $stock);
            if ($stmt->execute()) {
                $message = 'Product added.';
            } else {
                $message = 'Add failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = 'Prepare failed: ' . $conn->error;
        }
    } elseif ($action === 'update') {
        if (!$isAdmin) {
            $message = 'Permission denied: only admins can update products.';
        } else {
            $id = intval($_POST['productID'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            $imagePath = trim($_POST['imagePath'] ?? '');
            $stock = intval($_POST['stock'] ?? 0);

            $stmt = $conn->prepare("UPDATE products SET name=?, price=?, category=?, imagePath=?, stockQuantity=? WHERE productID=?");
            if ($stmt) {
                $stmt->bind_param('sdssii', $name, $price, $category, $imagePath, $stock, $id);
                if ($stmt->execute()) {
                    $message = 'Product updated.';
                } else {
                    $message = 'Update failed: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = 'Prepare failed: ' . $conn->error;
            }
        }
    }
}

// Handle Delete (require super admin)
if (isset($_GET['delete'])) {
    if (!$isAdmin) {
        $message = 'Permission denied: only admins can delete products.';
    } else {
        $delid = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM products WHERE productID = ?");
        if ($stmt) {
            $stmt->bind_param('i', $delid);
            if ($stmt->execute()) {
                $message = 'Product deleted.';
            } else {
                $message = 'Delete failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = 'Prepare failed: ' . $conn->error;
        }
    }
}

// If editing, fetch product
$editing = false;
$editProduct = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT productID, name, price, category, imagePath, stockQuantity FROM products WHERE productID = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $eid);
        $stmt->execute();
        $res = $stmt->get_result();
        $editProduct = $res->fetch_assoc();
        $editing = $editProduct ? true : false;
        $stmt->close();
    }
}

// Fetch all products
$products = [];
$res = $conn->query("SELECT productID, name, price, category, imagePath, stockQuantity FROM products ORDER BY productID DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $products[] = $row;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Products</title>
    <link rel="stylesheet" href="style.css">
    <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #ddd;text-align:left}form.inline{display:inline}</style>
</head>
<body>
    <h1>Manage Products</h1>
    <?php if ($message): ?><p><strong><?php echo htmlspecialchars($message); ?></strong></p><?php endif; ?>

    <h2><?php echo $editing ? 'Edit Product' : 'Add Product'; ?></h2>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'add'; ?>">
        <?php if ($editing): ?><input type="hidden" name="productID" value="<?php echo (int)$editProduct['productID']; ?>"><?php endif; ?>
        <label>Name: <input name="name" required value="<?php echo $editing ? htmlspecialchars($editProduct['name']) : ''; ?>"></label><br>
        <label>Price: <input name="price" type="number" step="0.01" required value="<?php echo $editing ? htmlspecialchars($editProduct['price']) : '0.00'; ?>"></label><br>
        <label>Category: <input name="category" value="<?php echo $editing ? htmlspecialchars($editProduct['category']) : ''; ?>"></label><br>
        <label>Image Path: <input name="imagePath" value="<?php echo $editing ? htmlspecialchars($editProduct['imagePath']) : ''; ?>"></label><br>
        <label>Stock: <input name="stock" type="number" value="<?php echo $editing ? (int)$editProduct['stockQuantity'] : 0; ?>"></label><br>
        <button type="submit"><?php echo $editing ? 'Update' : 'Add'; ?></button>
        <?php if ($editing): ?><a href="manage_products.php">Cancel</a><?php endif; ?>
    </form>

    <h2>Products List</h2>
    <table>
        <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Category</th><th>Image</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?php echo (int)$p['productID']; ?></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td>RM <?php echo number_format((float)$p['price'],2); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
                <td><img src="<?php echo htmlspecialchars($p['imagePath']); ?>" alt="" style="height:40px;object-fit:cover" onerror="this.src='https://placehold.co/80x40?text=No+Image'"></td>
                <td><?php echo (int)$p['stockQuantity']; ?></td>
                <td>
                    <?php if ($isAdmin): ?>
                        <a href="manage_products.php?edit=<?php echo (int)$p['productID']; ?>">Edit</a>
                        &nbsp;|&nbsp;
                        <a href="manage_products.php?delete=<?php echo (int)$p['productID']; ?>" onclick="return confirm('Delete this product?');">Delete</a>
                    <?php else: ?>
                        <span style="color:#666;">(Edit/Delete only for admins)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
