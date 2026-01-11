<?php
// 1. Adjust paths to go up one level (..) because this file is in 'user_adv/'
require_once __DIR__ . "/../includes/auth.php";
require_role('admin'); // Only admins can delete
require_once __DIR__ . "/../db.php";

// 2. Check if ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $user_id_to_delete = (int)$_GET['id'];
    
    // 3. SAFETY CHECK: Prevent deleting yourself
    // Assuming 'user_id' is stored in session from auth.php
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id_to_delete) {
        // Stop execution and alert the user (or redirect with error)
        echo "<script>
                alert('❌ Security Warning: You cannot delete your own account while logged in.');
                window.location.href = '../users.php';
              </script>";
        exit;
    }

    // 4. Prepare the DELETE statement
    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id_to_delete);
        
        if ($stmt->execute()) {
            // Success
            $stmt->close();
            header("Location: ../users.php?msg=User deleted successfully");
            exit;
        } else {
            // SQL Error
            echo "Error deleting record: " . $mysqli->error;
        }
    } else {
        echo "Database preparation error.";
    }

} else {
    // If no ID provided, just go back
    header("Location: ../users.php");
    exit;
}
?>