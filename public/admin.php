<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'includes/config.php';

// Flash message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Handle Approve/Reject/Delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $car_id = intval($_POST['car_id'] ?? 0);

    if ($action && $car_id > 0) {
        if ($action === 'approve') {
            // Start transaction
            pg_query($conn, 'BEGIN');

            try {
                // Get pending car info
                $res = pg_query_params($conn, "SELECT * FROM pending_cars WHERE car_id = $1", [$car_id]);
                $car = pg_fetch_assoc($res);

                if ($car) {
                    // Get the image path from car_images table for this pending car first
                    $img_res = pg_query_params($conn, "SELECT image_path FROM car_images WHERE car_id = $1 LIMIT 1", [$car_id]);
                    $image_data = pg_fetch_assoc($img_res);
                    $image_path = $image_data['image_path'] ?? 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=400';

                    // Insert into cars table with image_path
                    $insert = pg_query_params($conn, "
                        INSERT INTO cars (owner_id, car_name, model, brand, model_year, price_per_day, image_path, available, created_at)
                        VALUES ($1, $2, $3, $4, $5, $6, $7, TRUE, NOW())
                        RETURNING id
                    ", [
                        $car['owner_id'], 
                        $car['car_name'], 
                        $car['model'], 
                        $car['brand'], 
                        $car['model_year'], 
                        $car['price_per_day'],
                        $image_path
                    ]);

                    if ($insert && pg_num_rows($insert) > 0) {
                        $new_car = pg_fetch_assoc($insert);
                        $new_car_id = $new_car['id'];

                        // Copy images from pending car to new approved car
                        $img_res_all = pg_query_params($conn, "SELECT * FROM car_images WHERE car_id = $1", [$car_id]);
                        while ($img = pg_fetch_assoc($img_res_all)) {
                            pg_query_params($conn, "
                                INSERT INTO car_images (car_id, image_path, created_at) 
                                VALUES ($1, $2, NOW())
                            ", [$new_car_id, $img['image_path']]);
                        }

                        // Add to available_cars table
                        $available_insert = pg_query_params($conn, "
                            INSERT INTO available_cars (car_id, car_name, model, brand, model_year, price_per_day, image_path, available)
                            VALUES ($1, $2, $3, $4, $5, $6, $7, TRUE)
                        ", [
                            $new_car_id,
                            $car['car_name'],
                            $car['model'],
                            $car['brand'],
                            $car['model_year'],
                            $car['price_per_day'],
                            $image_path
                        ]);

                        if ($available_insert) {
                            // Delete pending car and its images
                            pg_query_params($conn, "DELETE FROM car_images WHERE car_id = $1", [$car_id]);
                            pg_query_params($conn, "DELETE FROM pending_cars WHERE car_id = $1", [$car_id]);

                            pg_query($conn, 'COMMIT');
                            $_SESSION['message'] = "Car approved successfully.";
                        } else {
                            throw new Exception("Failed to add car to available_cars table.");
                        }
                    } else {
                        throw new Exception("Failed to insert approved car.");
                    }
                } else {
                    throw new Exception("Pending car not found.");
                }
            } catch (Exception $e) {
                pg_query($conn, 'ROLLBACK');
                $_SESSION['message'] = "Error approving car: " . $e->getMessage();
            }

            header('Location: admin.php');
            exit;

        } elseif ($action === 'reject') {
            // Delete pending car and its images
            pg_query_params($conn, "DELETE FROM car_images WHERE car_id = $1", [$car_id]);
            $delete_result = pg_query_params($conn, "DELETE FROM pending_cars WHERE car_id = $1", [$car_id]);
            
            if ($delete_result) {
                $_SESSION['message'] = "Car rejected and deleted successfully.";
            } else {
                $_SESSION['message'] = "Error rejecting car.";
            }
            header('Location: admin.php');
            exit;

        } elseif ($action === 'delete') {
            // Delete approved car and its related data
            pg_query($conn, 'BEGIN');
            
            try {
                // Delete from available_cars first
                pg_query_params($conn, "DELETE FROM available_cars WHERE car_id = $1", [$car_id]);
                
                // Delete car images
                pg_query_params($conn, "DELETE FROM car_images WHERE car_id = $1", [$car_id]);
                
                // Delete any bookings for this car
                pg_query_params($conn, "DELETE FROM bookings WHERE car_id = $1", [$car_id]);
                
                // Finally delete the car itself
                $delete_car = pg_query_params($conn, "DELETE FROM cars WHERE id = $1", [$car_id]);
                
                if ($delete_car) {
                    pg_query($conn, 'COMMIT');
                    $_SESSION['message'] = "Approved car deleted successfully.";
                } else {
                    throw new Exception("Failed to delete car.");
                }
            } catch (Exception $e) {
                pg_query($conn, 'ROLLBACK');
                $_SESSION['message'] = "Error deleting car: " . $e->getMessage();
            }
            
            header('Location: admin.php');
            exit;
        }
    } else {
        $_SESSION['message'] = "Invalid request parameters.";
        header('Location: admin.php');
        exit;
    }
}

// Fetch pending cars with images
$pending_sql = "
    SELECT DISTINCT ON (p.car_id) 
           p.car_id, p.car_name, p.model, p.brand, p.model_year, p.price_per_day, p.status, p.created_at,
           ci.image_path
    FROM pending_cars p
    LEFT JOIN car_images ci ON p.car_id = ci.car_id
    WHERE p.status = 'pending'
    ORDER BY p.car_id, p.created_at DESC
