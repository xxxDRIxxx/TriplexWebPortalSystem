<?php
// 1. SHOW ERRORS FOR DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================================================
// 2. DATABASE CONFIGURATION & AUTO-SETUP
// =========================================================
$host   = "sql312.infinityfree.com"; // MySQL Host
$user   = "if0_42341917";            // MySQL Username
$pass   = "NnqVzR6xyX3ZH3";          // Account Password
$dbname = "if0_42341917_db_triplexinventorystructureacsie"; // Database Name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Auto-create Tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
)");

$conn->query("CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00
)");

// Stock Requests Table with Priority & Pickup Date
$conn->query("CREATE TABLE IF NOT EXISTS stock_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    requested_by VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    purpose TEXT,
    priority ENUM('Rush', '2-3 Days', 'Willing to Wait') DEFAULT 'Willing to Wait',
    pickup_date DATE NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
)");

// Auto-migration for existing tables
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS priority ENUM('Rush', '2-3 Days', 'Willing to Wait') DEFAULT 'Willing to Wait'");
$conn->query("ALTER TABLE stock_requests ADD COLUMN IF NOT EXISTS pickup_date DATE NULL");

$self = htmlspecialchars($_SERVER['PHP_SELF']);
$active_tab = $_GET['tab'] ?? 'items';

// =========================================================
// 3. CATEGORY LOGIC
// =========================================================
if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $cat_name = trim($_POST['category_name']);
    if (!empty($cat_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $cat_name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $self . "?tab=categories&msg=cat_added");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete_category') {
    $cat_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $self . "?tab=categories&msg=cat_deleted");
    exit();
}

