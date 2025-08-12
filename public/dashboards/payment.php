<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include '../includes/config.php';

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    die('Invalid booking.');
}

// Fetch booking details
$sql = "
    SELECT b.*, ac.car_name, ac.price_per_day
    FROM bookings b
    JOIN available_cars ac ON b.car_id = ac.car_id
    WHERE b.id = $1 AND b.user_id = $2 AND b.status = 'pending_payment'
";
$res = pg_query_params($conn, $sql, [$booking_id, $user_id]);
$booking = $res ? pg_fetch_assoc($res) : null;

if (!$booking) {
    die('Booking not found or already paid.');
}

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Here you would integrate real payment gateway & verify payment success.
    // For demo, assume payment success and generate a dummy transaction id:
    $transaction_id = 'TXN' . time() . rand(1000,9999);
    $payment_method = 'credit_card'; // Or get from a form input if you add it

    // Insert payment record
    $sql_payment = "
        INSERT INTO payments (booking_id, payment_date, amount, status, payment_method, transaction_id)
        VALUES ($1, NOW(), $2, 'completed', $3, $4)
        RETURNING id
    ";
    $res_payment = pg_query_params($conn, $sql_payment, [
        $booking_id,
        $booking['total_price'],
        $payment_method,
        $transaction_id
    ]);

    if (!$res_payment || pg_num_rows($res_payment) === 0) {
        die('Payment record failed to save.');
    }

    // Update booking status to 'booked'
    $update_booking_sql = "UPDATE bookings SET status = 'booked' WHERE id = $1";
    pg_query_params($conn, $update_booking_sql, [$booking_id]);

    // Mark car unavailable
    $update_car_sql = "UPDATE available_cars SET available = FALSE WHERE car_id = $1";
    pg_query_params($conn, $update_car_sql, [$booking['car_id']]);

    // Redirect to dashboard with payment success
    header('Location: customer.php?payment_success=1');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Payment - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include '../includes/customer_header.php'; ?>

<main class="container mx-auto px-4 py-8 max-w-lg">
  <h1 class="text-2xl font-semibold mb-6">Complete Payment</h1>

  <div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($booking['car_name']) ?></h2>
    <p><strong>Pickup Date:</strong> <?= htmlspecialchars($booking['pickup_date']) ?></p>
    <p><strong>Return Date:</strong> <?= htmlspecialchars($booking['return_date']) ?></p>
    <p><strong>Driver Needed:</strong> <?= $booking['driver_needed'] === 't' ? 'Yes' : 'No' ?></p>
    <p class="mt-3 font-bold text-lg">Total Amount: ₹<?= number_format($booking['total_price'], 2) ?></p>
  </div>

  <form method="POST" class="bg-white p-6 rounded shadow">
    <!-- Add real payment fields here if integrating with gateway -->
    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded text-lg">
      Pay Now
    </button>
  </form>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