";
$res_pending = pg_query($conn, $pending_sql);
$pending_cars = $res_pending ? pg_fetch_all($res_pending) : [];

// Fetch approved cars with images
$approved_sql = "
    SELECT DISTINCT ON (c.id) 
           c.id AS car_id, c.car_name, c.model, c.brand, c.model_year, c.price_per_day, c.available, c.created_at,
           ci.image_path
    FROM cars c
    LEFT JOIN car_images ci ON c.id = ci.car_id
    ORDER BY c.id, c.created_at DESC
";
$res_approved = pg_query($conn, $approved_sql);
$approved_cars = $res_approved ? pg_fetch_all($res_approved) : [];

function getImageUrl($image_path) {
    if (!$image_path) return 'https://images.pexels.com/photos/170811/pexels-photo-170811.jpeg?auto=compress&cs=tinysrgb&w=400';
    
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
<body class="bg-gray-50 min-h-screen">

<?php include 'includes/admin_header.php'; ?>

<main class="container mx-auto px-4 py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Dashboard</h1>
    <p class="text-gray-600">Manage car approvals and system overview</p>
  </div>

  <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?= strpos($message, 'successfully') !== false || strpos($message, 'approved') !== false ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
      <div class="flex items-center">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <?= htmlspecialchars($message) ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Statistics Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
      <div class="flex items-center">
        <div class="p-2 bg-yellow-100 rounded-lg">
          <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Pending Cars</p>
          <p class="text-2xl font-semibold text-gray-900"><?= count($pending_cars) ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
      <div class="flex items-center">
        <div class="p-2 bg-green-100 rounded-lg">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Approved Cars</p>
          <p class="text-2xl font-semibold text-gray-900"><?= count($approved_cars) ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
      <div class="flex items-center">
        <div class="p-2 bg-blue-100 rounded-lg">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Total Cars</p>
          <p class="text-2xl font-semibold text-gray-900"><?= count($pending_cars) + count($approved_cars) ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Cars Section -->
  <section class="mb-12">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
          <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
          </svg>
          Pending Cars Awaiting Approval
        </h2>
      </div>
      
      <div class="p-6">
        <?php if (!$pending_cars): ?>
          <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No pending cars</h3>
            <p class="mt-1 text-sm text-gray-500">All car submissions have been processed.</p>
          </div>
        <?php else: ?>
          <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($pending_cars as $car): ?>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                <img
                  src="<?= htmlspecialchars(getImageUrl($car['image_path'])) ?>"
                  alt="<?= htmlspecialchars($car['car_name']) ?>"
                  class="h-40 w-full object-cover rounded-lg mb-4"
                  loading="lazy"
                />
                <div class="space-y-2">
                  <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($car['car_name']) ?></h3>
                  <p class="text-sm text-gray-600"><?= htmlspecialchars($car['brand']) ?> - <?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['model_year']) ?>)</p>
                  <p class="text-lg font-bold text-indigo-600">₹<?= number_format($car['price_per_day'], 2) ?> / day</p>
                  <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                    Submitted: <?= date('M j, Y', strtotime($car['created_at'])) ?>
                  </div>
                </div>
                
                <div class="mt-4 flex gap-2">
                  <form method="POST" class="flex-1">
                    <input type="hidden" name="car_id" value="<?= $car['car_id'] ?>" />
                    <button 
                      type="submit" 
                      name="action" 
                      value="approve" 
                      class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                    >
                      Approve
                    </button>
                  </form>
                  <form method="POST" class="flex-1">
                    <input type="hidden" name="car_id" value="<?= $car['car_id'] ?>" />
                    <button 
                      type="submit" 
                      name="action" 
                      value="reject" 
                      class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                      onclick="return confirm('Are you sure you want to reject this car? This action cannot be undone.')"
                    >
                      Reject
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Approved Cars Section -->
  <section>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
          <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          Approved Cars
        </h2>
      </div>
      
      <div class="p-6">
        <?php if (!$approved_cars): ?>
          <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No approved cars</h3>
            <p class="mt-1 text-sm text-gray-500">No cars have been approved yet.</p>
          </div>
        <?php else: ?>
          <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($approved_cars as $car): ?>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                <img
                  src="<?= htmlspecialchars(getImageUrl($car['image_path'])) ?>"
                  alt="<?= htmlspecialchars($car['car_name']) ?>"
                  class="h-40 w-full object-cover rounded-lg mb-4"
                  loading="lazy"
                />
                <div class="space-y-2">
                  <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($car['car_name']) ?></h3>
                  <p class="text-sm text-gray-600"><?= htmlspecialchars($car['brand']) ?> - <?= htmlspecialchars($car['model']) ?> (<?= htmlspecialchars($car['model_year']) ?>)</p>
                  <p class="text-lg font-bold text-indigo-600">₹<?= number_format($car['price_per_day'], 2) ?> / day</p>
                  <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($car['available'] === 't') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                      <?= ($car['available'] === 't') ? 'Available' : 'Unavailable' ?>
                    </span>
                    <span class="text-sm text-gray-500">
                      <?= date('M j, Y', strtotime($car['created_at'])) ?>
                    </span>
                  </div>
                </div>
                
                <form method="POST" class="mt-4">
                  <input type="hidden" name="car_id" value="<?= $car['car_id'] ?>" />
                  <button 
                    type="submit" 
                    name="action" 
                    value="delete" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                    onclick="return confirm('Are you sure you want to delete this approved car? This action cannot be undone.')"
                  >
                    Delete Car
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>