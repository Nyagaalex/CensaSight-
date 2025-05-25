<?php
require_once 'includes/auth.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'includes/db.php';

// Fetch stats
$stmt = $pdo->query("SELECT COUNT(*) AS total_ids FROM ids");
$total_ids = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) AS total_transactions FROM transactions WHERE status = 'successful'");
$total_transactions = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) AS total_visitors FROM visitor_logs");
$total_visitors = $stmt->fetchColumn();

// Fetch data for chart (IDs per county)
$chart_stmt = $pdo->query("SELECT county, COUNT(*) as count FROM ids GROUP BY county");
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = array_column($chart_data, 'county');
$data = array_column($chart_data, 'count');

// Fetch pending buyers for authorization
$buyers_stmt = $pdo->query("SELECT user_id, username, email, is_authorized FROM users WHERE role = 'buyer' ORDER BY created_at DESC");
$buyers = $buyers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle buyer authorization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['authorize_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("UPDATE users SET is_authorized = TRUE WHERE user_id = ? AND role = 'buyer'");
    $stmt->execute([$user_id]);

    // Log the action in audit_logs
    $admin_id = $_SESSION['user_id'];
    $action = "Authorize Buyer";
    $details = "Admin authorized buyer with user_id: $user_id";
    $log_stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, log_time) VALUES (?, ?, ?, NOW())");
    $log_stmt->execute([$admin_id, $action, $details]);

    header('Location: dashboard.php?success=Buyer authorized successfully.');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CensaSight System</title>
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

        /* Stats Cards */
        .card {
            border: none;
            border-radius: 12px;
            background: linear-gradient(145deg, var(--card-bg), #f8f9fa);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-icon {
            font-size: 2rem;
            color: var(--secondary);
            /* Use accent blue instead of green */
        }

        .card-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-muted);
            margin: 0;
        }

        .card-text {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            /* Use main blue instead of green */
        }

        /* Chart Section */
        #countyChart {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* Tables */
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

        .btn-success {
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .btn-success:hover {
            background: #085b61;
            transform: scale(1.05);
        }

        /* Dark Mode */
        body.dark-mode {
            --background: #121212;
            --card-bg: #1e1e1e;
            --text: #e0e0e0;
            --text-muted: #a0a0a0;
        }

        body.dark-mode .dashboard-overlay {
            background: rgba(18, 18, 18, 0.9);
        }

        body.dark-mode .card {
            background: linear-gradient(145deg, #1e1e1e, #2a2a2a);
        }

        body.dark-mode .table {
            background: #1e1e1e;
        }

        body.dark-mode .table tbody tr:hover {
            background: #2a2a2a;
        }

        /* Footer */
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

        /* Alerts */
        .alert {
            border-radius: 10px;
            font-weight: 500;
            animation: fadeIn 0.5s ease-in;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Responsive Design */
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

        /* Notification bell animation and badge */
        .fa-bell {
            font-size: 1.3rem;
            transition: color 0.2s;
        }

        .btn-outline-light:focus .fa-bell,
        .btn-outline-light:hover .fa-bell {
            color: #00ddeb;
            animation: ringBell 0.7s 1;
        }

        @keyframes ringBell {
            0% {
                transform: rotate(0);
            }

            10% {
                transform: rotate(15deg);
            }

            20% {
                transform: rotate(-10deg);
            }

            30% {
                transform: rotate(7deg);
            }

            40% {
                transform: rotate(-5deg);
            }

            50% {
                transform: rotate(3deg);
            }

            60% {
                transform: rotate(-2deg);
            }

            70% {
                transform: rotate(1deg);
            }

            80% {
                transform: rotate(-1deg);
            }

            100% {
                transform: rotate(0);
            }
        }

        .badge.bg-danger {
            font-size: 0.75em;
            padding: 0.3em 0.5em;
        }

        .dropdown-menu[aria-labelledby="notificationDropdown"] {
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="dashboard-overlay"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid justify-content-between">
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto" id="languageSelect" aria-label="Language selector">
                    <option value="en">English</option>
                    <option value="sw">Swahili</option>
                </select>
            </div>
            <!-- Notification Icon -->
            <div class="dropdown navbar-box position-relative me-2">
                <button class="btn btn-outline-light btn-sm position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php
                    // Example: Count of unread notifications (replace with your logic)
                    $notif_count = 0;
                    $notif_stmt = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE log_time > NOW() - INTERVAL 1 DAY");
                    $notif_count = $notif_stmt->fetchColumn();
                    if ($notif_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notif_count; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="notificationDropdown" style="min-width: 320px; max-height: 350px; overflow-y: auto;">
                    <li class="dropdown-header fw-bold text-primary">Notifications</li>
                    <?php
                    // Show latest 5 notifications (replace with your logic)
                    $notif_stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY log_time DESC LIMIT 5");
                    while ($notif = $notif_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li class="px-3 py-2 border-bottom small">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle text-secondary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">' . htmlspecialchars($notif['action']) . '</div>
                                        <div class="text-muted" style="font-size:0.93em;">' . htmlspecialchars($notif['details']) . '</div>
                                        <div class="text-muted" style="font-size:0.85em;">' . date('M d, H:i', strtotime($notif['log_time'])) . '</div>
                                    </div>
                                </div>
                              </li>';
                    }
                    ?>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-center" href="#audit-logs">View all notifications</a></li>
                </ul>
            </div>
            <div>
                <button class="btn btn-outline-light btn-sm" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-sun"></i>
                </button>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="imgs/admin.jpeg" alt="Admin profile" width="32" height="32" class="rounded-circle me-2">
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
                <img src="imgs/logo.png" alt="CensaSight Logo" style="height: 60px;">
            </a>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="uploads.php" class="nav-link"><i class="fas fa-upload"></i> Upload ID</a></li>
                <li><a href="view_ids.php" class="nav-link"><i class="fas fa-id-card"></i> View IDs</a></li>
                <li><a href="transactions.php" class="nav-link"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid" style="margin-left: 250px;">
            <div class="container pt-4">
                <h2 class="animate__animated animate__fadeIn">Admin Dashboard</h2>
                <p class="welcome-text">Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

                <!-- Success Message -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-4">
                        <div class="card animate__animated animate__zoomIn">
                            <div class="card-body">
                                <i class="fas fa-id-badge card-icon" style="color:#0a6c74;"></i>
                                <div>
                                    <h5 class="card-title">Total IDs</h5>
                                    <p class="card-text"><?php echo $total_ids; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card animate__animated animate__zoomIn">
                            <div class="card-body">
                                <i class="fas fa-check-circle card-icon" style="color:#00bcd4;"></i>
                                <div>
                                    <h5 class="card-title">Successful Transactions</h5>
                                    <p class="card-text"><?php echo $total_transactions; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card animate__animated animate__zoomIn">
                            <div class="card-body">
                                <i class="fas fa-users card-icon" style="color:#ff9800;"></i>
                                <div>
                                    <h5 class="card-title">Website Visitors</h5>
                                    <p class="card-text"><?php echo $total_visitors; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="mb-5 mt-5">
                    <h3 class="text-center">ID Distribution by County</h3>
                    <canvas id="countyChart" height="120" aria-label="Chart of ID distribution by county"></canvas>
                </div>

                <!-- Manage Buyers -->
                <h3 class="mt-5">Manage Buyers</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buyers as $buyer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($buyer['username']); ?></td>
                                <td><?php echo htmlspecialchars($buyer['email']); ?></td>
                                <td><?php echo $buyer['is_authorized'] ? 'Authorized' : 'Pending'; ?></td>
                                <td>
                                    <?php if (!$buyer['is_authorized']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $buyer['user_id']; ?>">
                                            <button type="submit" name="authorize_user" class="btn btn-success btn-sm">Authorize</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Audit Logs -->
                <h3 class="mt-5">Recent Audit Logs</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY log_time DESC LIMIT 10");
                        while ($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>
                                <td>" . htmlspecialchars($log['log_time']) . "</td>
                                <td>" . htmlspecialchars($log['username'] ?? 'Unknown') . "</td>
                                <td>" . htmlspecialchars($log['action']) . "</td>
                                <td>" . htmlspecialchars($log['details']) . "</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        // Chart.js configuration
        const ctx = document.getElementById('countyChart').getContext('2d');
        const countyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'IDs per County',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: 'rgba(0, 188, 212, 0.7)', // blue accent
                    borderColor: 'rgba(25, 118, 210, 1)', // blue
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Inter',
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1976d2',
                        titleFont: {
                            family: 'Inter',
                            size: 14
                        },
                        bodyFont: {
                            family: 'Inter',
                            size: 12
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                family: 'Inter'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter'
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });

        // Theme toggle
        document.getElementById('themeToggle').onclick = function() {
            document.body.classList.toggle('dark-mode');
        };

        // Language selector (demo)
        document.getElementById('languageSelect').onchange = function() {
            alert('Language switched to: ' + this.value);
        };

        // GSAP animations
        gsap.from('.card', {
            opacity: 0,
            y: 50,
            stagger: 0.2,
            duration: 0.8,
            ease: 'power3.out'
        });
        gsap.from('h2, .welcome-text', {
            opacity: 0,
            y: -20,
            duration: 0.8,
            ease: 'power3.out'
        });
    </script>
</body>

</html>