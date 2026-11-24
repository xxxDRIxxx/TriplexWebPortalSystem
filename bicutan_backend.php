<?php
// =========================================================
// FILE: bicutan_backend.php
// =========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user has a valid active session
$is_valid_user = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);

// 2. DYNAMIC REDIRECT GUARD (Prevents ERR_TOO_MANY_REDIRECTS)
if (defined('IS_LOGIN_PAGE')) {
    if ($is_valid_user) {
        header("Location: bicutan_front.php");
        exit;
    }
} else {
    if (basename($_SERVER['PHP_SELF']) !== 'logout.php') {
        if (!$is_valid_user) {
            header("Location: bicutan_login.php");
            exit;
        }
    }
}

// 3. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('IS_LOGIN_PAGE')) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token. Please refresh the page and try again.");
    }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone
date_default_timezone_set('Asia/Manila');

$host   = "sql306.infinityfree.com"; 
$user   = "if0_42844444";            
$pass   = "JSCAgAw4uVlFoE";          
$dbname = "if0_42844444_db_triplexinventorystructurebicutan"; 

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+08:00'");

// Auto-create Tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE IF NOT EXISTS sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE IF NOT EXISTS markings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    branch VARCHAR(100) DEFAULT 'Makati Bicutan Hub',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$cat_check = $conn->query("SELECT COUNT(*) as c FROM categories");
if ($cat_check && $cat_check->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT IGNORE INTO categories (name) VALUES ('Copy Paper'), ('Special Paper'), ('Supplies')");
}

$size_check = $conn->query("SELECT COUNT(*) as c FROM sizes");
if ($size_check && $size_check->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT IGNORE INTO sizes (name) VALUES ('Standard'), ('Short (8.5 x 11 inches)'), ('Long (8.5 x 13 inches)'), ('A4 (8.27 x 11.69 inches)'), ('Special Cut')");
}

$mark_check = $conn->query("SELECT COUNT(*) as c FROM markings");
if ($mark_check && $mark_check->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT IGNORE INTO markings (name) VALUES ('Home Carry'), ('Deliver'), ('Pick-up')");
}

$user_check = $conn->query("SELECT COUNT(*) as c FROM users");
if ($user_check && $user_check->fetch_assoc()['c'] == 0) {
    $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (username, password, name, branch) VALUES ('admin', '$default_pass', 'Bicutan Admin', 'Makati Bicutan Hub')");
}

$conn->query("CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(255) NOT NULL,
    size VARCHAR(100) NOT NULL DEFAULT 'Standard',
    is_roll TINYINT(1) DEFAULT 0,
    roll_length DECIMAL(10,2) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00
)");

$conn->query("CREATE TABLE IF NOT EXISTS stock_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT DEFAULT NULL,
    requested_by VARCHAR(255) NOT NULL,
    branch VARCHAR(100) NOT NULL DEFAULT 'Makati Bicutan Hub',
    quantity INT NOT NULL,
    purpose TEXT,
    priority ENUM('Rush', '2-3 Days', 'Willing to Wait') DEFAULT 'Willing to Wait',
    target_date DATE NULL,
    pickup_date DATE NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    request_type VARCHAR(50) DEFAULT 'STANDARD',
    paper_item_name VARCHAR(255) DEFAULT NULL,
    paper_type VARCHAR(255) DEFAULT NULL,
    original_size VARCHAR(100) DEFAULT NULL,
    special_cut_size VARCHAR(100) DEFAULT NULL,
    width DECIMAL(10,2) DEFAULT NULL,
    height DECIMAL(10,2) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    marking VARCHAR(100) DEFAULT NULL,
    care_of VARCHAR(255) DEFAULT NULL,
    special_instructions TEXT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    approved_by VARCHAR(255) DEFAULT NULL,
    confirmed_by VARCHAR(255) DEFAULT NULL,
    shipped_by VARCHAR(255) DEFAULT NULL,
    received_by VARCHAR(255) DEFAULT NULL,
    approved_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_id INT DEFAULT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS transaction_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id INT DEFAULT NULL,
    description TEXT NOT NULL,
    quantity_change INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100) NOT NULL DEFAULT 'User'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS branch VARCHAR(100) DEFAULT 'Makati Bicutan Hub'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) NOT NULL DEFAULT 1");

$conn->query("ALTER TABLE items ADD COLUMN IF NOT EXISTS size VARCHAR(100) NOT NULL DEFAULT 'Standard'");
$conn->query("ALTER TABLE items ADD COLUMN IF NOT EXISTS is_roll TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE items ADD COLUMN IF NOT EXISTS roll_length DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS target_date DATE NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS approved_by VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS confirmed_by VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS shipped_by VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS received_by VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS received_at TIMESTAMP NULL");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE stock_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending'");

