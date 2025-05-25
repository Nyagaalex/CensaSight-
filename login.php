<?php
require_once 'includes/auth.php';

$success = isset($_GET['success']) ? $_GET['success'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        if (login($username, $password)) {
            if ($_SESSION['role'] === 'buyer') {
                if ($_SESSION['is_authorized']) {
                    header('Location: view_ids.php');
                } else {
                    $error = "Your account is not yet authorized. Please wait for admin approval.";
                }
            } elseif ($_SESSION['role'] === 'admin') {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = "Invalid credentials! Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CensaSight System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a6c74 0%, #00ddeb 100%);
            min-height: 100vh;
            font-family: 'Poppins', 'Inter', sans-serif;
        }

        .login-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(10, 108, 116, 0.15), 0 1.5px 8px rgba(0, 0, 0, 0.04);
            padding: 2.5rem 2rem 2rem 2rem;
            margin-top: 3rem;
            margin-bottom: 3rem;
            max-width: 420px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: "";
            position: absolute;
            top: -60px;
            right: -60px;
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #00ddeb 0%, #0a6c74 100%);
            opacity: 0.12;
            border-radius: 50%;
            z-index: 0;
        }

        .login-card .form-label {
            font-weight: 600;
            color: #0a6c74;
        }

        .login-card .form-control {
            background: transparent;
            border: none;
            border-bottom: 2px solid #0a6c74;
            border-radius: 0;
            box-shadow: none;
            transition: border-color 0.3s;
            font-size: 1.1rem;
        }

        .login-card .form-control:focus {
            border-bottom-color: #00ddeb;
            background: transparent;
            box-shadow: none;
        }

        .login-card .input-group .btn-outline-secondary {
            border: none;
            border-bottom: 2px solid #0a6c74;
            border-radius: 0;
            background: transparent;
            color: #0a6c74;
        }

        .login-card .input-group .btn-outline-secondary:hover {
            border-bottom-color: #00ddeb;
            color: #00ddeb;
        }

        .login-card .btn-primary {
            background: linear-gradient(90deg, #0a6c74 0%, #00ddeb 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(10, 108, 116, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }

        .login-card .btn-primary:hover {
            background: linear-gradient(90deg, #00ddeb 0%, #0a6c74 100%);
            box-shadow: 0 4px 16px rgba(10, 108, 116, 0.13);
        }

        .login-card .forgot-link {
            color: #0a6c74;
            font-size: 0.97rem;
            text-decoration: underline;
        }

        .login-card .forgot-link:hover {
            color: #00ddeb;
        }

        .login-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .login-logo img {
            height: 56px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(10, 108, 116, 0.10);
        }

        .login-title {
            font-weight: 700;
            color: #0a6c74;
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .login-subtitle {
            color: #6c757d;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.08rem;
        }

        @media (max-width: 600px) {
            .login-card {
                padding: 1.5rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100">
        <div class="login-card animate__animated animate__fadeInDown">
            <div class="login-logo">
                <img src="imgs/logo.png" alt="CensaSight Logo">
            </div>
            <div class="login-title">Welcome Back</div>
            <div class="login-subtitle">Sign in to your CensaSight account</div>
            <?php if (isset($success)): ?>
                <div class="alert alert-success animate__animated animate__fadeIn">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger animate__animated animate__shakeX">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="animate__animated animate__fadeIn" style="animation-delay:0.3s;">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                        required
                        autocomplete="username"
                        placeholder="Enter your username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                            <span id="eyeIcon" class="bi bi-eye"></span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">Login</button>
            </form>
            <div class="d-flex justify-content-between mt-3">
                <a href="reset_pass.php" class="forgot-link">Forgot your password?</a>
                <span class="text-muted">|</span>
                <a href="signup.php" class="forgot-link">Create an account</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>

</html>