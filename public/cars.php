<?php
// cars.php

// Database connection using PDO (adjust credentials)
$host = 'localhost';
$db   = 'drivemint';
$user = 'postgres';
$pass = '2003';
$dsn = "pgsql:host=$host;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch available cars with seats
    $stmt = $pdo->prepare("SELECT id, model, description, price_per_day, image_path, seats FROM cars WHERE available = TRUE ORDER BY id DESC");
    $stmt->execute();
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Available Cars - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include 'includes/header.php'; ?>

<main class="container mx-auto px-4 py-10">
  <h1 class="text-4xl font-bold text-center mb-12 text-gray-800"> Cars</h1>

  <?php if (empty($cars)): ?>
    <p class="text-center text-gray-600">No cars available at the moment. Please check back later.</p>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
      <?php foreach ($cars as $car): ?>
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden flex flex-col">
          <img src="<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo htmlspecialchars($car['model']); ?>" class="w-full h-48 object-cover">
          <div class="p-4 flex flex-col flex-grow">
            <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($car['model']); ?></h2>
            <p class="text-gray-600 flex-grow mt-2"><?php echo htmlspecialchars($car['description']); ?></p>
            <p class="mt-2 text-gray-700 font-medium">Seats: <?php echo intval($car['seats']); ?></p>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-indigo-600 font-bold text-lg">₹<?php echo number_format($car['price_per_day'], 2); ?> / day</span>
              <button
                data-car-id="<?php echo $car['id']; ?>"
                class="book-btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-semibold transition"
              >
                Book Now
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// JavaScript to handle the Book Now button click event
document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('.book-btn');
  buttons.forEach(button => {
    button.addEventListener('click', () => {
      const carId = button.getAttribute('data-car-id');
      // Check if user is logged in
      <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Please login to book a car.');
        window.location.href = 'login.php';
      <?php else: ?>
        // Redirect to booking page with car id
        window.location.href = `book.php?car_id=${carId}`;
      <?php endif; ?>
    });
  });
});
</script>

</body>
</html>
