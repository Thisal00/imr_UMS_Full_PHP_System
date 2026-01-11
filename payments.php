<?php
require_once 'db.php';
require_once 'auth.php';
require_login();
require_once 'send_mail.php'; // email sender

$msg = '';

<?php
require_once 'db.php';
require_once 'auth.php';
require_login();
require_once 'send_mail.php'; // email sender

$msg = '';

/* LOAD OUTSTANDING BILLS */
$bills = $mysqli->query("
    SELECT b.id, c.customer_code, c.full_name, 
           b.billing_year, b.billing_month, 
           b.total_amount, b.amount_paid, b.outstanding
    FROM bills b
    JOIN customers c ON c.id = b.customer_id
    WHERE b.outstanding > 0
    ORDER BY b.billing_year DESC, b.billing_month DESC
");

/* SAVE PAYMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bill_id = (int)($_POST['bill_id'] ?? 0);
    $amount  = (float)($_POST['amount'] ?? 0);
    $date    = trim($_POST['payment_date'] ?? '');
    $method  = trim($_POST['method'] ?? 'Cash');
    $ref     = trim($_POST['reference_no'] ?? '');

    if ($bill_id && $amount > 0 && $date) {

        $stmt = $mysqli->prepare("
            INSERT INTO payments 
            (bill_id, payment_date, amount, method, reference_no) 
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("isdss", $bill_id, $date, $amount, $method, $ref);

        if ($stmt->execute()) {

            /* UPDATE TOTAL PAID + OUTSTANDING */
            $mysqli->query("
                UPDATE bills b
                SET amount_paid = (
                    SELECT COALESCE(SUM(p.amount), 0)
                    FROM payments p
                    WHERE p.bill_id = b.id
                ),
                outstanding = GREATEST(
                    b.total_amount - (
                        SELECT COALESCE(SUM(p.amount), 0)
                        FROM payments p
                        WHERE p.bill_id = b.id
                    ), 
                0)
                WHERE b.id = $bill_id
            ");

            /* GET BILL + CUSTOMER FOR EMAIL */
            $bill = $mysqli->query("
                SELECT b.*, c.full_name, c.email
                FROM bills b
                JOIN customers c ON c.id = b.customer_id
                WHERE b.id = $bill_id
                LIMIT 1
            ")->fetch_assoc();

            /*  UPDATE BILL STATUS */
            $status = 'Pending';
            if ($bill['amount_paid'] >= $bill['total_amount']) $status = 'Paid';
            elseif ($bill['amount_paid'] > 0) $status = 'Partially Paid';

            $today = date('Y-m-d');
            if ($status !== 'Paid' && $today > $bill['due_date']) $status = 'Overdue';

            $mysqli->query("UPDATE bills SET status='$status' WHERE id=$bill_id");

            /*  SEND EMAIL*/
            if (!empty($bill['email'])) {

                $subject = "Payment Confirmation - Bill #{$bill['id']}";

                $body = "
                Hello {$bill['full_name']},<br><br>

                Your payment has been successfully received.<br><br>

                <b>Bill No:</b> {$bill['id']}<br>
                <b>Amount Paid:</b> Rs. " . number_format($amount,2) . "<br>
                <b>Payment Date:</b> {$date}<br>
                <b>Current Bill Status:</b> {$status}<br><br>

                Thank you for your payment.<br>
                Utility Management System
                ";

                sendMail($bill['email'], $subject, $body);
            }

            $msg = "Payment recorded successfully ✔️ Email sent!";

        } else {
            $msg = "Error: " . $mysqli->error;
        }

        $stmt->close();
    } else {
        $msg = "⚠️ Please fill all fields correctly.";
    }
}

