<?php
// Define this constant BEFORE requiring the backend to prevent redirect loops
define('IS_LOGIN_PAGE', true);

require_once 'bicutan_backend.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // 1. REGISTRATION LOGIC
    // ==========================================
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'register') {
        $reg_name = trim($_POST['reg_name'] ?? '');
        $reg_username = trim($_POST['reg_username'] ?? '');
        $reg_password = $_POST['reg_password'] ?? '';
        $reg_branch = 'Makati Bicutan Hub'; // Locked to Bicutan Hub
        
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $reg_username);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error_message = 'Error: Username is already taken. Please choose another.';
        } else {
            $hashed_password = password_hash($reg_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, branch, is_approved) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("ssss", $reg_username, $hashed_password, $reg_name, $reg_branch);
            
            if ($stmt->execute()) {
                $success_message = 'Account successfully created! Please wait for IT Department approval before you can log in.';
            } else {
                $error_message = 'Registration failed. Please contact IT.';
            }
            $stmt->close();
        }
        $check->close();
    }
    
    // ==========================================
    // 2. LOGIN LOGIC
    // ==========================================
    elseif (isset($_POST['form_type']) && $_POST['form_type'] === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, username, password, name, branch, is_approved FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (trim(strtolower($row['branch'])) !== 'makati bicutan hub') {
                $error_message = 'Access Denied: Your account is restricted to ' . htmlspecialchars(ucfirst($row['branch'])) . ' branch. You cannot log in here.';
            } 
            elseif ($row['is_approved'] == 0) {
                $error_message = 'Access Denied: Your account is still pending approval from the IT Department.';
            } 
            else {
                if (password_verify($password, $row['password'])) {
                    // Strictly bind the actual registered user identity
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['branch'] = $row['branch'];
                    $_SESSION['name'] = $row['name']; // Extracts real registered name
                    $_SESSION['username'] = $row['username'];
                    
                    header("Location: bicutan_front.php");
                    exit;
                } else {
                    $error_message = 'Invalid username or password.';
                }
            }
        } else {
            $error_message = 'Invalid username or password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bicutan Inventory Hub - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --border-color: #cbd5e1;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --success: #059669;
            --success-bg: #ecfdf5;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: var(--card-bg); padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; border: 1px solid var(--border-color); }
        .login-card h2 { color: var(--text-main); margin-bottom: 8px; font-size: 1.5rem; text-align: center; }
        .login-card p { color: #64748b; font-size: 0.875rem; text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.875rem; font-weight: 600; color: #475569; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .btn { width: 100%; background-color: var(--primary); color: white; border: none; padding: 10px; border-radius: 6px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: background-color 0.2s; margin-top: 8px; }
        .btn:hover { background-color: var(--primary-hover); }
        .btn-outline { background-color: transparent; color: var(--primary); border: 1px solid var(--primary); margin-top: 12px; }
        .btn-outline:hover { background-color: #eff6ff; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.875rem; text-align: center; }
        .alert-danger { background-color: var(--danger-bg); color: var(--danger); border: 1px solid #fca5a5; }
        .alert-success { background-color: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        #registerForm { display: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Bicutan Hub</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <div id="loginForm">
            <p>Sign in to access the Bicutan inventory</p>
            <form method="POST" action="bicutan_login.php">
                <input type="hidden" name="form_type" value="login">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Log In</button>
                <button type="button" class="btn btn-outline" onclick="toggleForms()">Create an Account</button>
            </form>
        </div>

        <!-- REGISTER FORM -->
        <div id="registerForm">
            <p>Register an account (Needs IT Approval)</p>
            <form method="POST" action="bicutan_login.php">
                <input type="hidden" name="form_type" value="register">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="reg_name" required placeholder="e.g. Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="reg_username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="reg_password" required minlength="6">
                </div>
                <button type="submit" class="btn" style="background-color: #059669;">Submit for Approval</button>
                <button type="button" class="btn btn-outline" onclick="toggleForms()">Back to Login</button>
            </form>
        </div>
    </div>

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
