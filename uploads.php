<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit;
}

$kenya_counties = [
    'Baringo',
    'Bomet',
    'Bungoma',
    'Busia',
    'Elgeyo Marakwet',
    'Embu',
    'Garissa',
    'Homa Bay',
    'Isiolo',
    'Kajiado',
    'Kakamega',
    'Kericho',
    'Kiambu',
    'Kilifi',
    'Kirinyaga',
    'Kisii',
    'Kisumu',
    'Kitui',
    'Kwale',
    'Laikipia',
    'Lamu',
    'Machakos',
    'Makueni',
    'Mandera',
    'Marsabit',
    'Meru',
    'Migori',
    'Mombasa',
    'Murang\'a',
    'Nairobi',
    'Nakuru',
    'Nandi',
    'Narok',
    'Nyamira',
    'Nyandarua',
    'Nyeri',
    'Samburu',
    'Siaya',
    'Taita Taveta',
    'Tana River',
    'Tharaka Nithi',
    'Trans Nzoia',
    'Turkana',
    'Uasin Gishu',
    'Vihiga',
    'Wajir',
    'West Pokot'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_number = $_POST['id_number'];
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $place_of_birth = $_POST['place_of_birth'];
    $county = $_POST['county'];
    $issue_date = $_POST['issue_date'];
    $file = $_FILES['id_file'] ?? null;

    if (uploadID($id_number, $name, $dob, $place_of_birth, $county, $issue_date, $file)) {
        $success = "ID uploaded successfully";
    } else {
        $error = "Failed to upload ID";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload ID - CensaSight</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
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
                    <a class="nav-link px-3" href="login.php">Login</a>
                    <a class="nav-link px-3" href="signup.php">Sign Up</a>
                    <a class="nav-link px-3" href="dashboard.php">Home</a>

                </div>
            </div>
        </div>
    </nav>

    <!-- Upload Hero Section -->
    <section class="upload-hero-section d-flex align-items-center justify-content-center text-center text-white">
        <div class="container">
            <h1 class="display-3 fw-bold animate__animated animate__fadeInDown">Upload National ID</h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s">Securely upload ID details to CensaSight's platform.</p>
        </div>
    </section>

    <!-- Upload Form Section -->
    <section class="upload-form-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4">Upload ID Details</h2>
                            <?php if (isset($success)) echo "<div class='alert alert-success animate__animated animate__fadeIn'>$success</div>"; ?>
                            <?php if (isset($error)) echo "<div class='alert alert-danger animate__animated animate__fadeIn'>$error</div>"; ?>
                            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                <div class="mb-4">
                                    <label for="id_number" class="form-label">ID Number</label>
                                    <input type="text" class="form-control form-control-lg" id="id_number" name="id_number" placeholder="Enter ID number" required aria-label="ID Number">
                                </div>
                                <div class="mb-4">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Enter full name" required aria-label="Full Name">
                                </div>
                                <div class="mb-4">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control form-control-lg" id="dob" name="dob" required aria-label="Date of Birth">
                                </div>
                                <div class="mb-4">
                                    <label for="place_of_birth" class="form-label">Place of Birth</label>
                                    <input type="text" class="form-control form-control-lg" id="place_of_birth" name="place_of_birth" placeholder="Enter place of birth" required aria-label="Place of Birth">
                                </div>
                                <div class="mb-4">
                                    <label for="county" class="form-label">County</label>
                                    <select class="form-select form-control-lg select2-county" id="county" name="county" required aria-label="Select a county">
                                        <option value="" disabled selected>Select a county</option>
                                        <?php foreach ($kenya_counties as $county) {
                                            echo "<option value='$county'>$county</option>";
                                        } ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label for="issue_date" class="form-label">Issue Date</label>
                                    <input type="date" class="form-control form-control-lg" id="issue_date" name="issue_date" required aria-label="Issue Date">
                                </div>
                                <div class="mb-4">
                                    <label for="id_file" class="form-label">Upload ID Image (Optional)</label>
                                    <input type="file" class="form-control form-control-lg" id="id_file" name="id_file" accept="image/*" aria-label="Upload ID Image">
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">Upload ID</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scanner Section -->
    <section class="scanner-section py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4">Scan ID</h2>
                            <div id="scanner-container" class="border p-3 rounded">
                                <video id="scanner" style="width: 100%; border-radius: 8px;"></video>
                            </div>
                            <div class="text-center mt-3">
                                <button id="start-scanner" class="btn btn-secondary btn-lg px-4">Start Scanner</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="/js/scripts.js"></script>
</body>

</html>