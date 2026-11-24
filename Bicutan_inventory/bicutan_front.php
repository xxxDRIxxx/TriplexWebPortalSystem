<?php
// =========================================================
// FILE 2: bicutan_front.php
// =========================================================
require_once 'bicutan_backend.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bicutan Inventory Hub — Central System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #ffffff;
            --sidebar-hover: #f1f5f9;
            --sidebar-text: #475569;
            --sidebar-active-text: #2563eb;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --success: #059669;
            --success-bg: #ecfdf5;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --radius: 8px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .mobile-header {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
            z-index: 1100;
        }

        .mobile-header h3 { font-size: 1rem; font-weight: 700; color: var(--primary); }
        .menu-toggle-btn { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-main); }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid var(--border-color);
            transition: transform 0.3s ease;
            z-index: 1200;
        }

        .sidebar-brand {
            padding: 22px 20px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu { list-style: none; padding: 16px 12px; flex-grow: 1; display: flex; flex-direction: column; gap: 6px; }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .sidebar-menu li a .menu-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu li a .menu-content svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
            opacity: 0.75;
        }

        .sidebar-menu li a:hover {
            background-color: var(--sidebar-hover);
            color: var(--text-main);
        }

        .sidebar-menu li a:hover .menu-content svg {
            opacity: 1;
        }

        .sidebar-menu li.active a {
            background-color: #eff6ff;
            color: var(--primary);
            font-weight: 600;
        }

        .sidebar-menu li.active a .menu-content svg {
            opacity: 1;
            stroke: var(--primary);
        }

        .nav-badge, .nav-badge-rush {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .nav-badge { background: #e2e8f0; color: #334155; }
        .nav-badge-rush { background: #fee2e2; color: #dc2626; }

        .main-content {
            flex-grow: 1;
            padding: 24px;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        header { margin-bottom: 20px; }
        header h1 { font-size: 1.4rem; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px; }
        header p { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
        }
        .alert-success { background-color: var(--success-bg); color: var(--success); border: 1px solid #a7f3d0; }
        .alert-danger { background-color: var(--danger-bg); color: var(--danger); border: 1px solid #fca5a5; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 16px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .stat-card span { font-size: 0.72rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .stat-card h3 { margin-top: 6px; font-size: 1.35rem; font-weight: 700; color: var(--text-main); }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .toolbar h2 { font-size: 1.05rem; font-weight: 600; color: var(--text-main); }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
            white-space: nowrap;
        }
        .btn:hover { background-color: var(--primary-hover); }
        .btn-success { background-color: var(--success); }
        .btn-success:hover { background-color: #047857; }
        .btn-danger { background-color: var(--danger); }
        .btn-danger:hover { background-color: #dc2626; }
        .btn-warning { background-color: #ffffff; color: #334155; border: 1px solid var(--border-color); }
        .btn-warning:hover { background-color: #f8fafc; }
        .btn-info { background-color: #0284c7; color: #ffffff; }
        .btn-info:hover { background-color: #0369a1; }

        .split-inventory-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }
        
        .three-col-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            align-items: start;
        }

        .split-col { min-width: 0; }

        .excel-table-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 20px;
        }

        .excel-grid {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            min-width: 100%;
            font-size: 0.8rem;
        }
        .excel-grid th, .excel-grid td {
            padding: 7px 9px;
            border: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .excel-grid th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.4px;
        }
        .excel-grid tbody tr:nth-child(even) { background-color: #fafafa; }
        .excel-grid tbody tr:hover { background-color: #f1f5f9; }

        code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.75rem; background: #f1f5f9; padding: 2px 4px; border-radius: 4px; color: #334155; border: 1px solid #cbd5e1; }

        .badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.68rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .badge-success { background-color: var(--success-bg); color: var(--success); }
        .badge-warning { background-color: var(--warning-bg); color: var(--warning); }
        .badge-danger { background-color: var(--danger-bg); color: var(--danger); }
        
        .badge-special { background-color: #fef08a; color: #854d0e; font-weight: 700; padding: 2px 5px; border-radius: 4px; font-size: 0.65rem; border: 1px solid #fde047; white-space: nowrap; }
        .badge-roll { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 700; }
        .badge-mark { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; padding: 2px 5px; border-radius: 4px; font-size: 0.65rem; font-weight: 600; }
        .badge-co { color: #0369a1; font-size: 0.75rem; font-weight: 600; }
        
        .priority-rush { background-color: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; font-weight: 700; animation: pulse 1.5s infinite; }
        .priority-normal { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .priority-wait { background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

        .branch-badge { background-color: transparent !important; color: #7e22ce; border: none !important; padding: 0 !important; font-weight: 700; }

        .three-screen-container {
            display: grid;
            grid-template-columns: 1.2fr 240px 240px;
            gap: 12px;
            align-items: start;
        }

        .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 10px;
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
        }
        .pagination a, .pagination span {
            padding: 4px 8px;
            font-size: 0.78rem;
            font-weight: 500;
            border-radius: 4px;
            text-decoration: none;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            background: #ffffff;
        }
        .pagination a:hover { background: #f1f5f9; }
        .pagination .active { background: var(--primary); color: white; border-color: var(--primary); }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 16px;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--radius);
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .modal-content h3 { margin-bottom: 16px; font-size: 1.1rem; font-weight: 600; color: var(--text-main); }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.8rem; font-weight: 600; color: #475569; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; color: var(--text-main);
            background-color: #fff;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-group input:read-only { background-color: #f8fafc; color: var(--text-muted); }
        .modal-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 8px; }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        @media print {
            .sidebar, .toolbar, .alert, .modal, .no-print, .mobile-header { display: none !important; }
            .main-content { padding: 0 !important; margin: 0 !important; width: 100% !important; }
            .excel-table-card { border: 1px solid #000 !important; box-shadow: none !important; }
            body { background: #fff !important; color: #000 !important; }
        }

        @media (max-width: 1200px) {
            .three-screen-container, .split-inventory-container, .three-col-container { grid-template-columns: 1fr !important; }
        }

        @media (max-width: 768px) {
            .mobile-header { display: flex; }
            body { flex-direction: column; padding-top: 60px; }
            .sidebar {
                position: fixed;
                top: 60px; left: -260px;
                height: calc(100vh - 60px);
                width: 260px;
                background: #ffffff;
                transition: transform 0.3s ease;
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            .sidebar.mobile-open { transform: translateX(260px); }
            .main-content { padding: 14px; margin-left: 0 !important; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar .btn { width: 100%; justify-content: center; }
            .modal-content { max-width: 100%; margin: 10px; padding: 16px; }
        }
    </style>
</head>
<body>

<div class="mobile-header no-print">
    <h3>Bicutan Hub</h3>
    <button class="menu-toggle-btn" onclick="toggleMobileMenu()">☰</button>
</div>

<div class="sidebar no-print" id="sidebarNav">
    <div class="sidebar-brand">
        <span>Bicutan Inventory Hub</span>
    </div>
    <ul class="sidebar-menu">
        <li class="<?php echo $active_tab==='items'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=items">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    Items & Stocks
                </span>
            </a>
        </li>
        
        <li class="<?php echo $active_tab==='queue'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=queue">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    Queue & Production
                </span>
                <?php if($rush_count > 0): ?>
                    <span class="nav-badge-rush"><?php echo $rush_count; ?> RUSH</span>
                <?php elseif($pending_req_count > 0): ?>
                    <span class="nav-badge"><?php echo $pending_req_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="<?php echo $active_tab==='special_cut'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=special_cut">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><circle cx="6" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><line x1="20" y1="4" x2="8.12" y2="15.88"></line><line x1="14.47" y1="14.48" x2="20" y2="20"></line><line x1="8.12" y1="8.12" x2="12" y2="12"></line></svg>
                    Special Cut Orders
                </span>
                <?php if(count($special_cut_orders) > 0): ?>
                    <span class="nav-badge" style="background:#e0e7ff; color:#3730a3;"><?php echo count($special_cut_orders); ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="<?php echo $active_tab==='shipping'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=shipping">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    Shipping & Tracking
                </span>
            </a>
        </li>

        <li class="<?php echo $active_tab==='completed'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=completed">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Complete Items
                </span>
                <?php if(isset($completed_count) && $completed_count > 0): ?>
                    <span class="nav-badge" style="background:#d1fae5; color:#065f46;"><?php echo $completed_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="<?php echo $active_tab==='all_requests'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=all_requests">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Request History
                </span>
            </a>
        </li>

        <li class="<?php echo $active_tab==='my_orders'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=my_orders">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Company Search
                </span>
            </a>
        </li>

        <li class="<?php echo $active_tab==='general_info'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=general_info">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    General Information
                </span>
            </a>
        </li>

        <li class="<?php echo $active_tab==='categories_markings'?'active':'';?>">
            <a href="<?php echo $self; ?>?tab=categories_markings">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                    Categories, Sizes & Markings
                </span>
            </a>
        </li>
        
        <li style="margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px;">
            <a href="<?php echo $self; ?>?action=export_backup_csv" style="color: #059669; font-weight: 600;">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Generate Backup CSV
                </span>
            </a>
        </li>

        <!-- DYNAMIC USER NAME AT LEFT BOTTOM -->
        <li style="margin-top: auto; padding: 16px 12px; border-top: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
            </div>
            <div style="line-height: 1.2;">
                <span style="display: block; font-weight: 700; color: var(--text-main); font-size: 0.85rem;">
                    <?php echo htmlspecialchars($_SESSION['name'] ?? 'Unknown User'); ?>
                </span>
                <span style="font-size: 0.7rem; color: var(--text-muted);">
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'User Account'); ?>
                </span>
            </div>
        </li>
        <li>
            <a href="logout.php" style="color: var(--danger); font-weight: 600;">
                <span class="menu-content">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Logout
                </span>
            </a>
        </li>
    </ul>
</div>

<div class="main-content">
    
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] === 'insufficient_stock'): ?>
            <div class="alert alert-danger">Cannot Approve: Insufficient stock available! Request needs production.</div>
        <?php elseif($_GET['msg'] === 'bulk_approved'): ?>
            <div class="alert alert-success">All selected stock requests have been successfully approved and inventory updated!</div>
        <?php elseif($_GET['msg'] === 'bulk_partial_stock'): ?>
            <div class="alert alert-danger">Some requests were approved, but others failed due to insufficient stock!</div>
        <?php elseif($_GET['msg'] === 'duplicate_sku_error'): ?>
            <div class="alert alert-danger">Error: SKU duplication collision intercepted. Please try submitting again.</div>
        <?php elseif($_GET['msg'] === 'req_scheduled'): ?>
            <div class="alert alert-success">Delivery schedule successfully confirmed! Item is now ready to ship.</div>
        <?php elseif($_GET['msg'] === 'req_shipped'): ?>
            <div class="alert alert-success">Item has been marked as shipped and is now en route!</div>
        <?php elseif($_GET['msg'] === 'req_received'): ?>
            <div class="alert alert-success">Item has been successfully received and completed!</div>
        <?php elseif($_GET['msg'] === 'unauthorized_action'): ?>
            <div class="alert alert-danger">❌ Error: Unauthorized action.</div>
        <?php else: ?>
            <div class="alert alert-success">Action completed successfully!</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="stats-grid no-print">
        <div class="stat-card">
            <span>Total Unique Items</span>
            <h3><?php echo number_format(count($items)); ?></h3>
        </div>
        <div class="stat-card">
            <span>Pending Queue</span>
            <h3 style="color: var(--warning);"><?php echo $pending_req_count; ?></h3>
        </div>
        <div class="stat-card">
            <span>For Production</span>
            <h3 style="color: #c2410c;"><?php echo count($production_items); ?></h3>
        </div>
        <div class="stat-card">
            <span>Deducted Today</span>
            <h3 style="color: var(--danger);">-<?php echo number_format($total_deducted_today); ?></h3>
        </div>
        <div class="stat-card">
            <span>Available Stocks</span>
            <h3 style="color: var(--primary);"><?php echo number_format($total_stock); ?></h3>
        </div>
    </div>

    <?php if ($active_tab === 'items'): ?>
        <header>
            <h1>Inventory Management</h1>
            <p>Manage regular items and roll items in the split view below.</p>
        </header>

        <div class="split-inventory-container">
            <div class="split-col">
                <div class="toolbar">
                    <h2>Regular Items</h2>
                    <button class="btn" onclick="openModal('addItemModal')">+ Add Item</button>
                </div>

                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>SKU ID</th>
                                <th>Item Description</th>
                                <th>Qty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($regular_items)): ?>
                                <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding:24px;">No regular items.</td></tr>
                            <?php else: ?>
                                <?php foreach ($regular_items as $item): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($item['sku']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                        <td><strong><?php echo number_format($item['quantity']); ?></strong></td>
                                        <td>
                                            <div style="display:flex; gap:3px;">
                                                <button class="btn btn-warning" style="padding:3px 5px; font-size:0.68rem;" 
                                                        onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo addslashes($item['sku']); ?>', '<?php echo addslashes($item['category']); ?>', '<?php echo addslashes($item['size'] ?? 'Standard'); ?>', <?php echo $item['quantity']; ?>, 0, '')">Edit</button>
                                                <a href="<?php echo $self; ?>?action=delete_item&id=<?php echo $item['id']; ?>" class="btn btn-danger" style="padding:3px 5px; font-size:0.68rem;" onclick="return confirm('Delete this item?')">Del</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="split-col">
                <div class="toolbar">
                    <h2 style="color: #0369a1;">Roll Items</h2>
                    <button class="btn btn-success" onclick="openModal('addRollItemModal')">+ Add Roll</button>
                </div>

                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>SKU ID</th>
                                <th>Roll Description</th>
                                <th>Qty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roll_items)): ?>
                                <tr><td colspan="4" style="text-align:center; color: var(--text-muted); padding:24px;">No roll items.</td></tr>
                            <?php else: ?>
                                <?php foreach ($roll_items as $roll): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($roll['sku']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($roll['item_name']); ?></strong></td>
                                        <td><strong><?php echo number_format($roll['quantity']); ?></strong></td>
                                        <td>
                                            <div style="display:flex; gap:3px;">
                                                <button class="btn btn-warning" style="padding:3px 5px; font-size:0.68rem;" 
                                                        onclick="openEditModal(<?php echo $roll['id']; ?>, '<?php echo addslashes($roll['item_name']); ?>', '<?php echo addslashes($roll['sku']); ?>', '<?php echo addslashes($roll['category']); ?>', 'Standard', <?php echo $roll['quantity']; ?>, 1, '<?php echo $roll['roll_length'] ?? ''; ?>')">Edit</button>
                                                <a href="<?php echo $self; ?>?action=delete_item&id=<?php echo $roll['id']; ?>" class="btn btn-danger" style="padding:3px 5px; font-size:0.68rem;" onclick="return confirm('Delete this roll item?')">Del</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- COMPLETED ITEMS TAB -->
    <?php if ($active_tab === 'completed'): ?>
        <header>
            <h1>Completed & Accepted Items (Makati & Bicutan Hub)</h1>
            <p>Dito makikita ang listahan ng lahat ng mga na-approve, na-schedule, na-accept, o nakumpletong transaksiyon.</p>
        </header>

        <div class="excel-table-card">
            <table class="excel-grid">
                <thead>
                    <tr>
                        <th>Tracking ID</th>
                        <th>Company & Details</th>
                        <th>Marking & Info</th>
                        <th>Qty</th>
                        <th>Permanent Audit Logs</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completed_orders)): ?>
                        <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding:32px;">Wala pang completed o approved records.</td></tr>
                    <?php else: ?>
                        <?php foreach ($completed_orders as $comp_item): ?>
                            <tr>
                                <td style="vertical-align: top;">
                                    <code>#TRK-<?php echo $comp_item['id']; ?></code>
                                </td>
                                <td>
                                    <strong style="color:var(--text-main);"><?php echo htmlspecialchars($comp_item['item_name']); ?></strong><br>
                                    <?php if(isset($comp_item['request_type']) && $comp_item['request_type'] === 'SPECIAL'): ?>
                                        <span class="badge-special">✂️ <?php echo htmlspecialchars($comp_item['width'] . ' x ' . $comp_item['height'] . ' ' . $comp_item['unit']); ?></span>
                                    <?php endif; ?>
                                    <br>
                                    <span class="badge branch-badge"><?php echo htmlspecialchars($comp_item['branch'] ?? 'Makati Bicutan Hub'); ?></span>
                                    <?php if(!empty($comp_item['company_name'])): ?>
                                        <br><small style="font-weight:700; color:#334155; margin-top:2px; display:inline-block;">🏢 <?php echo htmlspecialchars($comp_item['company_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($comp_item['marking'])): ?>
                                        <span class="badge-mark"><?php echo htmlspecialchars($comp_item['marking']); ?></span>
                                    <?php else: ?>
                                        <small style="color:var(--text-muted);">No Marking</small>
                                    <?php endif; ?>
                                    <?php if(!empty($comp_item['care_of'])): ?>
                                        <br><span class="badge-co" style="margin-top:4px;">c/o <?php echo htmlspecialchars($comp_item['care_of']); ?></span>
                                    <?php endif; ?>
                                    <br><small style="color:var(--text-muted); display:inline-block; margin-top: 4px;">Req by: <?php echo htmlspecialchars($comp_item['requested_by']); ?></small>
                                </td>
                                <td style="vertical-align: top;">
                                    <strong style="font-size: 1rem; color:var(--text-main);"><?php echo number_format($comp_item['quantity']); ?></strong>
                                </td>
                                <td style="background-color: #f8fafc;">
                                    <?php if (!empty($comp_item['approved_by'])): ?>
                                        <small style="color:#0369a1; display:block;">✔️ Approved by: <strong><?php echo htmlspecialchars($comp_item['approved_by']); ?></strong></small>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($comp_item['confirmed_by'])): ?>
                                        <small style="color:#d97706; display:block;">📅 Confirmed by: <strong><?php echo htmlspecialchars($comp_item['confirmed_by']); ?></strong></small>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($comp_item['shipped_by'])): ?>
                                        <small style="color:#059669; display:block;">🚚 Shipped by: <strong><?php echo htmlspecialchars($comp_item['shipped_by']); ?></strong></small>
                                    <?php endif; ?>

                                    <?php if (!empty($comp_item['received_by'])): ?>
                                        <small style="color:#7e22ce; display:block;">📦 Received by: <strong><?php echo htmlspecialchars($comp_item['received_by']); ?></strong></small>
                                    <?php endif; ?>
                                </td>
                                <td style="vertical-align: top;">
                                    <span class="badge badge-success"><?php echo htmlspecialchars($comp_item['status']); ?></span><br>
                                    <small style="color:var(--text-muted); display:inline-block; margin-top: 4px;">Upd: <?php echo date('M d, y h:ia', strtotime($comp_item['updated_at'])); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages_completed > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages_completed; $i++): ?>
                        <?php if ($i == $page_completed): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo $self; ?>?tab=completed&page_completed=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'queue'): ?>
        <header>
            <h1>3-Screen Live Operations Dashboard</h1>
            <p>1st Screen: Queue List | 2nd Screen: Live Stock Viewer | 3rd Screen: Items Needed for Production.</p>
        </header>

        <div class="three-screen-container">
            <div class="screen-col">
                <form action="<?php echo $self; ?>" method="POST" id="bulkApproveForm">
                    <input type="hidden" name="action" value="bulk_approve">
                    <input type="hidden" name="tab" value="queue">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="toolbar">
                        <h2>1st: Pending Queue (<?php echo count($queue_requests); ?>)</h2>
                        <div style="display: flex; gap: 4px;">
                            <?php if (!empty($queue_requests)): ?>
                                <button type="submit" class="btn btn-success" style="padding: 5px 8px; font-size:0.75rem;" onclick="return confirm('Approve all selected?')">✓ Approve</button>
                            <?php endif; ?>
                            <button type="button" class="btn" style="padding: 5px 8px; font-size:0.75rem;" onclick="openModal('addRequestModal')">+ New</button>
                        </div>
                    </div>

                    <div class="excel-table-card">
                        <table class="excel-grid">
                            <thead>
                                <tr>
                                    <th style="width: 20px; text-align: center;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                                    <th>Item ID</th>
                                    <th>Inventory Item Description</th>
                                    <th>Size & Quality</th>
                                    <th style="text-align: center;">Qty</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($queue_requests)): ?>
                                    <tr><td colspan="7" style="text-align:center; color: var(--text-muted); padding:20px;">Queue is empty!</td></tr>
                                <?php else: ?>
                                    <?php foreach ($queue_requests as $req): ?>
                                        <tr style="<?php echo ($req['priority'] === 'Rush') ? 'background-color: #fff5f5;' : ''; ?>">
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="request_ids[]" value="<?php echo $req['id']; ?>" class="rowCheckbox">
                                            </td>
                                            <td><code><?php echo htmlspecialchars($req['sku']); ?></code></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($req['item_name']); ?></strong><br>
                                                <small style="color: var(--text-muted); display:block; line-height:1.2;">
                                                    <?php echo !empty($req['company_name']) ? '<strong>' . htmlspecialchars($req['company_name']) . '</strong> • ' : ''; ?>
                                                    <?php echo htmlspecialchars($req['requested_by']); ?> (<?php echo htmlspecialchars($req['branch']); ?>)
                                                </small>
                                                <?php if(!empty($req['care_of'])): ?>
                                                    <span class="badge-co" style="margin-top:2px;">c/o <?php echo htmlspecialchars($req['care_of']); ?></span>
                                                <?php endif; ?>
                                                <?php if(!empty($req['remarks'])): ?>
                                                    <div style="margin-top: 4px; padding: 4px 6px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; font-size: 0.75rem; color: #92400e;">
                                                        💬 <strong>Remarks:</strong> <?php echo htmlspecialchars($req['remarks']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($req['request_type']) && $req['request_type'] === 'SPECIAL'): ?>
                                                    <span class="badge-special">✂️ <?php echo htmlspecialchars($req['width'] . ' x ' . $req['height'] . ' ' . $req['unit']); ?></span>
                                                <?php endif; ?>
                                                <br>
                                                <small style="color: var(--text-muted); display:block; margin-top:2px;">
                                                    📅 Order Date: <strong><?php echo !empty($req['target_date']) ? date('M d, Y', strtotime($req['target_date'])) : date('M d, Y', strtotime($req['created_at'])); ?></strong>
                                                </small>
                                                <?php if($req['priority'] === 'Rush'): ?>
                                                    <span class="badge priority-rush" style="margin-top:2px;">🚨 RUSH</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;"><strong><?php echo number_format($req['quantity']); ?></strong></td>
                                            <td>
                                                <?php if(isset($req['request_type']) && $req['request_type'] === 'SPECIAL'): ?>
                                                    <span class="badge-special">⚙️ SPECIAL</span>
                                                <?php elseif($req['available_stock'] <= 0): ?>
                                                    <span class="badge badge-danger">0 Stock</span><br>
                                                    <span class="badge" style="background:#fff7ed; color:#c2410c; margin-top:2px;">⚠️ PROD NEEDED</span>
                                                <?php elseif($req['available_stock'] < $req['quantity']): ?>
                                                    <span class="badge badge-warning">Short (-<?php echo ($req['quantity'] - $req['available_stock']); ?>)</span><br>
                                                    <span class="badge" style="background:#fff7ed; color:#c2410c; margin-top:2px;">⚠️ PROD NEEDED</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">OK (<?php echo $req['available_stock']; ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display:flex; gap:2px;">
                                                    <a href="<?php echo $self; ?>?action=approve_request&id=<?php echo $req['id']; ?>&tab=queue" class="btn btn-success" style="padding:2px 5px; font-size:0.68rem;" onclick="return confirm('Approve?')">✓</a>
                                                    <a href="<?php echo $self; ?>?action=reject_request&id=<?php echo $req['id']; ?>&tab=queue" class="btn btn-danger" style="padding:2px 5px; font-size:0.68rem;" onclick="return confirm('Reject?')">✕</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="screen-col">
                <div class="toolbar" style="margin-bottom: 6px;">
                    <h2>2nd: Stock Levels</h2>
                </div>
                <div style="margin-bottom: 8px;">
                    <input type="text" id="stockSearchInput" onkeyup="filterStockList()" placeholder="🔍 Search SKU..." style="width:100%; padding: 5px 8px; border-radius: 4px; border: 1px solid var(--border-color); font-size: 0.78rem;">
                </div>

                <div class="excel-table-card" style="max-height: 520px; overflow-y: auto;">
                    <table class="excel-grid" id="stockSearchTable">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th style="text-align: right; width: 80px;">Avail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="2" style="text-align:center; padding:10px;">No items found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td style="padding: 6px 8px; vertical-align: top;">
                                            <strong style="font-size:0.78rem; display:block; line-height: 1.2;"><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                            <code style="font-size:0.68rem; padding: 1px 3px;"><?php echo htmlspecialchars($item['sku']); ?></code>
                                        </td>
                                        <td style="text-align: right; padding: 6px 8px;">
                                            <div>
                                                <?php if($item['quantity'] <= 0): ?>
                                                    <span class="badge badge-danger" style="font-size:0.72rem; padding: 2px 5px;">0</span>
                                                <?php elseif($item['quantity'] < 5): ?>
                                                    <span class="badge badge-warning" style="font-size:0.72rem; padding: 2px 5px;"><?php echo $item['quantity']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-success" style="font-size:0.72rem; padding: 2px 5px;"><?php echo $item['quantity']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="margin-top: 4px;">
                                                <button type="button" class="btn btn-info" style="font-size: 0.65rem; padding: 3px 5px; width: 100%; justify-content: center;" 
                                                        onclick="viewItemOrders(<?php echo $item['id']; ?>, this.getAttribute('data-name'))" 
                                                        data-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                                    Orders
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="screen-col">
                <div class="toolbar" style="margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="color: #c2410c;">3rd: For Production</h2>
                    <a href="<?php echo $self; ?>?action=export_company_shortage_excel" class="btn btn-warning" style="padding: 4px 8px; font-size: 0.75rem; background: #fffbeb; color: #9a3412; border: 1px solid #fde68a;">
                        📊 Excel Report
                    </a>
                </div>
                <div style="margin-bottom: 8px;">
                    <span class="badge" style="background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; width:100%; justify-content:center; padding: 5px;">
                        ⚠️ Needs Cutting / Production (<?php echo count($production_items); ?> Items)
                    </span>
                </div>

                <div class="excel-table-card" style="max-height: 520px; overflow-y: auto;">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th style="text-align: right; width: 75px;">Needed Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($production_items)): ?>
                                <tr><td colspan="2" style="text-align:center; padding:20px; color:var(--success);">✅ All requests have available stocks! Nothing needs production.</td></tr>
                            <?php else: ?>
                                <?php foreach ($production_items as $p_item): ?>
                                    <tr style="background-color: #fffbeb;">
                                        <td style="padding: 6px 8px;">
                                            <strong style="font-size:0.78rem; color:#9a3412; display:block; line-height: 1.2;">
                                                <?php echo htmlspecialchars($p_item['name']); ?>
                                            </strong>
                                            <?php if($p_item['type'] === 'SPECIAL'): ?>
                                                <span class="badge-special" style="margin-top:2px; display:inline-block;">SPECIAL CUT</span>
                                            <?php else: ?>
                                                <small style="color:var(--text-muted); font-size:0.7rem;">Stock: <?php echo $p_item['available_stock']; ?></small>
                                            <?php endif; ?>
                                            <?php if($p_item['rush_count'] > 0): ?>
                                                <span class="badge priority-rush" style="font-size:0.65rem; padding:1px 4px; margin-left:2px;">🚨 <?php echo $p_item['rush_count']; ?> RUSH</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; padding: 6px 8px;">
                                            <strong style="color:#c2410c; font-size:0.85rem; display:block;">
                                                +<?php echo number_format($p_item['shortage_qty']); ?>
                                            </strong>
                                            <small style="font-size:0.65rem; color:var(--text-muted);">Shortage</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'special_cut'): ?>
        <header>
            <h1>Special Cut Orders</h1>
            <p>Monitors all filtered orders and requests specifically categorized under Special Cut items, sizes, or custom dimensions.</p>
        </header>

        <div class="excel-table-card">
            <table class="excel-grid">
                <thead>
                    <tr>
                        <th>Inventory Item ID</th>
                        <th>Inventory Item Description</th>
                        <th>Size & Quality</th>
                        <th>Req ID</th>
                        <th>Branch / Company</th>
                        <th>Requested By</th>
                        <th>Marking</th>
                        <th>Qty</th>
                        <th>Timer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($special_cut_orders)): ?>
                        <tr><td colspan="9" style="text-align:center; color: var(--text-muted); padding:32px;">No orders or requests found for Special Cut.</td></tr>
                    <?php else: ?>
                        <?php foreach ($special_cut_orders as $sc): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($sc['sku']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($sc['item_name']); ?></strong></td>
                                <td>
                                    <span class="badge-special">✂️ W: <?php echo $sc['width']; ?> x H: <?php echo $sc['height']; ?> <?php echo htmlspecialchars($sc['unit'] ?? ''); ?></span>
                                </td>
                                <td><code>#REQ-<?php echo $sc['id']; ?></code></td>
                                <td>
                                    <span class="badge branch-badge"><?php echo htmlspecialchars($sc['branch'] ?? 'Makati Bicutan Hub'); ?></span>
                                    <?php if(!empty($sc['company_name'])): ?><br><small style="font-weight:600; color:#334155;"><?php echo htmlspecialchars($sc['company_name']); ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sc['requested_by']); ?></strong>
                                    <?php if(!empty($sc['care_of'])): ?><br><span class="badge-co">c/o <?php echo htmlspecialchars($sc['care_of']); ?></span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($sc['marking'])): ?>
                                        <span class="badge-mark"><?php echo htmlspecialchars($sc['marking']); ?></span>
                                    <?php else: ?>
                                        <small style="color:var(--text-muted);">None</small>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo number_format($sc['quantity']); ?></strong></td>
                                <td><?php echo getCountdownBadge($sc); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'my_orders'): ?>
        <header>
            <h1>Branch & Company Request Search</h1>
            <p>Filter and view requests coming from specific hubs, companies, or sales c/o personnel.</p>
        </header>

        <div class="excel-table-card" style="padding: 16px; margin-bottom: 20px;">
            <form action="<?php echo $self; ?>" method="GET">
                <input type="hidden" name="tab" value="my_orders">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Search by Company, Branch, Requester Name or c/o Salesperson:</label>
                    <div style="display:flex; gap:8px; flex-wrap: wrap;">
                        <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="e.g. Triplex Corp or Juan Dela Cruz" required style="flex:1; min-width: 200px;">
                        <button type="submit" class="btn">Filter Requests</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($search_name)): ?>
            <div class="excel-table-card">
                <table class="excel-grid">
                    <thead>
                        <tr>
                            <th>Inventory Item ID</th>
                            <th>Inventory Item Description</th>
                            <th>Size & Quality</th>
                            <th>Req ID</th>
                            <th>Branch / Company</th>
                            <th>Requested By</th>
                            <th>Marking</th>
                            <th>Qty</th>
                            <th>Timer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_orders)): ?>
                            <tr><td colspan="9" style="text-align:center; color: var(--text-muted); padding:32px;">No orders or requests found for "<?php echo htmlspecialchars($search_name); ?>".</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_orders as $order): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($order['sku']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($order['item_name']); ?></strong></td>
                                    <td>
                                        <?php if(isset($order['request_type']) && $order['request_type'] === 'SPECIAL'): ?>
                                            <span class="badge-special">W: <?php echo $order['width']; ?> x H: <?php echo $order['height']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code>#REQ-<?php echo $order['id']; ?></code></td>
                                    <td>
                                        <span class="badge branch-badge"><?php echo htmlspecialchars($order['branch'] ?? 'Makati Bicutan Hub'); ?></span>
                                        <?php if(!empty($order['company_name'])): ?><br><small style="font-weight:600; color:#334155;"><?php echo htmlspecialchars($order['company_name']); ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['requested_by']); ?></strong>
                                        <?php if(!empty($order['care_of'])): ?><br><span class="badge-co">c/o <?php echo htmlspecialchars($order['care_of']); ?></span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($order['marking'])): ?>
                                            <span class="badge-mark"><?php echo htmlspecialchars($order['marking']); ?></span>
                                        <?php else: ?>
                                            <small style="color:var(--text-muted);">None</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo number_format($order['quantity']); ?></strong></td>
                                    <td><?php echo getCountdownBadge($order); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $self; ?>?tab=my_orders&search_name=<?php echo urlencode($search_name); ?>&page=<?php echo ($page - 1); ?>">« Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $self; ?>?tab=my_orders&search_name=<?php echo urlencode($search_name); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $self; ?>?tab=my_orders&search_name=<?php echo urlencode($search_name); ?>&page=<?php echo ($page + 1); ?>">Next »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($active_tab === 'all_requests'): ?>
        <header>
            <h1>Stock Request History</h1>
            <p>Archive and complete transaction logs of past stock requests across all company branches.</p>
        </header>

        <div class="excel-table-card">
            <table class="excel-grid">
                <thead>
                    <tr>
                        <th>Inventory Item ID</th>
                        <th>Inventory Item Description</th>
                        <th>Size & Quality</th>
                        <th>Req ID</th>
                        <th>Branch / Company</th>
                        <th>Requested By</th>
                        <th>Marking</th>
                        <th>Qty</th>
                        <th>Timer & Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_requests)): ?>
                        <tr><td colspan="9" style="text-align:center; color: var(--text-muted); padding:32px;">No stock request records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_requests as $req): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($req['sku']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($req['item_name']); ?></strong></td>
                                <td>
                                    <?php if(isset($req['request_type']) && $req['request_type'] === 'SPECIAL'): ?>
                                        <span class="badge-special">W: <?php echo $req['width']; ?> x H: <?php echo $req['height']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><code>#REQ-<?php echo $req['id']; ?></code></td>
                                <td>
                                    <span class="badge branch-badge"><?php echo htmlspecialchars($req['branch'] ?? 'Makati Bicutan Hub'); ?></span>
                                    <?php if(!empty($req['company_name'])): ?><br><small style="font-weight:600; color:#334155;"><?php echo htmlspecialchars($req['company_name']); ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($req['requested_by']); ?></strong>
                                    <?php if(!empty($req['care_of'])): ?><br><span class="badge-co">c/o <?php echo htmlspecialchars($req['care_of']); ?></span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($req['marking'])): ?>
                                        <span class="badge-mark"><?php echo htmlspecialchars($req['marking']); ?></span>
                                    <?php else: ?>
                                        <small style="color:var(--text-muted);">None</small>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo number_format($req['quantity']); ?></strong></td>
                                <td>
                                    <?php echo getCountdownBadge($req); ?>
                                    <?php if (!empty($req['approved_by'])): ?>
                                        <br><small style="color:#0369a1;">Approved by: <strong><?php echo htmlspecialchars($req['approved_by']); ?></strong></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($history_total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($history_page > 1): ?>
                        <a href="<?php echo $self; ?>?tab=all_requests&h_page=<?php echo ($history_page - 1); ?>">« Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $history_total_pages; $i++): ?>
                        <?php if ($i == $history_page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo $self; ?>?tab=all_requests&h_page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($history_page < $history_total_pages): ?>
                        <a href="<?php echo $self; ?>?tab=all_requests&h_page=<?php echo ($history_page + 1); ?>">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'general_info'): ?>
        <header>
            <h1>General Information Transaction</h1>
            <p>Excel-style spreadsheet ledger tracking added stocks in Bicutan, total requests from Makati, and deducted inventory stocks.</p>
        </header>

        <div class="excel-table-card" style="padding: 16px; margin-bottom: 20px;">
            <form action="<?php echo $self; ?>" method="GET">
                <input type="hidden" name="tab" value="general_info">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px 100px; gap: 12px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Filter Transaction Type:</label>
                        <select name="filter_type">
                            <option value="">All Transactions</option>
                            <option value="ADDED_STOCK" <?php echo ($filter_type === 'ADDED_STOCK') ? 'selected' : ''; ?>>Added Stocks in Bicutan</option>
                            <option value="MAKATI_REQUEST" <?php echo ($filter_type === 'MAKATI_REQUEST') ? 'selected' : ''; ?>>Total Requests from Makati</option>
                            <option value="DEDUCTED_STOCK" <?php echo ($filter_type === 'DEDUCTED_STOCK') ? 'selected' : ''; ?>>Deducted Stocks in Bicutan</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Start Date:</label>
                        <input type="date" name="filter_start" value="<?php echo htmlspecialchars($filter_start); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>End Date:</label>
                        <input type="date" name="filter_end" value="<?php echo htmlspecialchars($filter_end); ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn" style="width: 100%; justify-content: center;">Filter</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" style="width: 100%; justify-content: center;" onclick="window.print()">Print Sheet</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="excel-table-card">
            <table class="excel-grid">
                <thead>
                    <tr>
                        <th style="width: 80px;">Log ID</th>
                        <th style="width: 220px;">Transaction Type</th>
                        <th>Description / Details</th>
                        <th style="width: 130px; text-align: right;">Quantity Impact</th>
                        <th style="width: 170px;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($git_logs)): ?>
                        <tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding:32px;">No transaction logs recorded matching the criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($git_logs as $log): ?>
                            <tr>
                                <td><code>#<?php echo $log['id']; ?></code></td>
                                <td>
                                    <?php if($log['transaction_type'] === 'ADDED_STOCK'): ?>
                                        <span class="badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;">Added in Bicutan</span>
                                    <?php elseif($log['transaction_type'] === 'MAKATI_REQUEST'): ?>
                                        <span class="badge" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">Request from Makati</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#fef2f2; color:#dc2626; border:1px solid #fca5a5;">Deducted in Bicutan</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td style="text-align: right;">
                                    <?php if($log['quantity_change'] > 0): ?>
                                        <strong style="color: var(--success);">+<?php echo number_format($log['quantity_change']); ?></strong>
                                    <?php elseif($log['quantity_change'] < 0): ?>
                                        <strong style="color: var(--danger);"><?php echo number_format($log['quantity_change']); ?></strong>
                                    <?php else: ?>
                                        <strong style="color: var(--text-muted);">0</strong>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($active_tab === 'categories_markings'): ?>
        <header>
            <h1>Categories, Sizes & Markings Configuration</h1>
            <p>Manage inventory classifications, size options (with CRUD delete for duplicates), and request markings.</p>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <div class="excel-table-card" style="padding: 16px; margin-bottom: 16px;">
                    <form action="<?php echo $self; ?>?tab=categories_markings" method="POST">
                        <input type="hidden" name="action" value="add_category">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Add New Category</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="category_name" placeholder="e.g. Copy Paper" required style="flex:1;">
                                <button type="submit" class="btn">+ Add</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="2" style="text-align:center; padding:20px; color:var(--text-muted);">No categories found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td>
                                            <a href="<?php echo $self; ?>?action=delete_category&id=<?php echo $cat['id']; ?>&tab=categories_markings" class="btn btn-danger" style="padding:3px 6px; font-size:0.7rem;" onclick="return confirm('Delete this category?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="excel-table-card" style="padding: 16px; margin-bottom: 16px;">
                    <form action="<?php echo $self; ?>?tab=categories_markings" method="POST">
                        <input type="hidden" name="action" value="add_size">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Add New Size Option</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="size_name" placeholder="e.g. A3 Size" required style="flex:1;">
                                <button type="submit" class="btn">+ Add</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Size Option</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sizes_list)): ?>
                                <tr><td colspan="2" style="text-align:center; padding:20px; color:var(--text-muted);">No sizes found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sizes_list as $sz): ?>
                                    <tr>
                                        <td><span class="badge" style="background:#f3f4f6; color:#1f2937;"><?php echo htmlspecialchars($sz['name']); ?></span></td>
                                        <td>
                                            <a href="<?php echo $self; ?>?action=delete_size&id=<?php echo $sz['id']; ?>&tab=categories_markings" class="btn btn-danger" style="padding:3px 6px; font-size:0.7rem;" onclick="return confirm('Delete this size option?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="excel-table-card" style="padding: 16px; margin-bottom: 16px;">
                    <form action="<?php echo $self; ?>?tab=categories_markings" method="POST">
                        <input type="hidden" name="action" value="add_marking">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Add New Marking Type</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="marking_name" placeholder="e.g. Home Carry" required style="flex:1;">
                                <button type="submit" class="btn">+ Add</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Marking Name</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($markings_list)): ?>
                                <tr><td colspan="2" style="text-align:center; padding:20px; color:var(--text-muted);">No markings found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($markings_list as $mk): ?>
                                    <tr>
                                        <td><span class="badge-mark" style="font-size: 0.8rem; padding: 3px 6px;"><?php echo htmlspecialchars($mk['name']); ?></span></td>
                                        <td>
                                            <a href="<?php echo $self; ?>?action=delete_marking&id=<?php echo $mk['id']; ?>&tab=categories_markings" class="btn btn-danger" style="padding:3px 6px; font-size:0.7rem;" onclick="return confirm('Delete this marking?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- SHIPPING TAB -->
    <?php if ($active_tab === 'shipping'): ?>
        <header>
            <h1>Shipping & Tracking (3-Stage Workflow)</h1>
            <p>1) Confirm delivery dates, 2) Ship physical items, 3) Monitor tracking en route.</p>
        </header>

        <div class="toolbar" style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
            <a href="<?php echo $self; ?>?action=export_shipping_excel" class="btn btn-success" style="font-size: 0.85rem; padding: 8px 14px; background-color: #059669;">
                📊 Generate Excel Report
            </a>
        </div>

        <div class="three-col-container">
            <div class="split-col">
                <div class="toolbar" style="align-items: center; justify-content: space-between;">
                    <h2 style="color: #d97706;">📅 Stage 1: Schedule</h2>
                    <form action="<?php echo $self; ?>" method="GET" style="margin: 0;">
                        <input type="hidden" name="tab" value="shipping">
                        <select name="sort_stage1" onchange="this.form.submit()" style="padding: 4px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border-color); cursor: pointer;">
                            <option value="priority" <?php echo (isset($_GET['sort_stage1']) && $_GET['sort_stage1'] === 'priority') ? 'selected' : ''; ?>>Sort: Priority (Rush First)</option>
                            <option value="date_asc" <?php echo (isset($_GET['sort_stage1']) && $_GET['sort_stage1'] === 'date_asc') ? 'selected' : ''; ?>>Sort: Nearest Date</option>
                            <option value="date_desc" <?php echo (isset($_GET['sort_stage1']) && $_GET['sort_stage1'] === 'date_desc') ? 'selected' : ''; ?>>Sort: Furthest Date</option>
                        </select>
                    </form>
                </div>
                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Tracking & Date</th>
                                <th>Item & Priority</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approved_orders)): ?>
                                <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding:20px;">No pending items to schedule.</td></tr>
                            <?php else: ?>
                                <?php foreach ($approved_orders as $req): ?>
                                    <tr>
                                        <td>
                                            <code>#TRK-<?php echo $req['id']; ?></code><br>
                                            <small style="color:var(--text-muted);">
                                                Target: <strong><?php echo !empty($req['target_date']) ? date('M d, Y', strtotime($req['target_date'])) : date('M d, Y', strtotime($req['created_at'])); ?></strong>
                                            </small>
                                            <?php if (!empty($req['approved_by'])): ?>
                                                <br><small style="color:#0369a1;">Approved: <strong><?php echo htmlspecialchars($req['approved_by']); ?></strong></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($req['item_name']); ?></strong> (x<?php echo number_format($req['quantity']); ?>)<br>
                                            
                                            <?php if($req['priority'] === 'Rush'): ?>
                                                <span class="badge priority-rush" style="font-size:0.6rem;">🚨 RUSH</span>
                                            <?php elseif($req['priority'] === 'Willing to Wait'): ?>
                                                <span class="badge priority-wait" style="font-size:0.6rem;">⏳ Willing to Wait</span>
                                            <?php else: ?>
                                                <span class="badge priority-normal" style="font-size:0.6rem;">📅 2-3 Days</span>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($req['marking'])): ?>
                                                <span class="badge-mark" style="font-size:0.6rem;"><?php echo htmlspecialchars($req['marking']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <a href="<?php echo $self; ?>?action=schedule_request&id=<?php echo $req['id']; ?>" class="btn btn-warning" style="padding: 4px 8px; font-size: 0.7rem;" onclick="return confirm('Confirm schedule based on target date?')">Confirm</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($total_pages_approved > 1): ?>
                        <div class="pagination">
                            <?php 
                                $current_sort = isset($_GET['sort_stage1']) ? $_GET['sort_stage1'] : 'priority';
                                for ($i = 1; $i <= $total_pages_approved; $i++): 
                            ?>
                                <?php if ($i == $page_approved): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo $self; ?>?tab=shipping&sort_stage1=<?php echo $current_sort; ?>&page_approved=<?php echo $i; ?>&page_scheduled=<?php echo $page_scheduled; ?>&page_shipped=<?php echo $page_shipped; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="split-col">
                <div class="toolbar">
                    <h2 style="color: #059669;">📦 Stage 2: Ship Item</h2>
                </div>
                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Tracking & Date</th>
                                <th>Item & Priority</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($scheduled_orders)): ?>
                                <tr><td colspan="3" style="text-align:center; color: var(--text-muted); padding:20px;">No scheduled items ready for dispatch.</td></tr>
                            <?php else: ?>
                                <?php foreach ($scheduled_orders as $req): ?>
                                    <tr>
                                        <td>
                                            <code>#TRK-<?php echo $req['id']; ?></code><br>
                                            <small style="color:var(--text-muted);">
                                                Target: <strong><?php echo !empty($req['target_date']) ? date('M d, Y', strtotime($req['target_date'])) : date('M d, Y', strtotime($req['created_at'])); ?></strong>
                                            </small>
                                            <?php if (!empty($req['approved_by'])): ?>
                                                <br><small style="color:#0369a1;">Approved: <strong><?php echo htmlspecialchars($req['approved_by']); ?></strong></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($req['item_name']); ?></strong> (x<?php echo number_format($req['quantity']); ?>)<br>
                                            
                                            <?php if($req['priority'] === 'Rush'): ?>
                                                <span class="badge priority-rush" style="font-size:0.6rem;">🚨 RUSH</span>
                                            <?php elseif($req['priority'] === 'Willing to Wait'): ?>
                                                <span class="badge priority-wait" style="font-size:0.6rem;">⏳ Willing to Wait</span>
                                            <?php else: ?>
                                                <span class="badge priority-normal" style="font-size:0.6rem;">📅 2-3 Days</span>
                                            <?php endif; ?>
                                            
                                            <br><span class="badge branch-badge" style="font-size:0.6rem;"><?php echo htmlspecialchars($req['branch']); ?></span>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <a href="<?php echo $self; ?>?action=ship_request&id=<?php echo $req['id']; ?>" class="btn btn-success" style="padding: 4px 8px; font-size: 0.7rem;" onclick="return confirm('Dispatch physical item now?')">Ship Out</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($total_pages_scheduled > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_pages_scheduled; $i++): ?>
                                <?php if ($i == $page_scheduled): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo $self; ?>?tab=shipping&page_approved=<?php echo $page_approved; ?>&page_scheduled=<?php echo $i; ?>&page_shipped=<?php echo $page_shipped; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="split-col">
                <div class="toolbar">
                    <h2 style="color: #0369a1;">🚚 Stage 3: In Transit</h2>
                </div>
                <div class="excel-table-card">
                    <table class="excel-grid">
                        <thead>
                            <tr>
                                <th>Tracking & Status</th>
                                <th>Item & Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shipped_orders)): ?>
                                <tr><td colspan="2" style="text-align:center; color: var(--text-muted); padding:20px;">No shipping records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($shipped_orders as $req): ?>
                                    <tr>
                                        <td>
                                            <code>#TRK-<?php echo $req['id']; ?></code><br>
                                            <small style="color:var(--text-muted);">
                                                Shipped: <strong><?php echo date('M d, Y', strtotime($req['updated_at'])); ?></strong>
                                            </small>
                                            <br>
                                            <?php if ($req['status'] === 'Shipped'): ?>
                                                <span class="badge" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; font-size:0.6rem; margin-top: 2px;">🚚 In Transit</span>
                                                <span class="badge" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a; font-size:0.6rem; margin-top: 4px; display:inline-block;">⏳ Waiting for Makati</span>
                                            <?php else: ?>
                                                <span class="badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:0.6rem; margin-top: 2px;">✅ Received by Makati</span>
                                            <?php endif; ?>
                                            <?php if (!empty($req['approved_by'])): ?>
                                                <br><small style="color:#0369a1;">Appr: <strong><?php echo htmlspecialchars($req['approved_by']); ?></strong></small>
                                            <?php endif; ?>
                                            <?php if (!empty($req['shipped_by'])): ?>
                                                <br><small style="color:#059669;">Ship: <strong><?php echo htmlspecialchars($req['shipped_by']); ?></strong></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($req['item_name']); ?></strong> (x<?php echo number_format($req['quantity']); ?>)<br>
                                            
                                            <?php if($req['priority'] === 'Rush'): ?>
                                                <span class="badge priority-rush" style="font-size:0.6rem;">🚨 RUSH</span>
                                            <?php elseif($req['priority'] === 'Willing to Wait'): ?>
                                                <span class="badge priority-wait" style="font-size:0.6rem;">⏳ Willing to Wait</span>
                                            <?php else: ?>
                                                <span class="badge priority-normal" style="font-size:0.6rem;">📅 2-3 Days</span>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($req['marking'])): ?>
                                                <span class="badge-mark" style="font-size:0.6rem;"><?php echo htmlspecialchars($req['marking']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($total_pages_shipped > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $total_pages_shipped; $i++): ?>
                                <?php if ($i == $page_shipped): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo $self; ?>?tab=shipping&page_approved=<?php echo $page_approved; ?>&page_scheduled=<?php echo $page_scheduled; ?>&page_shipped=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODALS -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <h3>Add Regular Item</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="create_item">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" required placeholder="e.g. Triplex Copy Paper A4">
            </div>
            <div class="form-group">
                <label>Item ID (SKU - System Generated)</label>
                <input type="text" value="<?php echo $next_preview_sku; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" list="categories_list" required placeholder="Select or type category...">
                <datalist id="categories_list">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['name']); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Size / Dimensions</label>
                <input type="text" name="size" list="sizes_list" value="Standard" required>
                <datalist id="sizes_list">
                    <?php foreach ($sizes_list as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['name']); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Initial Quantity</label>
                <input type="number" name="quantity" min="0" required value="0">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-warning" onclick="closeModal('addItemModal')">Cancel</button>
                <button type="submit" class="btn">Save Item</button>
            </div>
        </form>
    </div>
