<?php
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

// Fetch all transactions for admin
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$query = "SELECT t.transaction_id, t.id_number, t.transaction_code, t.amount, t.access_time, t.status, e.name 
          FROM transactions t 
          LEFT JOIN entities e ON t.entity_id = e.entity_id";
if ($status_filter !== 'all') {
    $query .= " WHERE t.status = :status";
}
$query .= " ORDER BY t.access_time DESC";

$stmt = $pdo->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bindValue(':status', $status_filter);
}
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - CensaSight System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary: #0a6c74;
            --secondary: #00ddeb;
            --background: #f5f7fa;
            --card-bg: #ffffff;
            --text: #1a1a2e;
            --text-muted: #6c757d;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text);
            overflow-x: hidden;
        }

        .dashboard-overlay {
            background: rgba(245, 247, 250, 0.9);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: -1;
        }

        /* Top Navbar - smaller and rounded */
        .navbar {
            background: linear-gradient(90deg, var(--primary), #0b8791) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            border-radius: 0 0 18px 18px;
            min-height: 48px;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        .navbar .form-select,
        .navbar .btn,
        .navbar .dropdown-toggle {
            border-radius: 12px !important;
        }

        .navbar .dropdown-menu {
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .navbar-box {
            background: var(--card-bg);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.25rem 0.5rem;
            margin: 0 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .navbar-box select,
        .navbar-box button,
        .navbar-box a {
            font-size: 0.85rem;
            line-height: 1.2;
        }

        .navbar-box img {
            width: 24px;
            height: 24px;
        }

        .navbar-box .btn {
            padding: 0.2rem 0.5rem;
        }

        /* Sidebar - smaller, smooth, rounded */
        .sidebar {
            background: linear-gradient(180deg, var(--primary), #0b8791) !important;
            width: 180px;
            min-width: 140px;
            max-width: 200px;
            padding: 1rem 0.5rem;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.08);
            border-radius: 0 18px 18px 0;
            margin-top: 10px;
            margin-bottom: 10px;
            margin-left: 8px;
            height: calc(100vh - 20px);
        }

        .sidebar .navbar-brand img {
            height: 40px !important;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            margin-bottom: 0.3rem;
            font-size: 0.97rem;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: var(--secondary) !important;
            color: var(--text) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        /* Main content margin for smaller sidebar */
        .container-fluid {
            margin-left: 180px !important;
            transition: margin-left 0.2s;
        }

        h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            text-align: center;
            margin-bottom: 1rem;
        }

        .welcome-text {
            font-size: 1rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
        }

        .filter-btn {
            margin: 0 0.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .filter-btn.active {
            background: var(--primary);
            color: #ffffff;
            transform: scale(1.05);
        }

        .table {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: var(--primary);
            color: #ffffff;
        }

        .table th,
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background 0.3s ease;
        }

        .table tbody tr:hover {
            background: #e9ecef;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending {
            background: #ffc107;
            color: #1a1a2e;
        }

        .status-successful {
            background: #28a745;
            color: #ffffff;
        }

        .status-failed {
            background: #dc3545;
            color: #ffffff;
        }

        body.dark-mode {
            --background: #121212;
            --card-bg: #1e1e1e;
            --text: #e0e0e0;
            --text-muted: #a0a0a0;
        }

        body.dark-mode .dashboard-overlay {
            background: rgba(18, 18, 18, 0.9);
        }

        body.dark-mode .table {
            background: #1e1e1e;
        }

        body.dark-mode .navbar-box {
            background: #2a2a2a;
            border-color: #444;
        }

        body.dark-mode .table tbody tr:hover {
            background: #2a2a2a;
        }

        .footer {
            background: linear-gradient(90deg, var(--primary), #0b8791);
            color: #ffffff;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer-text {
            font-size: 0.9rem;
            font-weight: 400;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                border-radius: 0 0 18px 18px;
                margin-left: 0;
                height: auto;
            }

            .container-fluid {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }

            .container-fluid {
                margin-left: 0 !important;
            }

            .filter-btn {
                margin: 0.5rem 0;
            }

            .navbar-box {
                margin: 0.25rem 0;
            }

            .navbar {
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-overlay"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid justify-content-between align-items-center">
            <div class="navbar-box">
                <select class="form-select form-select-sm" id="languageSelect" aria-label="Language selector">
                    <option value="en">English</option>
                    <option value="sw">Swahili</option>
                </select>
            </div>
            <div class="navbar-box">
                <button class="btn btn-outline-light btn-sm" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <div class="navbar-box">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="imgs/admin.jpeg" alt="Admin profile" class="rounded-circle me-1">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="#">Edit Profile</a></li>
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar + Main Content -->
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column p-3">
            <a class="navbar-brand mb-4 text-center" href="index.php">
                <img src="imgs/logo.png" alt="CensaSight Logo" style="height: 40px;">
            </a>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="uploads.php" class="nav-link"><i class="fas fa-upload"></i> Upload ID</a></li>
                <li><a href="view_ids.php" class="nav-link"><i class="fas fa-id-card"></i> View IDs</a></li>
                <li><a href="transactions.php" class="nav-link active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">
            <div class="container pt-4">
                <h2 class="animate__animated animate__fadeIn">Transaction History</h2>
                <p class="welcome-text">View all transactions across the system.</p>

                <!-- Status Filters -->
                <div class="d-flex justify-content-center mb-4">
                    <a href="?status=all" class="btn filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=pending" class="btn filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=successful" class="btn filter-btn <?php echo $status_filter === 'successful' ? 'active' : ''; ?>">Successful</a>
                    <a href="?status=failed" class="btn filter-btn <?php echo $status_filter === 'failed' ? 'active' : ''; ?>">Failed</a>
                </div>

                <!-- Transactions Table -->
                <?php if (empty($transactions)): ?>
                    <p class="text-center text-muted">No transactions found.</p>
                <?php else: ?>
                    <table class="table animate__animated animate__fadeIn">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Entity Name</th>
                                <th>ID Number</th>
                                <th>Transaction Code</th>
                                <th>Amount (KES)</th>
                                <th>Status</th>
                                <th>Access Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['transaction_code']); ?></td>
                                    <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($transaction['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($transaction['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['access_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <span class="footer-text">© 2025 CensaSight System. All rights reserved.</span>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        document.getElementById('themeToggle').onclick = function() {
            document.body.classList.toggle('dark-mode');
        };
        document.getElementById('languageSelect').onchange = function() {
            alert('Language switched to: ' + this.value);
        };
        gsap.from('h2, .welcome-text', {
            opacity: 0,
            y: -20,
            duration: 0.8,
            ease: 'power3.out'
        });
        gsap.from('.table, .filter-btn', {
            opacity: 0,
            y: 50,
            stagger: 0.2,
            duration: 0.8,
            ease: 'power3.out'
        });
    </script>
</body>

</html>