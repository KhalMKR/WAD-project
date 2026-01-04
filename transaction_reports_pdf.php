<?php
session_start();

// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}

include 'includes/db.php';

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
    $sql = "SELECT orderID, orderNumber, userID, fullName, phone, address, paymentMethod, totalAmount, orderDate FROM orders WHERE DATE(orderDate) = CURDATE() ORDER BY orderDate DESC";
} elseif ($filter === 'monthly') {
    $sql = "SELECT orderID, orderNumber, userID, fullName, phone, address, paymentMethod, totalAmount, orderDate FROM orders WHERE MONTH(orderDate)=MONTH(CURDATE()) AND YEAR(orderDate)=YEAR(CURDATE()) ORDER BY orderDate DESC";
} else {
    $sql = "SELECT orderID, orderNumber, userID, fullName, phone, address, paymentMethod, totalAmount, orderDate FROM orders ORDER BY orderDate DESC";
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
    fputcsv($out, ['OrderNumber','CustomerName','Phone','Address','PaymentMethod','TotalAmount','OrderDate']);
    foreach ($rows as $r) {
        $orderNum = $r['orderNumber'] ?? 'UMH-' . str_pad($r['orderID'], 6, '0', STR_PAD_LEFT);
        fputcsv($out, [$orderNum, $r['fullName'], $r['phone'], $r['address'], $r['paymentMethod'], $r['totalAmount'], $r['orderDate']]);
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
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:10px;">';
    $html .= '<thead><tr><th>Order Number</th><th>Customer</th><th>Phone</th><th>Address</th><th>Payment</th><th>Total</th><th>Date</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $orderNum = $r['orderNumber'] ?? 'UMH-' . str_pad($r['orderID'], 6, '0', STR_PAD_LEFT);
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($orderNum) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['fullName'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($r['phone'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($r['address'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($r['paymentMethod'] ?? 'N/A') . '</td>';
        $html .= '<td>RM ' . number_format((float)$r['totalAmount'],2) . '</td>';
        $html .= '<td>' . htmlspecialchars($r['orderDate']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('transaction_report_' . $filter . '.pdf', array('Attachment' => 1));
    exit;
}

// Fallback: output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transaction_report_' . $filter . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['OrderNumber','CustomerName','Phone','Address','PaymentMethod','TotalAmount','OrderDate']);
foreach ($rows as $r) {
    $orderNum = $r['orderNumber'] ?? 'UMH-' . str_pad($r['orderID'], 6, '0', STR_PAD_LEFT);
    fputcsv($out, [$orderNum, $r['fullName'], $r['phone'], $r['address'], $r['paymentMethod'], $r['totalAmount'], $r['orderDate']]);
}
fclose($out);
exit;
?>