</div>

<div id="addRollItemModal" class="modal">
    <div class="modal-content">
        <h3>Add Roll Item</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="create_item">
            <input type="hidden" name="is_roll" value="1">
            <input type="hidden" name="size" value="Roll">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label>Roll Item Name</label>
                <input type="text" name="item_name" required placeholder="e.g. Plotter Paper Roll">
            </div>
            <div class="form-group">
                <label>Item ID (SKU - System Generated)</label>
                <input type="text" value="<?php echo $next_preview_sku; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" list="categories_list" required placeholder="Select or type category...">
            </div>
            <div class="form-group">
                <label>Roll Length (Meters)</label>
                <input type="number" step="0.01" name="roll_length" required placeholder="e.g. 50">
            </div>
            <div class="form-group">
                <label>Initial Quantity</label>
                <input type="number" name="quantity" min="0" required value="0">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-warning" onclick="closeModal('addRollItemModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Save Roll Item</button>
            </div>
        </form>
    </div>
</div>

<div id="editItemModal" class="modal">
    <div class="modal-content">
        <h3>Edit Item Details</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="item_name" id="edit_name" required>
            </div>
            <div class="form-group">
                <label>Item ID (SKU)</label>
                <input type="text" id="edit_sku" readonly>
            </div>
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" id="edit_category" list="categories_list" required>
            </div>

            <div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); margin-bottom: 14px;">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_roll" id="edit_is_roll" value="1" onchange="toggleRollInput('edit')"> 
                        <strong style="color: var(--primary);">Is this a Roll Item?</strong>
                    </label>
                </div>
                <div class="form-group" id="edit_roll_length_group" style="display: none; margin-bottom:0;">
                    <label>Roll Length (Meters)</label>
                    <input type="number" step="0.01" name="roll_length" id="edit_roll_length" placeholder="e.g. 50">
                </div>
            </div>

            <div class="form-group" id="edit_size_group">
                <label>Size / Dimensions</label>
                <input type="text" name="size" id="edit_size" list="sizes_list">
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" id="edit_quantity" min="0" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-warning" onclick="closeModal('editItemModal')">Cancel</button>
                <button type="submit" class="btn">Update Item</button>
            </div>
        </form>
    </div>
