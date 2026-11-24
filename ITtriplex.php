<?php
// =========================================================
// FILE: ITtriplex.php
// =========================================================
session_start();
date_default_timezone_set('Asia/Manila');

// ---------------------------------------------------------
// DATABASE CONNECTION
// ---------------------------------------------------------
$host   = "sql306.infinityfree.com"; 
$user   = "if0_42844444";            
$pass   = "JSCAgAw4uVlFoE";          
$dbname = "if0_42844444_db_triplexinventorystructurebicutan"; 

$conn = @new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database Connection Error. Please verify your credentials.");
}
$conn->set_charset("utf8mb4");

// Database Sync for Compatibility between Bicutan Portal & IT Portal
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) NOT NULL DEFAULT 1");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100) DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS firstname VARCHAR(100) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS lastname VARCHAR(100) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT ''");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'User'");

$msg = "";
$active_tab = "pending"; // Default open tab if there are approvals

// ---------------------------------------------------------
// HANDLE CRUD ACTIONS (CREATE, UPDATE, DELETE, APPROVE)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 0. APPROVE USER
    if (isset($_POST['action']) && $_POST['action'] === 'approve_user') {
        $user_id = (int)$_POST['user_id'];
        if ($user_id > 0) {
            $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $msg = "user_approved";
                $active_tab = 'pending';
            }
            $stmt->close();
        }
    }

    // 1. DELETE USER
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $user_id = (int)$_POST['user_id'];
        $branch_target = trim($_POST['branch_target']);
        if ($user_id > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $msg = "user_deleted";
                if ($branch_target === 'pending') {
                    $active_tab = 'pending';
                } else {
                    $active_tab = ($branch_target === 'bicutan') ? 'viewBicutan' : 'viewMakati';
                }
            }
            $stmt->close();
        }
    }
    
    // 2. UPDATE USER
    if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
        $user_id   = (int)$_POST['user_id'];
        $firstname = trim($_POST['firstname']);
        $lastname  = trim($_POST['lastname']);
        $email     = trim($_POST['email']);
        $username  = trim($_POST['username']);
        $password  = trim($_POST['password']);
        $role      = trim($_POST['role']);
        $branch    = trim($_POST['branch']);
        $full_name = $firstname . ' ' . $lastname;

        if ($user_id > 0) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, name = ?, email = ?, username = ?, password = ?, role = ?, branch = ? WHERE id = ?");
                $stmt->bind_param("ssssssssi", $firstname, $lastname, $full_name, $email, $username, $hashed, $role, $branch, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, name = ?, email = ?, username = ?, role = ?, branch = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $firstname, $lastname, $full_name, $email, $username, $role, $branch, $user_id);
            }
            
            if ($stmt->execute()) {
                $msg = "user_updated";
                $active_tab = (strtolower($branch) === 'bicutan' || strpos(strtolower($branch), 'bicutan') !== false) ? 'viewBicutan' : 'viewMakati';
            }
            $stmt->close();
        }
    }

    // 3. SECURE MANIFEST UPDATE
    if (isset($_POST['action']) && $_POST['action'] === 'secure_manifest_update') {
        $req_id = (int)$_POST['req_id'];
        $new_manifesting_code = trim($_POST['manifesting_code']);
        
        if ($req_id > 0 && !empty($new_manifesting_code)) {
            $stmt = $conn->prepare("UPDATE stock_requests SET manifesting_code = ?, marking = CONCAT(COALESCE(marking, ''), ' | Manifest Updated (IT)') WHERE id = ?");
            $stmt->bind_param("si", $new_manifesting_code, $req_id);
            if ($stmt->execute()) {
                $msg = "manifest_updated";
                $active_tab = "manifest"; 
            }
            $stmt->close();
        }
    }
}

