<?php
// index.php
session_start();
include 'includes/config.php';

// Initialize variables
$search_results = [];
$pickup_date = '';
$dropoff_date = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_date = $_POST['pickup_date'] ?? '';
    $dropoff_date = $_POST['dropoff_date'] ?? '';

    // Validate dates
    if (!$pickup_date || !$dropoff_date) {
        $error = "Please fill in both dates.";
    } elseif ($pickup_date > $dropoff_date) {
        $error = "Return date cannot be earlier than pickup date.";
    } else {
        // Query available cars (no car type filter)
        $sql = "
            SELECT car_id, car_name, model, brand, model_year, price_per_day, image_path, available
            FROM available_cars
            WHERE available = TRUE
        ";
        $result = pg_query($conn, $sql);
        if ($result) {
            $search_results = pg_fetch_all($result) ?: [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Car Rental | Home</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-[500px] overflow-hidden">
  <video autoplay muted loop playsinline class="absolute top-0 left-0 w-full h-full object-cover">
    <source src="assets/videos/home.mp4" type="video/mp4" />
    Your browser does not support the video tag.
  </video>
  <div class="absolute inset-0 bg-black bg-opacity-50 flex flex-col justify-center items-center text-center px-6">
    <h1 class="text-white text-4xl md:text-5xl font-extrabold mb-4 drop-shadow-lg">Drive Your Dream Car Today</h1>
    <p class="text-gray-300 max-w-xl mb-8 text-lg md:text-xl drop-shadow-md">Affordable rentals with premium service and best deals.</p>
    <a href="login.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded font-semibold transition">Book Now</a>
  </div>
</section>

<!-- Booking Form -->
<section id="booking-form" class="bg-white shadow-lg rounded-lg -mt-20 max-w-4xl mx-auto p-8 relative z-10">
  <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Book Your Car</h2>

  <?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
      <label for="pickup_date" class="block text-sm font-medium text-gray-700 mb-1">Pick-up Date</label>
      <input
        type="date"
        id="pickup_date"
        name="pickup_date"
        required
        value="<?php echo htmlspecialchars($pickup_date); ?>"
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        min="<?php echo date('Y-m-d'); ?>"
      />
    </div>

    <div>
      <label for="dropoff_date" class="block text-sm font-medium text-gray-700 mb-1">Return Date</label>
      <input
        type="date"
        id="dropoff_date"
        name="dropoff_date"
        required
        value="<?php echo htmlspecialchars($dropoff_date); ?>"
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        min="<?php echo date('Y-m-d'); ?>"
      />
    </div>

    <div class="flex items-end">
      <button
        type="submit"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded transition"
      >
        Search Cars
      </button>
    </div>
  </form>
</section>

<!-- Search Results -->
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<section class="container mx-auto px-6 py-16">
  <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Search Results</h2>

  <?php if (empty($search_results)): ?>
    <p class="text-center text-gray-600">No cars found matching your search.</p>
  <?php else: ?>
    <div class="grid gap-8 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      <?php foreach ($search_results as $car): ?>
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
          <img src="<?php echo htmlspecialchars(getImageUrl($car['image_path'])); ?>" alt="<?php echo htmlspecialchars($car['car_name']); ?>" class="w-full h-48 object-cover" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image';" />
          <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($car['car_name']); ?></h3>
            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($car['brand']); ?> - <?php echo htmlspecialchars($car['model_year']); ?></p>
            <p class="text-indigo-600 font-bold mt-2">₹<?php echo number_format($car['price_per_day'], 2); ?> <span class="text-gray-600 font-normal">/ day</span></p>
            <a href="login.php?car_id=<?php echo $car['car_id']; ?>&pickup_date=<?php echo urlencode($pickup_date); ?>&dropoff_date=<?php echo urlencode($dropoff_date); ?>" class="inline-block mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Book Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<?php
function getImageUrl($image_path) {
    if (!$image_path) return 'https://via.placeholder.com/400x300?text=No+Image';
    
    // If it's already a full URL, return as is
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        return $image_path;
    }
    
    // If it starts with /, it's already a proper path
    if (strpos($image_path, '/') === 0) {
        return $image_path;
    }
    
    // Otherwise, add leading slash
    return '/' . $image_path;
}
?>

</body>
</html>
