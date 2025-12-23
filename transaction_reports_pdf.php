<?php
session_start();
// Admin-only and super-admin check
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}
$isSuperAdmin = isset($_SESSION['isSuperAdmin']) && $_SESSION['isSuperAdmin'];
if (!$isSuperAdmin) {
    echo "Permission denied: only super admins can export reports.";
    exit();
}
include 'db.php';

$filter = isset($_POST['filter']) ? $_POST['filter'] : 'daily';

if ($filter === 'daily') {
    $sql = "SELECT orderID, userID, totalAmount, orderDate FROM orders WHERE DATE(orderDate) = CURDATE() ORDER BY orderDate DESC";
} elseif ($filter === 'monthly') {
    $sql = "SELECT orderID, userID, totalAmount, orderDate FROM orders WHERE MONTH(orderDate)=MONTH(CURDATE()) AND YEAR(orderDate)=YEAR(CURDATE()) ORDER BY orderDate DESC";
} else {
    $sql = "SELECT orderID, userID, totalAmount, orderDate FROM orders ORDER BY orderDate DESC";
}

$rows = [];
$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
}

// Try to generate PDF using Dompdf if available
// Look for autoload in common locations (composer vendor or bundled dompdf)
$possible = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.inc.php',
    __DIR__ . '/dompdf/autoload.inc.php',
    __DIR__ . '/dompdf/dompdf/autoload.inc.php',
    __DIR__ . '/dompdf/src/autoload.php'
];
$loaded = false;
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        $loaded = true;
        break;
    }
}
if ($loaded && class_exists('\Dompdf\\Dompdf')) {
    $dompdf = new \Dompdf\\Dompdf();
    $html = '<h1>Transaction Report</h1>';
    $html .= '<p>Filter: ' . htmlspecialchars($filter) . '</p>';
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">';
    $html .= '<thead><tr><th>Order ID</th><th>User ID</th><th>Total</th><th>Order Date</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $html .= '<tr><td>' . intval($r['orderID']) . '</td><td>' . intval($r['userID']) . '</td><td>RM ' . number_format((float)$r['totalAmount'],2) . '</td><td>' . htmlspecialchars($r['orderDate']) . '</td></tr>';
    }
    $html .= '</tbody></table>';
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('transaction_report_' . $filter . '.pdf', array('Attachment' => 1));
    exit;
}
// Fallback: output CSV download
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">';
        $html .= '<thead><tr><th>Order ID</th><th>User ID</th><th>Total</th><th>Order Date</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . intval($r['orderID']) . '</td><td>' . intval($r['userID']) . '</td><td>RM ' . number_format((float)$r['totalAmount'],2) . '</td><td>' . htmlspecialchars($r['orderDate']) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('transaction_report_' . $filter . '.pdf', array('Attachment' => 1));
        exit;
    }
}

// Fallback: output CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transaction_report_' . $filter . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['OrderID','UserID','TotalAmount','OrderDate']);
foreach ($rows as $r) {
    fputcsv($out, [$r['orderID'], $r['userID'], $r['totalAmount'], $r['orderDate']]);
}
fclose($out);
exit;
?>
