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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_table'])) {
    // Create tariffs table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `tariffs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `utility_id` int(11) NOT NULL,
      `name` varchar(255) NOT NULL COMMENT 'Tariff name (e.g., Residential A, Commercial B)',
      `price_per_unit` decimal(10,4) NOT NULL COMMENT 'Price per consumption unit (PKR)',
      `fixed_charge` decimal(10,2) NOT NULL COMMENT 'Fixed monthly/base charge (PKR)',
      `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`utility_id`) REFERENCES `utilities`(`id`) ON DELETE CASCADE,
      KEY `utility_idx` (`utility_id`),
      KEY `is_active_idx` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($mysqli->query($sql)) {
        $message = 'Tariffs table created successfully!';
        
        // Insert sample tariffs if needed
        $check = $mysqli->query("SELECT COUNT(*) as cnt FROM tariffs")->fetch_assoc();
        if ($check['cnt'] == 0) {
            $sample = "INSERT INTO `tariffs` (`utility_id`, `name`, `price_per_unit`, `fixed_charge`, `is_active`, `created_at`) 
            SELECT u.id, 'Standard Rate', 25.50, 500.00, 1, NOW() FROM utilities u LIMIT 1";
            $mysqli->query($sample);
        }
    } else {
        $error = 'Error creating table: ' . $mysqli->error;
    }
}

// Check if tariffs table exists
$tableExists = false;
$result = $mysqli->query("SHOW TABLES LIKE 'tariffs'");
if ($result && $result->num_rows > 0) {
    $tableExists = true;
}

// Count tariffs
$tariffCount = 0;
if ($tableExists) {
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM tariffs");
    if ($result) {
        $tariffCount = $result->fetch_assoc()['cnt'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tariffs Setup - UMS</title>
    <?php include 'header.php'; ?>
</head>
<body>
    <div class="container-fluid py-4" style="margin-top: 80px;">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card card-glass" style="border-radius: 20px; border: 1px solid rgba(96,165,250,0.2);">
                    <div class="card-body p-4">
                        <h4 class="mb-4">
                            <i class="bi bi-tools me-2"></i>Tariffs Setup
                        </h4>

                        <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <div class="status-check mb-4">
                            <p class="mb-2">
                                <i class="bi bi-check-circle" style="color: <?= $tableExists ? '#22c55e' : '#ef4444' ?>;"></i>
                                <strong>Tariffs Table:</strong>
                                <span style="color: <?= $tableExists ? '#22c55e' : '#ef4444' ?>;">
                                    <?= $tableExists ? 'EXISTS' : 'NOT FOUND' ?>
                                </span>
                            </p>
                            <?php if ($tableExists): ?>
                            <p class="mb-2 text-muted small">
                                <i class="bi bi-info-circle me-2"></i>Current records: <strong><?= $tariffCount ?></strong>
                            </p>
                            <?php endif; ?>
                        </div>

                        <?php if (!$tableExists): ?>
                        <form method="POST">
                            <button type="submit" name="create_table" value="1" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>Create Tariffs Table
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="tariffs.php" class="btn btn-success w-100">
                            <i class="bi bi-arrow-right me-2"></i>Go to Tariffs Manager
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
