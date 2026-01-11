<?php
require_once '../db.php';
require_once '../auth.php';
require_login();
require_once '../send_bill_email.php';  // EMAIL FUNCTION
require_once '../vendor/autoload.php';  // MPDF

$bill_id     = (int)($_POST['bill_id'] ?? 0);
$paid_amount = (float)($_POST['amount_paid'] ?? 0);
$pay_date    = date("Y-m-d");

// --------------- VALIDATION ----------------
if ($bill_id <= 0 || $paid_amount <= 0) {
    header("Location: ../payments.php?err=1");
    exit;
}

// ---- GET CURRENT BILL DATA ----
$bill = $mysqli->query("
    SELECT b.*, c.full_name, c.email, m.meter_number, u.name AS utility_name
    FROM bills b
    JOIN customers c ON c.id = b.customer_id
    JOIN meters m ON m.id = b.meter_id
    JOIN utilities u ON u.id = m.utility_id
    WHERE b.id = $bill_id
    LIMIT 1
")->fetch_assoc();

if (!$bill) {
    header("Location: ../payments.php?err=2");
    exit;
}

// ---- UPDATE PAYMENT ----

$new_outstanding = $bill['total_amount'] - $paid_amount;

$upd = $mysqli->prepare("
    UPDATE bills SET 
        amount_paid = ?, 
        outstanding = ?, 
        status = IF(? >= total_amount, 'Paid', 'Partially Paid')
    WHERE id = ?
");

$upd->bind_param("dddi", $paid_amount, $new_outstanding, $paid_amount, $bill_id);
$upd->execute();
$upd->close();

// ---------------- GENERATE RECEIPT PDF ----------------

$pdf_file = "../temp/receipt_" . $bill_id . ".pdf";

ob_start();
include "receipt.php";
$html = ob_get_clean();

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output($pdf_file, "F");

// ---------------- SEND EMAIL ----------------

if (!empty($bill['email'])) {

    $subject = "Payment Receipt - Bill #{$bill_id}";
    $body = "
        Hello {$bill['full_name']}, <br><br>
        Your payment has been successfully recorded.<br><br>

        <b>Bill No:</b> {$bill_id}<br>
        <b>Meter:</b> {$bill['meter_number']}<br>
        <b>Utility:</b> {$bill['utility_name']}<br>
        <b>Total Amount:</b> Rs. {$bill['total_amount']}<br>
        <b>Paid Amount:</b> Rs. {$paid_amount}<br>
        <b>Balance:</b> Rs. {$new_outstanding}<br><br>

        Your receipt is attached.<br><br>

        Thank you,<br>
        Utility Management System
    ";

    sendBillEmail($bill['email'], $subject, $body, $pdf_file);
}

// ---------------- REDIRECT ----------------

header("Location: ../payments.php?ok=1");
exit;

?>
