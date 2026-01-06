<?php
require_once 'db.php';
require_once 'auth.php';
require_login();

$search       = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$util_filter   = (int)($_GET['utility'] ?? 0);

// load utilities for filter
$utilsRes = $mysqli->query("SELECT id, name FROM utilities ORDER BY name");

$sql = "
    SELECT m.*,
           c.full_name AS customer_name,
           u.name      AS utility_name
    FROM meters m
    JOIN customers c ON c.id = m.customer_id
    JOIN utilities u ON u.id = m.utility_id
    WHERE 1
";

// search by meter no or customer name
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $sql .= " AND (m.meter_number LIKE '%$s%' OR c.full_name LIKE '%$s%')";
}

// filter by status
if ($status_filter !== '') {
    $st = $mysqli->real_escape_string($status_filter);
    $sql .= " AND m.status = '$st'";
}

// filter by utility
if ($util_filter > 0) {
    $sql .= " AND m.utility_id = $util_filter";
}

$sql .= " ORDER BY m.id DESC";

$meters = $mysqli->query($sql);

include 'header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

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
    padding: 28px;
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
}
.action-btn {
    border: none;
    background: none;
    padding: 6px 10px;
    cursor: pointer;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 8px;
}
.action-btn i { 
    font-size: 1.3rem;
    transition: all 0.35s ease;
}
.action-view { color: #0d6efd; }
.action-edit { color: #ffc107; }
.action-delete { color: #dc3545; }
.action-btn:hover {
    transform: translateY(-3px) scale(1.15);
}
.action-view:hover { background: rgba(13, 110, 253, 0.1); }
.action-edit:hover { background: rgba(255, 193, 7, 0.1); }
.action-delete:hover { background: rgba(220, 53, 69, 0.1); }
.btn-add {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(25, 135, 84, 0.3);
}
.btn-search {
    border-radius: 12px;
    padding: 10px 18px;
    font-weight: 600;
}
.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid var(--card-border);
    padding: 10px 16px;
    background: var(--card-bg);
    color: var(--card-text);
}
</style>

<div class="page-header">
    <h2>
        <i class="bi bi-speedometer"></i>
        <span>Meters Management</span>
    </h2>
</div>

<div class="card-glass" style="max-width: 1300px; margin:auto;">

    <div class="d-flex justify-content-between mb-3">

        <!-- search + filters -->
        <form class="d-flex flex-wrap gap-2" method="get">

            <input type="text"
                   class="form-control"
                   style="min-width:220px"
                   name="search"
                   placeholder="Search by meter / customer..."
                   value="<?= htmlspecialchars($search) ?>">

            <select name="utility" class="form-select" style="min-width:180px">
                <option value="0">All Utilities</option>
                <?php while($u = $utilsRes->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>" <?= $util_filter==$u['id']?'selected':'' ?>>
                        <?= htmlspecialchars($u['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="status" class="form-select" style="min-width:150px">
                <option value="">All Status</option>
                <option value="Active"   <?= $status_filter=='Active'?'selected':'' ?>>Active</option>
                <option value="Inactive" <?= $status_filter=='Inactive'?'selected':'' ?>>Inactive</option>
            </select>

            <button class="btn btn-dark btn-search">
                <i class="bi bi-search me-1"></i>Search
            </button>
        </form>

        <!-- add meter -->
        <a href="meter_adv/add.php" class="btn btn-success btn-add">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Add Meter</span>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Meter No</th>
                    <th>Customer</th>
                    <th>Utility</th>
                    <th>Install Date</th>
                    <th>Status</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = $meters->fetch_assoc()): ?>
                <tr id="mrow_<?= $m['id'] ?>">

                    <td><span class="badge bg-secondary"><?= htmlspecialchars($m['meter_number']) ?></span></td>

                    <td><strong><?= htmlspecialchars($m['customer_name']) ?></strong></td>

                    <td>
                        <?php
                        $uname = $m['utility_name'];
                        if (stripos($uname,'Water') !== false) {
                            $cls = 'bg-info text-dark';
                        } elseif (stripos($uname,'Gas') !== false) {
                            $cls = 'bg-warning text-dark';
                        } else { // Electricity or other
                            $cls = 'bg-primary';
                        }
                        ?>
                        <span class="badge <?= $cls ?>">
                            <?= htmlspecialchars($uname) ?>
                        </span>
                    </td>

                    <td><?= htmlspecialchars($m['install_date']) ?></td>

                    <td>
                        <?php if ($m['status']=='Active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-center">

                        <!-- view -->
                        <a href="meter_adv/view.php?id=<?= $m['id'] ?>"
                           class="action-btn action-view" title="View Meter Details">
                            <i class="bi bi-eye-fill"></i>
                        </a>

                        <!-- edit -->
                        <button class="action-btn action-edit mEditBtn"
                                data-id="<?= $m['id'] ?>" title="Edit Meter">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <!-- delete -->
                        <button class="action-btn action-delete mDeleteBtn"
                                data-id="<?= $m['id'] ?>" title="Delete Meter">
                            <i class="bi bi-trash-fill"></i>
                        </button>

                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include 'meter_adv/edit_modal.php'; ?>

<script src="meter_adv/js_handlers.js"></script>

<?php include 'footer.php'; ?>
