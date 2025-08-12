<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    die("Booking ID missing.");
}

// Get car_id from the booking
$sql = "SELECT car_id FROM bookings WHERE id = $1 AND user_id = $2";
$res = pg_query_params($conn, $sql, [$booking_id, $_SESSION['user_id']]);
$booking = pg_fetch_assoc($res);

if (!$booking) {
    die("Booking not found.");
}

// 1️⃣ Update booking status to "returned"
pg_query_params($conn, "UPDATE bookings SET status = 'returned' WHERE id = $1", [$booking_id]);

// 2️⃣ Make car available again
pg_query_params($conn, "UPDATE available_cars SET available = TRUE WHERE car_id = $1", [$booking['car_id']]);

header("Location: customer_dashboard.php?msg=Car returned successfully");
exit;
