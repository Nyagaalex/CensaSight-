<?php
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer' || !$_SESSION['is_authorized'] || !isset($_SESSION['cart_ids']) || !isset($_SESSION['total_amount'])) {
    header('Location: cart.html');
    exit;
}

global $pdo;

// M-Pesa API credentials (replace with your own)
$consumer_key = 'YOUR_CONSUMER_KEY';
$consumer_secret = 'YOUR_CONSUMER_SECRET';
$shortcode = 'YOUR_SHORTCODE';
$passkey = 'YOUR_PASSKEY';
$callback_url = 'YOUR_CALLBACK_URL'; // e.g., https://yourdomain.com/callback.php

// Generate access token
function getAccessToken()
{
    global $consumer_key, $consumer_secret;
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response)->access_token;
}

// Initiate STK Push
function initiateSTKPush($access_token, $phone, $amount, $transaction_code)
{
    global $shortcode, $passkey, $callback_url;
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $data = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone, // Buyer’s phone number
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $callback_url,
        'AccountReference' => $transaction_code,
        'TransactionDesc' => 'ID Purchase'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);
}

// Process payment
$phone = $_POST['phone'] ?? ''; // Assume phone is submitted via form
if (empty($phone) || !preg_match('/^254[0-9]{9}$/', $phone)) {
    header('Location: cart.html?error=Invalid phone number');
    exit;
}

$transaction_code = 'TX' . strtoupper(substr(md5(uniqid()), 0, 8));
$access_token = getAccessToken();
$response = initiateSTKPush($access_token, $phone, $_SESSION['total_amount'], $transaction_code);

if (isset($response->ResponseCode) && $response->ResponseCode == '0') {
    // Store pending transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (entity_id, id_number, status, transaction_code, amount, created_at) VALUES (?, ?, 'pending', ?, ?, NOW())");
    foreach ($_SESSION['cart_ids'] as $id_number) {
        $stmt->execute([$_SESSION['user_id'], $id_number, $transaction_code, $_SESSION['total_amount']]);
    }

    // Redirect to a waiting page or display a message
    header('Location: payment_status.php?transaction_code=' . $transaction_code);
    exit;
} else {
    header('Location: cart.html?error=Payment initiation failed');
    exit;
}
