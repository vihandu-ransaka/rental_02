<?php
session_start();
if (!isset($_SESSION['admin_id']) && $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'includes/config.php'; // your DB connection

// Flash message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Handle Approve/Reject/Delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $car_id = intval($_POST['car_id'] ?? 0);

    if ($action && $car_id > 0) {
        if ($action === 'approve') {
            // Move car from pending_cars to cars table
            $conn = $conn; // your pg connection
            pg_query($conn, 'BEGIN');

            // Get pending car info
            $res = pg_query_params($conn, "SELECT * FROM pending_cars WHERE car_id = $1", [$car_id]);
            $car = pg_fetch_assoc($res);

            if ($car) {
                // Insert into cars
                $insert = pg_query_params($conn, "
                    INSERT INTO cars (owner_id, car_name, model, brand, model_year, price_per_day, available, created_at)
                    VALUES ($1, $2, $3, $4, $5, $6, TRUE, NOW())
                    RETURNING id
                ", [
                    $car['owner_id'], $car['car_name'], $car['model'], $car['brand'], $car['model_year'], $car['price_per_day']
                ]);

                $new_car = pg_fetch_assoc($insert);

                if ($new_car) {
                    $new_car_id = $new_car['id'];

                    // Copy images from pending_cars to cars_images with new car_id
                    $img_res = pg_query_params($conn, "SELECT * FROM car_images WHERE car_id = $1", [$car_id]);
                    while ($img = pg_fetch_assoc($img_res)) {
                        pg_query_params($conn, "INSERT INTO car_images (car_id, image_path, created_at) VALUES ($1, $2, NOW())", [$new_car_id, $img['image_path']]);
                    }

                    // Delete pending car and its images
                    pg_query_params($conn, "DELETE FROM car_images WHERE car_id = $1", [$car_id]);
                    pg_query_params($conn, "DELETE FROM pending_cars WHERE car_id = $1", [$car_id]);

                    pg_query($conn, 'COMMIT');

                    $_SESSION['message'] = "Car approved successfully.";
                } else {
                    pg_query($conn, 'ROLLBACK');
                    $_SESSION['message'] = "Failed to insert approved car.";
                }
            } else {
                pg_query($conn, 'ROLLBACK');
                $_SESSION['message'] = "Pending car not found.";
            }

            header('Location: admin_dashboard.php');
            exit;
        } elseif ($action === 'reject') {
            // Delete pending car and its images
            pg_query_params($conn, "DELETE FROM car_images WHERE car_id = $1", [$car_id]);
            pg_query_params($conn, "DELETE FROM pending_cars WHERE car_id = $1", [$car_id]);
            $_SESSION['message'] = "Car rejected and deleted.";
            header('Location: admin_dashboard.php');
            exit;
        } elseif ($action === 'delete') {
            // Delete approved car and its images
            pg_query_params($conn, "DELETE FROM car_images WHERE car_id = $1", [$car_id]);
            pg_query_params($conn, "DELETE FROM cars WHERE id = $1", [$car_id]);
            $_SESSION['message'] = "Approved car deleted.";
            header('Location: admin_dashboard.php');
            exit;
        }
    }
}

// Fetch pending cars with images
$pending_sql = "
    SELECT p.car_id, p.car_name, p.model, p.brand, p.model_year, p.price_per_day, p.status, p.created_at,
           ci.image_path
    FROM pending_cars p
    LEFT JOIN car_images ci ON p.car_id = ci.car_id
    ORDER BY p.created_at DESC
";
$res_pending = pg_query($conn, $pending_sql);
$pending_cars = $res_pending ? pg_fetch_all($res_pending) : [];

// Fetch approved cars with images
$approved_sql = "
    SELECT c.id AS car_id, c.car_name, c.model, c.brand, c.model_year, c.price_per_day, c.available, c.created_at,
           ci.image_path
    FROM cars c
    LEFT JOIN car_images ci ON c.id = ci.car_id
    ORDER BY c.created_at DESC
";
$res_approved = pg_query($conn, $approved_sql);
$approved_cars = $res_approved ? pg_fetch_all($res_approved) : [];

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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<?php include 'includes/admin_header.php'; ?>

<main class="container mx-auto px-4 py-8">
  <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>

  <?php if ($message): ?>
    <div class="mb-4 p-3 <?= strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> rounded">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Pending Cars -->
  <section class="mb-12">
    <h2 class="text-2xl font-semibold mb-4">Pending Cars</h2>
    <?php if (!$pending_cars): ?>
      <p class="text-gray-600">No pending cars at the moment.</p>
    <?php else: ?>
      <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($pending_cars as $car): ?>
          <div class="bg-white p-4 rounded shadow flex flex-col">
            <img
              src="<?= htmlspecialchars(getImageUrl($car['image_path'])) ?>"
              alt="<?= htmlspecialchars($car['car_name']) ?>"
              class="h-40 w-full object-cover rounded mb-4"
              loading="lazy"
              onerror="this.src='/assets/images/no-image.png';"
            />
            <h3 class="text-lg font-semibold"><?= htmlspecialchars($car['car_name']) ?></h3>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($car['brand']) ?> - <?= htmlspecialchars($car['model']) ?></p>
            <p class="mb-2 font-bold">₹<?= number_format($car['price_per_day'], 2) ?> / day</p>
            <p class="text-yellow-600 font-semibold">Status: <?= htmlspecialchars(ucfirst($car['status'])) ?></p>
            <form method="POST" class="mt-auto flex gap-2">
              <input type="hidden" name="car_id" value="<?= $car['car_id'] ?>" />
              <button type="submit" name="action" value="approve" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Approve</button>
              <button type="submit" name="action" value="reject" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Reject</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Approved Cars -->
  <section>
    <h2 class="text-2xl font-semibold mb-4">Approved Cars</h2>
    <?php if (!$approved_cars): ?>
      <p class="text-gray-600">No approved cars found.</p>
    <?php else: ?>
      <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($approved_cars as $car): ?>
          <div class="bg-white p-4 rounded shadow flex flex-col">
            <img
              src="<?= htmlspecialchars(getImageUrl($car['image_path'])) ?>"
              alt="<?= htmlspecialchars($car['car_name']) ?>"
              class="h-40 w-full object-cover rounded mb-4"
              loading="lazy"
              onerror="this.src='/assets/images/no-image.png';"
            />
            <h3 class="text-lg font-semibold"><?= htmlspecialchars($car['car_name']) ?></h3>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($car['brand']) ?> - <?= htmlspecialchars($car['model']) ?></p>
            <p class="mb-2 font-bold">₹<?= number_format($car['price_per_day'], 2) ?> / day</p>
            <p class="<?= ($car['available'] === 't') ? 'text-green-600' : 'text-red-600' ?> font-semibold">
              <?= ($car['available'] === 't') ? 'Available' : 'Unavailable' ?>
            </p>
            <form method="POST" class="mt-auto">
              <input type="hidden" name="car_id" value="<?= $car['car_id'] ?>" />
              <button type="submit" name="action" value="delete" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded mt-2 w-full">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
