<?php
require_once 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO contact_messages(name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $subject, $message])) {
        echo "<div class='alert alert-success'>Message sent successfully! Thank you for contacting CensaSight.We'll get back in contact with you.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error sending message.</div>";
    }
}
