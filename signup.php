<?php
require_once 'includes/auth.php';

// Prevent logged-in users from accessing signup
if (isset($_SESSION['user_id'])) {
    header('Location: view_ids.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'buyer'; // Default role; admins created manually

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username or email exists
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username or email already taken.";
        } else {
            // Register user
            if (register($username, $email, $password, $role)) {
                $success = "Registration successful! Please <a href='login.php'>log in</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CensaSight System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a6c74 0%, #00ddeb 100%);
            min-height: 100vh;
            font-family: 'Poppins', 'Inter', sans-serif;
        }

        .signup-card {
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

        .signup-card::before {
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

        .signup-card .form-label {
            font-weight: 600;
            color: #0a6c74;
        }

        /* Smooth underline input style */
        .signup-card .form-control {
            border: none;
            border-bottom: 2px solid #e0e0e0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            font-size: 1.1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            padding-left: 0;
            padding-right: 0;
        }

        .signup-card .form-control:focus {
            border-bottom: 2.5px solid #00ddeb;
            outline: none;
            box-shadow: 0 2px 0 #00ddeb33;
            background: transparent;
        }

        .signup-card .form-control:hover:not(:focus) {
            border-bottom: 2px solid #0a6c74;
        }

        .signup-card .btn-primary {
            background: linear-gradient(90deg, #0a6c74 0%, #00ddeb 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(10, 108, 116, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }

        .signup-card .btn-primary:hover {
            background: linear-gradient(90deg, #00ddeb 0%, #0a6c74 100%);
            box-shadow: 0 4px 16px rgba(10, 108, 116, 0.13);
        }

        .signup-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .signup-logo img {
            height: 56px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(10, 108, 116, 0.10);
        }

        .signup-title {
            font-weight: 700;
            color: #0a6c74;
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .signup-subtitle {
            color: #6c757d;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.08rem;
        }

        @media (max-width: 600px) {
            .signup-card {
                padding: 1.5rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100">
        <div class="signup-card animate__animated animate__fadeInDown">
            <div class="signup-logo">
                <img src="imgs/logo.png" alt="CensaSight Logo">
            </div>
            <div class="signup-title">Create Account</div>
            <div class="signup-subtitle">Join CensaSight and explore data with insight</div>
            <?php if ($success): ?>
                <div class="alert alert-success animate__animated animate__fadeIn">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger animate__animated animate__shakeX">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="animate__animated animate__fadeIn" style="animation-delay:0.3s;">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign Up</button>
            </form>
            <div class="d-flex justify-content-between mt-3">
                <a href="login.php" class="text-decoration-underline" style="color:#0a6c74;">Already have an account? Log In</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>