// ---------------------------------------------------------
// FETCH DATA PARA SA IT PORTAL
// ---------------------------------------------------------
$manifested_items = [];
$res_man = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') as item_name
    FROM stock_requests r
    LEFT JOIN companies c ON r.company_id = c.id
    LEFT JOIN items i ON r.item_id = i.id
    WHERE r.receiving_status = 'Manifested' OR r.status = 'Completed'
    ORDER BY r.updated_at DESC
");
if ($res_man) $manifested_items = $res_man->fetch_all(MYSQLI_ASSOC);

$pending_users = [];
$bicutan_users = [];
$makati_users = [];
$users_res = $conn->query("SELECT * FROM users ORDER BY id DESC");
if ($users_res) {
    while ($u = $users_res->fetch_assoc()) {
        if (isset($u['is_approved']) && $u['is_approved'] == 0) {
            $pending_users[] = $u;
        } else {
            if (strtolower($u['branch']) === 'bicutan' || strtolower(trim($u['branch'])) === 'makati bicutan hub') {
                $bicutan_users[] = $u;
            } else {
                $makati_users[] = $u;
            }
        }
    }
}
$pending_count = count($pending_users);
if($pending_count == 0 && $active_tab == "pending") {
    $active_tab = "viewBicutan";
}

$history_directory = [];
$hc_sql = "
    SELECT c.id, c.company_name, COUNT(r.id) as total_requests, SUM(r.quantity) as total_quantity, MAX(r.created_at) as latest_request 
    FROM companies c 
    JOIN stock_requests r ON c.id = r.company_id 
    GROUP BY c.id, c.company_name ORDER BY latest_request DESC
";
$hc_res = $conn->query($hc_sql);
if ($hc_res) $history_directory = $hc_res->fetch_all(MYSQLI_ASSOC);

