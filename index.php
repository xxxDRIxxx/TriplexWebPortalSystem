<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management Hub</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            color: #0f172a;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        body::before, body::after {
            content: "";
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: -1;
            opacity: 0.7;
            animation: float 15s infinite ease-in-out alternate;
        }

        body::before {
            width: 600px;
            height: 600px;
            background: #ffffff;
            top: -10%;
            left: -10%;
        }

        body::after {
            width: 500px;
            height: 500px;
            background: #bae6fd;
            bottom: -10%;
            right: -10%;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(120px, 80px) scale(1.1); }
        }

        .page {
            width: 100%;
            max-width: 1150px;
        }

        .header {
            text-align: center;
            color: #0f172a;
            margin-bottom: 35px;
        }

        .logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-size: 30px;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -0.8px;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 16px;
            font-weight: 500;
        }

        .dashboard {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.2));
            border-top: 1px solid rgba(255, 255, 255, 0.9);
            border-left: 1px solid rgba(255, 255, 255, 0.9);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 800;
            color: #1e293b;
        }

        .section-description {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
            font-weight: 500;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .card {
            position: relative;
            min-height: 130px;
            padding: 24px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            text-decoration: none;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 1);
            border-color: #ffffff;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.06);
        }

        .icon {
            position: relative;
            width: 54px;
            height: 54px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff, #f1f5f9);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 2px 4px 10px rgba(0, 0, 0, 0.03);
            color: #475569;
            font-size: 24px;
            transition: transform 0.3s ease;
        }

        .card:hover .icon {
            transform: scale(1.05);
        }

        .card-content {
            position: relative;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #1e293b;
        }

        .card-description {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .arrow {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 20px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .card:hover .arrow {
            transform: translate(6px, -50%);
            color: #0f172a;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 12px;
            }
            .header h1 {
                font-size: 28px;
            }
            .header p {
                font-size: 14px;
            }
            .dashboard {
                padding: 20px;
                border-radius: 20px;
            }
            .grid {
                grid-template-columns: 1fr;
            }
            .card {
                min-height: 100px;
            }
        }
    </style>
</head>
<body>

<div class="page">
    <div class="header">
        <div class="logo">📦</div>
        <h1>Inventory Management Hub</h1>
        <p>Centralized access to inventory and customer management</p>
    </div>

    <div class="dashboard">
        <div class="section-header">
            <div>
                <div class="section-title">Inventory Locations</div>
                <div class="section-description">Select an inventory location to continue</div>
            </div>
        </div>

        <div class="grid">
            <a href="Makati_inventory/Makati.php" class="card">
                <div class="icon">🏙️</div>
                <div class="card-content">
                    <div class="card-title">Makati</div>
                    <div class="card-description">Makati inventory</div>
                </div>
                <div class="arrow">→</div>
            </a>

            <a href="Bicutan_inventory/bicutan_front.php" class="card">
                <div class="icon">📦</div>
                <div class="card-content">
                    <div class="card-title">Bicutan</div>
                    <div class="card-description">Bicutan inventory</div>
                </div>
                <div class="arrow">→</div>
            </a>

            <a href="Robinson_inventory/Robinson_inventory.php" class="card">
                <div class="icon">🏢</div>
                <div class="card-content">
                    <div class="card-title">Robinson Inventory</div>
                    <div class="card-description">View Robinson inventory</div>
                </div>
                <div class="arrow">→</div>
            </a>

            <a href="Acsie_inventory/Acsie_inventory.php" class="card">
                <div class="icon">📋</div>
                <div class="card-content">
                    <div class="card-title">ACSie Inventory</div>
                    <div class="card-description">Manage ACSie inventory</div>
                </div>
                <div class="arrow">→</div>
            </a>

            <a href="BLS_inventory/BLS_inventory.php" class="card">
                <div class="icon">🗃️</div>
                <div class="card-content">
                    <div class="card-title">BLS Inventory</div>
                    <div class="card-description">View BLS inventory</div>
                </div>
                <div class="arrow">→</div>
            </a>

            <a href="Cerventes_inventory/Cerventes_inventory.php" class="card">
                <div class="icon">🏬</div>
                <div class="card-content">
                    <div class="card-title">Cervantes Inventory</div>
                    <div class="card-description">Manage Cervantes inventory</div>
                </div>
                <div class="arrow">→</div>
            </a>

            <a href="IT/ITtriplex.php" class="card">
                <div class="icon">👥</div>
                <div class="card-content">
                    <div class="card-title">IT-Triplex</div>
                    <div class="card-description">management</div>
                </div>
                <div class="arrow">→</div>
            </a>
        </div>
    </div>

    <div class="footer">
        Inventory Management System
    </div>
</div>

</body>
</html>