// =========================================================
// 4. ITEM LOGIC (CRUD)
// =========================================================
if (isset($_POST['action']) && $_POST['action'] === 'create_item') {
    $item_name  = trim($_POST['item_name']);
    $sku        = trim($_POST['sku']);
    $category   = trim($_POST['category']);
    $quantity   = (int)$_POST['quantity'];
    $price      = (float)$_POST['price'];

    $stmt = $conn->prepare("INSERT INTO items (item_name, sku, category, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssid", $item_name, $sku, $category, $quantity, $price);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $self . "?tab=items&msg=item_added");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'update_item') {
    $id         = (int)$_POST['id'];
    $item_name  = trim($_POST['item_name']);
    $sku        = trim($_POST['sku']);
    $category   = trim($_POST['category']);
    $quantity   = (int)$_POST['quantity'];
    $price      = (float)$_POST['price'];

    $stmt = $conn->prepare("UPDATE items SET item_name=?, sku=?, category=?, quantity=?, price=? WHERE id=?");
    $stmt->bind_param("sssidi", $item_name, $sku, $category, $quantity, $price, $id);
    $stmt->execute();
    $stmt->close();

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

// =========================================================
// 5. STOCK REQUEST & APPROVAL LOGIC
// =========================================================

// CREATE REQUEST WITH PRIORITY QUEUE
if (isset($_POST['action']) && $_POST['action'] === 'create_request') {
    $item_id      = (int)$_POST['item_id'];
    $requested_by = trim($_POST['requested_by']);
    $req_qty      = (int)$_POST['quantity'];
    $purpose      = trim($_POST['purpose']);
    $priority     = $_POST['priority'] ?? 'Willing to Wait';
    $pickup_date  = !empty($_POST['pickup_date']) ? $_POST['pickup_date'] : NULL;

    $stmt = $conn->prepare("INSERT INTO stock_requests (item_id, requested_by, quantity, purpose, priority, pickup_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $item_id, $requested_by, $req_qty, $purpose, $priority, $pickup_date);
    $stmt->execute();
    $stmt->close();

    $redirect_tab = $_POST['redirect_tab'] ?? 'queue';
    header("Location: " . $self . "?tab=" . $redirect_tab . "&msg=req_created");
    exit();
}

// APPROVE REQUEST
if (isset($_GET['action']) && $_GET['action'] === 'approve_request') {
    $req_id = (int)$_GET['id'];
    $ref_tab = $_GET['tab'] ?? 'queue';

    // Get Request Details
    $req_stmt = $conn->prepare("SELECT r.*, i.quantity as stock_qty FROM stock_requests r JOIN items i ON r.item_id = i.id WHERE r.id = ? AND r.status = 'Pending'");
    $req_stmt->bind_param("i", $req_id);
    $req_stmt->execute();
    $request = $req_stmt->get_result()->fetch_assoc();
    $req_stmt->close();

    if ($request) {
        if ($request['stock_qty'] >= $request['quantity']) {
            // Deduct Stock
            $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
            $deduct_stmt->bind_param("ii", $request['quantity'], $request['item_id']);
            $deduct_stmt->execute();
            $deduct_stmt->close();

            // Update Status to Approved
            $app_stmt = $conn->prepare("UPDATE stock_requests SET status = 'Approved' WHERE id = ?");
            $app_stmt->bind_param("i", $req_id);
            $app_stmt->execute();
            $app_stmt->close();

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

// REJECT REQUEST
if (isset($_GET['action']) && $_GET['action'] === 'reject_request') {
    $req_id = (int)$_GET['id'];
    $ref_tab = $_GET['tab'] ?? 'queue';

    $stmt = $conn->prepare("UPDATE stock_requests SET status = 'Rejected' WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $self . "?tab=" . $ref_tab . "&msg=req_rejected");
    exit();
}

// =========================================================
// 6. FETCH DATA FROM DATABASE
// =========================================================
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$items      = $conn->query("SELECT * FROM items ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Queue Query (ONLY Pending, sorted by Priority: Rush -> 2-3 Days -> Willing to Wait)
$queue_res = $conn->query("
    SELECT r.*, i.item_name, i.sku, i.quantity as available_stock 
    FROM stock_requests r 
    JOIN items i ON r.item_id = i.id 
    WHERE r.status = 'Pending'
    ORDER BY 
        FIELD(r.priority, 'Rush', '2-3 Days', 'Willing to Wait'),
        r.id ASC
");
$queue_requests = ($queue_res) ? $queue_res->fetch_all(MYSQLI_ASSOC) : array();

// All Requests History
$all_requests_res = $conn->query("
    SELECT r.*, i.item_name, i.sku, i.quantity as available_stock 
    FROM stock_requests r 
    JOIN items i ON r.item_id = i.id 
    ORDER BY r.id DESC
");
$all_requests = ($all_requests_res) ? $all_requests_res->fetch_all(MYSQLI_ASSOC) : array();

// Counters
$pending_req_count = count($queue_requests);
$rush_count = 0;
foreach($queue_requests as $qr) {
    if($qr['priority'] === 'Rush') $rush_count++;
}

$total_stock = 0;
$total_value = 0;
foreach ($items as $item) {
    $total_stock += $item['quantity'];
    $total_value += ($item['quantity'] * $item['price']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory & Priority Queue System</title>
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --danger-hover: #b91c1c;
            --warning: #f59e0b;
            --success: #10b981;
            --success-hover: #047857;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            --radius: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background-color: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar-brand {
            padding: 24px;
            font-size: 1.2rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu { list-style: none; padding: 16px 0; flex-grow: 1; }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar-menu li a:hover, .sidebar-menu li.active a {
            background-color: var(--primary-light);
            color: #ffffff;
            border-left: 4px solid var(--accent);
        }

        .nav-badge {
            background: var(--warning);
            color: #000;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .nav-badge-rush {
            background: var(--danger);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .main-content {
            flex-grow: 1;
            padding: 32px;
            overflow-y: auto;
        }

        header { margin-bottom: 24px; }
        header h1 { font-size: 1.75rem; font-weight: 700; }
        header p { color: var(--text-muted); font-size: 0.95rem; margin-top: 4px; }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .stat-card span { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .stat-card h3 { margin-top: 8px; font-size: 1.6rem; font-weight: 700; }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover { background-color: var(--accent-hover); }
        .btn-success { background-color: var(--success); }
        .btn-success:hover { background-color: var(--success-hover); }
        .btn-danger { background-color: var(--danger); }
        .btn-danger:hover { background-color: var(--danger-hover); }
        .btn-warning { background-color: var(--warning); color: #000; }

        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 18px; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f8fafc; color: var(--text-muted); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        tbody tr:hover { background-color: #f8fafc; }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        
        /* Queue / Priority Badges */
        .priority-rush { background-color: #dc2626; color: #ffffff; font-weight: 700; animation: pulse 1.5s infinite; }
        .priority-normal { background-color: #3b82f6; color: #ffffff; }
        .priority-wait { background-color: #64748b; color: #ffffff; }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--radius);
            width: 100%;
            max-width: 520px;
            box-shadow: var(--shadow);
        }

        .modal-content h3 { margin-bottom: 18px; font-size: 1.2rem; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem;
        }
        .modal-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>

<!-- SIDEBAR MENU WITH QUEUE TAB -->
<div class="sidebar">
    <div class="sidebar-brand">🏢 Robinson Inventory</div>
    <ul class="sidebar-menu">
        <li class="<?php echo $active_tab==='items'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=items">📦 Items & Stocks</a>
        </li>
        <!-- QUEUE OF REQUESTS IN SIDEBAR -->
        <li class="<?php echo $active_tab==='queue'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=queue">
                ⚡ Queue of Requests
                <?php if($rush_count > 0): ?>
                    <span class="nav-badge-rush"><?php echo $rush_count; ?> RUSH</span>
                <?php elseif($pending_req_count > 0): ?>
                    <span class="nav-badge"><?php echo $pending_req_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="<?php echo $active_tab==='all_requests'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=all_requests">📋 Request History</a>
        </li>
        <li class="<?php echo $active_tab==='categories'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=categories">📁 Categories</a>
        </li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    
    <!-- ALERT NOTIFICATIONS -->
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] === 'insufficient_stock'): ?>
            <div class="alert alert-danger">❌ Cannot Approve: Insufficient stock available for this item!</div>
        <?php else: ?>
            <div class="alert alert-success">✓ Action completed successfully!</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- STATS METRICS -->
    <div class="stats-grid">
        <div class="stat-card">
            <span>Total Unique Items</span>
            <h3><?php echo number_format(count($items)); ?></h3>
        </div>
        <div class="stat-card">
            <span>Pending in Queue</span>
            <h3 style="color: var(--warning);"><?php echo $pending_req_count; ?></h3>
        </div>
        <div class="stat-card">
            <span>⚡ Urgent Rush Queue</span>
            <h3 style="color: var(--danger);"><?php echo $rush_count; ?></h3>
        </div>
        <div class="stat-card">
            <span>Inventory Value</span>
            <h3>₱<?php echo number_format($total_value, 2); ?></h3>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: ITEMS & STOCKS -->
    <!-- ========================================================= -->
    <?php if ($active_tab === 'items'): ?>
        <header>
            <h1>Inventory Stock Management</h1>
            <p>View, add, edit, and monitor current warehouse inventory.</p>
        </header>

        <div class="toolbar">
            <h2>Inventory Items List</h2>
            <button class="btn" onclick="openModal('addItemModal')">+ Add New Item</button>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Available Stock</th>
                        <th>Status</th>
                        <th>Unit Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding:24px;">No inventory items found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($item['sku']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td><span class="badge" style="background:#e2e8f0;"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                <td><strong><?php echo number_format($item['quantity']); ?></strong></td>
                                <td>
                                    <?php if($item['quantity'] == 0): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif($item['quantity'] < 5): ?>
                                        <span class="badge badge-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td style="display:flex; gap:6px;">
                                    <button class="btn btn-warning" style="padding:4px 10px; font-size:0.75rem;" 
                                            onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo addslashes($item['sku']); ?>', '<?php echo addslashes($item['category']); ?>', <?php echo $item['quantity']; ?>, <?php echo $item['price']; ?>)">Edit</button>
                                    <a href="<?php echo $self; ?>?action=delete_item&id=<?php echo $item['id']; ?>" class="btn btn-danger" style="padding:4px 10px; font-size:0.75rem;" onclick="return confirm('Delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ========================================================= -->
    <!-- TAB 2: QUEUE OF REQUESTS (DEDICATED SIDEBAR PAGE) -->
    <!-- ========================================================= -->
    <?php if ($active_tab === 'queue'): ?>
        <header>
            <h1>⚡ Priority Queue of Requests</h1>
            <p>Active pending requests ordered by urgency. Rush requests are placed at the top.</p>
        </header>

        <div class="toolbar">
            <h2>Current Queue (<?php echo count($queue_requests); ?> Pending)</h2>
            <button class="btn btn-success" onclick="openModal('addRequestModal')">+ New Stock Request</button>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Queue Rank</th>
                        <th>Urgency / Priority</th>
                        <th>Requested By</th>
                        <th>Item Requested</th>
                        <th>Qty</th>
                        <th>Target Date</th>
                        <th>Actions / Approval</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queue_requests)): ?>
                        <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding:32px;">🎉 The queue is empty! No pending stock requests.</td></tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($queue_requests as $req): ?>
                            <tr>
                                <td><strong>#<?php echo $rank++; ?></strong></td>
                                <td>
                                    <?php if($req['priority'] === 'Rush'): ?>
                                        <span class="badge priority-rush">⚡ RUSH (Immediate)</span>
                                    <?php elseif($req['priority'] === '2-3 Days'): ?>
                                        <span class="badge priority-normal">🗓️ 2-3 Days</span>
                                    <?php else: ?>
                                        <span class="badge priority-wait">⏳ Willing to Wait</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($req['requested_by']); ?></strong><br>
                                    <small style="color:var(--text-muted);"><?php echo htmlspecialchars($req['purpose']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($req['item_name']); ?> <br><small style="color:var(--text-muted);">(Stock: <?php echo $req['available_stock']; ?>)</small></td>
                                <td><strong><?php echo number_format($req['quantity']); ?></strong></td>
                                <td>
                                    <?php echo !empty($req['pickup_date']) ? date('M d, Y', strtotime($req['pickup_date'])) : '<em style="color:#94a3b8;">Not Specified</em>'; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="<?php echo $self; ?>?action=approve_request&id=<?php echo $req['id']; ?>&tab=queue" 
                                           class="btn btn-success" style="padding:4px 10px; font-size:0.75rem;" 
                                           onclick="return confirm('Approve this request? Stock will be automatically deducted.')">
                                           ✓ Approve
                                        </a>
                                        <a href="<?php echo $self; ?>?action=reject_request&id=<?php echo $req['id']; ?>&tab=queue" 
                                           class="btn btn-danger" style="padding:4px 10px; font-size:0.75rem;" 
                                           onclick="return confirm('Reject this request?')">
                                           ✕ Reject
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ========================================================= -->
    <!-- TAB 3: ALL REQUESTS HISTORY -->
    <!-- ========================================================= -->
    <?php if ($active_tab === 'all_requests'): ?>
        <header>
            <h1>Stock Request History</h1>
            <p>Archive and complete history of all stock requests (Approved, Rejected, and Pending).</p>
        </header>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Requested By</th>
                        <th>Item Requested</th>
                        <th>Qty</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Filed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_requests)): ?>
                        <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding:24px;">No stock request records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_requests as $req): ?>
                            <tr>
                                <td>#REQ-<?php echo $req['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($req['requested_by']); ?></strong></td>
                                <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                                <td><strong><?php echo number_format($req['quantity']); ?></strong></td>
                                <td>
                                    <?php if($req['priority'] === 'Rush'): ?>
                                        <span class="badge priority-rush">⚡ Rush</span>
                                    <?php elseif($req['priority'] === '2-3 Days'): ?>
                                        <span class="badge priority-normal">🗓️ 2-3 Days</span>
                                    <?php else: ?>
                                        <span class="badge priority-wait">⏳ Willing to Wait</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($req['status'] === 'Approved'): ?>
                                        <span class="badge badge-success">Approved</span>
                                    <?php elseif($req['status'] === 'Rejected'): ?>
                                        <span class="badge badge-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ========================================================= -->
    <!-- TAB 4: CATEGORIES -->
    <!-- ========================================================= -->
    <?php if ($active_tab === 'categories'): ?>
        <header>
            <h1>Category Management</h1>
            <p>Manage list of product categories.</p>
        </header>

        <div style="max-width: 600px;">
            <div class="table-card" style="padding: 20px; margin-bottom: 20px;">
                <form action="<?php echo $self; ?>" method="POST">
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-group">
                        <label>New Category Name</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" name="category_name" placeholder="e.g. Office Supplies" required>
                            <button type="submit" class="btn">+ Add</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="2" style="text-align:center; padding:16px;">No categories found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                    <td>
                                        <a href="<?php echo $self; ?>?action=delete_category&id=<?php echo $cat['id']; ?>" class="btn btn-danger" style="padding:4px 10px; font-size:0.75rem;" onclick="return confirm('Delete category?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ========================================================= -->
<!-- MODALS -->
<!-- ========================================================= -->

<!-- MODAL: ADD ITEM -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <h3>Add New Product / Item</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="create_item">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" required placeholder="e.g. Triplex PPC Ream">
            </div>
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku" required placeholder="e.g. TRIPLEX-75GSM-8511">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="" disabled selected>-- Select Category --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Initial Quantity</label>
                <input type="number" name="quantity" min="0" required value="0">
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" step="0.01" name="price" min="0" required value="0.00">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn" style="background:#64748b;" onclick="closeModal('addItemModal')">Cancel</button>
                <button type="submit" class="btn">Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT ITEM -->
<div id="editItemModal" class="modal">
    <div class="modal-content">
        <h3>Edit Item Details</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" id="edit_name" required>
            </div>
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku" id="edit_sku" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="edit_category" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" id="edit_quantity" min="0" required>
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" step="0.01" name="price" id="edit_price" min="0" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn" style="background:#64748b;" onclick="closeModal('editItemModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">Update Item</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CREATE STOCK REQUEST WITH PRIORITY QUEUE -->
<div id="addRequestModal" class="modal">
    <div class="modal-content">
        <h3>File a Stock Request</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="create_request">
            <input type="hidden" name="redirect_tab" value="<?php echo $active_tab; ?>">
            
            <div class="form-group">
                <label>Priority Level / Schedule Queue</label>
                <select name="priority" required>
                    <option value="Rush">⚡ Rush (Immediate / Within Today)</option>
                    <option value="2-3 Days" selected>🗓️ Standard (Within 2-3 Days)</option>
                    <option value="Willing to Wait">⏳ Low Priority (Willing to Wait)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Target Pickup / Release Date (Optional)</label>
                <input type="date" name="pickup_date">
            </div>

            <div class="form-group">
                <label>Select Item to Request</label>
                <select name="item_id" required>
                    <option value="" disabled selected>-- Select Item --</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?php echo $it['id']; ?>">
                            <?php echo htmlspecialchars($it['item_name']); ?> (Available Stock: <?php echo $it['quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Requested By (Employee / Dept)</label>
                <input type="text" name="requested_by" required placeholder="e.g. Juan Dela Cruz - Accounting">
            </div>

            <div class="form-group">
                <label>Requested Quantity</label>
                <input type="number" name="quantity" min="1" required placeholder="1">
            </div>

            <div class="form-group">
                <label>Purpose / Remarks</label>
                <textarea name="purpose" rows="2" required placeholder="e.g. Urgent client printing job"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn" style="background:#64748b;" onclick="closeModal('addRequestModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openEditModal(id, name, sku, category, quantity, price) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('edit_price').value = price;
    openModal('editItemModal');
}
</script>

</body>
</html>