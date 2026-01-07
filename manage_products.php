<?php
session_start();
// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}
// admin role flag
$isAdmin = isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin';
include 'includes/db.php';

$message = '';

// Handle POST actions (add, update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $imagePath = '';
        // Handle uploaded image file (optional)
        if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/images/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['imageFile']['name']));
            $target = $uploadDir . $fname;
            if (move_uploaded_file($_FILES['imageFile']['tmp_name'], $target)) {
                $imagePath = $target;
            }
        }
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
            // Preserve existing imagePath unless a new file is uploaded
            $imagePath = '';
            // fetch existing imagePath
            $stmtPrev = $conn->prepare("SELECT imagePath FROM products WHERE productID = ? LIMIT 1");
            if ($stmtPrev) {
                $stmtPrev->bind_param('i', $id);
                $stmtPrev->execute();
                $resPrev = $stmtPrev->get_result();
                $rowPrev = $resPrev->fetch_assoc();
                $imagePath = $rowPrev['imagePath'] ?? '';
                $stmtPrev->close();
            }
            // If a new file uploaded, replace
            if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'assets/images/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['imageFile']['name']));
                $target = $uploadDir . $fname;
                if (move_uploaded_file($_FILES['imageFile']['tmp_name'], $target)) {
                    $imagePath = $target;
                }
            }
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
    <?php include 'header.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Products</title>
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
        .btn { padding: 10px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-success { background-color: #27ae60; color: white; }
        .btn-success:hover { background-color: #229954; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn-danger:hover { background-color: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background-color: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background-color: #f8f9fa; }
        input, select { padding: 8px; border-radius: 5px; border: 1px solid #ddd; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); align-items: center; justify-content: center; z-index: 999; }
        .modal-content { background: #fff; padding: 25px; border-radius: 8px; max-width: 600px; width: 100%; }
        .modal-wide { max-width: 900px; max-height: 90vh; overflow: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input { width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h1>Dashboard</h1>
            <nav>
                <ul class="nav-menu">
                    <li><a href="backend_8sp/index.php">🏠 Home</a></li>
                    <li><a href="backend_8sp/manage_members.php">👥 Members</a></li>
                    <li><a href="manage_products.php" class="active">📦 Products & Prices</a></li>
                    <li><a href="transaction_reports.php">📊 Transaction Reports</a></li>
                    <li><a href="backend_8sp/logout.php">🚪 Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <div class="header">
                <h1>Manage Products</h1>
            </div>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="section">
                <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px">
                    <button id="btnAdd" class="btn btn-success">➕ Add Product</button>
                    <button id="btnEdit" class="btn btn-primary">✎ Edit / Delete</button>
                </div>

                <h2 style="margin-bottom: 15px;">Products List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Image</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo (int)$p['productID']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td>RM <?php echo number_format((float)$p['price'],2); ?></td>
                            <td><?php echo htmlspecialchars($p['category']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($p['imagePath']); ?>" alt="" style="height:40px;object-fit:cover" onerror="this.src='https://placehold.co/80x40?text=No+Image'"></td>
                            <td><?php echo (int)$p['stockQuantity']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="modalAdd" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:999">
        <div style="background:#fff;padding:20px;border-radius:8px;max-width:600px;width:100%">
            <h3>Add Product</h3>
            <form method="post" id="formAdd" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div style="display:grid;gap:8px">
                    <label>Name: <input name="name" required></label>
                    <label>Price: <input name="price" type="number" step="0.01" required value="0.00"></label>
                    <label>Category: <input name="category"></label>
                    <label>Image: <input type="file" name="imageFile" accept="image/*"></label>
                    <label>Stock: <input name="stock" type="number" value="0"></label>
                </div>
                <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn" id="addCancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Products Modal -->
    <div id="modalEdit" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:999">
        <div style="background:#fff;padding:20px;border-radius:8px;max-width:900px;width:100%;max-height:90vh;overflow:auto">
            <h3>Edit / Delete Products</h3>
            <div style="margin-bottom:12px">
                <input type="text" id="searchProd" placeholder="Search products..." style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px">
            </div>

            <table id="editTable" style="width:100%;border-collapse:collapse">
                <thead><tr><th style="padding:8px;border-bottom:1px solid #eee">ID</th><th style="padding:8px;border-bottom:1px solid #eee">Name</th><th style="padding:8px;border-bottom:1px solid #eee">Price</th><th style="padding:8px;border-bottom:1px solid #eee">Stock</th><th style="padding:8px;border-bottom:1px solid #eee">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>">
                        <td style="padding:8px;border-bottom:1px solid #f2f4f6"><?php echo (int)$p['productID']; ?></td>
                        <td style="padding:8px;border-bottom:1px solid #f2f4f6"><?php echo htmlspecialchars($p['name']); ?></td>
                        <td style="padding:8px;border-bottom:1px solid #f2f4f6">RM <?php echo number_format((float)$p['price'],2); ?></td>
                        <td style="padding:8px;border-bottom:1px solid #f2f4f6"><?php echo (int)$p['stockQuantity']; ?></td>
                        <td style="padding:8px;border-bottom:1px solid #f2f4f6">
                            <button class="btn editItem" data-id="<?php echo (int)$p['productID']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo htmlspecialchars($p['price']); ?>" data-category="<?php echo htmlspecialchars($p['category']); ?>" data-image="<?php echo htmlspecialchars($p['imagePath']); ?>" data-stock="<?php echo (int)$p['stockQuantity']; ?>">Edit</button>
                            &nbsp;
                            <button class="btn" onclick="if(confirm('Delete this product?')){window.location='manage_products.php?delete=<?php echo (int)$p['productID']; ?>'}">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div id="editFormWrap" style="margin-top:16px;display:none;border-top:1px solid #eee;padding-top:12px">
                <h4>Edit Product</h4>
                <form method="post" id="formEdit" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="productID" id="edit_productID">
                    <div style="display:grid;gap:8px">
                        <label>Name: <input name="name" id="edit_name" required></label>
                        <label>Price: <input name="price" id="edit_price" type="number" step="0.01" required></label>
                        <label>Category: <input name="category" id="edit_category"></label>
                        <label>Current Image: <input name="imagePath" id="edit_imagePath" readonly></label>
                        <label>Change Image: <input type="file" name="imageFile" accept="image/*"></label>
                        <label>Stock: <input name="stock" id="edit_stock" type="number"></label>
                    </div>
                    <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
                        <button type="button" class="btn" id="editCancel">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <div style="margin-top:12px;text-align:right">
                <button class="btn" id="editDone">Done</button>
            </div>
        </div>
    </div>

    <script>
        // Simple modal handlers
        const modalAdd = document.getElementById('modalAdd');
        const modalEdit = document.getElementById('modalEdit');
        document.getElementById('btnAdd').addEventListener('click', ()=> modalAdd.style.display='flex');
        document.getElementById('btnEdit').addEventListener('click', ()=> modalEdit.style.display='flex');
        document.getElementById('addCancel').addEventListener('click', ()=> modalAdd.style.display='none');
        document.getElementById('editDone').addEventListener('click', ()=> modalEdit.style.display='none');
        document.getElementById('editCancel').addEventListener('click', ()=> {
            document.getElementById('editFormWrap').style.display='none';
        });

        // Populate edit form when clicking Edit on a product row
        document.querySelectorAll('.editItem').forEach(btn=>{
            btn.addEventListener('click', ()=>{
                const id = btn.dataset.id;
                document.getElementById('edit_productID').value = id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_price').value = btn.dataset.price;
                document.getElementById('edit_category').value = btn.dataset.category;
                document.getElementById('edit_imagePath').value = btn.dataset.image;
                document.getElementById('edit_stock').value = btn.dataset.stock;
                document.getElementById('editFormWrap').style.display='block';
                window.scrollTo({top: document.getElementById('editFormWrap').offsetTop-20, behavior:'smooth'});
            });
        });

        // Simple search/filter in edit modal
        document.getElementById('searchProd').addEventListener('input', function(){
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#editTable tbody tr').forEach(tr=>{
                const name = tr.getAttribute('data-name')||'';
                tr.style.display = name.indexOf(q) === -1 ? 'none' : '';
            });
        });
    </script>

</body>
</html>
