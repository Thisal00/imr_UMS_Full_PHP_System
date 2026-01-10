<?php
require_once '../db.php';
require_once '../auth.php';
require_login();
require_once '../send_mail.php';   

/*READ INPUTS */
$meter_id   = (int)($_POST['meter_id'] ?? 0);
$prev       = (float)($_POST['previous_reading'] ?? 0);
$curr       = (float)($_POST['current_reading'] ?? 0);
$date       = trim($_POST['reading_date'] ?? '');
$tariff_id  = (int)($_POST['tariff_id'] ?? 0);
$due_days   = (int)($_POST['due_days'] ?? 14);

/* BASIC VALIDATION */
if ($meter_id === 0 || $tariff_id === 0 || !$date || $curr < $prev) {
    header("Location: ../readings.php?err=1");
    exit;
}

$bm = (int)date('m', strtotime($date));
$by = (int)date('Y', strtotime($date));
$units = $curr - $prev;

/* INSERT READING */
$stmt = $mysqli->prepare("
    INSERT INTO meter_readings
    (meter_id, reading_date, previous_reading, current_reading, units_used, billing_month, billing_year)
    VALUES (?,?,?,?,?,?,?)
");

$stmt->bind_param("isdddii", $meter_id, $date, $prev, $curr, $units, $bm, $by);

if (!$stmt->execute()) {
    error_log("Reading Insert Error: " . $stmt->error);
    $stmt->close();
    header("Location: ../readings.php?err=2");
    exit;
}

$reading_id = $stmt->insert_id;
$stmt->close();

/* CALL STORED PROCEDURE */
$sp = $mysqli->prepare("CALL sp_generate_bill(?,?,?)");

if (!$sp) {
    error_log("SP ERROR PREPARE: " . $mysqli->error);
    header("Location: ../readings.php?err=3");
    exit;
}

$sp->bind_param("iii", $reading_id, $tariff_id, $due_days);

if (!$sp->execute()) {
    error_log("SP EXEC ERROR: " . $sp->error);
    $sp->close();
    header("Location: ../readings.php?err=4");
    exit;
}

$sp->close();

/* GET BILL DETAILS FOR EMAIL */
$bill = $mysqli->query("
    SELECT b.*, c.full_name, c.email, c.customer_code,
           m.meter_number, u.name AS utility_name
    FROM bills b
    JOIN customers c ON c.id = b.customer_id
    JOIN meters m ON m.id = b.meter_id
    JOIN utilities u ON u.id = m.utility_id
    WHERE b.reading_id = $reading_id
    LIMIT 1
")->fetch_assoc();

if ($bill && !empty($bill['email'])) {

    /* EMAIL TEMPLATE */

    $subject = "Your Utility Bill – Please Pay Before Due Date";

    $body = "
    <div style='font-family: Arial; padding: 20px; color:#333;'>
      <h2 style='color:#0d6efd;'>Utility Bill Notice</h2>

      <p>Dear <strong>{$bill['full_name']}</strong>,</p>

      <p>Your bill for the month <strong>{$bill['billing_year']}-{$bill['billing_month']}</strong> has been generated.</p>

      <table style='width:100%; border-collapse: collapse; margin-top:10px;'>
        <tr>
          <td style='padding:8px; border:1px solid #ccc;'>Customer Code</td>
          <td style='padding:8px; border:1px solid #ccc;'><strong>{$bill['customer_code']}</strong></td>
        </tr>
        <tr>
          <td style='padding:8px; border:1px solid #ccc;'>Utility</td>
          <td style='padding:8px; border:1px solid #ccc;'>{$bill['utility_name']}</td>
        </tr>
        <tr>
          <td style='padding:8px; border:1px solid #ccc;'>Meter No</td>
          <td style='padding:8px; border:1px solid #ccc;'>{$bill['meter_number']}</td>
        </tr>
        <tr>
          <td style='padding:8px; border:1px solid #ccc;'>Units Used</td>
          <td style='padding:8px; border:1px solid #ccc;'>{$bill['units']}</td>
        </tr>
        <tr>
          <td style='padding:8px; border:1px solid #ccc;'>Total Amount</td>
          <td style='padding:8px; border:1px solid #ccc; color:red; font-size:18px;'>
            <strong>Rs. {$bill['total_amount']}</strong>
          </td>
        </tr>
        <tr>
          <td style='padding:8px; border:1px solid #ccc;'>Due Date</td>
          <td style='padding:8px; border:1px solid #ccc; color:#0d6efd;'>
            <strong>{$bill['due_date']}</strong>
          </td>
        </tr>
      </table>

      <br>

      <p style='color:#b30000; font-size:16px;'>
        ⚠ Please pay before the due date to avoid service disconnection.
      </p>

      <p>If you have already paid, kindly ignore this message.</p>

      <p>Thank you,<br>
      <strong>Utility Management System</strong></p>
    </div>
    ";

    // send email
    sendMail($bill['email'], $subject, $body);
}

/* SUCCESS REDIRECT */
header("Location: ../readings.php?ok=1");
exit;

?>