$history_data = [];
$hist_res = $conn->query("
    SELECT r.*, c.company_name, c.id as comp_id, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') as item_name 
    FROM stock_requests r 
    LEFT JOIN companies c ON r.company_id = c.id 
    LEFT JOIN items i ON r.item_id = i.id 
    ORDER BY r.created_at DESC
");
if ($hist_res) $history_data = $hist_res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT-Triplex - Admin Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
        body { min-height: 100vh; color: #0f172a; background-color: #f8fafc; display: flex; }
        .sidebar { width: 280px; background-color: #ffffff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; padding: 24px 20px; }
        .sidebar-brand { font-size: 20px; font-weight: 800; margin-bottom: 30px; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .sidebar-brand span { background: #2563eb; color: white; padding: 4px 8px; border-radius: 6px; font-size: 12px; }
        .sidebar-menu { list-style: none; display: flex; flex-direction: column; gap: 6px; flex-grow: 1; }
        .sidebar-menu a, .dropdown-btn { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 12px 14px; color: #475569; background: none; border: none; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; text-align: left; }
        .sidebar-menu a:hover, .dropdown-btn:hover { background-color: #f1f5f9; color: #0f172a; }
        .sidebar-menu a.active-link { background-color: #eff6ff; color: #2563eb; font-weight: 600; }
        .dropdown-container { display: flex; flex-direction: column; padding-left: 15px; gap: 4px; margin-top: 4px; max-height: 250px; overflow-y: auto; }
        .dropdown-container::-webkit-scrollbar { width: 6px; }
        .dropdown-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .dropdown-container a { padding: 8px 12px; font-size: 13px; color: #64748b; }
        .dropdown-container a.active-link { background-color: #f1f5f9; color: #2563eb; font-weight: 600; }
        .main-content { flex-grow: 1; display: flex; align-items: flex-start; justify-content: center; padding: 40px; overflow-y: auto; }
        .container { background: #ffffff; padding: 35px 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0; max-width: 950px; width: 100%; }
        .section-box { display: none; }
        .section-box.active { display: block; }
        .form-header { margin-bottom: 24px; }
        h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        p { color: #64748b; font-size: 14px; }
        .badge { display: inline-block; padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 20px; background-color: #eff6ff; color: #2563eb; margin-bottom: 12px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; text-align: left; }
        label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input, select { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #0f172a; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
        input:focus, select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .submit-btn { width: 100%; padding: 12px; background-color: #2563eb; color: #ffffff; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background-color 0.2s ease; margin-top: 8px; }
        .submit-btn:hover { background-color: #1d4ed8; }
        .table-responsive { width: 100%; max-height: 500px; overflow-y: auto; margin-top: 20px; border: 1px solid #e2e8f0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 10;}
        td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        tr:last-child td { border-bottom: none; }
        .pass-code { font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: #0f172a; font-weight: 600;}
        .edit-btn { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .edit-btn:hover { background-color: #e2e8f0; color: #0f172a; }
        .del-btn { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 6px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .del-btn:hover { background-color: #fecaca; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-done { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-pend { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .status-fail { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .header-flex { display: flex; justify-content: space-between; align-items: flex-start; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">IT-Triplex</div>
        <ul class="sidebar-menu">
            <li><a href="../index.php">🏠 Bicutan Login / Hub</a></li>
            
            <li>
                <a href="#" id="menuPending" onclick="switchView('pending')" class="<?php echo ($active_tab == 'pending') ? 'active-link' : ''; ?>">
                    🔔 Pending Approvals
                    <?php if($pending_count > 0): ?>
                        <span style="background-color: #ef4444; color: white; border-radius: 20px; padding: 2px 8px; font-size: 11px; font-weight: bold; margin-left: auto;"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <button class="dropdown-btn" onclick="toggleDropdown('accountDropdown', 'arrow')">
                    <span>👤 Create Account</span><span id="arrow">▲</span>
                </button>
                <div class="dropdown-container" id="accountDropdown">
                    <a href="#" id="menuBicutan" onclick="switchView('bicutan')" class="<?php echo ($active_tab == 'bicutan') ? 'active-link' : ''; ?>">🏢 Bicutan Branch</a>
                    <a href="#" id="menuMakati" onclick="switchView('makati')" class="<?php echo ($active_tab == 'makati') ? 'active-link' : ''; ?>">🏢 Makati Branch</a>
                </div>
            </li>
            
            <li>
                <button class="dropdown-btn" onclick="toggleDropdown('viewDropdown', 'arrowView')">
                    <span>🔑 View Accounts</span><span id="arrowView">▼</span>
                </button>
                <div class="dropdown-container" id="viewDropdown" style="display: none;">
                    <a href="#" id="menuViewBicutan" onclick="switchView('viewBicutan')" class="<?php echo ($active_tab == 'viewBicutan') ? 'active-link' : ''; ?>">📋 Bicutan Directory</a>
                    <a href="#" id="menuViewMakati" onclick="switchView('viewMakati')" class="<?php echo ($active_tab == 'viewMakati') ? 'active-link' : ''; ?>">📋 Makati Directory</a>
                </div>
            </li>
            
            <li>
                <a href="#" id="menuManifest" onclick="switchView('manifest')" class="<?php echo ($active_tab == 'manifest') ? 'active-link' : ''; ?>">
                    📦 Edit Manifest IDs
                </a>
            </li>

            <li>
                <a href="#" id="menuHistoryDir" onclick="switchView('historyDir')" class="<?php echo ($active_tab == 'historyDir') ? 'active-link' : ''; ?>">
                    📜 Makati History
                </a>
            </li>

            <li><a href="#">⚙️ Settings Logs</a></li>
        </ul>
    </div>

    <div class="main-content">

        <!-- PENDING APPROVALS FORM -->
        <div class="container section-box <?php echo ($active_tab == 'pending') ? 'active' : ''; ?>" id="boxPending">
            <div class="form-header">
                <div class="badge" style="background-color: #fef08a; color: #854d0e;">IT REVIEW QUEUE</div>
                <h1>Pending Account Registrations</h1>
                <p>Approve or reject employee accounts created through the login portal.</p>
            </div>
            
            <?php if($msg === 'user_approved'): ?>
                <div style="background-color: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
                    ✅ Account successfully approved! The user can now log in.
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Branch</th>
                            <th>Username Setup</th>
                            <th>Date Requested</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($pending_users)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No pending account approvals at the moment.</td></tr>
                        <?php else: ?>
                            <?php foreach($pending_users as $pu): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php 
                                            $disp_name = trim($pu['firstname'] . ' ' . $pu['lastname']); 
                                            echo !empty($disp_name) ? htmlspecialchars($disp_name) : htmlspecialchars($pu['name'] ?? 'Unknown User');
                                            ?>
                                        </strong>
                                    </td>
                                    <td><span class="status-badge status-pend"><?php echo htmlspecialchars(strtoupper($pu['branch'])); ?></span></td>
                                    <td><span class="pass-code"><?php echo htmlspecialchars($pu['username']); ?></span></td>
                                    <td><small style="color: #64748b;"><?php echo date('M d, Y h:i A', strtotime($pu['created_at'])); ?></small></td>
                                    <td style="text-align: right;">
                                        <form action="ITtriplex.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Allow this user to log in to the system?');">
                                            <input type="hidden" name="action" value="approve_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$pu['id']; ?>">
                                            <button type="submit" class="edit-btn" style="background: #dcfce7; color: #166534; border-color: #bbf7d0;">Approve</button>
                                        </form>
                                        <form action="ITtriplex.php" method="POST" style="display:inline-block; margin-left: 4px;" onsubmit="return confirm('Reject and permanently delete this account request?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$pu['id']; ?>">
                                            <input type="hidden" name="branch_target" value="pending">
                                            <button type="submit" class="del-btn">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- BICUTAN CREATE FORM -->
        <div class="container section-box <?php echo ($active_tab == 'bicutan') ? 'active' : ''; ?>" id="boxBicutan">
            <div class="form-header">
                <div class="badge">BICUTAN BRANCH</div>
                <h1>Create Bicutan Account</h1>
                <p>Fill in the required details to provision a new user account for Bicutan.</p>
            </div>
            <form action="process_create.php" method="POST">
                <input type="hidden" name="branch" value="Makati Bicutan Hub">
                <div class="form-grid">
                    <div class="form-group"><label>First Name</label><input type="text" name="firstname" placeholder="Enter first name" required></div>
                    <div class="form-group"><label>Last Name</label><input type="text" name="lastname" placeholder="Enter last name" required></div>
                </div>
                <div class="form-group"><label>Company Email Address</label><input type="email" name="email" placeholder="name@triplex.com"></div>
                <div class="form-grid">
                    <div class="form-group"><label>Username</label><input type="text" name="username" placeholder="username" required></div>
                    <div class="form-group"><label>Temporary Password</label><input type="password" name="password" placeholder="••••••••" required></div>
                </div>
                <div class="form-group">
                    <label>Department / Role</label>
                    <select name="role" required>
                        <option value="">Select Department</option>
                        <option value="IT Support">IT Support</option>
                        <option value="Operations">Operations</option>
                        <option value="Logistics">Logistics</option>
                        <option value="Management">Management</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Create Bicutan Account</button>
            </form>
        </div>

        <!-- MAKATI CREATE FORM -->
        <div class="container section-box <?php echo ($active_tab == 'makati') ? 'active' : ''; ?>" id="boxMakati">
            <div class="form-header">
                <div class="badge" style="background-color: #fef3c7; color: #d97706;">MAKATI BRANCH</div>
                <h1>Create Makati Account</h1>
                <p>Fill in the required details to provision a new user account for Makati.</p>
            </div>
            <form action="process_create.php" method="POST">
                <input type="hidden" name="branch" value="makati">
                <div class="form-grid">
                    <div class="form-group"><label>First Name</label><input type="text" name="firstname" placeholder="Enter first name" required></div>
                    <div class="form-group"><label>Last Name</label><input type="text" name="lastname" placeholder="Enter last name" required></div>
                </div>
                <div class="form-group"><label>Company Email Address</label><input type="email" name="email" placeholder="name@triplex.com"></div>
                <div class="form-grid">
                    <div class="form-group"><label>Username</label><input type="text" name="username" placeholder="username" required></div>
                    <div class="form-group"><label>Temporary Password</label><input type="password" name="password" placeholder="••••••••" required></div>
                </div>
                <div class="form-group">
                    <label>Department / Role</label>
                    <select name="role" required>
                        <option value="">Select Department</option>
                        <option value="IT Support">IT Support</option>
                        <option value="Operations">Operations</option>
                        <option value="Logistics">Logistics</option>
                        <option value="Management">Management</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn" style="background-color: #d97706;">Create Makati Account</button>
            </form>
        </div>

        <!-- BICUTAN ACCOUNTS (VIEW & CRUD) -->
        <div class="container section-box <?php echo ($active_tab == 'viewBicutan') ? 'active' : ''; ?>" id="boxViewBicutan">
            <div class="form-header">
                <div class="badge">BICUTAN DIRECTORY</div>
                <h1>Bicutan Accounts</h1>
                <p>List of all active authorized user accounts assigned to the Bicutan branch with passwords and management tools.</p>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role / Title</th>
                            <th>Username</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($bicutan_users)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No Bicutan accounts found.</td></tr>
                        <?php else: ?>
                            <?php foreach($bicutan_users as $bu): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php 
                                            $disp_name = trim(($bu['firstname'] ?? '') . ' ' . ($bu['lastname'] ?? '')); 
                                            echo !empty($disp_name) ? htmlspecialchars($disp_name) : htmlspecialchars($bu['name'] ?? 'Unknown User');
                                            ?>
                                        </strong>
                                        <br><small style="color: #64748b;"><?php echo htmlspecialchars($bu['email'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($bu['role'] ?? 'User'); ?></td>
                                    <td><span class="pass-code"><?php echo htmlspecialchars($bu['username']); ?></span></td>
                                    <td style="text-align: right;">
                                        <button type="button" class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($bu), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                        <form action="ITtriplex.php" method="POST" style="display:inline-block; margin-left: 4px;" onsubmit="return confirm('Sigurado ka bang gusto mong burahin ang account na ito?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$bu['id']; ?>">
                                            <input type="hidden" name="branch_target" value="bicutan">
                                            <button type="submit" class="del-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MAKATI ACCOUNTS (VIEW & CRUD) -->
        <div class="container section-box <?php echo ($active_tab == 'viewMakati') ? 'active' : ''; ?>" id="boxViewMakati">
            <div class="form-header">
                <div class="badge" style="background-color: #fef3c7; color: #d97706;">MAKATI DIRECTORY</div>
                <h1>Makati Accounts</h1>
                <p>List of all active authorized user accounts assigned to the Makati branch with passwords and management tools.</p>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role / Title</th>
                            <th>Username</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($makati_users)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No Makati accounts found.</td></tr>
                        <?php else: ?>
                            <?php foreach($makati_users as $mu): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php 
                                            $disp_name = trim(($mu['firstname'] ?? '') . ' ' . ($mu['lastname'] ?? '')); 
                                            echo !empty($disp_name) ? htmlspecialchars($disp_name) : htmlspecialchars($mu['name'] ?? 'Unknown User');
                                            ?>
                                        </strong>
                                        <br><small style="color: #64748b;"><?php echo htmlspecialchars($mu['email'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($mu['role'] ?? 'User'); ?></td>
                                    <td><span class="pass-code"><?php echo htmlspecialchars($mu['username']); ?></span></td>
                                    <td style="text-align: right;">
                                        <button type="button" class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($mu), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                        <form action="ITtriplex.php" method="POST" style="display:inline-block; margin-left: 4px;" onsubmit="return confirm('Sigurado ka bang gusto mong burahin ang account na ito?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$mu['id']; ?>">
                                            <input type="hidden" name="branch_target" value="makati">
                                            <button type="submit" class="del-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- EDIT USER MODAL FORM (Overlay Container) -->
        <div id="editUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: #ffffff; padding: 30px; border-radius: 16px; max-width: 500px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div class="form-header" style="margin-bottom: 16px;">
                    <div class="badge" style="background-color: #e2e8f0; color: #334155;">EDIT ACCOUNT</div>
                    <h1>Modify User Details</h1>
                    <p>Update user information or change their password below.</p>
                </div>
                <form action="ITtriplex.php" method="POST">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-grid">
                        <div class="form-group"><label>First Name</label><input type="text" id="edit_firstname" name="firstname" required></div>
                        <div class="form-group"><label>Last Name</label><input type="text" id="edit_lastname" name="lastname" required></div>
                    </div>
                    <div class="form-group"><label>Email Address</label><input type="email" id="edit_email" name="email"></div>
                    <div class="form-grid">
                        <div class="form-group"><label>Username</label><input type="text" id="edit_username" name="username" required></div>
                        <div class="form-group"><label>New Password (Optional)</label><input type="password" name="password" placeholder="Leave blank if unchanged"></div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Role / Department</label>
                            <select id="edit_role" name="role" required>
                                <option value="User">User</option>
                                <option value="IT Support">IT Support</option>
                                <option value="Operations">Operations</option>
                                <option value="Logistics">Logistics</option>
                                <option value="Management">Management</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Branch</label>
                            <select id="edit_branch" name="branch" required>
                                <option value="Makati Bicutan Hub">Bicutan</option>
                                <option value="makati">Makati</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" class="submit-btn" style="margin-top: 0;">Save Changes</button>
                        <button type="button" class="edit-btn" onclick="closeEditModal()" style="width: 100%; padding: 12px;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- MANIFEST ID MANAGEMENT UI -->
        <div class="container section-box <?php echo ($active_tab == 'manifest') ? 'active' : ''; ?>" id="boxManifest">
            <?php if($msg === 'manifest_updated'): ?>
                <div style="background-color: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
                    ✅ Successfully updated the Manifesting Code!
                </div>
            <?php endif; ?>
            <div class="form-header">
                <div class="badge" style="background-color: #f3e8ff; color: #7e22ce;">ADMIN TOOL</div>
                <h1>Manage Manifest IDs</h1>
                <p>Safely select and modify Manifesting Codes for items received in the Makati hub.</p>
            </div>
            <form action="ITtriplex.php" method="POST" style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
                <input type="hidden" name="action" value="secure_manifest_update">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Target Request ID</label>
                        <input type="number" id="edit_req_id" name="req_id" placeholder="Select from table below" required readonly style="background: #e2e8f0;">
                    </div>
                    <div class="form-group">
                        <label>New Manifest ID</label>
                        <input type="text" id="edit_manifest_code" name="manifesting_code" placeholder="Type new code..." required>
                    </div>
                </div>
                <button type="submit" class="submit-btn" style="background-color: #7e22ce;">Force Update Manifest Record</button>
            </form>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Req ID</th>
                            <th>Current Manifest Code</th>
                            <th>Item Name</th>
                            <th>Company</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($manifested_items)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No manifested items found.</td></tr>
                        <?php else: ?>
                            <?php foreach($manifested_items as $man): ?>
                                <tr>
                                    <td><strong><?php echo (int)$man['id']; ?></strong></td>
                                    <td><span class="pass-code"><?php echo htmlspecialchars($man['manifesting_code'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo htmlspecialchars($man['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($man['company_name'] ?? 'N/A'); ?></td>
                                    <td style="text-align: right;">
                                        <button type="button" class="edit-btn" onclick="loadManifestData(<?php echo (int)$man['id']; ?>, '<?php echo htmlspecialchars($man['manifesting_code'], ENT_QUOTES); ?>')">Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MAKATI COMPANY DIRECTORY -->
        <div class="container section-box <?php echo ($active_tab == 'historyDir') ? 'active' : ''; ?>" id="boxHistoryDir">
            <div class="header-flex">
                <div class="form-header">
                    <div class="badge" style="background-color: #e0f2fe; color: #0369a1;">MAKATI HUB HISTORY</div>
                    <h1>Company Directory</h1>
                    <p>Select a company to view their complete request history and tracked transactions.</p>
                </div>
                <button class="edit-btn" onclick="showHistory('all', 'All Companies')">View All Data</button>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th style="text-align: center;">Total Requests</th>
                            <th style="text-align: center;">Total Qty</th>
                            <th>Latest Update</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history_directory)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">No companies found.</td></tr>
                        <?php else: ?>
                            <?php foreach($history_directory as $hc): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($hc['company_name']); ?></strong></td>
                                    <td style="text-align: center;">
                                        <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #475569;">
                                            <?php echo (int)$hc['total_requests']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="background: #eff6ff; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #2563eb;">
                                            <?php echo number_format($hc['total_quantity']); ?>
                                        </span>
                                    </td>
                                    <td><small style="color: #64748b;"><?php echo date('M d, Y h:i A', strtotime($hc['latest_request'])); ?></small></td>
                                    <td style="text-align: right;">
                                        <button class="edit-btn" onclick="showHistory(<?php echo $hc['id']; ?>, '<?php echo htmlspecialchars($hc['company_name'], ENT_QUOTES); ?>')">
                                            View History
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MAKATI DETAILED REQUEST HISTORY -->
        <div class="container section-box" id="boxHistoryDetail">
            <div class="header-flex">
                <div class="form-header">
                    <div class="badge" style="background-color: #e0f2fe; color: #0369a1;">MAKATI HUB HISTORY</div>
                    <h1 id="historyDisplayTitle">Request History</h1>
                    <p>Complete track record of requests routed from the Makati branch.</p>
                </div>
                <button class="edit-btn" onclick="switchView('historyDir')">← Back to Directory</button>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Req ID</th>
                            <th>Batch Ref</th>
                            <th>Item Details</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php if(empty($history_data)): ?>
                            <tr id="noDataRow"><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No history found.</td></tr>
                        <?php else: ?>
                            <?php foreach($history_data as $h): ?>
                                <tr class="history-row" data-comp-id="<?php echo $h['comp_id']; ?>">
                                    <td><strong>#<?php echo (int)$h['id']; ?></strong></td>
                                    <td><span class="pass-code"><?php echo htmlspecialchars($h['batch_reference'] ?? 'N/A'); ?></span></td>
                                    <td>
                                        <?php echo htmlspecialchars($h['item_name']); ?>
                                        <?php if($h['request_type'] === 'SPECIAL'): ?>
                                            <br><span style="font-size: 11px; background-color: #fef08a; padding: 2px 5px; border-radius: 4px; font-weight: bold; color: #854d0e;">SPECIAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo number_format($h['quantity']); ?></strong></td>
                                    <td>
                                        <?php 
                                            $stat = $h['status'];
                                            if($stat == 'Approved' || $stat == 'Shipped' || $stat == 'Completed') {
                                                echo "<span class='status-badge status-done'>$stat</span>";
                                            } elseif($stat == 'Rejected') {
                                                echo "<span class='status-badge status-fail'>$stat</span>";
                                            } else {
                                                echo "<span class='status-badge status-pend'>$stat</span>";
                                            }
                                        ?>
                                    </td>
                                    <td><small style="color: #64748b;"><?php echo date('M d, Y', strtotime($h['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- JavaScript Navigation & CRUD Control -->
    <script>
        function toggleDropdown(containerId, arrowId) {
            const dropdown = document.getElementById(containerId);
            const arrow = document.getElementById(arrowId);
            const isHidden = dropdown.style.display === "none" || dropdown.style.display === "";
            dropdown.style.display = isHidden ? "flex" : "none";
            arrow.textContent = isHidden ? "▲" : "▼";
        }

        function switchView(target) {
            document.querySelectorAll('.section-box').forEach(box => {
                box.classList.remove('active');
            });
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active-link');
            });

            if (target === 'bicutan') {
                document.getElementById('boxBicutan').classList.add('active');
                document.getElementById('menuBicutan').classList.add('active-link');
            } else if (target === 'makati') {
                document.getElementById('boxMakati').classList.add('active');
                document.getElementById('menuMakati').classList.add('active-link');
            } else if (target === 'viewBicutan') {
                document.getElementById('boxViewBicutan').classList.add('active');
                document.getElementById('menuViewBicutan').classList.add('active-link');
            } else if (target === 'viewMakati') {
                document.getElementById('boxViewMakati').classList.add('active');
                document.getElementById('menuViewMakati').classList.add('active-link');
            } else if (target === 'manifest') {
                document.getElementById('boxManifest').classList.add('active');
                document.getElementById('menuManifest').classList.add('active-link');
            } else if (target === 'historyDir') {
                document.getElementById('boxHistoryDir').classList.add('active');
                document.getElementById('menuHistoryDir').classList.add('active-link');
            } else if (target === 'historyDetail') {
                document.getElementById('boxHistoryDetail').classList.add('active');
                document.getElementById('menuHistoryDir').classList.add('active-link');
            } else if (target === 'pending') {
                document.getElementById('boxPending').classList.add('active');
                document.getElementById('menuPending').classList.add('active-link');
            }
        }

        function openEditModal(userData) {
            document.getElementById('edit_user_id').value = userData.id;
            
            // Reconcile name format display for the modal inputs
            let fName = userData.firstname || "";
            let lName = userData.lastname || "";
            if (fName === "" && lName === "" && userData.name) {
                let parts = userData.name.split(" ");
                fName = parts[0];
                lName = parts.slice(1).join(" ");
            }
            
            document.getElementById('edit_firstname').value = fName;
            document.getElementById('edit_lastname').value = lName;
            document.getElementById('edit_email').value = userData.email || '';
            document.getElementById('edit_username').value = userData.username;
            document.getElementById('edit_role').value = userData.role || 'User';
            
            // Set correct select value based on database string
            let branchVal = userData.branch.toLowerCase();
            if (branchVal.includes("bicutan")) {
                document.getElementById('edit_branch').value = "Makati Bicutan Hub";
            } else {
                document.getElementById('edit_branch').value = "makati";
            }
            
            document.getElementById('editUserModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        function loadManifestData(reqId, currentCode) {
            document.getElementById('edit_req_id').value = reqId;
            document.getElementById('edit_manifest_code').value = currentCode;
            document.getElementById('edit_manifest_code').focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showHistory(compId, compName) {
            switchView('historyDetail');
            document.getElementById('historyDisplayTitle').innerText = "Request History - " + compName;
            
            let rows = document.querySelectorAll('.history-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (compId === 'all' || row.getAttribute('data-comp-id') == compId) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            let noDataRow = document.getElementById('noDataRow');
            if (!noDataRow && visibleCount === 0) {
                let tbody = document.getElementById('historyTableBody');
                tbody.insertAdjacentHTML('beforeend', '<tr id="noDataRow"><td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">No records found for this company.</td></tr>');
            } else if (noDataRow && visibleCount > 0) {
                noDataRow.remove();
            } else if (noDataRow && visibleCount === 0) {
                noDataRow.style.display = '';
            }
        }
    </script>

</body>
</html>