$self = 'bicutan_front.php';
$active_tab = $_GET['tab'] ?? 'items';

// =========================================================
// HELPER FUNCTIONS
// =========================================================
function generateSKU($conn) {
    $conn->begin_transaction();
    try {
        $result = $conn->query("SELECT MAX(id) as max_id FROM items FOR UPDATE");
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ? $row['max_id'] + 1 : 1);
        
        do {
            $sku = 'TRX-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
            $check = $conn->prepare("SELECT id FROM items WHERE sku = ?");
            $check->bind_param("s", $sku);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $next_id++;
                $check->close();
            } else {
                $check->close();
                break;
            }
        } while (true);

        $conn->commit();
        return $sku;
    } catch (Exception $e) {
        $conn->rollback();
        return 'TRX-' . str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT);
    }
}

function getCountdownBadge($req) {
    $status = $req['status'] ?? 'Pending';
    if ($status === 'Approved' || $status === 'Scheduled') {
        return '<span class="badge badge-success">Approved / Done</span>';
    } elseif ($status === 'Rejected') {
        return '<span class="badge badge-danger">Rejected</span>';
    } elseif ($status === 'Shipped' || $status === 'Completed' || $status === 'Shipping Already Accepted') {
        return '<span class="badge badge-success">Delivered / Shipped</span>';
    }
    
    $reference_date = !empty($req['target_date']) ? $req['target_date'] : (!empty($req['pickup_date']) ? $req['pickup_date'] : null);
    if (!empty($reference_date)) {
        $target_datetime = $reference_date . ' 23:59:59';
    } else {
        $limit_days = 3;
        if ($req['priority'] === 'Rush') $limit_days = 1;
        if ($req['priority'] === '2-3 Days') $limit_days = 3;
        $target_datetime = date('Y-m-d 23:59:59', strtotime($req['created_at'] . ' + ' . $limit_days . ' days'));
    }
    
    return '<span class="live-countdown" data-target="' . $target_datetime . '">...</span>';
}

// =========================================================
// CRUD & ACTION HANDLERS
// =========================================================

// 1. CONFIRM / SCHEDULE (Stage 1)
if (isset($_GET['action']) && $_GET['action'] === 'schedule_request') {
    $req_id = (int)$_GET['id'];
    $active_user = $_SESSION['name']; // Strict identity lock
    
    $stmt = $conn->prepare("UPDATE stock_requests SET status = 'Scheduled', confirmed_by = ?, confirmed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'Approved'");
    $stmt->bind_param("si", $active_user, $req_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $self . "?tab=shipping&msg=req_scheduled");
    exit();
}

// 2. SHIP OUT (Stage 2)
if (isset($_GET['action']) && $_GET['action'] === 'ship_request') {
    $req_id = (int)$_GET['id'];
    $active_user = $_SESSION['name']; // Strict identity lock
    
    $stmt = $conn->prepare("UPDATE stock_requests SET status = 'Shipped', shipped_by = ?, shipped_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'Scheduled'");
    $stmt->bind_param("si", $active_user, $req_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $self . "?tab=shipping&msg=req_shipped");
    exit();
}

// 3. RECEIVE / COMPLETE (Stage 3)
if (isset($_GET['action']) && $_GET['action'] === 'receive_request') {
    $req_id = (int)$_GET['id'];
    $active_user = $_SESSION['name']; // Strict identity lock
    
    $stmt = $conn->prepare("UPDATE stock_requests SET status = 'Completed', received_by = ?, received_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'Shipped'");
    $stmt->bind_param("si", $active_user, $req_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $self . "?tab=shipping&msg=req_received");
    exit();
}

