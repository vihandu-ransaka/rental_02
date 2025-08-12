<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header('Location: ../login.php');
    exit;
}

include '../includes/config.php';

$driver_id = $_SESSION['user_id'];
$message = '';

// Handle accept/remove POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = intval($_POST['booking_id'] ?? 0);

    if ($action === 'accept' && $booking_id) {
        // Check if booking already accepted by someone else
        $check = pg_query_params($conn, "SELECT * FROM accepted_bookings WHERE booking_id = $1", [$booking_id]);
        if (pg_num_rows($check) == 0) {
            // Accept booking
            pg_query_params($conn, "INSERT INTO accepted_bookings (booking_id, driver_id) VALUES ($1, $2)", [$booking_id, $driver_id]);
            $message = "Booking #$booking_id accepted.";
        } else {
            $message = "Sorry, booking #$booking_id is already accepted by another driver.";
        }
    } elseif ($action === 'remove' && $booking_id) {
        // Remove acceptance by this driver only
        pg_query_params($conn, "DELETE FROM accepted_bookings WHERE booking_id = $1 AND driver_id = $2", [$booking_id, $driver_id]);
        $message = "Booking #$booking_id acceptance removed.";
    }
}

// Fetch bookings needing driver and their acceptance status
$sql = "
    SELECT b.id, b.pickup_date, b.return_date, b.status, b.total_price, b.driver_needed,
           ac.car_name, ac.model, ac.image_path,
           ab.driver_id AS accepted_by
    FROM bookings b
    JOIN available_cars ac ON b.car_id = ac.car_id
    LEFT JOIN accepted_bookings ab ON b.id = ab.booking_id
    WHERE b.driver_needed = TRUE
      AND b.status = 'booked'
    ORDER BY b.pickup_date ASC
";

$res = pg_query($conn, $sql);
$bookings = $res ? pg_fetch_all($res) : [];

function getImageUrl($imagePath) {
    $baseUrl = '/public'; // adjust if needed
    return $baseUrl . '/' . ltrim($imagePath, '/');
}
?>
<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Driver Dashboard - DriveMint</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include '../includes/driver_header.php'; ?>

<main class="container mx-auto p-6">
  <h1 class="text-3xl font-bold mb-6 text-center">Driver Dashboard</h1>

  <?php if ($message): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded shadow"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <?php if (!$bookings || count($bookings) === 0): ?>
    <p class="text-center text-gray-600">No bookings currently require a driver.</p>
  <?php else: ?>
    <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($bookings as $b): ?>
        <div class="bg-white rounded-lg shadow p-4 flex flex-col">
          <img src="<?= htmlspecialchars(getImageUrl($b['image_path'])) ?>" alt="<?= htmlspecialchars($b['car_name']) ?>" class="h-40 w-full object-cover rounded mb-4" loading="lazy" />
          <h2 class="text-xl font-semibold mb-1"><?= htmlspecialchars($b['car_name']) ?> <?= htmlspecialchars($b['model']) ?></h2>
          <p><strong>Pickup:</strong> <?= htmlspecialchars($b['pickup_date']) ?></p>
          <p><strong>Return:</strong> <?= htmlspecialchars($b['return_date']) ?></p>
          <p><strong>Price:</strong> ₹<?= number_format($b['total_price'], 2) ?></p>
          <p class="mb-3"><strong>Status:</strong> <?= htmlspecialchars($b['status']) ?></p>
          <p class="mb-3"><strong>Driver Needed:</strong> <?= $b['driver_needed'] ? 'Yes' : 'No' ?></p>

          <?php if ($b['accepted_by'] === null): ?>
            <!-- Not accepted by any driver yet -->
            <form method="POST" class="mt-auto">
              <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>" />
              <input type="hidden" name="action" value="accept" />
              <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded font-semibold">
                Accept Booking
              </button>
            </form>
          <?php elseif ($b['accepted_by'] == $driver_id): ?>
            <!-- Accepted by current driver -->
            <form method="POST" class="mt-auto">
              <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>" />
              <input type="hidden" name="action" value="remove" />
              <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded font-semibold">
                Remove Acceptance
              </button>
            </form>
          <?php else: ?>
            <!-- Accepted by other driver -->
            <p class="mt-auto text-center text-gray-500 italic font-medium">Accepted by another driver</p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
