<?php
session_start();

// ---------------------------------------------------------
// BUILT-IN LOGOUT LOGIC (Para hindi na hiwalay ang logout.php)
// ---------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Kung nakapag-login na at taga-Makati, rekta na agad sa makati.php
if (isset($_SESSION['logged_in']) && $_SESSION['branch'] === 'makati') {
    header("Location: Makati.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = "sql306.infinityfree.com"; 
    $user   = "if0_42844444";            
    $pass   = "JSCAgAw4uVlFoE";          
    $dbname = "if0_42844444_db_triplexinventorystructurebicutan"; 
    
    // Disable strict reporting temporarily to prevent silent 500 errors on connection failure
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        $error = "Database connection failed. Please try again later.";
    } else {
        
        // ==========================================
        // 1. REGISTRATION LOGIC
        // ==========================================
        if (isset($_POST['form_type']) && $_POST['form_type'] === 'register') {
            $reg_firstname = trim($_POST['reg_firstname'] ?? '');
            $reg_lastname  = trim($_POST['reg_lastname'] ?? '');
            $reg_username  = trim($_POST['reg_username'] ?? '');
            $reg_password  = $_POST['reg_password'] ?? '';
            $reg_name      = $reg_firstname . ' ' . $reg_lastname;
            $reg_branch    = 'makati';
            $reg_role      = 'User'; // Default role kapag nag-register sila sa labas
            
            // Check kung existing na ang username
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            if ($check) {
                $check->bind_param("s", $reg_username);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows > 0) {
                    $error = 'Error: Username is already taken. Please choose another.';
                } else {
                    $hashed_password = password_hash($reg_password, PASSWORD_DEFAULT);
                    // is_approved = 0 (Pending IT Approval)
                    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, name, username, password, branch, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                    if ($stmt) {
                        $stmt->bind_param("sssssss", $reg_firstname, $reg_lastname, $reg_name, $reg_username, $hashed_password, $reg_branch, $reg_role);
                        if ($stmt->execute()) {
                            $success = 'Account successfully created! Please wait for IT Department approval before you can log in.';
                        } else {
                            $error = 'Registration failed. Please contact IT.';
                        }
                        $stmt->close();
                    }
                }
                $check->close();
            }
        }
        
        // ==========================================
        // 2. LOGIN LOGIC
        // ==========================================
        elseif (isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            
            // Isinama natin ang is_approved sa sinesearch
            $stmt = $conn->prepare("SELECT id, password, role, branch, firstname, is_approved FROM users WHERE username = ? AND branch = 'makati'");
            
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                
                // FIX: Use bind_result() instead of get_result() for InfinityFree compatibility
                $stmt->bind_result($db_id, $db_password, $db_role, $db_branch, $db_firstname, $db_is_approved);
                
                if ($stmt->fetch()) {
                    // CHECK KUNG APPROVED NA NG IT
                    if ($db_is_approved == 0) {
                        $error = "Access Denied: Your account is still pending approval from the IT Department.";
                    } else {
                        if (password_verify($password, $db_password)) {
                            $_SESSION['logged_in'] = true;
                            $_SESSION['user_id'] = $db_id;
                            $_SESSION['branch'] = $db_branch;
                            $_SESSION['name'] = $db_firstname;
                            $_SESSION['role'] = $db_role;
                            
                            // FIX: Corrected casing to match the filename
                            header("Location: Makati.php");
                            exit();
                        } else {
                            $error = "Mali ang iyong password.";
                        }
                    }
                } else {
                    $error = "Walang account na nakita para sa username na ito, o hindi ito Makati account.";
                }
                $stmt->close();
            } else {
                $error = "System error during authentication.";
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Makati Hub - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .back-link:hover {
            color: #d97706 !important;
            text-decoration: underline !important;
        }
        /* Itatago by default ang Registration form */
        #registerForm { display: none; }
    </style>
</head>
<body style="background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px; border: none; border-radius: 12px;">
        <h3 class="text-center fw-bold mb-4" style="color: #d97706;">Makati Hub</h3>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <div class="alert alert-success py-2 small"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <div id="loginForm">
            <p class="text-center text-muted small mb-3">Sign in to access the Makati inventory</p>
            <form method="POST">
                <input type="hidden" name="form_type" value="login">
                <div class="mb-3">
                    <label class="form-label text-muted fw-semibold small">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted fw-semibold small">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn w-100 fw-bold mb-2" style="background-color: #d97706; color: white;">Log In</button>
                <button type="button" class="btn w-100 fw-bold btn-outline-warning" onclick="toggleForms()" style="color: #d97706; border-color: #d97706;">Create an Account</button>
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-decoration-none text-muted small back-link">&larr; Bumalik sa Dashboard</a>
                </div>
            </form>
        </div>

        <!-- REGISTER FORM -->
        <div id="registerForm">
            <p class="text-center text-muted small mb-3">Register a Makati account (Needs IT Approval)</p>
            <form method="POST">
                <input type="hidden" name="form_type" value="register">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted fw-semibold small">First Name</label>
                        <input type="text" name="reg_firstname" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted fw-semibold small">Last Name</label>
                        <input type="text" name="reg_lastname" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fw-semibold small">Username</label>
                    <input type="text" name="reg_username" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted fw-semibold small">Password</label>
                    <input type="password" name="reg_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn w-100 fw-bold mb-2" style="background-color: #059669; color: white;">Submit for Approval</button>
                <button type="button" class="btn w-100 fw-bold btn-outline-secondary" onclick="toggleForms()">Back to Login</button>
            </form>
        </div>
    </div>

    <!-- Script para sa pag-switch ng forms -->
    <script>
        function toggleForms() {
            const login = document.getElementById('loginForm');
            const register = document.getElementById('registerForm');
            
            if (login.style.display === 'none') {
                login.style.display = 'block';
                register.style.display = 'none';
            } else {
                login.style.display = 'none';
                register.style.display = 'block';
            }
        }
    </script>
</body>
</html>