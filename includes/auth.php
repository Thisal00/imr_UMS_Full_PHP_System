<?php
session_start();

/*
-------------------------------------------
 BASIC LOGIN CHECK
-------------------------------------------
*/
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }

    // If user is disabled, force logout
    if (isset($_SESSION['status']) && $_SESSION['status'] === 'disabled') {
        session_destroy();
        header("Location: /login.php?error=Account Disabled");
        exit;
    }
}

/*
-------------------------------------------
 ROLE CHECK (ONE ROLE ONLY)
 Example: require_role('admin')
-------------------------------------------
*/
function require_role($role) {
    require_login(); // ensure logged in first

    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        die("<h2 style='color:red;text-align:center;margin-top:50px;'>❌ ACCESS DENIED</h2>");
    }
}

/*
-------------------------------------------
 ALLOW MULTIPLE ROLES
 Example: allow_roles(['admin','cashier'])
-------------------------------------------
*/
function allow_roles($roles = []) {
    require_login();

    if (!in_array($_SESSION['user_role'], $roles)) {
        die("<h2 style='color:red;text-align:center;margin-top:50px;'>❌ ACCESS DENIED</h2>");
    }
}

/*
-------------------------------------------
 ADMIN CHECK HELPER
-------------------------------------------
*/
function is_admin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

?>
