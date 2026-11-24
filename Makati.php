<?php
// =========================================================
// SECURITY & SESSION CONFIGURATION (PHILIPPINE TIME)
// =========================================================
session_start();

// LOGIN SESSION GUARD: I-block kapag walang valid na session
if (!isset($_SESSION['logged_in']) || $_SESSION['branch'] !== 'makati') {
    header("Location: makati_login.php"); // FIX: Corrected target login file
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Disable error display for production security
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ITAMA ANG TIMEZONE PARA SA PHILIPPINES
date_default_timezone_set('Asia/Manila');

// Database Credentials
$host   = "sql306.infinityfree.com"; 
$user   = "if0_42844444";            
$pass   = "JSCAgAw4uVlFoE";          
$dbname = "if0_42844444_db_triplexinventorystructurebicutan"; 

$conn = @new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("<div style='font-family: sans-serif; padding: 20px; background: #ffeeef; color: #c00; border: 1px solid #f99; border-radius: 8px;'>
            <h3>Database Connection Error</h3>
            <p>The system is currently unavailable. Please try again later.</p>
         </div>");
}
$conn->set_charset("utf8mb4");

// Itakda rin ang MySQL session timezone sa Philippine Time (+08:00) para sa mga NOW() queries
$conn->query("SET time_zone = '+08:00'");

// =========================================================
// DATABASE STRUCTURE: TABLES & ALTERATIONS
// =========================================================

$conn->query("CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) DEFAULT NULL,
    item_name VARCHAR(255) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS request_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_reference VARCHAR(50) NOT NULL UNIQUE,
    company_id INT DEFAULT NULL,
    requested_by VARCHAR(255) NOT NULL,
    priority VARCHAR(50) DEFAULT 'Willing to Wait',
    target_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS stock_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT DEFAULT NULL,
    requested_by VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    purpose TEXT,
    priority VARCHAR(50) DEFAULT 'Willing to Wait',
    target_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS markings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE IF NOT EXISTS salespersons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)");

// SEED INITIAL MARKINGS
$mark_check = $conn->query("SELECT COUNT(*) as c FROM markings");
if ($mark_check && $mark_check->fetch_assoc()['c'] == 0) {
    $conn->query("INSERT IGNORE INTO markings (name) VALUES 
        ('Pang-uwi'), 
        ('Deliver'), 
        ('Pick-up'), 
        ('Rolls / Jumbo Roll'), 
        ('Bond Paper Roll'), 
        ('Special Cut Roll')");
}

$columns_to_check = [
    'company_id' => "INT DEFAULT NULL",
    'batch_reference' => "VARCHAR(50) DEFAULT NULL",
    'target_date' => "DATE DEFAULT NULL",
    'request_type' => "VARCHAR(50) DEFAULT 'STANDARD'",
    'paper_item_name' => "VARCHAR(255) DEFAULT NULL",
    'special_cut_size' => "VARCHAR(100) DEFAULT NULL",
    'width' => "DECIMAL(10,2) DEFAULT NULL",
    'height' => "DECIMAL(10,2) DEFAULT NULL",
    'unit' => "VARCHAR(50) DEFAULT NULL",
    'marking' => "VARCHAR(100) DEFAULT NULL",
    'care_of' => "VARCHAR(255) DEFAULT NULL",
    'manifesting_code' => "VARCHAR(100) DEFAULT NULL",
    'received_at' => "DATETIME DEFAULT NULL",
    'receiving_status' => "VARCHAR(50) DEFAULT 'For Receiving'"
];

foreach ($columns_to_check as $column => $definition) {
    $check_col = $conn->query("SHOW COLUMNS FROM stock_requests LIKE '$column'");
    if ($check_col->num_rows == 0) {
        $conn->query("ALTER TABLE stock_requests ADD COLUMN $column $definition");
    }
}

// FIXED: Now reads the msg from the URL for the PRG pattern
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'create';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$limit = 10;
$page_inventory = isset($_GET['p_inv']) ? max(1, (int)$_GET['p_inv']) : 1;
$offset_inventory = ($page_inventory - 1) * $limit;

// =========================================================
// CSV BACKUP GENERATOR (NO PAGINATION)
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $filename = "makati_transactions_backup_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Output standard CSV column headers
    fputcsv($output, [
        'Transaction / Request Number',
        'Company',
        'Item ID / SKU',
        'Item Name',
        'Quantity',
        'Transaction Status',
        'Date Created',
        'Date Approved',
        'Date Shipped',
        'Date Received',
        'Requested By',
        'Approved By',
        'Shipped By',
        'Received By',
        'Manifest ID',
        'Priority',
        'Request Type',
        'Marking',
        'Care Of',
        'Special Cut Details'
    ]);
    
    // Query all transactions
    $query = "SELECT r.*, c.company_name, i.sku, 
              COALESCE(i.item_name, r.paper_item_name, 'Special Cut') AS resolved_item_name 
              FROM stock_requests r 
              LEFT JOIN companies c ON r.company_id = c.id 
              LEFT JOIN items i ON r.item_id = i.id 
              ORDER BY r.created_at DESC";
              
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $special_details = ($row['request_type'] === 'SPECIAL') 
                ? $row['width'] . 'x' . $row['height'] . ' ' . $row['unit'] 
                : 'N/A';
                
            fputcsv($output, [
                $row['batch_reference'],
                $row['company_name'] ?? 'N/A',
                $row['sku'] ?? 'SPECIAL',
                $row['resolved_item_name'],
                $row['quantity'],
                $row['status'],
                $row['created_at'],
                '', // Date Approved (Not explicitly tracked in schema)
                '', // Date Shipped (Not explicitly tracked in schema)
                $row['received_at'],
                $row['requested_by'],
                '', // Approved By (Not explicitly tracked in schema)
                '', // Shipped By (Not explicitly tracked in schema)
                '', // Received By (Not explicitly tracked in schema)
                $row['manifesting_code'],
                $row['priority'],
                $row['request_type'],
                $row['marking'],
                $row['care_of'],
                $special_details
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// =========================================================
// CRUD LOGIC FOR MARKINGS, SALESPERSONS, AND SHIPMENTS
// =========================================================

if (isset($_POST['action']) && $_POST['action'] === 'confirm_receiving') {
    $req_id = (int)$_POST['req_id'];
    $manifesting_code = trim($_POST['manifesting_code']);
    
    if ($req_id > 0 && !empty($manifesting_code)) {
        $stmt = $conn->prepare("UPDATE stock_requests SET manifesting_code = ?, received_at = NOW(), receiving_status = 'Manifested', status = 'Completed' WHERE id = ? AND status IN ('Approved', 'Shipped', 'Shipping Already Accepted')");
        $stmt->bind_param("si", $manifesting_code, $req_id);
        $stmt->execute();
        $stmt->close();
        header("Location: ?tab=tracking&msg=shipping_accepted");
        exit();
    } else {
        header("Location: ?tab=tracking&msg=validation_failed");
        exit();
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'accept_shipping') {
    $req_id = (int)$_POST['req_id'];
    $stmt = $conn->prepare("UPDATE stock_requests SET status = 'Shipping Already Accepted' WHERE id = ? AND status IN ('Approved', 'Shipped')");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?tab=tracking&msg=shipping_accepted");
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
    header("Location: ?tab=markings&msg=marking_added");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'edit_marking') {
    $m_id   = (int)$_POST['marking_id'];
    $m_name = trim($_POST['marking_name']);
    if ($m_id > 0 && !empty($m_name)) {
        $stmt = $conn->prepare("UPDATE markings SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $m_name, $m_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ?tab=markings&msg=marking_updated");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_marking') {
    $m_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM markings WHERE id = ?");
    $stmt->bind_param("i", $m_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?tab=markings&msg=marking_deleted");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'add_salesperson') {
    $s_name = trim($_POST['salesperson_name']);
    if (!empty($s_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO salespersons (name) VALUES (?)");
        $stmt->bind_param("s", $s_name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ?tab=markings&msg=salesperson_added");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'edit_salesperson') {
    $s_id   = (int)$_POST['salesperson_id'];
    $s_name = trim($_POST['salesperson_name']);
    if ($s_id > 0 && !empty($s_name)) {
        $stmt = $conn->prepare("UPDATE salespersons SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $s_name, $s_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ?tab=markings&msg=salesperson_updated");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_salesperson') {
    $s_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM salespersons WHERE id = ?");
    $stmt->bind_param("i", $s_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ?tab=markings&msg=salesperson_deleted");
    exit();
}

$companies_list   = $conn->query("SELECT * FROM companies ORDER BY company_name ASC")->fetch_all(MYSQLI_ASSOC);
$markings_list    = $conn->query("SELECT * FROM markings ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$salesperson_list = $conn->query("SELECT * FROM salespersons ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$items            = $conn->query("SELECT * FROM items ORDER BY item_name ASC")->fetch_all(MYSQLI_ASSOC);

// =========================================================
// HANDLE BULK REQUEST SUBMISSION (STRICT BACKEND VALIDATION)
// =========================================================
if (isset($_POST['action']) && $_POST['action'] === 'submit_bulk_request') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) die("Invalid CSRF token.");

    $requested_by = "Makati Hub User";
    $company_id   = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
    $new_company  = isset($_POST['new_company']) ? trim($_POST['new_company']) : '';
    $priority     = isset($_POST['priority']) ? $_POST['priority'] : 'Willing to Wait';
    $target_date  = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
    $purpose      = "Direct Request from Makati Hub";
    $global_marking = trim($_POST['global_marking'] ?? '');

    // BACKEND VALIDATION PARA SA MARKING AT IBA PA
    if (($company_id === 0 && empty($new_company)) || empty($target_date) || empty($global_marking)) {
        if (empty($global_marking)) {
            header("Location: ?tab=create&msg=missing_marking");
            exit();
        }
        header("Location: ?tab=create&msg=validation_failed");
        exit();
    }

    $req_types          = $_POST['request_types'] ?? [];
    $item_ids           = $_POST['item_ids'] ?? [];
    $paper_names        = $_POST['paper_names'] ?? [];
    $cut_sizes          = $_POST['cut_sizes'] ?? [];
    $widths             = $_POST['widths'] ?? [];
    $heights            = $_POST['heights'] ?? [];
    $units              = $_POST['units'] ?? [];
    $care_ofs_text      = $_POST['care_ofs_text'] ?? [];
    $care_of_modes      = $_POST['care_of_modes'] ?? [];
    $quantities         = $_POST['quantities'] ?? [];

    if (empty($quantities)) {
        header("Location: ?tab=create&msg=validation_failed");
        exit();
    }

    for ($i = 0; $i < count($quantities); $i++) {
        $qty    = (int)($quantities[$i] ?? 0);
        $r_type = $req_types[$i] ?? 'STANDARD';
        // Awtomatikong kukunin ang session name para sa c/o
        $c_of   = trim($_SESSION['name'] ?? 'Makati Hub User');

        if ($qty <= 0) {
            header("Location: ?tab=create&msg=invalid_qty");
            exit();
        }

        if (empty($c_of)) {
            header("Location: ?tab=create&msg=missing_care_of");
            exit();
        }

        if ($r_type === 'STANDARD') {
            $it_id = (int)($item_ids[$i] ?? 0);
            if ($it_id <= 0) {
                header("Location: ?tab=create&msg=missing_item");
                exit();
            }
        } else if ($r_type === 'SPECIAL') {
            $p_name = trim($paper_names[$i] ?? '');
            $w      = (float)($widths[$i] ?? 0);
            $h      = (float)($heights[$i] ?? 0);
            if (empty($p_name) || $w <= 0 || $h <= 0) {
                header("Location: ?tab=create&msg=missing_special_details");
                exit();
            }
        }
    }

    $conn->begin_transaction();
    try {
        if ($company_id === 0 && !empty($new_company)) {
            $chk_stmt = $conn->prepare("SELECT id FROM companies WHERE company_name = ?");
            $chk_stmt->bind_param("s", $new_company);
            $chk_stmt->execute();
            $chk_res = $chk_stmt->get_result();
            if ($chk_row = $chk_res->fetch_assoc()) $company_id = $chk_row['id'];
            else {
                $ins_comp = $conn->prepare("INSERT INTO companies (company_name) VALUES (?)");
                $ins_comp->bind_param("s", $new_company);
                $ins_comp->execute();
                $company_id = $ins_comp->insert_id;
                $ins_comp->close();
            }
            $chk_stmt->close();
        }

        $batch_ref = 'REQ-FILE-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');

        $trans_stmt = $conn->prepare("INSERT INTO request_transactions (batch_reference, company_id, requested_by, priority, target_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $trans_stmt->bind_param("sisss", $batch_ref, $company_id, $requested_by, $priority, $target_date);
        $trans_stmt->execute();
        $trans_stmt->close();

        $stmt = $conn->prepare("INSERT INTO stock_requests (item_id, requested_by, company_id, quantity, purpose, priority, target_date, status, batch_reference, request_type, paper_item_name, special_cut_size, width, height, unit, marking, care_of, receiving_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'For Receiving')");
        
        $success_count = 0;
        for ($i = 0; $i < count($quantities); $i++) {
            $r_type = $req_types[$i] ?? 'STANDARD';
            $qty    = (int)$quantities[$i];
            
            // Awtomatikong c/o mula sa session
            $c_of   = trim($_SESSION['name'] ?? 'Makati Hub User');

            $it_id  = ($r_type === 'STANDARD') ? (int)($item_ids[$i] ?? 0) : NULL;
            $p_name = ($r_type === 'SPECIAL') ? trim($paper_names[$i] ?? '') : NULL;
            $c_size = ($r_type === 'SPECIAL') ? trim($cut_sizes[$i] ?? '') : NULL;
            $w      = ($r_type === 'SPECIAL' && !empty($widths[$i])) ? (float)$widths[$i] : NULL;
            $h      = ($r_type === 'SPECIAL' && !empty($heights[$i])) ? (float)$heights[$i] : NULL;
            $u      = ($r_type === 'SPECIAL') ? trim($units[$i] ?? '') : NULL;

            $stmt->bind_param("isiisssssssddsss", $it_id, $requested_by, $company_id, $qty, $purpose, $priority, $target_date, $batch_ref, $r_type, $p_name, $c_size, $w, $h, $u, $global_marking, $c_of);
            if ($stmt->execute()) $success_count++;
        }
        $stmt->close();
        
        // FIXED: Replaced direct variable assignment with HTTP Redirects to prevent form resubmission
        if ($success_count > 0) {
            $conn->commit();
            header("Location: ?tab=create&msg=success_request");
            exit();
        } else {
            $conn->rollback();
            header("Location: ?tab=create&msg=empty_request");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction error: " . $e->getMessage());
        header("Location: ?tab=create&msg=error_request");
        exit();
    }
}

// =========================================================
// FETCH DATA FOR TABS
// =========================================================
$approved_inventory = [];
$total_inventory_records = 0;
$search_sql_filter = " WHERE r.status = 'Approved' ";
if (!empty($search_query)) {
    $esc_q = $conn->real_escape_string(str_replace(['%', '_'], ['\%', '\_'], $search_query));
    $search_sql_filter .= " AND (i.item_name LIKE '%$esc_q%' OR r.paper_item_name LIKE '%$esc_q%' OR r.batch_reference LIKE '%$esc_q%' OR c.company_name LIKE '%$esc_q%' OR t_comp.company_name LIKE '%$esc_q%') ";
}
$count_inv_query = "SELECT COUNT(DISTINCT r.id) as total FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id LEFT JOIN request_transactions t ON r.batch_reference = t.batch_reference LEFT JOIN companies t_comp ON t.company_id = t_comp.id $search_sql_filter";
$count_inv_res = $conn->query($count_inv_query);
if($count_inv_res) $total_inventory_records = (int)$count_inv_res->fetch_assoc()['total'];
$total_inventory_pages = max(1, ceil($total_inventory_records / $limit));

$approved_query = "
    SELECT DISTINCT r.id AS master_id, i.id AS item_catalog_id, COALESCE(i.item_name, r.paper_item_name, 'Special Cut Item') AS item_name, r.quantity, COALESCE(c.company_name, t_comp.company_name, 'N/A') AS company, r.batch_reference AS request_number, r.created_at AS request_date, r.status AS approval_status
    FROM stock_requests r
    LEFT JOIN items i ON r.item_id = i.id LEFT JOIN companies c ON r.company_id = c.id LEFT JOIN request_transactions t ON r.batch_reference = t.batch_reference LEFT JOIN companies t_comp ON t.company_id = t_comp.id
    $search_sql_filter ORDER BY r.id DESC LIMIT $limit OFFSET $offset_inventory
";
$approved_result = @$conn->query($approved_query);
if ($approved_result) {
    $raw_approved = $approved_result->fetch_all(MYSQLI_ASSOC);
    $sequence_counter = $total_inventory_records - $offset_inventory;
    $seen_master_ids = [];
    foreach ($raw_approved as $row) {
        if (in_array($row['master_id'], $seen_master_ids)) continue;
        $seen_master_ids[] = $row['master_id'];
        $row['sku'] = 'BIC-' . str_pad($sequence_counter, 6, '0', STR_PAD_LEFT);
        $sequence_counter--;
        $approved_inventory[] = $row;
    }
}

$queues_data = [];
if ($tab === 'queues') {
    $q_res = $conn->query("
        SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') as item_name, COALESCE(i.sku, 'SPECIAL') as sku 
        FROM stock_requests r LEFT JOIN companies c ON r.company_id = c.id LEFT JOIN items i ON r.item_id = i.id 
        WHERE r.status = 'Pending' ORDER BY FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'), r.created_at ASC
    ");
    if ($q_res) $queues_data = $q_res->fetch_all(MYSQLI_ASSOC);
}

$view_company_id = isset($_GET['view_company']) ? (int)$_GET['view_company'] : null;
$company_orders = [];
$target_company_name = '';
$history_search = isset($_GET['h_search']) ? trim($_GET['h_search']) : '';

if ($view_company_id) {
    $c_q = $conn->prepare("SELECT company_name FROM companies WHERE id = ?");
    $c_q->bind_param("i", $view_company_id);
    $c_q->execute();
    $target_company_name = $c_q->get_result()->fetch_assoc()['company_name'] ?? '';
    $c_q->close();

    $o_q = $conn->prepare("
        SELECT r.*, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') AS item_name, COALESCE(i.sku, 'SPECIAL') as original_sku 
        FROM stock_requests r 
        LEFT JOIN items i ON r.item_id = i.id 
        WHERE r.company_id = ? 
        ORDER BY r.id DESC
    ");
    $o_q->bind_param("i", $view_company_id);
    $o_q->execute();
    $company_orders = $o_q->get_result()->fetch_all(MYSQLI_ASSOC);
    $o_q->close();
}

$history_companies = [];
$hc_sql = "
    SELECT c.id, c.company_name, COUNT(r.id) as total_requests, SUM(r.quantity) as total_quantity, MAX(r.created_at) as latest_request 
    FROM companies c 
    JOIN stock_requests r ON c.id = r.company_id 
";
if (!empty($history_search)) {
    $esc_hs = $conn->real_escape_string($history_search);
    $hc_sql .= " WHERE c.company_name LIKE '%$esc_hs%' ";
}
$hc_sql .= " GROUP BY c.id, c.company_name ORDER BY latest_request DESC";
$hc_res = $conn->query($hc_sql);
if ($hc_res) $history_companies = $hc_res->fetch_all(MYSQLI_ASSOC);

$query_results = [];
$total_queries_pages = 1;
$q_page = isset($_GET['q_page']) ? max(1, (int)$_GET['q_page']) : 1;
$q_limit = 10;
$q_offset = ($q_page - 1) * $q_limit;
$q_company = $_GET['q_company'] ?? '';
$q_start   = $_GET['q_start'] ?? '';
$q_end     = $_GET['q_end'] ?? '';
$q_status  = $_GET['q_status'] ?? '';
$q_search  = $_GET['q_search'] ?? '';

if ($tab === 'queries') {
    $where_sql = " WHERE 1=1";
    $types = ""; $params = [];
    
    if (!empty($q_company)) { $where_sql .= " AND r.company_id = ?"; $types .= "i"; $params[] = $q_company; }
    if (!empty($q_start)) { $where_sql .= " AND DATE(r.created_at) >= ?"; $types .= "s"; $params[] = $q_start; }
    if (!empty($q_end)) { $where_sql .= " AND DATE(r.created_at) <= ?"; $types .= "s"; $params[] = $q_end; }
    if (!empty($q_status)) { $where_sql .= " AND r.status = ?"; $types .= "s"; $params[] = $q_status; }
    if (!empty($q_search)) { 
        $where_sql .= " AND (r.batch_reference LIKE ? OR r.purpose LIKE ? OR i.item_name LIKE ? OR r.status LIKE ? OR r.marking LIKE ? OR r.care_of LIKE ?)"; 
        $types .= "ssssss"; 
        $like_val = "%" . $q_search . "%";
        $params[] = $like_val; $params[] = $like_val; $params[] = $like_val; $params[] = $like_val; $params[] = $like_val; $params[] = $like_val;
    }
    
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM stock_requests r LEFT JOIN items i ON r.item_id = i.id " . $where_sql);
    if (!empty($params)) $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_queries_records = (int)$count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
    
    $total_queries_pages = max(1, ceil($total_queries_records / $q_limit));

    $data_sql = "SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') as item_name, COALESCE(i.sku, 'SPECIAL') as sku 
                 FROM stock_requests r LEFT JOIN companies c ON r.company_id = c.id LEFT JOIN items i ON r.item_id = i.id " . $where_sql . " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
    
    $types .= "ii";
    $params[] = $q_limit; $params[] = $q_offset;

    $stmt = $conn->prepare($data_sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $query_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Request Hub - Makati Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --sidebar-bg: #ffffff; --sidebar-color: #475569; --sidebar-hover-bg: #f8fafc; --sidebar-active-bg: #eff6ff; --sidebar-active-color: #2563eb; --primary-color: #2563eb; }
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; color: #334155; overflow-x: hidden; font-size: 0.88rem; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background: var(--sidebar-bg); color: var(--sidebar-color); border-right: 1px solid #cbd5e1; box-shadow: 4px 0 15px rgba(0, 0, 0, 0.03); display: flex; flex-direction: column; z-index: 1000; }
        .sidebar-brand { padding: 20px; font-size: 1rem; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; }
        .sidebar-menu { list-style: none; padding: 16px 12px; margin: 0; flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column;}
        .sidebar-menu li a { display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--sidebar-color); text-decoration: none; font-weight: 500; font-size: 0.86rem; border-radius: 6px; margin-bottom: 4px; transition: all 0.15s ease; }
        .sidebar-menu li a:hover { background: var(--sidebar-hover-bg); color: #0f172a; }
        .sidebar-menu li.active a { background: var(--sidebar-active-bg); color: var(--sidebar-active-color); font-weight: 600; }
        .main-content { margin-left: var(--sidebar-width); padding: 28px; }
        .card { border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        h2 { letter-spacing: -0.02em; color: #0f172a; font-size: 1.35rem; font-weight: 700; margin-bottom: 16px; }
        .table th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #cbd5e1; padding: 10px 12px; }
        .table td { padding: 10px 12px; color: #334155; font-size: 0.84rem; vertical-align: middle; }
        
        .form-control, 
        .form-select, 
        .input-group .btn, 
        .btn:not(.btn-close) {
            height: 38px !important;
            padding: 6px 12px !important;
            font-size: 0.86rem !important;
            line-height: 1.5 !important;
            display: inline-flex;
            align-items: center;
            box-sizing: border-box;
        }
        .form-control i, .form-select i, .btn i { font-size: 0.86rem !important; width: 1rem; text-align: center; }
        .form-control, .form-select { width: 100%; max-width: 100%; }
        .input-group > .form-control, .input-group > .form-select, .input-group > .btn { height: 38px !important; }

        .btn-primary { background-color: #2563eb; border-color: #2563eb; border-radius: 6px; font-weight: 500; }
        .bic-badge { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; font-family: monospace; font-weight: 600; padding: 3px 6px; border-radius: 4px; font-size: 0.75rem; }
        .badge-special { background-color: #fef08a; color: #854d0e; font-weight: 700; padding: 3px 6px; border-radius: 4px; font-size: 0.7rem; border: 1px solid #fde047; }
        .badge-success-soft { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 3px 8px; border-radius: 4px; font-weight: 500; font-size: 0.75rem; }
        .badge-danger-soft { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 4px; font-weight: 500; font-size: 0.75rem; }
        .badge-warning-soft { background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 4px; font-weight: 500; font-size: 0.75rem; }
        .badge-rush { background-color: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; font-weight: 700; padding: 2px 5px; border-radius: 4px; font-size: 0.7rem; }
        .badge-mark { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; padding: 3px 6px; border-radius: 4px; font-size: 0.72rem; font-weight: 600; display: inline-block; }
        .badge-co { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 3px 6px; border-radius: 4px; font-size: 0.72rem; font-weight: 600; display: inline-block; }
        .align-items-center-custom { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-top: 2px; }
        
        @media print {
            .sidebar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="sidebar no-print">
    <div class="sidebar-brand"><i class="fa-solid fa-boxes-stacked text-primary"></i> Makati Inventory Hub</div>
    <ul class="sidebar-menu">
        <li class="<?php echo $tab=='create'?'active':''; ?>"><a href="?tab=create"><i class="fa-solid fa-cart-plus"></i> Create Request</a></li>
        <li class="<?php echo $tab=='queues'?'active':''; ?>"><a href="?tab=queues"><i class="fa-solid fa-hourglass-half"></i> Queues</a></li>
        <li class="<?php echo $tab=='inventory'?'active':''; ?>"><a href="?tab=inventory"><i class="fa-solid fa-warehouse"></i> Inventory</a></li>
        <li class="<?php echo $tab=='tracking'?'active':''; ?>"><a href="?tab=tracking"><i class="fa-solid fa-truck-fast"></i> Tracking & Receiving</a></li>
        <li class="<?php echo $tab=='history'?'active':''; ?>"><a href="?tab=history"><i class="fa-solid fa-clock-rotate-left"></i> Request History</a></li>
        <li class="<?php echo $tab=='queries'?'active':''; ?>"><a href="?tab=queries"><i class="fa-solid fa-magnifying-glass-chart"></i> Companies & Queries</a></li>
        <li class="<?php echo $tab=='markings'?'active':''; ?>"><a href="?tab=markings"><i class="fa-solid fa-tags"></i> Markings Config</a></li>
        
        <li>
            <a href="?action=export_csv" class="text-success fw-bold" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; margin-top: 10px;">
                <i class="fa-solid fa-file-csv"></i> Generate Backup CSV
            </a>
        </li>
        
        <!-- Naka-lagay na ang Username sa tabi ng user icon bago ang Logout -->
        <li class="mt-auto px-3 py-2 text-muted small border-top pt-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-circle text-primary fs-5"></i>
            <div style="line-height: 1.2;">
                <span class="d-block text-dark fw-bold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Makati User'); ?></span>
                <span style="font-size: 0.72rem;">Makati Hub</span>
            </div>
        </li>
        <li><a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content">

    <!-- ALERT MESSAGES -->
    <?php if($msg === 'success_request'): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 bg-success bg-opacity-10 text-success py-2 px-3 small mb-3 no-print" role="alert">Request successfully logged and consolidated!<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'shipping_accepted'): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 bg-success bg-opacity-10 text-success py-2 px-3 small mb-3 no-print" role="alert">Shipment successfully received and stored with Manifesting Code!<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'missing_marking'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-3 no-print" role="alert"><strong>Bawal i-push through!</strong> Kailangang pumili muna ng <strong>Marking</strong> bago i-submit ang request batch.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'missing_care_of'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-3 no-print" role="alert"><strong>Bawal i-push through!</strong> Kailangang may napili o nakalagay na "c/o" sa lahat ng item.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'missing_item'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-3 no-print" role="alert"><strong>Bawal i-push through!</strong> Kailangang may napiling Item sa lahat ng Standard items.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'missing_special_details'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-3 no-print" role="alert"><strong>Bawal i-push through!</strong> Kumpletuhin ang Material Name, Width, at Height ng Special Cut / Roll items.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'invalid_qty'): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-10 text-danger py-2 px-3 small mb-3 no-print" role="alert"><strong>Bawal i-push through!</strong> Dapat mas mataas sa 0 ang Quantity.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif($msg === 'validation_failed' || $msg === 'empty_request'): ?>
        <div class="alert alert-warning alert-dismissible fade show border-0 bg-warning bg-opacity-10 text-warning py-2 px-3 small mb-3 no-print" role="alert">Please fill in all required fields / valid codes (including batch marking).<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php elseif(strpos($msg, 'added') !== false || strpos($msg, 'updated') !== false || strpos($msg, 'deleted') !== false): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 bg-success bg-opacity-10 text-success py-2 px-3 small mb-3 no-print" role="alert">Configuration successfully saved!<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if($tab === 'create'): ?>
        <h2 class="fw-bold"><i class="fa-solid fa-cart-plus text-primary"></i> Create Consolidated Request</h2>
        <div class="card p-4">
            <form method="POST" id="requestForm" onsubmit="return validateForm(event)">
                <input type="hidden" name="action" value="submit_bulk_request">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Master File <span class="text-danger">*</span></label>
                        <select name="company_id" id="company_select" class="form-select" required>
                            <option value="" disabled selected>-- Select Existing Company --</option>
                            <?php foreach($companies_list as $comp): ?>
                                <option value="<?php echo (int)$comp['id']; ?>"><?php echo htmlspecialchars($comp['company_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Or Enter New Company Name</label>
                        <input type="text" name="new_company" id="new_company_input" class="form-control" placeholder="e.g., Triplex Corp">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Priority Level <span class="text-danger">*</span></label>
                        <select name="priority" id="priority_select" class="form-select" required>
                            <option value="Willing to Wait">Willing to Wait</option>
                            <option value="2-3 Days">2-3 Days</option>
                            <option value="Rush">Rush</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Target Date <span class="text-danger">*</span></label>
                            <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="auto_date_toggle" checked style="cursor:pointer;"><label class="form-check-label text-muted" for="auto_date_toggle" style="font-size:0.8rem;">Auto-calculate</label></div>
                        </div>
                        <input type="date" name="target_date" id="target_date_input" class="form-control" required>
                    </div>
                </div>

                <hr class="text-muted my-3">
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0 fw-semibold">Items to Request</label>
                    
                    <div class="dropdown">
                        <input type="hidden" name="global_marking" id="global_marking_input" value="" required>
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle fw-semibold" type="button" id="markingDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-tags"></i> <span id="selectedMarkingLabel">Select Batch Marking *</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="markingDropdownBtn">
                            <li><h6 class="dropdown-header">Choose Request Marking:</h6></li>
                            <?php foreach($markings_list as $mk): ?>
                                <li><a class="dropdown-item marking-option" href="#" data-value="<?php echo htmlspecialchars($mk['name']); ?>"><i class="fa-solid fa-tag text-muted me-2"></i> <?php echo htmlspecialchars($mk['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div id="markingErrorMsg" class="text-danger small mb-2 fw-semibold" style="display:none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Kailangan mong pumili ng Marking bago i-submit ang request!
                </div>

                <div id="request-items-container" class="p-3 bg-light border rounded-3 mb-3">
                    <div class="card p-3 bg-white border mb-3 item-row shadow-sm">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-2">
                                <label class="text-muted" style="font-size:0.75rem;">Item Type <span class="text-danger">*</span></label>
                                <select name="request_types[]" class="form-select req-type-select" required>
                                    <option value="STANDARD">Standard Item</option>
                                    <option value="SPECIAL">Special Cut / Roll</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 standard-fields">
                                <label class="text-muted" style="font-size:0.75rem;">Item Name <span class="text-danger">*</span></label>
                                <select name="item_ids[]" class="form-select" required>
                                    <option value="" disabled selected>-- Select Item --</option>
                                    <?php foreach($items as $i): ?><option value="<?php echo (int)$i['id']; ?>"><?php echo htmlspecialchars($i['item_name']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 special-fields" style="display:none;">
                                <label class="text-muted" style="font-size:0.75rem;">Cut / Roll Details <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="paper_names[]" class="form-control" placeholder="Material">
                                    <input type="number" step="0.01" min="0.01" name="widths[]" class="form-control text-center px-1" placeholder="W" style="max-width: 60px;">
                                    <input type="number" step="0.01" min="0.01" name="heights[]" class="form-control text-center px-1" placeholder="H" style="max-width: 60px;">
                                    <select name="units[]" class="form-select px-1" style="max-width: 75px;">
                                        <option value="Inches">in</option>
                                        <option value="CM">cm</option>
                                        <option value="MM">mm</option>
                                        <option value="Rolls">Rolls</option>
                                        <option value="Meters">m</option>
                                        <option value="Feet">ft</option>
                                        <option value="GSM">GSM</option>
                                    </select>
                                </div>
                                <input type="hidden" name="cut_sizes[]" value="">
                            </div>

                            <!-- Automatic c/o Account batay sa naka-login na session -->
                            <div class="col-md-4">
                                <label class="text-muted" style="font-size:0.75rem;">c/o Account (Automatic)</label>
                                <input type="text" name="care_ofs_text[]" class="form-control" value="<?php echo htmlspecialchars($_SESSION['name'] ?? 'Makati User'); ?>" readonly style="background-color: #f8fafc; font-weight: 600; color: #0369a1;">
                                <input type="hidden" name="care_of_modes[]" value="manual">
                            </div>

                            <div class="col-md-1">
                                <label class="text-muted" style="font-size:0.75rem;">Qty <span class="text-danger">*</span></label>
                                <input type="number" name="quantities[]" class="form-control text-center px-1" min="1" value="1" required>
                            </div>
                            
                            <div class="col-md-1 text-end pt-4">
                                <button type="button" class="btn btn-outline-danger remove-row w-100" style="padding: 8px;" disabled><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" id="add-item-btn" class="btn btn-outline-secondary mb-3"><i class="fa-solid fa-plus"></i> Add Another Item</button>
                <div><button type="submit" id="submitBtn" class="btn btn-primary fw-semibold px-4 py-2">Submit Request Batch</button></div>
            </form>
        </div>

    <?php elseif($tab === 'queues'): ?>
        <h2 class="fw-bold"><i class="fa-solid fa-hourglass-half text-primary"></i> Pending Queues</h2>
        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Req ID</th><th>Company</th><th>Requested Item & Details</th><th>Marking</th><th>c/o Person</th><th>Qty</th><th>Status</th><th>Date Filed</th></tr></thead>
                    <tbody>
                        <?php if(empty($queues_data)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No pending requests in the queue.</td></tr>
                        <?php else: ?>
                            <?php foreach($queues_data as $q): ?>
                                <tr>
                                    <td><code>#<?php echo (int)$q['id']; ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($q['company_name'] ?? 'N/A'); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($q['item_name']); ?></strong>
                                        <?php if($q['request_type'] === 'SPECIAL'): ?>
                                            <br><span class="badge-special">✂️ SPECIAL</span> <small class="text-muted"><?php echo $q['width']; ?>x<?php echo $q['height']; ?> <?php echo htmlspecialchars($q['unit']); ?></small>
                                        <?php else: ?>
                                            <br><small class="text-muted"><code><?php echo htmlspecialchars($q['sku']); ?></code></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if(!empty($q['marking'])) echo "<span class='badge-mark'>".htmlspecialchars($q['marking'])."</span>"; else echo "<small class='text-muted'>-</small>"; ?></td>
                                    <td><?php if(!empty($q['care_of'])) echo "<span class='badge-co'>c/o ".htmlspecialchars($q['care_of'])."</span>"; else echo "<small class='text-muted'>-</small>"; ?></td>
                                    <td><?php echo number_format($q['quantity']); ?></td>
                                    <td><span class="badge-warning-soft">Pending</span></td>
                                    <td><small class="text-muted"><?php echo date('M d, Y', strtotime($q['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($tab === 'inventory'): ?>
        <h2 class="fw-bold"><i class="fa-solid fa-warehouse text-primary"></i> Approved Item Catalog (Bicutan Origin)</h2>
        <div class="card p-3 mb-3">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="inventory">
                <div class="col-md-10"><input type="text" name="q" class="form-control" placeholder="Search items..." value="<?php echo htmlspecialchars($search_query); ?>"></div>
                <div class="col-md-2 d-grid"><button type="submit" class="btn btn-primary">Search</button></div>
            </form>
        </div>
        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>SKU</th><th>Item Name</th><th>Company Record</th><th class="text-center">Qty</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($approved_inventory as $inv): ?>
                            <tr>
                                <td><span class="bic-badge"><?php echo htmlspecialchars($inv['sku']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($inv['item_name']); ?></strong></td>
                                <td><span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($inv['company']); ?></span></td>
                                <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo (int)$inv['quantity']; ?></span></td>
                                <td><span class="badge-success-soft"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($inv['approval_status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if($total_inventory_pages > 1): ?>
                <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for($i = 1; $i <= $total_inventory_pages; $i++): ?><li class="page-item <?php echo ($page_inventory == $i) ? 'active' : ''; ?>"><a class="page-link" href="?tab=inventory&q=<?php echo urlencode($search_query); ?>&p_inv=<?php echo $i; ?>"><?php echo $i; ?></a></li><?php endfor; ?>
                </ul></nav>
            <?php endif; ?>
        </div>

    <?php elseif($tab === 'tracking'): ?>
        <?php
        $left_res = $conn->query("
            SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') as item_name
            FROM stock_requests r
            LEFT JOIN companies c ON r.company_id = c.id
            LEFT JOIN items i ON r.item_id = i.id
            WHERE r.status IN ('Approved', 'Shipped', 'Shipping Already Accepted') 
              AND (r.receiving_status IS NULL OR r.receiving_status != 'Manifested')
            ORDER BY FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'), r.updated_at DESC
        ");
        $incoming_items = $left_res ? $left_res->fetch_all(MYSQLI_ASSOC) : [];

        $right_res = $conn->query("
            SELECT r.*, c.company_name, COALESCE(i.item_name, r.paper_item_name, 'Special Cut') as item_name
            FROM stock_requests r
            LEFT JOIN companies c ON r.company_id = c.id
            LEFT JOIN items i ON r.item_id = i.id
            WHERE r.receiving_status = 'Manifested' OR r.status = 'Completed'
            ORDER BY r.received_at DESC, r.updated_at DESC
        ");
        $manifested_items = $right_res ? $right_res->fetch_all(MYSQLI_ASSOC) : [];
        ?>

        <h2 class="fw-bold"><i class="fa-solid fa-truck-fast text-primary"></i> Tracking & Receiving Interface</h2>
        
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card p-4 h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-box-arrow-down"></i> Incoming / For Receiving</h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1"><?php echo count($incoming_items); ?> Items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tracking ID</th>
                                    <th>Company</th>
                                    <th>Item & Marking</th>
                                    <th class="text-center">Qty</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($incoming_items)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No incoming shipments waiting.</td></tr>
                                <?php else: ?>
                                    <?php foreach($incoming_items as $inc): ?>
                                        <tr>
                                            <td><code>#TRK-<?php echo (int)$inc['id']; ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($inc['company_name'] ?? 'N/A'); ?></strong></td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($inc['item_name']); ?></div>
                                                <div class="align-items-center-custom">
                                                    <?php if(!empty($inc['marking'])): ?><span class="badge-mark"><?php echo htmlspecialchars($inc['marking']); ?></span><?php endif; ?>
                                                    <?php if(isset($inc['priority']) && $inc['priority'] === 'Rush'): ?>
                                                        <span class="badge-rush">Rush</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center"><strong><?php echo number_format($inc['quantity']); ?></strong></td>
                                            <td>
                                                <?php if($inc['status'] === 'Shipped'): ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary py-1 px-2" style="font-size: 0.72rem;">Shipped</span>
                                                <?php elseif($inc['status'] === 'Approved'): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success py-1 px-2" style="font-size: 0.72rem;">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning py-1 px-2" style="font-size: 0.72rem;">Accepted</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-success py-1 px-3 fw-semibold" onclick="openReceiveModal(<?php echo (int)$inc['id']; ?>)">
                                                    Receive
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card p-4 h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <h5 class="fw-bold text-success mb-0"><i class="fa-solid fa-clipboard-check"></i> Completed</h5>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1"><?php echo count($manifested_items); ?> Done</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Manifest ID</th>
                                    <th>Item Name</th>
                                    <th>Company</th>
                                    <th class="text-center">Qty</th>
                                    <th>Date/Time (PH Time)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($manifested_items)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No manifested items yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach($manifested_items as $man): ?>
                                        <tr>
                                            <td>
                                                <code class="text-success fw-bold"><?php echo htmlspecialchars($man['manifesting_code'] ?? 'N/A'); ?></code>
                                                <?php if(!empty($man['marking']) && strpos($man['marking'], 'Manifest Updated') !== false): ?>
                                                    <br><span class="badge bg-info bg-opacity-10 text-info border border-info px-1 py-0.5 mt-1" style="font-size: 0.65rem;">Updated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($man['item_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($man['company_name'] ?? 'N/A'); ?></td>
                                            <td class="text-center"><strong><?php echo number_format($man['quantity']); ?></strong></td>
                                            <td><small class="text-muted"><?php echo !empty($man['received_at']) ? date('M d, Y h:i A', strtotime($man['received_at'])) : 'N/A'; ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="?tab=tracking">
                        <input type="hidden" name="action" value="confirm_receiving">
                        <input type="hidden" name="req_id" id="modal_req_id">
                        <div class="modal-header">
                            <h5 class="modal-title fs-5 fw-bold"><i class="fa-solid fa-barcode text-primary"></i> Enter Manifest ID</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">Please enter the valid <strong>Manifest ID</strong> to officially accept and store this item:</p>
                            <div class="mb-2">
                                <label class="form-label">Manifest ID <span class="text-danger">*</span></label>
                                <input type="text" name="manifesting_code" class="form-control" placeholder="e.g. MAN-2026-XYZ" required autofocus>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php elseif($tab === 'history'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Request History by Company</h2>
            <button type="button" class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        </div>
        
        <?php if($view_company_id): ?>
            <div class="card p-4 mb-3 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-building"></i> Company: <?php echo htmlspecialchars($target_company_name); ?></h5>
                        <small class="text-muted">Showing history line items.</small>
                    </div>
                    <a href="?tab=history" class="btn btn-outline-primary btn-sm px-3"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mt-2 mb-0">
                        <thead><tr><th>Req ID</th><th>Batch Ref</th><th>Item Details</th><th>Marking</th><th>c/o</th><th>Qty</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php if(empty($company_orders)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach($company_orders as $ord): ?>
                                    <tr>
                                        <td><code>#<?php echo (int)$ord['id']; ?></code></td>
                                        <td><span class="bic-badge"><?php echo htmlspecialchars($ord['batch_reference'] ?? 'N/A'); ?></span></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ord['item_name']); ?></strong>
                                            <?php if($ord['request_type'] === 'SPECIAL'): ?>
                                                <br><span class="badge-special">SPECIAL</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php if(!empty($ord['marking'])) echo "<span class='badge-mark'>".htmlspecialchars($ord['marking'])."</span>"; else echo "<small class='text-muted'>-</small>"; ?></td>
                                        <td><?php if(!empty($ord['care_of'])) echo "<span class='badge-co'>c/o ".htmlspecialchars($ord['care_of'])."</span>"; else echo "<small class='text-muted'>-</small>"; ?></td>
                                        <td><strong><?php echo number_format($ord['quantity']); ?></strong></td>
                                        <td>
                                            <?php if($ord['status']=='Approved' || $ord['status'] == 'Shipped' || $ord['status'] == 'Completed'): ?><span class="badge-success-soft">Done</span>
                                            <?php elseif($ord['status']=='Rejected'): ?><span class="badge-danger-soft">Rejected</span>
                                            <?php else: ?><span class="badge-warning-soft">Pending</span><?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card p-3 mb-3 no-print">
                <form method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="history">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-search"></i></span>
                            <input type="text" name="h_search" class="form-control" placeholder="Search company name..." value="<?php echo htmlspecialchars($history_search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>

            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Company Name</th><th class="text-center">Total Requests</th><th class="text-center">Total Qty</th><th>Latest Order</th><th style="width:120px;" class="no-print">Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($history_companies)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No history found.</td></tr>
                            <?php else: ?>
                                <?php foreach($history_companies as $hc): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($hc['company_name']); ?></strong></td>
                                        <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo (int)$hc['total_requests']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><?php echo number_format($hc['total_quantity']); ?></span></td>
                                        <td><small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($hc['latest_request'])); ?></small></td>
                                        <td class="no-print"><a href="?tab=history&view_company=<?php echo (int)$hc['id']; ?>" class="btn btn-sm btn-outline-primary px-3"><i class="fa-solid fa-eye"></i> View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif($tab === 'queries'): ?>
        <h2 class="fw-bold"><i class="fa-solid fa-magnifying-glass-chart text-primary"></i> Companies & Queries</h2>
        <div class="card p-4 mb-3">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="queries">
                <div class="col-md-3">
                    <label class="form-label">Company Filter</label>
                    <select name="q_company" class="form-select">
                        <option value="">All Companies</option>
                        <?php foreach($companies_list as $comp): ?>
                            <option value="<?php echo $comp['id']; ?>" <?php echo ($q_company == $comp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($comp['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="q_start" class="form-control" value="<?php echo htmlspecialchars($q_start); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="q_end" class="form-control" value="<?php echo htmlspecialchars($q_end); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Keywords</label>
                    <input type="text" name="q_search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($q_search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="q_status" class="form-select">
                        <option value="">All</option>
                        <option value="Pending" <?php echo ($q_status=='Pending')?'selected':''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($q_status=='Approved')?'selected':''; ?>>Approved</option>
                        <option value="Completed" <?php echo ($q_status=='Completed')?'selected':''; ?>>Completed</option>
                        <option value="Rejected" <?php echo ($q_status=='Rejected')?'selected':''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i></button>
                </div>
            </form>
        </div>

        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Req ID</th><th>Company</th><th>Item Details</th><th>Marking</th><th>c/o</th><th>Qty</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if(empty($query_results)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No data matches filters.</td></tr>
                        <?php else: ?>
                            <?php foreach($query_results as $qr): ?>
                                <tr>
                                    <td><code>#<?php echo (int)$qr['id']; ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($qr['company_name'] ?? 'N/A'); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($qr['item_name']); ?></strong>
                                        <?php if($qr['request_type'] === 'SPECIAL'): ?>
                                            <br><span class="badge-special">SPECIAL</span> <small class="text-muted"><?php echo $qr['width'].'x'.$qr['height'].' '.htmlspecialchars($qr['unit']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if(!empty($qr['marking'])) echo "<span class='badge-mark'>".htmlspecialchars($qr['marking'])."</span>"; ?></td>
                                    <td><?php if(!empty($qr['care_of'])) echo "<span class='badge-co'>c/o ".htmlspecialchars($qr['care_of'])."</span>"; ?></td>
                                    <td><?php echo number_format($qr['quantity']); ?></td>
                                    <td>
                                        <?php if($qr['status']=='Approved' || $qr['status']=='Shipped' || $qr['status']=='Completed'): ?><span class="badge-success-soft"><?php echo htmlspecialchars($qr['status']); ?></span>
                                        <?php elseif($qr['status']=='Rejected'): ?><span class="badge-danger-soft"><?php echo htmlspecialchars($qr['status']); ?></span>
                                        <?php else: ?><span class="badge-warning-soft"><?php echo htmlspecialchars($qr['status']); ?></span><?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('M d, Y', strtotime($qr['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($total_queries_pages > 1): ?>
                <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php 
                    $qs_array = ['tab' => 'queries', 'q_company' => $q_company, 'q_start' => $q_start, 'q_end' => $q_end, 'q_search' => $q_search, 'q_status' => $q_status];
                    for($i = 1; $i <= $total_queries_pages; $i++): $qs_array['q_page'] = $i; ?>
                        <li class="page-item <?php echo ($q_page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query($qs_array); ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                </ul></nav>
            <?php endif; ?>
        </div>

    <?php elseif($tab === 'markings'): ?>
        <h2 class="fw-bold"><i class="fa-solid fa-tags text-primary"></i> Markings & Salespersons Config</h2>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4 mb-3">
                    <form action="?tab=markings" method="POST">
                        <input type="hidden" name="action" value="add_marking">
                        <label class="form-label">Add New Marking Tag</label>
                        <div class="input-group">
                            <input type="text" name="marking_name" class="form-control" placeholder="e.g. Jumbo Roll, Deliver" required>
                            <button type="submit" class="btn btn-primary px-4">Add</button>
                        </div>
                    </form>
                </div>

                <div class="card p-4">
                    <h5 class="fw-bold mb-3 text-secondary" style="font-size:0.95rem;">Existing Markings</h5>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Marking Name</th><th style="width:100px;" class="text-center">Action</th></tr></thead>
                        <tbody>
                            <?php foreach($markings_list as $mk): ?>
                                <tr>
                                    <td><span class="badge-mark"><?php echo htmlspecialchars($mk['name']); ?></span></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2" onclick="openEditMarkingModal(<?php echo $mk['id']; ?>, '<?php echo htmlspecialchars($mk['name'], ENT_QUOTES); ?>')"><i class="fa-solid fa-pen"></i></button>
                                        <a href="?action=delete_marking&id=<?php echo $mk['id']; ?>&tab=markings" class="btn btn-sm btn-outline-danger px-2" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 mb-3">
                    <form action="?tab=markings" method="POST">
                        <input type="hidden" name="action" value="add_salesperson">
                        <label class="form-label">Add Salesperson / Account Person</label>
                        <div class="input-group">
                            <input type="text" name="salesperson_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
                            <button type="submit" class="btn btn-primary px-4">Add</button>
                        </div>
                    </form>
                </div>

                <div class="card p-4">
                    <h5 class="fw-bold mb-3 text-secondary" style="font-size:0.95rem;">Configured Account Persons</h5>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Person Name</th><th style="width:100px;" class="text-center">Action</th></tr></thead>
                        <tbody>
                            <?php if(empty($salesperson_list)): ?>
                                <tr><td colspan="2" class="text-muted text-center py-3">No names yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($salesperson_list as $sp): ?>
                                    <tr>
                                        <td><span class="badge-co">c/o <?php echo htmlspecialchars($sp['name']); ?></span></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary px-2" onclick="openEditSalesModal(<?php echo $sp['id']; ?>, '<?php echo htmlspecialchars($sp['name'], ENT_QUOTES); ?>')"><i class="fa-solid fa-pen"></i></button>
                                            <a href="?action=delete_salesperson&id=<?php echo $sp['id']; ?>&tab=markings" class="btn btn-sm btn-outline-danger px-2" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editMarkingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?tab=markings" method="POST">
                        <input type="hidden" name="action" value="edit_marking">
                        <input type="hidden" name="marking_id" id="edit_marking_id">
                        <div class="modal-header">
                            <h5 class="modal-title fs-5 fw-bold">Edit Marking Tag</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Marking Name</label>
                            <input type="text" name="marking_name" id="edit_marking_name" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editSalesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="?tab=markings" method="POST">
                        <input type="hidden" name="action" value="edit_salesperson">
                        <input type="hidden" name="salesperson_id" id="edit_salesperson_id">
                        <div class="modal-header">
                            <h5 class="modal-title fs-5 fw-bold">Edit Salesperson Name</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Name</label>
                            <input type="text" name="salesperson_name" id="edit_salesperson_name" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const companySelect = document.getElementById('company_select');
    const newCompanyInput = document.getElementById('new_company_input');

    companySelect?.addEventListener('change', function() {
        if (this.value !== "") { newCompanyInput.value = ""; newCompanyInput.removeAttribute('required'); } 
        else { newCompanyInput.setAttribute('required', 'required'); }
    });

    newCompanyInput?.addEventListener('input', function() {
        if (this.value.trim() !== "") { companySelect.value = ""; companySelect.removeAttribute('required'); } 
        else { companySelect.setAttribute('required', 'required'); }
    });

    document.querySelectorAll('.marking-option').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const val = this.getAttribute('data-value');
            document.getElementById('global_marking_input').value = val;
            document.getElementById('selectedMarkingLabel').textContent = val;
            
            const errorMsg = document.getElementById('markingErrorMsg');
            if (errorMsg) errorMsg.style.display = 'none';
            document.getElementById('markingDropdownBtn').classList.remove('border-danger');
        });
    });

    function validateForm(event) {
        const markingInput = document.getElementById('global_marking_input');
        const errorMsg = document.getElementById('markingErrorMsg');
        const markingBtn = document.getElementById('markingDropdownBtn');

        if (!markingInput.value || markingInput.value.trim() === "") {
            event.preventDefault(); 
            if (errorMsg) errorMsg.style.display = 'block';
            if (markingBtn) {
                markingBtn.classList.add('border-danger');
                markingBtn.focus();
            }
            return false;
        }
        return true;
    }

    const targetDateInput = document.getElementById('target_date_input');
    const autoDateToggle = document.getElementById('auto_date_toggle');
    const prioritySelect = document.getElementById('priority_select');

    function updateTargetDate() {
        if (!autoDateToggle || !autoDateToggle.checked) return;
        const today = new Date();
        let daysToAdd = 7;
        if (prioritySelect.value === '2-3 Days') daysToAdd = 3;
        else if (prioritySelect.value === 'Rush') daysToAdd = 1; 
        today.setDate(today.getDate() + daysToAdd);
        targetDateInput.value = today.toISOString().split('T')[0];
    }
    
    prioritySelect?.addEventListener('change', updateTargetDate);
    autoDateToggle?.addEventListener('change', function() {
        if (this.checked) { targetDateInput.setAttribute('readonly', 'readonly'); updateTargetDate(); } 
        else { targetDateInput.removeAttribute('readonly'); }
    });
    if (autoDateToggle && autoDateToggle.checked && targetDateInput) {
        targetDateInput.setAttribute('readonly', 'readonly'); updateTargetDate();
    }

    function openReceiveModal(reqId) {
        document.getElementById('modal_req_id').value = reqId;
        new bootstrap.Modal(document.getElementById('receiveModal')).show();
    }

    function bindRowEvents(row) {
        row.querySelector('.req-type-select').addEventListener('change', function() {
            const isSpecial = this.value === 'SPECIAL';
            row.querySelector('.standard-fields').style.display = isSpecial ? 'none' : 'block';
            row.querySelector('.special-fields').style.display = isSpecial ? 'block' : 'none';
            
            const itemSelect = row.querySelector('select[name="item_ids[]"]');
            const paperInput = row.querySelector('input[name="paper_names[]"]');
            const widthInput = row.querySelector('input[name="widths[]"]');
            const heightInput = row.querySelector('input[name="heights[]"]');

            if (isSpecial) {
                itemSelect.removeAttribute('required'); 
                itemSelect.value = '';

                paperInput.setAttribute('required', 'required');
                widthInput.setAttribute('required', 'required');
                heightInput.setAttribute('required', 'required');
            } else {
                itemSelect.setAttribute('required', 'required'); 

                paperInput.removeAttribute('required');
                paperInput.value = '';
                widthInput.removeAttribute('required');
                widthInput.value = '';
                heightInput.removeAttribute('required');
                heightInput.value = '';
            }
        });

        row.querySelector('.remove-row').addEventListener('click', function() { row.remove(); });
    }
    
    document.querySelectorAll('.item-row').forEach(bindRowEvents);

    document.getElementById('add-item-btn')?.addEventListener('click', function() {
        const container = document.getElementById('request-items-container');
        const template = container.querySelector('.item-row').cloneNode(true);
        
        template.querySelector('input[name="quantities[]"]').value = '1';
        template.querySelector('.req-type-select').value = 'STANDARD';
        template.querySelector('.standard-fields').style.display = 'block';
        template.querySelector('.special-fields').style.display = 'none';

        const itemSelect = template.querySelector('select[name="item_ids[]"]');
        itemSelect.selectedIndex = 0;
        itemSelect.setAttribute('required', 'required');

        template.querySelectorAll('input[type="text"]:not([name="care_ofs_text[]"]), input[type="number"]:not([name="quantities[]"])').forEach(i => i.value = '');
        template.querySelector('.remove-row').removeAttribute('disabled');
        
        bindRowEvents(template);
        container.appendChild(template);
    });

    function openEditMarkingModal(id, name) {
        document.getElementById('edit_marking_id').value = id;
        document.getElementById('edit_marking_name').value = name;
        new bootstrap.Modal(document.getElementById('editMarkingModal')).show();
    }

    function openEditSalesModal(id, name) {
        document.getElementById('edit_salesperson_id').value = id;
        document.getElementById('edit_salesperson_name').value = name;
        new bootstrap.Modal(document.getElementById('editSalesModal')).show();
    }
</script>
</body>
</html>