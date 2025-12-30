<?php
session_start();

// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}

include 'db.php';

// Do not use `use` here; we'll check for the namespaced class at runtime

// Sanitize and validate inputs
$filter = isset($_POST['filter']) ? $_POST['filter'] : 'daily';
$allowed_filters = ['daily', 'monthly', 'all'];
if (!in_array($filter, $allowed_filters)) {
    $filter = 'daily'; // Default to safe value
}

// requested format: 'pdf' or 'csv'
$format = isset($_POST['format']) ? strtolower(trim($_POST['format'])) : 'pdf';
$allowed_formats = ['pdf', 'csv'];
if (!in_array($format, $allowed_formats)) {
    $format = 'pdf'; // Default to safe value
}

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

// If CSV explicitly requested, stream CSV immediately
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transaction_report_' . $filter . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['OrderID','UserID','TotalAmount','OrderDate']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['orderID'], $r['userID'], $r['totalAmount'], $r['orderDate']]);
    }
    fclose($out);
    exit;
}

// Try to load Dompdf from common locations
$possible = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/vendor/autoload.inc.php',
    __DIR__ . '/dompdf/autoload.inc.php',
    __DIR__ . '/dompdf/dompdf/autoload.inc.php',
    __DIR__ . '/dompdf/src/autoload.php'
];
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        break;
    }
}

// If Dompdf is available, generate PDF
if (class_exists('\\Dompdf\\Dompdf') || class_exists('Dompdf\\Dompdf')) {
    $dompdf = new \Dompdf\Dompdf();
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

// Fallback: output CSV
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
