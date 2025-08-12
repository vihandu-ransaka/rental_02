<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include '../includes/config.php'; // $conn PostgreSQL connection

$user_id = $_SESSION['user_id'];
$car_id = isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0;
$pickup_date = $_POST['pickup_date'] ?? '';
$return_date = $_POST['return_date'] ?? '';
$driver_needed = isset($_POST['driver_needed']) && $_POST['driver_needed'] === '1';

if (empty($car_id) || empty($pickup_date) || empty($return_date)) {
    die('Invalid booking request. Missing required fields.');
}

$d1 = DateTime::createFromFormat('Y-m-d', $pickup_date);
$d2 = DateTime::createFromFormat('Y-m-d', $return_date);
if (!$d1 || !$d2 || $d1 > $d2) {
    die('Invalid pickup or return date.');
}

$sql_car = "SELECT price_per_day, available FROM available_cars WHERE car_id = $1";
$res_car = pg_query_params($conn, $sql_car, [$car_id]);
$car = $res_car ? pg_fetch_assoc($res_car) : null;

if (!$car) {
    die('Car not found.');
}

if (!$car['available']) {
    die('Car is not available.');
}

$days = $d1->diff($d2)->days + 1;
$total_price = $days * (float)$car['price_per_day'];

$driver_fee_per_day = 500;
if ($driver_needed) {
    $total_price += $days * $driver_fee_per_day;
}

$driver_needed_db = $driver_needed ? 't' : 'f';

// Insert booking with status 'pending_payment'
$sql_insert = "
    INSERT INTO bookings 
        (user_id, car_id, pickup_date, return_date, status, total_price, driver_needed, created_at)
    VALUES 
        ($1, $2, $3, $4, 'pending_payment', $5, $6, NOW())
    RETURNING id
";

$res_insert = pg_query_params($conn, $sql_insert, [
    $user_id,
    $car_id,
    $pickup_date,
    $return_date,
    $total_price,
    $driver_needed_db
]);

if ($res_insert && pg_num_rows($res_insert) > 0) {
    $booking_id = pg_fetch_result($res_insert, 0, 'id');

    // Redirect to payment page with booking ID
    header("Location: payment.php?booking_id=$booking_id");
    exit;
} else {
    die('Booking failed. Please try again.');
}
?>
