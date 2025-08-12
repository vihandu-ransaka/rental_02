<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include '../includes/config.php'; // PostgreSQL connection ($conn)

$user_id = $_SESSION['user_id'];
$pickup_date = $_GET['pickup_date'] ?? null;
$return_date = $_GET['return_date'] ?? null;

$search_error = '';
$available_cars = false;

/* --- Handle Return Request --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_booking_id'])) {
    $booking_id = intval($_POST['return_booking_id']);

    // Update booking to returned
    pg_query_params($conn, "
        UPDATE bookings 
        SET status = 'returned' 
        WHERE id = $1 AND user_id = $2 AND status != 'returned'
    ", [$booking_id, $user_id]);

    // Update the car to available
    pg_query_params($conn, "
        UPDATE available_cars
        SET available = TRUE
        WHERE car_id = (
            SELECT car_id FROM bookings WHERE id = $1
        )
    ", [$booking_id]);
}

/* --- Automatic availability fix --- */
pg_query($conn, "
    UPDATE available_cars ac
    SET available = TRUE
    WHERE EXISTS (
        SELECT 1 FROM bookings b 
        WHERE b.car_id = ac.car_id 
        AND b.status = 'returned'
        AND NOT EXISTS (
            SELECT 1 FROM bookings b2 
            WHERE b2.car_id = ac.car_id 
            AND b2.status IN ('booked', 'confirmed')
        )
    )
");

/* --- Date Validation --- */
if ($pickup_date && $return_date) {
    $d1 = DateTime::createFromFormat('Y-m-d', $pickup_date);
    $d2 = DateTime::createFromFormat('Y-m-d', $return_date);
    if (!$d1 || !$d2 || $d1 > $d2) {
        $search_error = "Please provide valid dates where pickup is before or equal to return.";
    }
}

/* --- Available Cars Query --- */
if (!$search_error) {
    if ($pickup_date && $return_date) {
        $sql = "
            SELECT ac.*
            FROM available_cars ac
            WHERE ac.available = TRUE
              AND NOT EXISTS (
                  SELECT 1 FROM bookings b
                  WHERE b.car_id = ac.car_id
                    AND b.status IN ('confirmed','booked')
                    AND b.pickup_date <= $2
                    AND b.return_date >= $1
              )
            ORDER BY ac.car_id DESC
        ";
        $res = pg_query_params($conn, $sql, [$pickup_date, $return_date]);
        $available_cars = $res ? pg_fetch_all($res) : [];
    } else {
        $sql = "SELECT * FROM available_cars WHERE available = TRUE ORDER BY car_id DESC";
        $res = pg_query($conn, $sql);
        $available_cars = $res ? pg_fetch_all($res) : [];
    }
}

/* --- Fetch User Bookings --- */
$sql_b = "
    SELECT b.id AS booking_id, b.pickup_date, b.return_date, b.status, b.total_price,
           b.driver_needed, ac.car_name, ac.model, ac.image_path
    FROM bookings b
    JOIN available_cars ac ON b.car_id = ac.car_id
    WHERE b.user_id = $1
    ORDER BY b.pickup_date DESC
";
$res_b = pg_query_params($conn, $sql_b, [$user_id]);
$bookings = $res_b ? pg_fetch_all($res_b) : [];

/**
 * Handle both URLs and local file paths for images
 */
function getImageUrl($imagePath) {
    if (!$imagePath) return 'https://via.placeholder.com/400x300?text=No+Image';
    
    // If it's already a full URL, return as is
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }
    
    // If it starts with /, it's already a proper path
    if (strpos($imagePath, '/') === 0) {
        return $imagePath;
    }
    
    // Otherwise, add leading slash
    return '/' . $imagePath;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Customer Dashboard - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include '../includes/customer_header.php'; ?>

<main class="container mx-auto px-4 py-8">

  <!-- Search form -->
  <section class="mb-8 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Search Available Cars</h2>
    <?php if (!empty($search_error)): ?>
      <div class="mb-3 text-red-700 bg-red-100 p-2 rounded"><?= htmlspecialchars($search_error) ?></div>
    <?php endif; ?>
    <form method="GET" class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block mb-1">Pickup Date</label>
        <input type="date" name="pickup_date" value="<?= htmlspecialchars($pickup_date ?? '') ?>" class="w-full p-2 border rounded" />
      </div>
      <div>
        <label class="block mb-1">Return Date</label>
        <input type="date" name="return_date" value="<?= htmlspecialchars($return_date ?? '') ?>" class="w-full p-2 border rounded" />
      </div>
      <div class="flex items-end">
        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Search</button>
      </div>
    </form>
  </section>

  <!-- Available cars -->
  <section class="mb-12">
    <h2 class="text-2xl font-semibold mb-4">Available Cars</h2>
    <?php if ($available_cars === false || count($available_cars) === 0): ?>
      <p class="text-gray-600">No cars available<?= ($pickup_date && $return_date) ? " for the selected date range" : "" ?>.</p>
    <?php else: ?>
      <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($available_cars as $car): ?>
          <div class="bg-white p-4 rounded shadow flex flex-col">
            <img src="<?= htmlspecialchars(getImageUrl($car['image_path'])) ?>" alt="<?= htmlspecialchars($car['car_name']) ?>" class="h-40 w-full object-cover rounded mb-4" loading="lazy" />
            <h3 class="text-lg font-semibold"><?= htmlspecialchars($car['car_name']) ?></h3>
            <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($car['model'] ?? '') ?></p>
            <p class="mb-3 font-bold">₹<?= number_format($car['price_per_day'], 2) ?> / day</p>

            <?php if ($pickup_date && $return_date): ?>
              <form method="POST" action="book.php" class="mt-auto">
                <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['car_id']) ?>">
                <input type="hidden" name="pickup_date" value="<?= htmlspecialchars($pickup_date) ?>">
                <input type="hidden" name="return_date" value="<?= htmlspecialchars($return_date) ?>">

                <label class="block mb-2 text-sm font-medium">Driver Needed?</label>
                <select name="driver_needed" class="w-full border rounded p-2 mb-3" required>
                  <option value="0">No</option>
                  <option value="1">Yes</option>
                </select>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                  Book Now
                </button>
              </form>
            <?php else: ?>
              <p class="text-sm text-red-600 mt-auto">Please select pickup and return dates above to book this car.</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- User bookings -->
  <section>
    <h2 class="text-2xl font-semibold mb-4">Your Bookings</h2>
    <?php if (!$bookings || count($bookings) === 0): ?>
      <p class="text-gray-600">You have no bookings yet.</p>
    <?php else: ?>
      <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($bookings as $b): ?>
          <div class="bg-white p-4 rounded shadow">
            <img src="<?= htmlspecialchars(getImageUrl($b['image_path'])) ?>" alt="<?= htmlspecialchars($b['car_name']) ?>" class="h-36 w-full object-cover rounded mb-3" loading="lazy" />
            <h3 class="font-semibold"><?= htmlspecialchars($b['car_name']) ?> <?= htmlspecialchars($b['model']) ?></h3>
            <p><strong>Pickup:</strong> <?= htmlspecialchars($b['pickup_date']) ?></p>
            <p><strong>Return:</strong> <?= htmlspecialchars($b['return_date']) ?></p>
            <p><strong>Driver Needed:</strong> <?= $b['driver_needed'] ? 'Yes' : 'No' ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($b['status']) ?></p>
            <p class="mt-2 font-semibold">Total: ₹<?= number_format($b['total_price'], 2) ?></p>

            <?php if ($b['status'] !== 'returned'): ?>
              <form method="POST" onsubmit="return confirm('Are you sure you want to return this car?');" class="mt-3">
                <input type="hidden" name="return_booking_id" value="<?= htmlspecialchars($b['booking_id']) ?>">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded">
                  Return Car
                </button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
