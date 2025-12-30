<?php
require_once 'db.php';
require_once 'auth.php';
require_login();

$search = trim($_GET['search'] ?? '');
$type_filter = trim($_GET['type'] ?? '');

$query = "SELECT * FROM customers WHERE 1";

// Search
if ($search !== '') {
    $safe = $mysqli->real_escape_string($search);
    $query .= " AND (customer_code LIKE '%$safe%' 
                  OR full_name LIKE '%$safe%' 
                  OR phone LIKE '%$safe%' 
                  OR email LIKE '%$safe%')";
}

// Type filter
if ($type_filter !== '') {
    $safeType = $mysqli->real_escape_string($type_filter);
    $query .= " AND type='$safeType'";
}

$query .= " ORDER BY id DESC";
$res = $mysqli->query($query);

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
.action-history { color: #198754; font-weight: bold; }
.action-btn:hover {
    transform: translateY(-3px) scale(1.15);
}
.action-view:hover { background: rgba(13, 110, 253, 0.1); }
.action-edit:hover { background: rgba(255, 193, 7, 0.1); }
.action-delete:hover { background: rgba(220, 53, 69, 0.1); }
.action-history:hover { background: rgba(25, 135, 84, 0.1); color: #0f5132; }
.table {
    margin-bottom: 0;
}
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
.form-control:focus, .form-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
</style>

<div class="page-header">
    <h2>
        <i class="bi bi-people-fill"></i>
        <span>Customers Management</span>
    </h2>
</div>

<div class="card-glass" style="max-width: 1300px; margin: auto;">
    
    <!-- Filters Section -->
    <div class="d-flex justify-content-between mb-3">

        <!-- Search -->
        <form class="d-flex" method="get">
            <input type="text" class="form-control me-2" name="search"
                   placeholder="Search customer..."
                   value="<?= htmlspecialchars($search) ?>">

            <select name="type" class="form-select me-2">
                <option value="">All Types</option>
                <option <?= $type_filter=='Household'?'selected':'' ?>>Household</option>
                <option <?= $type_filter=='Business'?'selected':'' ?>>Business</option>
                <option <?= $type_filter=='Government'?'selected':'' ?>>Government</option>
            </select>

            <button class="btn btn-dark btn-search">
                <i class="bi bi-search me-1"></i>Search
            </button>
        </form>

        <!-- Add Customer -->
        <a href="customer_adv/add.php" class="btn btn-success btn-add">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Add Customer</span>
        </a>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                <tr id="row_<?= $row['id'] ?>">
                    <td><span class="badge bg-secondary"><?= $row['customer_code'] ?></span></td>

                    <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>

                    <td>
                        <?php if ($row['type'] == 'Household'): ?>
                            <span class="badge bg-primary">Household</span>
                        <?php elseif ($row['type'] == 'Business'): ?>
                            <span class="badge bg-warning text-dark">Business</span>
                        <?php else: ?>
                            <span class="badge bg-success">Government</span>
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>

                    <td class="text-center">

                        <!-- View -->
                        <a href="customer_adv/view.php?id=<?= $row['id'] ?>"
                           class="action-btn action-view" title="View Details">
                           <i class="bi bi-eye-fill"></i>
                        </a>

                        <!-- Edit -->
                        <button class="action-btn action-edit editBtn"
                                data-id="<?= $row['id'] ?>" title="Edit Customer">
                                <i class="bi bi-pencil-square"></i>
                        </button>

                        <!-- Delete -->
                        <button class="action-btn action-delete deleteBtn"
                                data-id="<?= $row['id'] ?>" title="Delete Customer">
                                <i class="bi bi-trash-fill"></i>
                        </button>

                        <!-- History -->
                        <a href="customer_adv/history.php?id=<?= $row['id'] ?>"
                           class="action-btn action-history" title="View History">
                           <i class="bi bi-clock-history"></i>
                        </a>

                        <!-- Download PDF -->
                        <a href="customer_adv/download_history.php?id=<?= $row['id'] ?>"
                           class="action-btn text-danger" title="Download PDF Report">
                           <i class="bi bi-file-earmark-pdf-fill"></i>
                        </a>

                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<?php include 'customer_adv/edit_modal.php'; ?>

<script src="customer_adv/js_handlers.js"></script>
<?php include 'footer.php'; ?>