// Export actions
if (isset($_GET['action']) && $_GET['action'] === 'export_backup_csv') {
    $filename = 'System_Backup_All_Transactions_' . date('Y_m_d_H_i_s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); 
    
    fputcsv($output, array('Transaction / Reference ID', 'Company / Branch Name', 'Requester Name', 'Approved By', 'Confirmed By', 'Shipped By', 'Received By', 'Quantity (Orders)', 'Item ID / SKU', 'Item Name', 'Size / Dimensions', 'Markings', 'Request Date & Time', 'Approval Date & Time', 'Current Status', 'Priority', 'c/o Salesperson', 'Remarks'));
    
    $backup_res = $conn->query("
        SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name_disp, COALESCE(i.sku, 'SPECIAL') AS sku_disp
        FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id
        ORDER BY r.id DESC
    ");

    if ($backup_res && $backup_res->num_rows > 0) {
        while ($row = $backup_res->fetch_assoc()) {
            $company_label = !empty($row['company_name']) ? $row['company_name'] : $row['branch'];
            $size_label = ($row['request_type'] === 'SPECIAL') ? $row['width'] . 'x' . $row['height'] . ' ' . $row['unit'] : 'Standard';
            
            fputcsv($output, array(
                '#REQ-' . $row['id'], $company_label, $row['requested_by'] ?? 'N/A', $row['approved_by'] ?? 'N/A',
                $row['confirmed_by'] ?? 'N/A', $row['shipped_by'] ?? 'N/A', $row['received_by'] ?? 'N/A', $row['quantity'],
                $row['sku_disp'], $row['item_name_disp'], $size_label, $row['marking'] ?? 'N/A',
                !empty($row['created_at']) ? date('M d, Y h:i A', strtotime($row['created_at'])) : 'N/A',
                !empty($row['approved_at']) ? date('M d, Y h:i A', strtotime($row['approved_at'])) : 'N/A',
                $row['status'], $row['priority'], $row['care_of'] ?? 'N/A', $row['remarks'] ?? 'N/A'
            ));
        }
    }
    fclose($output);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'export_shipping_excel') {
    $filename = 'Bicutan_Shipping_Report_' . date('Y_m_d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); 
    fputcsv($output, array('Request Number', 'SKU ID', 'Item Description', 'Quantity', 'Branch / Company', 'Approved By', 'Confirmed By', 'Shipped By', 'Received By', 'Status', 'Updated Date'));
    
    $ship_res = $conn->query("
        SELECT r.id, r.quantity, r.status, r.updated_at, r.approved_by, r.confirmed_by, r.shipped_by, r.received_by,
               c.company_name, r.branch, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, COALESCE(i.sku, 'SPECIAL') AS sku
        FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id
        WHERE r.status IN ('Shipped', 'Shipping Already Accepted', 'Approved', 'Completed') 
        ORDER BY FIELD(r.status, 'Shipped', 'Completed', 'Shipping Already Accepted', 'Approved'), r.updated_at DESC
    ");

    if ($ship_res && $ship_res->num_rows > 0) {
        while ($row = $ship_res->fetch_assoc()) {
            fputcsv($output, array(
                '#TRK-' . $row['id'], $row['sku'], $row['item_name'], $row['quantity'],
                !empty($row['company_name']) ? $row['company_name'] : $row['branch'],
                $row['approved_by'] ?? 'N/A', $row['confirmed_by'] ?? 'N/A', $row['shipped_by'] ?? 'N/A',
                $row['received_by'] ?? 'N/A', ($row['status'] === 'Shipping Already Accepted') ? 'Accepted' : $row['status'],
                date('M d, Y h:i A', strtotime($row['updated_at']))
            ));
        }
    }
    fclose($output);
    exit();
}

// Inventory Add/Update/Delete handlers
if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $cat_name = trim($_POST['category_name']);
    if (!empty($cat_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $self . "?tab=categories_markings&msg=cat_added");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_category') {
    $cat_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $self . "?tab=categories_markings&msg=cat_deleted");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'add_size') {
    $size_name = trim($_POST['size_name']);
    if (!empty($size_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO sizes (name) VALUES (?)");
        $stmt->bind_param("s", $size_name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $self . "?tab=categories_markings&msg=size_added");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_size') {
    $size_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM sizes WHERE id = ?");
    $stmt->bind_param("i", $size_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $self . "?tab=categories_markings&msg=size_deleted");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'add_marking') {
    $m_name = trim($_POST['marking_name']);
    if (!empty($m_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO markings (name) VALUES (?)");
        $stmt->bind_param("s", $m_name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $self . "?tab=categories_markings&msg=marking_added");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_marking') {
    $m_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM markings WHERE id = ?");
    $stmt->bind_param("i", $m_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $self . "?tab=categories_markings&msg=marking_deleted");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'create_item') {
    $item_name   = trim($_POST['item_name']);
    $category    = trim($_POST['category']);
    $size        = trim($_POST['size']);
    $is_roll     = isset($_POST['is_roll']) ? 1 : 0;
    $roll_length = ($is_roll && !empty($_POST['roll_length'])) ? (float)$_POST['roll_length'] : NULL;
    $quantity    = (int)$_POST['quantity'];
    $price       = 0.00;
    $active_user = $_SESSION['name']; // Strict identity lock

    if (!empty($category)) {
        $c_stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $c_stmt->bind_param("s", $category);
        $c_stmt->execute();
        $c_stmt->close();
    }
    if (!empty($size)) {
        $s_stmt = $conn->prepare("INSERT IGNORE INTO sizes (name) VALUES (?)");
        $s_stmt->bind_param("s", $size);
        $s_stmt->execute();
        $s_stmt->close();
    }

    $sku = generateSKU($conn);

    $stmt = $conn->prepare("INSERT INTO items (item_name, sku, category, size, is_roll, roll_length, quantity, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiddi", $item_name, $sku, $category, $size, $is_roll, $roll_length, $quantity, $price);
    
    if ($stmt->execute()) {
        $new_item_id = $stmt->insert_id;
        $stmt->close();

        if ($quantity > 0) {
            $log_desc = "Added new stock item: " . $item_name . " (SKU: " . $sku . ") with initial qty: " . $quantity . " by " . $active_user;
            $log_stmt = $conn->prepare("INSERT INTO transaction_logs (transaction_type, reference_id, description, quantity_change) VALUES ('ADDED_STOCK', ?, ?, ?)");
            $log_stmt->bind_param("isi", $new_item_id, $log_desc, $quantity);
            $log_stmt->execute();
            $log_stmt->close();
        }
        header("Location: " . $self . "?tab=items&msg=item_added");
    } else {
        $stmt->close();
        header("Location: " . $self . "?tab=items&msg=duplicate_sku_error");
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'update_item') {
    $id          = (int)$_POST['id'];
    $item_name   = trim($_POST['item_name']);
    $category    = trim($_POST['category']);
    $size        = trim($_POST['size']);
    $is_roll     = isset($_POST['is_roll']) ? 1 : 0;
    $roll_length = ($is_roll && !empty($_POST['roll_length'])) ? (float)$_POST['roll_length'] : NULL;
    $quantity    = (int)$_POST['quantity'];
    $price       = 0.00;
    $active_user = $_SESSION['name']; // Strict identity lock

    $old_q_stmt = $conn->prepare("SELECT quantity, item_name, sku FROM items WHERE id = ?");
    $old_q_stmt->bind_param("i", $id);
    $old_q_stmt->execute();
    $old_data = $old_q_stmt->get_result()->fetch_assoc();
    $old_q_stmt->close();

    $stmt = $conn->prepare("UPDATE items SET item_name=?, category=?, size=?, is_roll=?, roll_length=?, quantity=?, price=? WHERE id=?");
    $stmt->bind_param("sssididi", $item_name, $category, $size, $is_roll, $roll_length, $quantity, $price, $id);
    $stmt->execute();
    $stmt->close();

    if ($old_data && $old_data['quantity'] != $quantity) {
        $diff = $quantity - $old_data['quantity'];
        $type = ($diff > 0) ? 'ADDED_STOCK' : 'DEDUCTED_STOCK';
        $log_desc = "Updated item stock (" . $item_name . "): adjusted from " . $old_data['quantity'] . " to " . $quantity . " by " . $active_user;
        $log_stmt = $conn->prepare("INSERT INTO transaction_logs (transaction_type, reference_id, description, quantity_change) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("sisi", $type, $id, $log_desc, $diff);
        $log_stmt->execute();
        $log_stmt->close();
    }
    header("Location: " . $self . "?tab=items&msg=item_updated");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_item') {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $self . "?tab=items&msg=item_deleted");
    exit();
}

// Request and Approval Logic
if (isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $item_id          = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : NULL;
    $active_user      = $_SESSION['name']; // Extracts directly from secure session
    $branch           = trim($_POST['branch']); 
    $req_qty          = (int)$_POST['quantity'];
    $purpose          = trim($_POST['purpose']);
    $priority         = $_POST['priority'] ?? 'Willing to Wait';
    $target_date      = !empty($_POST['target_date']) ? $_POST['target_date'] : NULL;
    $pickup_date      = !empty($_POST['pickup_date']) ? $_POST['pickup_date'] : NULL;
    $marking          = trim($_POST['marking'] ?? '');
    $care_of          = trim($_POST['care_of'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');
    $request_type     = $_POST['request_type'] ?? 'STANDARD';
    $paper_item_name  = trim($_POST['paper_item_name'] ?? '');
    $special_cut_size = trim($_POST['special_cut_size'] ?? '');
    $width            = !empty($_POST['width']) ? (float)$_POST['width'] : NULL;
    $height           = !empty($_POST['height']) ? (float)$_POST['height'] : NULL;
    $unit             = trim($_POST['unit'] ?? '');

    $stmt = $conn->prepare("INSERT INTO stock_requests (item_id, requested_by, branch, quantity, purpose, priority, target_date, pickup_date, request_type, paper_item_name, special_cut_size, width, height, unit, marking, care_of, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississssssddsssss", $item_id, $active_user, $branch, $req_qty, $purpose, $priority, $target_date, $pickup_date, $request_type, $paper_item_name, $special_cut_size, $width, $height, $unit, $marking, $care_of, $remarks);
    $stmt->execute();
    $new_req_id = $stmt->insert_id;
    $stmt->close();

    $log_desc = "Stock request submitted by " . $active_user . " (c/o " . $care_of . ") from " . $branch . " (Qty: " . $req_qty . ")";
    $log_stmt = $conn->prepare("INSERT INTO transaction_logs (transaction_type, reference_id, description, quantity_change) VALUES ('MAKATI_REQUEST', ?, ?, ?)");
    $log_stmt->bind_param("isi", $new_req_id, $log_desc, $req_qty);
    $log_stmt->execute();
    $log_stmt->close();

    $redirect_tab = $_POST['redirect_tab'] ?? 'queue';
    header("Location: " . $self . "?tab=" . $redirect_tab . "&msg=req_created");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'approve_request') {
    $req_id = (int)$_GET['id'];
    $ref_tab = $_GET['tab'] ?? 'queue';
    $active_user = $_SESSION['name']; // Strict identity lock

    $req_stmt = $conn->prepare("SELECT r.*, i.quantity as stock_qty, i.item_name FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id WHERE r.id = ? AND r.status = 'Pending'");
    $req_stmt->bind_param("i", $req_id);
    $req_stmt->execute();
    $request = $req_stmt->get_result()->fetch_assoc();
    $req_stmt->close();

    if ($request) {
        $can_approve = true;
        if (!empty($request['item_id']) && $request['item_id'] > 0 && $request['request_type'] !== 'SPECIAL') {
            if ($request['stock_qty'] >= $request['quantity']) {
                $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                $deduct_stmt->bind_param("ii", $request['quantity'], $request['item_id']);
                $deduct_stmt->execute();
                $deduct_stmt->close();
            } else {
                $can_approve = false;
            }
        }

        if ($can_approve) {
            $app_stmt = $conn->prepare("UPDATE stock_requests SET status = 'Approved', approved_by = ?, approved_at = CURRENT_TIMESTAMP WHERE id = ?");
            $app_stmt->bind_param("si", $active_user, $req_id);
            $app_stmt->execute();
            $app_stmt->close();

            $item_label = $request['item_name'] ?? $request['paper_item_name'] ?? 'Special Item';
            $log_desc = "Approved request #" . $req_id . " from " . $request['branch'] . " by " . $active_user . ". Deducted stocks for: " . $item_label;
            $neg_qty = -abs($request['quantity']);
            $log_stmt = $conn->prepare("INSERT INTO transaction_logs (transaction_type, reference_id, description, quantity_change) VALUES ('DEDUCTED_STOCK', ?, ?, ?)");
            $log_stmt->bind_param("isi", $req_id, $log_desc, $neg_qty);
            $log_stmt->execute();
            $log_stmt->close();

            header("Location: " . $self . "?tab=" . $ref_tab . "&msg=req_approved");
            exit();
        } else {
            header("Location: " . $self . "?tab=" . $ref_tab . "&msg=insufficient_stock");
            exit();
        }
    } else {
        header("Location: " . $self . "?tab=" . $ref_tab);
        exit();
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'bulk_approve') {
    $ref_tab = $_POST['tab'] ?? 'queue';
    $active_user = $_SESSION['name']; // Strict identity lock
    
    if (!empty($_POST['request_ids']) && is_array($_POST['request_ids'])) {
        $success_count = 0;
        $failed_stock_count = 0;

        foreach ($_POST['request_ids'] as $req_id) {
            $req_id = (int)$req_id;
            
            $req_stmt = $conn->prepare("SELECT r.*, i.quantity as stock_qty, i.item_name FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id WHERE r.id = ? AND r.status = 'Pending'");
            $req_stmt->bind_param("i", $req_id);
            $req_stmt->execute();
            $request = $req_stmt->get_result()->fetch_assoc();
            $req_stmt->close();

            if ($request) {
                $can_approve = true;
                if (!empty($request['item_id']) && $request['item_id'] > 0 && $request['request_type'] !== 'SPECIAL') {
                    if ($request['stock_qty'] >= $request['quantity']) {
                        $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                        $deduct_stmt->bind_param("ii", $request['quantity'], $request['item_id']);
                        $deduct_stmt->execute();
                        $deduct_stmt->close();
                    } else {
                        $can_approve = false;
                    }
                }

                if ($can_approve) {
                    $app_stmt = $conn->prepare("UPDATE stock_requests SET status = 'Approved', approved_by = ?, approved_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $app_stmt->bind_param("si", $active_user, $req_id);
                    $app_stmt->execute();
                    $app_stmt->close();

                    $item_label = $request['item_name'] ?? $request['paper_item_name'] ?? 'Special Item';
                    $log_desc = "Bulk approved request #" . $req_id . " from " . $request['branch'] . " by " . $active_user . ". Deducted stocks for: " . $item_label;
                    $neg_qty = -abs($request['quantity']);
                    $log_stmt = $conn->prepare("INSERT INTO transaction_logs (transaction_type, reference_id, description, quantity_change) VALUES ('DEDUCTED_STOCK', ?, ?, ?)");
                    $log_stmt->bind_param("isi", $req_id, $log_desc, $neg_qty);
                    $log_stmt->execute();
                    $log_stmt->close();

                    $success_count++;
                } else {
                    $failed_stock_count++;
                }
            }
        }

        if ($failed_stock_count > 0) {
            header("Location: " . $self . "?tab=" . $ref_tab . "&msg=bulk_partial_stock");
        } else {
            header("Location: " . $self . "?tab=" . $ref_tab . "&msg=bulk_approved");
        }
        exit();
    } else {
        header("Location: " . $self . "?tab=" . $ref_tab);
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'reject_request') {
    $req_id = (int)$_GET['id'];
    $ref_tab = $_GET['tab'] ?? 'queue';
    $active_user = $_SESSION['name']; // Strict identity lock

    $stmt = $conn->prepare("UPDATE stock_requests SET status = 'Rejected' WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $stmt->close();
    
    $log_desc = "Rejected request #" . $req_id . " by " . $active_user;
    $log_stmt = $conn->prepare("INSERT INTO transaction_logs (transaction_type, reference_id, description, quantity_change) VALUES ('REJECTED_REQUEST', ?, ?, 0)");
    $log_stmt->bind_param("is", $req_id, $log_desc);
    $log_stmt->execute();
    $log_stmt->close();

    header("Location: " . $self . "?tab=" . $ref_tab . "&msg=req_rejected");
    exit();
}

// =========================================================
// DATA FETCHING QUERIES
// =========================================================
$categories    = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$sizes_list    = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$markings_list = $conn->query("SELECT * FROM markings ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$items         = $conn->query("SELECT * FROM items ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

$orders_res = $conn->query("
    SELECT r.item_id, r.quantity, r.status, r.created_at, r.target_date, r.updated_at, r.priority, r.approved_by, r.shipped_by, r.confirmed_by, r.received_by,
           c.company_name, r.branch, r.requested_by
    FROM stock_requests r
    LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.item_id IS NOT NULL
    ORDER BY FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'), COALESCE(r.target_date, r.created_at) ASC
");
$item_orders_map = [];
if ($orders_res) {
    while ($row = $orders_res->fetch_assoc()) {
        $item_orders_map[$row['item_id']][] = $row;
    }
}

$regular_items = [];
$roll_items = [];
foreach ($items as $it) {
    if (!empty($it['is_roll']) && $it['is_roll'] == 1) {
        $roll_items[] = $it;
    } else {
        $regular_items[] = $it;
    }
}

$next_preview_sku = generateSKU($conn);

$queue_res = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, 
           COALESCE(i.sku, 'SPECIAL') AS sku, COALESCE(i.quantity, 0) as available_stock 
    FROM stock_requests r 
    LEFT JOIN items i ON r.item_id = i.id 
    LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.status = 'Pending'
    ORDER BY FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'), r.id ASC
");
$queue_requests = ($queue_res) ? $queue_res->fetch_all(MYSQLI_ASSOC) : array();

$production_items = array();
foreach ($queue_requests as $q_req) {
    if ($q_req['request_type'] === 'SPECIAL' || $q_req['available_stock'] < $q_req['quantity']) {
        $item_key = !empty($q_req['item_id']) ? 'ITEM_' . $q_req['item_id'] : 'SPEC_' . $q_req['id'];
        
        $dim_label = '';
        if ($q_req['request_type'] === 'SPECIAL') {
            $dim_label = ' (' . $q_req['width'] . 'x' . $q_req['height'] . ' ' . $q_req['unit'] . ')';
        }

        if (!isset($production_items[$item_key])) {
            $shortage = ($q_req['request_type'] === 'SPECIAL') ? $q_req['quantity'] : max(0, $q_req['quantity'] - $q_req['available_stock']);
            $production_items[$item_key] = array(
                'name'            => $q_req['item_name'] . $dim_label,
                'sku'             => $q_req['sku'],
                'type'            => $q_req['request_type'],
                'requested_qty'   => $q_req['quantity'],
                'available_stock' => $q_req['available_stock'],
                'shortage_qty'    => $shortage,
                'rush_count'      => ($q_req['priority'] === 'Rush') ? 1 : 0
            );
        } else {
            $production_items[$item_key]['requested_qty'] += $q_req['quantity'];
            if ($q_req['request_type'] !== 'SPECIAL') {
                $production_items[$item_key]['shortage_qty'] = max(0, $production_items[$item_key]['requested_qty'] - $production_items[$item_key]['available_stock']);
            } else {
                $production_items[$item_key]['shortage_qty'] += $q_req['quantity'];
            }
            if ($q_req['priority'] === 'Rush') {
                $production_items[$item_key]['rush_count']++;
            }
        }
    }
}

$special_cut_res = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, 
           COALESCE(i.sku, 'SPECIAL') AS sku, COALESCE(i.quantity, 0) as available_stock 
    FROM stock_requests r 
    LEFT JOIN items i ON r.item_id = i.id 
    LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.request_type = 'SPECIAL' OR i.category LIKE '%Special Cut%' OR i.size LIKE '%Special Cut%' OR r.purpose LIKE '%Special Cut%'
    ORDER BY r.id DESC
");
$special_cut_orders = ($special_cut_res) ? $special_cut_res->fetch_all(MYSQLI_ASSOC) : array();

$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$my_orders = array();
$total_pages = 1;

if (!empty($search_name)) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM stock_requests r LEFT JOIN companies c ON r.company_id = c.id WHERE r.requested_by LIKE ? OR r.branch LIKE ? OR c.company_name LIKE ? OR r.care_of LIKE ?");
    $like_val = "%" . $search_name . "%";
    $count_stmt->bind_param("ssss", $like_val, $like_val, $like_val, $like_val);
    $count_stmt->execute();
    $total_row = $count_stmt->get_result()->fetch_assoc();
    $total_records = $total_row['total'];
    $count_stmt->close();

    $total_pages = ceil($total_records / $limit) ?: 1;

    $my_stmt = $conn->prepare("
        SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, 
               COALESCE(i.sku, 'SPECIAL') AS sku, COALESCE(i.quantity, 0) as available_stock 
        FROM stock_requests r 
        LEFT JOIN items i ON r.item_id = i.id 
        LEFT JOIN companies c ON r.company_id = c.id
        WHERE r.requested_by LIKE ? OR r.branch LIKE ? OR c.company_name LIKE ? OR r.care_of LIKE ?
        ORDER BY r.id DESC LIMIT ? OFFSET ?
    ");
    $my_stmt->bind_param("ssssii", $like_val, $like_val, $like_val, $like_val, $limit, $offset);
    $my_stmt->execute();
    $my_orders = $my_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $my_stmt->close();
}

$history_page = isset($_GET['h_page']) ? (int)$_GET['h_page'] : 1;
$history_limit = 10;
$history_offset = ($history_page - 1) * $history_limit;

$history_count_res = $conn->query("SELECT COUNT(*) as total FROM stock_requests");
$history_total_records = $history_count_res->fetch_assoc()['total'];
$history_total_pages = ceil($history_total_records / $history_limit) ?: 1;

$all_requests_res = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, 
           COALESCE(i.sku, 'SPECIAL') AS sku, COALESCE(i.quantity, 0) as available_stock 
    FROM stock_requests r 
    LEFT JOIN items i ON r.item_id = i.id 
    LEFT JOIN companies c ON r.company_id = c.id
    ORDER BY r.id DESC LIMIT $history_limit OFFSET $history_offset
");
$all_requests = ($all_requests_res) ? $all_requests_res->fetch_all(MYSQLI_ASSOC) : array();

$filter_type = $_GET['filter_type'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

$git_query = "SELECT * FROM transaction_logs WHERE 1=1";
$git_params = [];
$git_types = "";

if (!empty($filter_type)) {
    $git_query .= " AND transaction_type = ?";
    $git_params[] = $filter_type;
    $git_types .= "s";
}
if (!empty($filter_start)) {
    $git_query .= " AND DATE(created_at) >= ?";
    $git_params[] = $filter_start;
    $git_types .= "s";
}
if (!empty($filter_end)) {
    $git_query .= " AND DATE(created_at) <= ?";
    $git_params[] = $filter_end;
    $git_types .= "s";
}

$git_query .= " ORDER BY id DESC LIMIT 100";

if (!empty($git_params)) {
    $git_stmt = $conn->prepare($git_query);
    $git_stmt->bind_param($git_types, ...$git_params);
    $git_stmt->execute();
    $git_logs = $git_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $git_stmt->close();
} else {
    $git_res = $conn->query($git_query);
    $git_logs = ($git_res) ? $git_res->fetch_all(MYSQLI_ASSOC) : [];
}

$pending_req_count = count($queue_requests);
$rush_count = 0;
foreach($queue_requests as $qr) {
    if($qr['priority'] === 'Rush') $rush_count++;
}

$total_stock = 0;
foreach ($items as $item) {
    $total_stock += $item['quantity'];
}

$today_str = date('Y-m-d');
$deducted_today_res = $conn->query("
    SELECT SUM(ABS(quantity_change)) as total_deducted 
    FROM transaction_logs 
    WHERE (transaction_type = 'DEDUCTED_STOCK' OR quantity_change < 0) AND DATE(created_at) = '$today_str'
");
$total_deducted_today = $deducted_today_res ? ($deducted_today_res->fetch_assoc()['total_deducted'] ?? 0) : 0;

$limit_shipping = 10;
$page_approved = isset($_GET['page_approved']) ? (int)$_GET['page_approved'] : 1;
$page_scheduled = isset($_GET['page_scheduled']) ? (int)$_GET['page_scheduled'] : 1;
$page_shipped = isset($_GET['page_shipped']) ? (int)$_GET['page_shipped'] : 1;

$offset_approved = ($page_approved - 1) * $limit_shipping;
$offset_scheduled = ($page_scheduled - 1) * $limit_shipping;
$offset_shipped = ($page_shipped - 1) * $limit_shipping;

$sort_stage1 = $_GET['sort_stage1'] ?? 'priority';
$stage1_order_sql = "FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'), COALESCE(r.target_date, r.created_at) ASC";
if ($sort_stage1 === 'date_asc') {
    $stage1_order_sql = "COALESCE(r.target_date, r.created_at) ASC, FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait')";
} elseif ($sort_stage1 === 'date_desc') {
    $stage1_order_sql = "COALESCE(r.target_date, r.created_at) DESC, FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait')";
}

$count_approved_res = $conn->query("SELECT COUNT(*) as c FROM stock_requests WHERE status = 'Approved'");
$total_pages_approved = ceil(($count_approved_res->fetch_assoc()['c']) / $limit_shipping) ?: 1;

$approved_res = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, COALESCE(i.sku, 'SPECIAL') AS sku
    FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.status = 'Approved' 
    ORDER BY $stage1_order_sql 
    LIMIT $limit_shipping OFFSET $offset_approved
");
$approved_orders = ($approved_res) ? $approved_res->fetch_all(MYSQLI_ASSOC) : [];

$count_scheduled_res = $conn->query("SELECT COUNT(*) as c FROM stock_requests WHERE status = 'Scheduled'");
$total_pages_scheduled = ceil(($count_scheduled_res->fetch_assoc()['c']) / $limit_shipping) ?: 1;

$scheduled_res = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, COALESCE(i.sku, 'SPECIAL') AS sku
    FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.status = 'Scheduled' 
    ORDER BY FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'), COALESCE(r.target_date, r.updated_at) ASC 
    LIMIT $limit_shipping OFFSET $offset_scheduled
");
$scheduled_orders = ($scheduled_res) ? $scheduled_res->fetch_all(MYSQLI_ASSOC) : [];

$count_shipped_res = $conn->query("SELECT COUNT(*) as c FROM stock_requests WHERE status IN ('Shipped', 'Shipping Already Accepted')");
$total_pages_shipped = ceil(($count_shipped_res->fetch_assoc()['c']) / $limit_shipping) ?: 1;

$shipped_res = $conn->query("
    SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, COALESCE(i.sku, 'SPECIAL') AS sku
    FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.status IN ('Shipped', 'Shipping Already Accepted') 
    ORDER BY r.updated_at DESC 
    LIMIT $limit_shipping OFFSET $offset_shipped
");
$shipped_orders = ($shipped_res) ? $shipped_res->fetch_all(MYSQLI_ASSOC) : [];

$page_completed = isset($_GET['page_completed']) ? (int)$_GET['page_completed'] : 1;
$limit_completed = 10;
$offset_completed = ($page_completed - 1) * $limit_completed;

$count_completed_res = $conn->query("
    SELECT COUNT(*) as c 
    FROM stock_requests r 
    WHERE r.status IN ('Approved', 'Scheduled', 'Shipped', 'Shipping Already Accepted', 'Completed', 'Delivered')
");
$completed_total_records = $count_completed_res->fetch_assoc()['c'] ?? 0;
$total_pages_completed = ceil($completed_total_records / $limit_completed) ?: 1;
$completed_count = $completed_total_records;

$completed_res = $conn->query("
    SELECT r.*, c.company_name, r.approved_by, r.confirmed_by, r.shipped_by, r.received_by, r.approved_at, r.shipped_at,
           COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, 
           COALESCE(i.sku, 'SPECIAL') AS sku
    FROM stock_requests r 
    LEFT JOIN items i ON r.item_id = i.id 
    LEFT JOIN companies c ON r.company_id = c.id
    WHERE r.status IN ('Approved', 'Scheduled', 'Shipped', 'Shipping Already Accepted', 'Completed', 'Delivered')
    ORDER BY r.updated_at DESC
    LIMIT $limit_completed OFFSET $offset_completed
");
$completed_orders = ($completed_res) ? $completed_res->fetch_all(MYSQLI_ASSOC) : [];
?>