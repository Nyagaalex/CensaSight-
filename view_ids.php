<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php'; // Ensure this includes PDO connection
if (
    !isset($_SESSION['user_id']) ||
    (
        $_SESSION['role'] !== 'buyer' &&
        $_SESSION['role'] !== 'admin'
    ) ||
    ($_SESSION['role'] === 'buyer' && !$_SESSION['is_authorized'])
) {
    header('Location: login.php');
    exit;
}

global $pdo;

// Handle filtering, sorting, and pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_number';
$order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query with filtering and sorting
$query = "SELECT id_number, county, price FROM ids_avl WHERE is_sold = FALSE";
$params = [];
if ($search) {
    $query .= " AND (id_number LIKE ? OR county LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY $sort $order LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$ids = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM ids_avl WHERE is_sold = FALSE";
if ($search) {
    $count_query .= " AND (id_number LIKE ? OR county LIKE ?)";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute(["%$search%", "%$search%"]);
} else {
    $count_stmt = $pdo->query($count_query);
}
$total_ids = $count_stmt->fetchColumn();
$total_pages = ceil($total_ids / $per_page);

// Fetch chart data
$chart_stmt = $pdo->query("SELECT county, COUNT(*) as count FROM ids_avl WHERE is_sold = FALSE GROUP BY county");
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = array_column($chart_data, 'county');
$data = array_column($chart_data, 'count');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - CensaSight</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="imgs/logo.png" alt="CensaSight Logo" style="height: 50px; transition: transform 0.3s;">
                <span class="ms-2 fs-4 fw-bold">CensaSight</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link px-3" href="index.php">Home</a>
                    <a class="nav-link px-3 active" href="view_ids.php">view IDs</a>
                    <a class="nav-link px-3" href="cart.html">Cart</a>
                    <a class="nav-link px-3" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="dashboard-hero-section d-flex align-items-center justify-content-center text-center text-white">
        <div class="container">
            <h1 class="display-3 fw-bold animate__animated animate__fadeInDown">Buyer Dashboard</h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! Explore available IDs and insights.</p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="dashboard-content-section py-5">
        <div class="container">
            <!-- IDs Table -->
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Available IDs for Sale</h3>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by ID or County" value="<?php echo htmlspecialchars($search); ?>">
                        <button id="exportCsv" class="btn btn-outline-primary"><i class="bi bi-download"></i> Export CSV</button>
                    </div>
                </div>
                <?php if (empty($ids)): ?>
                    <p class="text-center">No IDs available for sale at the moment.</p>
                <?php else: ?>
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><a href="?sort=id_number&order=<?php echo $sort == 'id_number' && $order == 'asc' ? 'desc' : 'asc'; ?>" class="text-decoration-none">ID Number</a></th>
                                        <th><a href="?sort=county&order=<?php echo $sort == 'county' && $order == 'asc' ? 'desc' : 'asc'; ?>" class="text-decoration-none">County</a></th>
                                        <th><a href="?sort=price&order=<?php echo $sort == 'price' && $order == 'asc' ? 'desc' : 'asc'; ?>" class="text-decoration-none">Price (KSH)</a></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="idsTable">
                                    <?php foreach ($ids as $id): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($id['id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($id['county']); ?></td>
                                            <td><?php echo number_format($id['price'], 2); ?></td>
                                            <td>
                                                <a href="purchase.php?id_number=<?php echo urlencode($id['id_number']); ?>" class="btn btn-success btn-sm">Purchase</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- Pagination -->
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chart Section -->
            <div class="mb-5">
                <h3 class="text-center">ID Distribution by County</h3>
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <div style="height:220px; max-width:600px; margin:0 auto;">
                            <canvas id="countyChart" height="120"></canvas>
                        </div>
                        <!-- Enhancement: Add summary stats below the chart -->
                        <div class="row mt-4 text-center">
                            <div class="col-6 col-md-3 mb-2">
                                <div class="bg-primary text-white rounded-3 py-2 px-1">
                                    <div class="fw-bold fs-5"><?php echo $total_ids; ?></div>
                                    <div style="font-size:0.97em;">Total IDs</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="bg-success text-white rounded-3 py-2 px-1">
                                    <div class="fw-bold fs-5">
                                        <?php echo count($labels); ?>
                                    </div>
                                    <div style="font-size:0.97em;">Counties Listed</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="bg-warning text-dark rounded-3 py-2 px-1">
                                    <div class="fw-bold fs-5">
                                        <?php
                                        $min = empty($data) ? 0 : min($data);
                                        $max = empty($data) ? 0 : max($data);
                                        echo $min;
                                        ?>
                                    </div>
                                    <div style="font-size:0.97em;">Min IDs/County</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-2">
                                <div class="bg-danger text-white rounded-3 py-2 px-1">
                                    <div class="fw-bold fs-5">
                                        <?php echo $max; ?>
                                    </div>
                                    <div style="font-size:0.97em;">Max IDs/County</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p>© 2025 CensaSight. All rights reserved.</p>
            <div>
                <a href="privacy.php" class="text-white mx-2">Privacy Policy</a> |
                <a href="#" class="text-white mx-2">Terms of Service</a> |
                <a href="contact.html" class="text-white mx-2">Contact Us</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="/js/scripts.js"></script>
    <script>
        // Chart.js for ID distribution by county (Doughnut Chart)
        const ctx = document.getElementById('countyChart').getContext('2d');
        const countyChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Number of IDs',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: [
                        '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
                        '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
                    ],
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Poppins',
                                size: 5
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw} IDs`;
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const county = countyChart.data.labels[elements[0].index];
                        document.getElementById('searchInput').value = county;
                        filterTable();
                    }
                }
            }
        });

        // GSAP Animations
        gsap.from('.dashboard-hero-section h1', {
            opacity: 0,
            y: -50,
            duration: 1,
            delay: 0.5
        });
        gsap.from('.dashboard-hero-section p', {
            opacity: 0,
            y: 50,
            duration: 1,
            delay: 1
        });
        gsap.from('.dashboard-content-section .card', {
            opacity: 0,
            y: 100,
            duration: 1,
            stagger: 0.3,
            scrollTrigger: {
                trigger: '.dashboard-content-section',
                start: 'top 80%'
            }
        });

        // Search and Filter Table
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#idsTable tr');
            rows.forEach(row => {
                const idNumber = row.cells[0].textContent.toLowerCase();
                const county = row.cells[1].textContent.toLowerCase();
                row.style.display = (idNumber.includes(search) || county.includes(search)) ? '' : 'none';
            });
        }

        document.getElementById('searchInput').addEventListener('input', filterTable);

        // Export Table to CSV
        document.getElementById('exportCsv').addEventListener('click', function() {
            const rows = document.querySelectorAll('#idsTable tr');
            let csv = 'ID Number,County,Price (KSH)\n';
            rows.forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length) {
                    csv += `${cols[0].textContent},${cols[1].textContent},${cols[2].textContent}\n`;
                }
            });
            const blob = new Blob([csv], {
                type: 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'ids_export.csv');
            a.click();
            window.URL.revokeObjectURL(url);
        });
    </script>
</body>

</html>