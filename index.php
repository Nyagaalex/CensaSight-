<?php require_once 'includes/db.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CensaSight - Explore Data with Insight</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="imgs/logo.png" alt="CensaSight Logo" style="height: 50px; transition: transform 0.3s;">
                <span class="ms-2 fs-4 fw-bold">CensaSight</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link px-3" href="login.php">Login</a>
                    <a class="nav-link px-3" href="signup.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center justify-content-center text-center text-white">
        <div class="container">
            <h1 class="display-3 fw-bold animate__animated animate__fadeInDown">Welcome to CensaSight</h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s">Unlock the power of data with our intuitive platform. Explore, analyze, and gain insights like never before.</p>
            <a href="signup.php" class="btn btn-primary btn-lg mt-3 animate__animated animate__pulse animate__infinite">Start Exploring Now</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose CensaSight?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-bar-chart-fill display-4 text-primary"></i>
                            <h5 class="card-title mt-3">Powerful Analytics</h5>
                            <p class="card-text">Dive deep into your data with advanced analytics tools designed for simplicity and impact.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-shield-lock-fill display-4 text-primary"></i>
                            <h5 class="card-title mt-3">Secure & Reliable</h5>
                            <p class="card-text">Your data is protected with state-of-the-art security measures you can trust.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-globe display-4 text-primary"></i>
                            <h5 class="card-title mt-3">Global Insights</h5>
                            <p class="card-text">Access data from around the world and make informed decisions with ease.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p>&copy; 2025 CensaSight. All rights reserved.</p>
            <div>
                <a href="privacy.php" class="text-white mx-2">Privacy Policy</a> |
                <a href="#" class="text-white mx-2">Terms of Service</a> |
                <a href="contact.html" class="text-white mx-2">Contact Us</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="/js/scripts.js"></script>
</body>

</html>