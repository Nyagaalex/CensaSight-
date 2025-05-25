<?php
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer' || !$_SESSION['is_authorized']) {
    header('Location: login.php');
    exit;
}

global $pdo;

// Add item to cart
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id_number'])) {
    $id_number = $_GET['id_number'];

    // Verify ID exists and is not sold
    $stmt = $pdo->prepare("SELECT is_sold FROM ids_avl WHERE id_number = ?");
    $stmt->execute([$id_number]);
    $id = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$id) {
        header('Location: view_ids.php?error=ID not found');
        exit;
    }
    if ($id['is_sold']) {
        header('Location: view_ids.php?error=ID already sold');
        exit;
    }

    // Add to cart table
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, id_number, added_at) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $id_number]);

    header('Location: cart.html?success=ID added to cart');
    exit;
}

// Remove item from cart
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id_number'])) {
    $id_number = $_GET['id_number'];

    // Remove from cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND id_number = ?");
    $stmt->execute([$_SESSION['user_id'], $id_number]);

    header('Location: cart.html?success=ID removed from cart');
    exit;
}

// Process cart purchase
if (isset($_POST['action']) && $_POST['action'] === 'purchase') {
    // Fetch cart items
    $stmt = $pdo->prepare("SELECT id_number FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($cart_items)) {
        header('Location: cart.html?error=Cart is empty');
        exit;
    }

    // Verify all IDs are available
    $stmt = $pdo->prepare("SELECT id_number, is_sold FROM ids_avl WHERE id_number IN (" . implode(',', array_fill(0, count($cart_items), '?')) . ")");
    $stmt->execute($cart_items);
    $available_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $valid_ids = [];
    foreach ($available_ids as $id) {
        if ($id['is_sold']) {
            header('Location: cart.html?error=Some IDs are already sold');
            exit;
        }
        $valid_ids[] = $id['id_number'];
    }

    // Calculate total amount (assuming price is stored in ids table)
    $stmt = $pdo->prepare("SELECT SUM(price) as total FROM ids WHERE id_number IN (" . implode(',', array_fill(0, count($valid_ids), '?')) . ")");
    $stmt->execute($valid_ids);
    $total_amount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 500; // Default to 500 if price not set

    // Initiate M-Pesa payment
    $_SESSION['cart_ids'] = $valid_ids; // Store cart IDs in session for payment.php
    $_SESSION['total_amount'] = $total_amount;

    header('Location: payment.php');
    exit;
}

header('Location: cart.html');
exit;
