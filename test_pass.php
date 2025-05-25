<?php
require_once 'includes/db.php';
$username = 'admin2'; // Replace with your admin username
$password = 'adminpass123'; // Replace with the password you're testing

$sql = "SELECT username, password_hash FROM users WHERE username = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Username: " . $user['username'] . "<br>";
    echo "Stored hash: " . $user['password_hash'] . "<br>";
    echo "Hash length: " . strlen($user['password_hash']) . "<br>";
    if (password_verify($password, $user['password_hash'])) {
        echo "Password is correct!";
    } else {
        echo "Password verification failed.";
    }
} else {
    echo "User not found.";
}
