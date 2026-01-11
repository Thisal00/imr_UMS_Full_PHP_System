<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
require 'auth.php';

// Only admin can access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Fetch all tariffs with utility names
$query = "SELECT t.*, u.name as utility_name FROM tariffs t 
          LEFT JOIN utilities u ON t.utility_id = u.id 
          ORDER BY t.created_at DESC";
$result = $mysqli->query($query);
$tariffs = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tariffs[] = $row;
    }
}

// Fetch utilities for filters/modals
$utilities_query = $mysqli->query("SELECT id, name as utility_name FROM utilities ORDER BY name");
$utilities = [];
while ($util = $utilities_query->fetch_assoc()) {
    $utilities[] = $util;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tariffs Management - UMS</title>
    <?php include 'header.php'; ?>
</head>
<body>
    <div class="container-fluid py-4" style="margin-top: 80px;">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="fw-bold" style="font-size: 2.5rem;">
                        <i class="bi bi-tags" style="font-size: 2.25rem; margin-right: 12px;"></i>Tariff Management
                    </h1>
                    <p class="text-muted">Manage utility pricing and tariff rates</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTariffModal" style="border-radius: 12px; font-weight: 600; padding: 12px 24px;">
                    <i class="bi bi-plus-circle me-2"></i>Add Tariff
                </button>
            </div>
        </div>

        <!-- Tariffs Table -->
        <div class="card card-glass" style="border-radius: 20px; border: 1px solid rgba(96,165,250,0.2); backdrop-filter: blur(20px);">
            <div class="card-header-custom p-4" style="border-bottom: 1px solid rgba(96,165,250,0.2);">
                <h5 class="m-0 fw-bold">
                    <i class="bi bi-table me-2"></i>All Tariffs
                </h5>
            </div>
            <div class="card-body-custom p-0">
                <?php if (count($tariffs) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Utility</th>
                                <th style="width: 20%;">Name</th>
                                <th style="width: 15%;">Price/Unit</th>
                                <th style="width: 15%;">Fixed Charge</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 15%;">Created</th>
                                <th style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tariffs as $tariff): ?>
                            <tr>
                                <td class="fw-500">
                                    <span class="badge" style="background: rgba(96,165,250,0.2); color: #60a5fa; padding: 6px 12px; border-radius: 8px;">
                                        <?= htmlspecialchars($tariff['utility_name'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($tariff['name']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background: rgba(34,197,94,0.2); color: #22c55e; padding: 6px 12px; border-radius: 8px;">
                                        PKR <?= number_format($tariff['price_per_unit'], 2) ?>
                                    </span>
                                </td>
                                <td>
                                    PKR <?= number_format($tariff['fixed_charge'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($tariff['is_active']): ?>
                                    <span class="badge" style="background: rgba(34,197,94,0.3); color: #22c55e; padding: 6px 12px;">
                                        <i class="bi bi-check-circle me-1"></i>Active
                                    </span>
                                    <?php else: ?>
                                    <span class="badge" style="background: rgba(239,68,68,0.3); color: #ef4444; padding: 6px 12px;">
                                        <i class="bi bi-x-circle me-1"></i>Inactive
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('M d, Y', strtotime($tariff['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary" onclick="editTariff(<?= $tariff['id'] ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" onclick="copyTariff(<?= $tariff['id'] ?>, '<?= htmlspecialchars($tariff['name']) ?>')" title="Copy">
                                            <i class="bi bi-files"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteTariff(<?= $tariff['id'] ?>)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: rgba(96,165,250,0.3);"></i>
                    <p class="text-muted mt-3">No tariffs found. Add a new one to get started.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Tariff Modal -->
    <div class="modal fade" id="addTariffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: rgba(15,23,42,0.8); border: 1px solid rgba(96,165,250,0.2); border-radius: 16px;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(96,165,250,0.2);">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Add New Tariff
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTariffForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-lightning me-2"></i>Utility</label>
                                <select name="utility_id" class="form-select" required>
                                    <option value="">Select Utility</option>
                                    <?php foreach ($utilities as $util): ?>
                                    <option value="<?= $util['id'] ?>"><?= htmlspecialchars($util['utility_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-tag me-2"></i>Tariff Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Residential A" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-percent me-2"></i>Price Per Unit</label>
                                <input type="number" step="0.01" name="price_per_unit" class="form-control" placeholder="0.00" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-cash me-2"></i>Fixed Charge</label>
                                <input type="number" step="0.01" name="fixed_charge" class="form-control" placeholder="0.00" required>
                            </div>

                            <div class="col-12">
                                <div class="form-check" style="padding: 12px; background: rgba(96,165,250,0.1); border-radius: 8px;">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                                    <label class="form-check-label fw-500" for="isActive">
                                        <i class="bi bi-check-circle me-2"></i>Active
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle-fill me-2"></i>Add Tariff
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Tariff Modal -->
    <div class="modal fade" id="editTariffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: rgba(15,23,42,0.8); border: 1px solid rgba(96,165,250,0.2); border-radius: 16px;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(96,165,250,0.2);">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Edit Tariff
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editTariffForm">
                        <input type="hidden" name="id" id="editTariffId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-lightning me-2"></i>Utility</label>
                                <select name="utility_id" class="form-select" id="editUtility" required>
                                    <option value="">Select Utility</option>
                                    <?php foreach ($utilities as $util): ?>
                                    <option value="<?= $util['id'] ?>"><?= htmlspecialchars($util['utility_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-tag me-2"></i>Tariff Name</label>
                                <input type="text" name="name" class="form-control" id="editName" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-percent me-2"></i>Price Per Unit</label>
                                <input type="number" step="0.01" name="price_per_unit" class="form-control" id="editPricePerUnit" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-cash me-2"></i>Fixed Charge</label>
                                <input type="number" step="0.01" name="fixed_charge" class="form-control" id="editFixedCharge" required>
                            </div>

                            <div class="col-12">
                                <div class="form-check" style="padding: 12px; background: rgba(96,165,250,0.1); border-radius: 8px;">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                                    <label class="form-check-label fw-500" for="editIsActive">
                                        <i class="bi bi-check-circle me-2"></i>Active
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-pencil-fill me-2"></i>Update Tariff
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Add Tariff
    document.getElementById('addTariffForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');
        // Explicitly set is_active value
        const isActiveCheckbox = document.getElementById('isActive');
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');

        fetch('tariff_adv/operations.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Tariff added successfully!');
                location.reload();
            } else {
                alert('Error: ' + (d.message || 'Failed to add tariff'));
            }
        })
        .catch(e => alert('Error: ' + e));
    });

    // Edit Tariff
    function editTariff(id) {
        fetch(`tariff_adv/get.php?id=${id}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('editTariffId').value = d.data.id;
                    document.getElementById('editUtility').value = d.data.utility_id;
                    document.getElementById('editName').value = d.data.name;
                    document.getElementById('editPricePerUnit').value = d.data.price_per_unit;
                    document.getElementById('editFixedCharge').value = d.data.fixed_charge;
                    document.getElementById('editIsActive').checked = parseInt(d.data.is_active) === 1;
                    new bootstrap.Modal(document.getElementById('editTariffModal')).show();
                }
            });
    }

    document.getElementById('editTariffForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'edit');
        // Explicitly set is_active value
        const isActiveCheckbox = document.getElementById('editIsActive');
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');

        fetch('tariff_adv/operations.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Tariff updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (d.message || 'Failed to update tariff'));
            }
        })
        .catch(e => alert('Error: ' + e));
    });

    // Copy Tariff
    function copyTariff(id, name) {
        const newName = prompt('Enter new tariff name:', name + ' (Copy)');
        if (newName) {
            const formData = new FormData();
            formData.append('action', 'copy');
            formData.append('id', id);
            formData.append('name', newName);

            fetch('tariff_adv/operations.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('Tariff copied successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (d.message || 'Failed to copy tariff'));
                }
            });
        }
    }

    // Delete Tariff
    function deleteTariff(id) {
        if (confirm('Are you sure you want to delete this tariff?')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('tariff_adv/operations.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('Tariff deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (d.message || 'Failed to delete tariff'));
                }
            });
        }
    }
    </script>

    <style>
    .page-header {
        background: linear-gradient(135deg, rgba(96,165,250,0.1) 0%, rgba(59,130,246,0.05) 100%);
        padding: 2rem;
        border-radius: 20px;
        border: 1px solid rgba(96,165,250,0.2);
        backdrop-filter: blur(20px);
    }

    .btn-group-sm .btn {
        border-radius: 8px !important;
        padding: 6px 10px !important;
        font-size: 0.875rem !important;
    }

    .form-control, .form-select {
        background: rgba(30,41,59,0.5) !important;
        border: 2px solid rgba(96,165,250,0.2) !important;
        border-radius: 10px !important;
        color: #e2e8f0 !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .form-control:focus, .form-select:focus {
        background: rgba(30,41,59,0.7) !important;
        border-color: #60a5fa !important;
        box-shadow: 0 0 20px rgba(96,165,250,0.3) !important;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        border: none !important;
        box-shadow: 0 4px 20px rgba(59,130,246,0.3) !important;
    }

    .btn-primary:hover {
        box-shadow: 0 6px 25px rgba(59,130,246,0.5) !important;
    }
    </style>
</body>
</html>
