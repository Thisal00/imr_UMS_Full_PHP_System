// ============= GENERATE BILL PDF =============
$pdfFile = "generated/bill_$bill_id.pdf";

require_once 'vendor/autoload.php';
$mpdf = new \Mpdf\Mpdf();

$html = "
<h2>Utility Bill #$bill_id</h2>
<p><strong>Customer:</strong> {$customer['full_name']}</p>
<p><strong>Month:</strong> {$bill_month}/{$bill_year}</p>
<p><strong>Units Used:</strong> $units</p>
<p><strong>Total Amount:</strong> Rs. ".number_format($total,2)."</p>
";

$mpdf->WriteHTML($html);
$mpdf->Output($pdfFile, \Mpdf\Output\Destination::FILE);

// ================= GET CUSTOMER EMAIL =================
$c = $mysqli->query("SELECT email, full_name FROM customers WHERE id=$customer_id")->fetch_assoc();

// ================= SEND EMAIL =================
require_once 'email_helper.php';
sendBillEmail($c['email'], $c['full_name'], $bill_id, $pdfFile);
