<?php
require_once 'db.php';
require_once 'auth.php';
require_login();

$msg = '';
if (isset($_GET['ok'])) $msg = "✅ Reading & Bill created successfully!";
if (isset($_GET['err'])) $msg = "❌ Error saving reading. Check inputs.";

/* LOAD METERS */
$meters = $mysqli->query("
    SELECT m.id, m.meter_number, 
           c.customer_code, c.full_name,
           u.id AS utility_id, u.name AS utility_name
    FROM meters m
    JOIN customers c ON c.id = m.customer_id
    JOIN utilities u ON u.id = m.utility_id
    ORDER BY c.full_name, m.meter_number
");

/* RECENT READINGS */
$list = $mysqli->query("
    SELECT r.*, m.meter_number, c.customer_code, c.full_name,
           u.name AS utility_name
    FROM meter_readings r
    JOIN meters m ON m.id = r.meter_id
    JOIN customers c ON c.id = m.customer_id
    JOIN utilities u ON u.id = m.utility_id
    ORDER BY r.reading_date DESC, r.id DESC
    LIMIT 50
");

include 'header.php';
?>

<div class="page-header">
    <h2>
        <i class="bi bi-activity"></i>
        <span>Meter Readings & Bill Generation</span>
    </h2>
</div>

<?php if ($msg): ?>
<div class="alert alert-info fw-bold"><?= $msg ?></div>
<?php endif; ?>

<div class="row">

<!-- ====================== LEFT SIDE (FORM) ====================== -->
<div class="col-md-5">
    <div class="card-glass">
        <div class="card-header-custom">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Add New Reading</span>
        </div>

        <div class="card-body-custom">

            <form method="post" action="reading_adv/save_reading.php" id="readingForm">

                <!-- METER -->
                <label class="form-label fw-bold">Meter</label>
                <select name="meter_id" id="meterSelect" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php while ($m = $meters->fetch_assoc()): ?>
                    <option value="<?= $m['id'] ?>" data-util="<?= $m['utility_id'] ?>">
                        <?= $m['meter_number'] ?> - <?= $m['full_name'] ?> (<?= $m['utility_name'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>

                <!-- DATE -->
                <label class="form-label mt-2 fw-bold">Reading Date</label>
                <input type="date" name="reading_date" value="<?= date('Y-m-d') ?>" class="form-control" required>

                <!-- PREVIOUS -->
                <label class="form-label mt-2 fw-bold">Previous Reading</label>
                <input type="number" step="0.01" name="previous_reading" id="prevReading" class="form-control" readonly>

                <!-- CURRENT -->
                <label class="form-label mt-2 fw-bold">Current Reading</label>
                <input type="number" step="0.01" name="current_reading" id="currReading" class="form-control" required>

                <!-- UNITS -->
                <label class="form-label mt-2 fw-bold">Units Used</label>
                <input type="text" id="unitsField" class="form-control" readonly>

                <!-- TARIFF -->
                <label class="form-label mt-2 fw-bold">Tariff</label>
                <select name="tariff_id" id="tariffSelect" class="form-select" required>
                    <option value="">-- Select Tariff --</option>
                </select>

                <!-- DUE DAYS -->
                <label class="form-label mt-2 fw-bold">Due Days</label>
                <input type="number" name="due_days" value="14" class="form-control">

                <!-- SUBMIT -->
                <button class="btn btn-success mt-3 w-100 btn-submit">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Save & Generate Bill</span>
                </button>

            </form>

        </div>
    </div>
</div>

<!-- ====================== RIGHT SIDE (TABLE) ====================== -->
<div class="col-md-7">
    <div class="card-glass">
        <div class="card-header-custom" style="background: #212529;">
            <i class="bi bi-clock-history"></i>
            <span>Recent Readings</span>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Meter</th>
                        <th>Customer</th>
                        <th>Prev</th>
                        <th>Curr</th>
                        <th>Units</th>
                        <th>Month</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($r = $list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['reading_date'] ?></td>
                        <td><strong><?= $r['meter_number'] ?></strong></td>
                        <td><?= $r['customer_code'] ?> - <?= $r['full_name'] ?></td>
                        <td><?= $r['previous_reading'] ?></td>
                        <td><?= $r['current_reading'] ?></td>
                        <td><span class="badge bg-info text-dark"><?= $r['units_used'] ?></span></td>
                        <td><?= $r['billing_year'] ?>-<?= $r['billing_month'] ?></td>

                        <td class="text-center">
                            <!-- VIEW -->
                            <a href="reading_adv/view.php?id=<?= $r['id'] ?>"
                               class="btn btn-sm btn-info text-white btn-action" title="View Reading Details">
                               <i class="bi bi-eye-fill"></i>
                            </a>

                            <!-- EDIT -->
                            <button class="btn btn-sm btn-warning text-white editBtn btn-action"
                                    data-id="<?= $r['id'] ?>" title="Edit Reading">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <!-- DELETE -->
                            <button class="btn btn-sm btn-danger deleteBtn btn-action"
                                    data-id="<?= $r['id'] ?>" title="Delete Reading">
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

<!-- MODALS -->
<?php include 'reading_adv/edit_modal.php'; ?>
<?php include 'reading_adv/delete_modal.php'; ?>

<script>
// ============= LOAD LAST READING + TARIFFS =============
document.getElementById("meterSelect").addEventListener("change", async function() {
    let id = this.value;
    if (!id) return;

    let util = this.selectedOptions[0].dataset.util;

    // last reading
    let res = await fetch("reading_adv/get_last_reading.php?meter_id=" + id);
    let js = await res.json();
    document.getElementById("prevReading").value = js.previous ?? 0;

    // load tariffs
    let t = await fetch("reading_adv/get_tariffs.php?utility_id=" + util);
    let tariffs = await t.json();

    let sel = document.getElementById("tariffSelect");
    sel.innerHTML = "<option value=''>-- Select Tariff --</option>";

    tariffs.forEach(tr => {
        sel.innerHTML += `<option value="${tr.id}">${tr.tariff_name}</option>`;
    });
});

// ============= AUTO UNITS =============
document.getElementById("currReading").addEventListener("input", function() {
    let prev = parseFloat(document.getElementById("prevReading").value || 0);
    let curr = parseFloat(this.value || 0);
    let diff = curr - prev;

    document.getElementById("unitsField").value = diff >= 0 ? diff.toFixed(2) : "ERR";
});

// ============= EDIT BUTTON =============
document.addEventListener("click", async function(e) {
    if (e.target.closest(".editBtn")) {
        let id = e.target.closest(".editBtn").dataset.id;

        let res = await fetch("reading_adv/get_reading.php?id=" + id);
        let data = await res.json();

        document.getElementById("edit_id").value = data.id;
        document.getElementById("edit_prev").value = data.previous_reading;
        document.getElementById("edit_curr").value = data.current_reading;

        new bootstrap.Modal(document.getElementById("editReadingModal")).show();
    }
});

// ============= DELETE BUTTON =============
document.addEventListener("click", function(e) {
    if (e.target.closest(".deleteBtn")) {
        let id = e.target.closest(".deleteBtn").dataset.id;
        document.getElementById("delete_id").value = id;
        new bootstrap.Modal(document.getElementById("deleteReadingModal")).show();
    }
});

// ============= DELETE CONFIRM =============
document.getElementById("confirmDeleteBtn").addEventListener("click", async function () {

    let id = document.getElementById("delete_id").value;

    let res = await fetch("reading_adv/delete_reading.php?id=" + id);
    let js = await res.json();

    if (js.status === "success") {
        location.reload();
    } else {
        alert("Delete failed: " + js.message);
    }
});

</script>

<?php include 'footer.php'; ?>