</div>

<div id="addRequestModal" class="modal">
    <div class="modal-content">
        <h3>File a Stock Request</h3>
        <form action="<?php echo $self; ?>" method="POST">
            <input type="hidden" name="action" value="create_request">
            <input type="hidden" name="redirect_tab" value="<?php echo $active_tab; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="form-group">
                <label>Branch / Hub Source</label>
                <select name="branch" required>
                    <option value="Makati Bicutan Hub" selected>Makati Bicutan Hub</option>
                    <option value="Main Warehouse">Main Warehouse</option>
                </select>
            </div>

            <div class="form-group">
                <label>Request Type</label>
                <select name="request_type" id="request_type_select" onchange="toggleRequestFields()" required>
                    <option value="STANDARD" selected>Standard Inventory Item</option>
                    <option value="SPECIAL">Special Cut / Custom Dimension</option>
                </select>
            </div>

            <div class="form-group" id="standard_item_group">
                <label>Select Standard Item</label>
                <select name="item_id">
                    <option value="" disabled selected>-- Select Item --</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?php echo $it['id']; ?>">
                            [<?php echo htmlspecialchars($it['sku']); ?>] <?php echo htmlspecialchars($it['item_name']); ?> - Size: <?php echo htmlspecialchars($it['size'] ?? 'Standard'); ?> (Stock: <?php echo $it['quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="special_cut_group" style="display: none; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); margin-bottom: 14px;">
                <label style="color: var(--primary); font-weight: 700; margin-bottom: 8px; display:block;">Special Cut Specifications</label>
                
                <div class="form-group">
                    <label>Paper / Item Name</label>
                    <input type="text" name="paper_item_name" placeholder="e.g. C2S Glossy Paper">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div class="form-group">
                        <label>Width</label>
                        <input type="number" step="0.01" name="width" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Height</label>
                        <input type="number" step="0.01" name="height" placeholder="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Unit of Measurement</label>
                    <select name="unit">
                        <option value="Inches">Inches</option>
                        <option value="CM">Centimeters (CM)</option>
                        <option value="MM">Millimeters (MM)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label>Special Cut Size Label</label>
                    <input type="text" name="special_cut_size" placeholder="e.g. 20 x 30 inches">
                </div>
            </div>

            <div class="form-group">
                <label>Marking / Handling Tag</label>
                <select name="marking">
                    <option value="">-- None / Standard --</option>
                    <?php foreach ($markings_list as $mk): ?>
                        <option value="<?php echo htmlspecialchars($mk['name']); ?>"><?php echo htmlspecialchars($mk['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>c/o Salesperson / Account</label>
                <input type="text" name="care_of" placeholder="e.g. Juan Dela Cruz">
            </div>

            <div class="form-group">
                <label>Remarks / Message (Optional)</label>
                <textarea name="remarks" rows="2" placeholder="Enter message or instructions..."></textarea>
            </div>

            <div class="form-group">
                <label>Priority Level / Schedule Queue</label>
                <select name="priority" required>
                    <option value="Rush">Rush (Immediate / Within Today)</option>
                    <option value="2-3 Days" selected>Standard (Within 2-3 Days)</option>
                    <option value="Willing to Wait">Low Priority (Willing to Wait)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Target / Order Date (Makati)</label>
                <input type="date" name="target_date" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>Requested By (Personnel)</label>
                <!-- Even if someone modifies this read-only value, the backend relies strictly on $_SESSION['name'] -->
                <input type="text" name="requested_by" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required placeholder="1">
            </div>

            <div class="form-group">
                <label>Purpose / Remarks</label>
                <textarea name="purpose" rows="2" required placeholder="e.g. Urgent customer order"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-warning" onclick="closeModal('addRequestModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<div id="viewOrdersModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <h3 style="margin-bottom: 5px;">Order Queue & History</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;" id="viewOrdersItemName"></p>
        
        <h4 style="color: #c2410c; margin-bottom: 8px; font-size: 0.95rem;">🔥 New / Queued Orders (Pending)</h4>
        <div class="excel-table-card" style="max-height: 250px; overflow-y: auto; margin-bottom: 20px;">
            <table class="excel-grid">
                <thead>
                    <tr>
                        <th style="text-align:center;">Priority</th>
                        <th>Date</th>
                        <th>Branch / Company</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody id="viewOrdersNewTableBody"></tbody>
            </table>
        </div>

        <h4 style="color: #059669; margin-bottom: 8px; font-size: 0.95rem;">✅ Previous Orders (Approved/Done)</h4>
        <div class="excel-table-card" style="max-height: 250px; overflow-y: auto;">
            <table class="excel-grid">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch / Company</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:center;">Status & User</th>
                    </tr>
                </thead>
                <tbody id="viewOrdersPreviousTableBody"></tbody>
            </table>
        </div>

        <div class="modal-actions">
            <button type="button" class="btn btn-warning" onclick="closeModal('viewOrdersModal')">Close Menu</button>
        </div>
    </div>
</div>

<script>
const itemOrdersMap = <?php echo json_encode($item_orders_map); ?>;

function viewItemOrders(itemId, itemName) {
    const orders = itemOrdersMap[itemId] || [];
    const newTbody = document.getElementById('viewOrdersNewTableBody');
    const prevTbody = document.getElementById('viewOrdersPreviousTableBody');
    document.getElementById('viewOrdersItemName').innerText = itemName;
    
    newTbody.innerHTML = '';
    prevTbody.innerHTML = '';
    
    let newHtml = '';
    let prevHtml = '';

    orders.forEach(o => {
        let comp = o.company_name ? o.company_name : o.branch;
        let dateToUse = o.target_date ? o.target_date : o.created_at;
        let dateObj = new Date(dateToUse);
        let formattedDate = dateObj.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        
        if (o.status === 'Pending') {
            let prioBadge = '';
            if (o.priority === 'Rush') prioBadge = '<span class="badge priority-rush">🚨 RUSH</span>';
            else if (o.priority === '2-3 Days') prioBadge = '<span class="badge priority-normal">Normal</span>';
            else prioBadge = '<span class="badge priority-wait">Wait</span>';

            newHtml += `<tr>
                <td style="text-align:center;">${prioBadge}</td>
                <td style="white-space:nowrap;">${formattedDate}</td>
                <td>
                    <strong style="color:#1e293b;">${comp}</strong><br>
                    <small style="color:var(--text-muted);">Req by: ${o.requested_by}</small>
                </td>
                <td style="text-align:center;"><strong>${o.quantity}</strong></td>
                <td style="text-align:center;"><span class="badge badge-warning">Pending</span></td>
            </tr>`;
        } else {
            let statBadge = '';
            if (o.status === 'Approved' || o.status === 'Scheduled') {
                statBadge = '<span class="badge badge-success">' + o.status + '</span>';
            } else if (o.status === 'Shipped' || o.status === 'Completed' || o.status === 'Shipping Already Accepted') {
                statBadge = '<span class="badge badge-success">Delivered/Shipped</span>';
            } else if (o.status === 'Rejected') {
                statBadge = '<span class="badge badge-danger">Rejected</span>';
            }

            if (o.approved_by) {
                statBadge += `<br><small style="color:#0369a1;">Appr: ${o.approved_by}</small>`;
            }
            if (o.confirmed_by) {
                statBadge += `<br><small style="color:#d97706;">Conf: ${o.confirmed_by}</small>`;
            }
            if (o.shipped_by) {
                statBadge += `<br><small style="color:#059669;">Ship: ${o.shipped_by}</small>`;
            }

            prevHtml += `<tr>
                <td style="white-space:nowrap;">${formattedDate}</td>
                <td>
                    <strong style="color:#1e293b;">${comp}</strong><br>
                    <small style="color:var(--text-muted);">Req by: ${o.requested_by}</small>
                </td>
                <td style="text-align:center;"><strong>${o.quantity}</strong></td>
                <td style="text-align:center;">${statBadge}</td>
            </tr>`;
        }
    });
    
    if (newHtml === '') newTbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No orders are currently in the queue.</td></tr>';
    else newTbody.innerHTML = newHtml;

    if (prevHtml === '') prevTbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">No previous (approved/rejected) orders found.</td></tr>';
    else prevTbody.innerHTML = prevHtml;

    openModal('viewOrdersModal');
}

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openEditModal(id, name, sku, category, size, quantity, isRoll, rollLength) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_size').value = size;
    document.getElementById('edit_quantity').value = quantity;
    
    var rollCheck = document.getElementById('edit_is_roll');
    rollCheck.checked = (isRoll == 1);
    document.getElementById('edit_roll_length').value = rollLength || '';
    toggleRollInput('edit');

    openModal('editItemModal');
}

function toggleRollInput(mode) {
    var isChecked = document.getElementById(mode + '_is_roll').checked;
    var lengthGroup = document.getElementById(mode + '_roll_length_group');
    var sizeGroup = document.getElementById(mode + '_size_group');

    if (isChecked) {
        lengthGroup.style.display = 'block';
        if (sizeGroup) sizeGroup.style.display = 'none';
    } else {
        lengthGroup.style.display = 'none';
        if (sizeGroup) sizeGroup.style.display = 'block';
    }
}

function toggleSelectAll(source) {
    var checkboxes = document.getElementsByClassName('rowCheckbox');
    for(var i = 0; i < checkboxes.length; i++) { 
        checkboxes[i].checked = source.checked;
    }
}

function toggleRequestFields() {
    var reqType = document.getElementById('request_type_select').value;
    var specialGroup = document.getElementById('special_cut_group');
    var standardGroup = document.getElementById('standard_item_group');
    
    if (reqType === 'SPECIAL') {
        specialGroup.style.display = 'block';
        standardGroup.style.display = 'none';
    } else {
        specialGroup.style.display = 'none';
        standardGroup.style.display = 'block';
    }
}

function toggleMobileMenu() {
    var sidebar = document.getElementById('sidebarNav');
    sidebar.classList.toggle('mobile-open');
}

function filterStockList() {
    var input = document.getElementById("stockSearchInput");
    if (!input) return;
    var filter = input.value.toLowerCase();
    var table = document.getElementById("stockSearchTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var text = tr[i].textContent || tr[i].innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function updateLiveCountdowns() {
    const timers = document.querySelectorAll('.live-countdown');
    timers.forEach(timer => {
        const targetStr = timer.getAttribute('data-target');
        const targetTime = new Date(targetStr).getTime();
        const now = new Date().getTime();
        const diff = targetTime - now;

        if (isNaN(targetTime)) {
            timer.innerHTML = '<span class="badge badge-warning">No Date</span>';
            return;
        }

        if (diff < 0) {
            const overdueMs = Math.abs(diff);
            const overdueDays = Math.floor(overdueMs / (1000 * 60 * 60 * 24));
            const overdueHrs = Math.floor((overdueMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            
            timer.innerHTML = `<span class="badge badge-danger">🚨 Delayed (${overdueDays}d ${overdueHrs}h)</span>`;
        } else {
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            if (days > 0) {
                timer.innerHTML = `<span class="badge" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">⏳ ${days}d ${hours}h left</span>`;
            } else {
                timer.innerHTML = `<span class="badge" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a;">⚠️ ${hours}h ${minutes}m left</span>`;
            }
        }
    });
}

setInterval(updateLiveCountdowns, 1000);
window.onload = updateLiveCountdowns;
</script>

</body>
</html>