/* RECENT PAYMENTS*/
$plist = $mysqli->query("
    SELECT p.*, c.customer_code, c.full_name
    FROM payments p
    JOIN bills b ON b.id = p.bill_id
    JOIN customers c ON c.id = b.customer_id
    ORDER BY p.payment_date DESC, p.id DESC
    LIMIT 100
");

// Generate next reference number
$lastPayment = $mysqli->query("SELECT reference_no FROM payments WHERE reference_no LIKE 'REF-%' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$nextRefNum = 1;
if ($lastPayment && preg_match('/\\d+$/', $lastPayment['reference_no'], $matches)) {
    $nextRefNum = intval($matches[0]) + 1;
}
$generatedRefNo = 'REF-' . date('Y-m-d') . '-' . str_pad($nextRefNum, 4, '0', STR_PAD_LEFT);

include 'header.php';
?>

<style>
.page-header {
    margin-bottom: 2rem;
    padding: 1.5rem 0;
}
.page-header h2 {
    color: var(--card-text);
    font-size: 2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}
.page-header h2 i {
    color: var(--accent);
    font-size: 2.25rem;
}
.card-glass {
    border-radius: 20px;
    background: var(--card-bg);
    backdrop-filter: blur(25px) saturate(150%);
    border: 1px solid var(--card-border);
    box-shadow: var(--card-shadow);
    padding: 0;
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    margin-bottom: 2rem;
}
.card-header-custom {
    background: var(--accent);
    color: #fff;
    padding: 18px 24px;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-body-custom {
    padding: 28px;
}
.btn-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(25, 135, 84, 0.3);
}
.btn-action {
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-action:hover {
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}
.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid var(--card-border);
    padding: 10px 16px;
    background: var(--card-bg);
    color: var(--card-text);
}
.form-label {
    color: var(--card-text);
    font-weight: 700;
}
</style>

<div class="page-header">
    <h2>
        <i class="bi bi-cash-coin"></i>
        <span>Payments Management</span>
    </h2>
</div>

<?php if ($msg): ?>
<div class="alert alert-success fw-bold"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="row">

<!-- ADD PAYMENT -->
<div class="col-md-5">
    <div class="card-glass">
      <div class="card-header-custom">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Add Payment</span>
      </div>

      <div class="card-body-custom">

        <form method="post" class="row g-3">

          <div class="col-12">
            <label class="form-label fw-bold">Select Bill</label>
            <select name="bill_id" class="form-select" required>
              <option value="">-- Select --</option>
              <?php while ($b = $bills->fetch_assoc()): ?>
              <option value="<?= $b['id'] ?>">
                #<?= $b['id'] ?> • <?= $b['customer_code'] ?> - <?= $b['full_name'] ?> 
                (<?= $b['billing_year'].'-'.$b['billing_month'] ?> • 
                 Due: <?= number_format($b['outstanding'],2) ?>)
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Method</label>
            <select name="method" class="form-select">
              <option>Cash</option>
              <option>Card</option>
              <option>Online</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold"><i class="bi bi-receipt me-2"></i>Reference No (Auto)</label>
            <div class="input-group">
              <input type="text" name="reference_no" class="form-control" value="<?= htmlspecialchars($generatedRefNo) ?>" style="background: rgba(56,189,248,0.1); font-weight: 700; letter-spacing: 1px;">
              <button type="button" class="btn btn-primary" onclick="document.querySelector('[name=reference_no]').value='<?= $generatedRefNo ?>'">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset
              </button>
            </div>
          </div>

          <div class="col-12">
            <button class="btn btn-success w-100 btn-submit">
              <i class="bi bi-check-circle-fill"></i>
              <span>Save Payment</span>
            </button>
          </div>

        </form>

      </div>
    </div>
</div>

<!-- RIGHT: RECENT PAYMENTS LIST -->
<div class="col-md-7">
    <div class="card-glass">
      <div class="card-header-custom" style="background: #212529;">
        <i class="bi bi-clock-history"></i>
        <span>Recent Payments</span>
      </div>

      <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0 align-middle small">
            <thead class="table-dark">
              <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Ref No</th>
                <th width="170">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($p = $plist->fetch_assoc()): ?>
              <tr>
                <td><span class="badge bg-secondary"><?= $p['payment_date'] ?></span></td>
                <td><?= $p['customer_code'].' - '.$p['full_name'] ?></td>
                <td class="fw-bold text-success">Rs. <?= number_format($p['amount'],2) ?></td>
                <td><span class="badge bg-info text-dark"><?= $p['method'] ?></span></td>
                <td><?= $p['reference_no'] ?></td>

                <td class="text-center">

                  <a href="payment_adv/view.php?id=<?= $p['id'] ?>" 
                     class="btn btn-sm btn-primary btn-action" title="View Payment Details">
                    <i class="bi bi-eye-fill"></i>
                  </a>

                  <a href="payment_adv/edit.php?id=<?= $p['id'] ?>" 
                     class="btn btn-sm btn-warning text-white btn-action" title="Edit Payment">
                    <i class="bi bi-pencil-square"></i>
                  </a>

                  <a href="payment_adv/receipt.php?id=<?= $p['id'] ?>" 
                     class="btn btn-sm btn-secondary btn-action" title="Download Receipt">
                    <i class="bi bi-file-pdf-fill"></i>
                  </a>

                  <button class="btn btn-sm btn-danger paymentDeleteBtn btn-action"
                          data-id="<?= $p['id'] ?>" title="Delete Payment">
                    <i class="bi bi-trash-fill"></i>
                  </button>

                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
      </div>
    </div>
</div>

</div>

<script src="payment_adv/js_handlers.js"></script>

<?php include 'footer.php'; ?>

