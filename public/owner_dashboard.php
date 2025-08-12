<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/config.php'; // PostgreSQL connection $conn
$owner_id = $_SESSION['user_id'];

// Flash message (success/error)
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Handle new car submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_name = trim($_POST['car_name'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model_year = intval($_POST['model_year'] ?? 0);
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');

    if (!$car_name || !$brand || !$model_year || !$price_per_day) {
        $message = "Please fill in all required fields.";
    } elseif (empty($image_url) && (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)) {
        $message = "Please either upload a car image or provide an image URL.";
    } else {
        $image_path = '';
        
        if (!empty($image_url)) {
            // Use the provided URL directly
            $image_path = $image_url;
        } else {
            // Handle file upload
            $upload_dir = 'uploads/cars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['image']['name']));
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = '/' . $target_file;
            } else {
                $message = "Failed to upload image.";
            }
        }
        
        if (!empty($image_path)) {
            $insert_car_sql = "INSERT INTO pending_cars (owner_id, car_name, model, brand, model_year, price_per_day, created_at, status) 
                               VALUES ($1, $2, $3, $4, $5, $6, NOW(), 'pending') RETURNING car_id";
            $result = pg_query_params($conn, $insert_car_sql, [
                $owner_id, $car_name, $model, $brand, $model_year, $price_per_day
            ]);

            if ($result && ($row = pg_fetch_assoc($result))) {
                $car_id = $row['car_id'];

                $insert_img_sql = "INSERT INTO car_images (car_id, image_path, created_at) VALUES ($1, $2, NOW())";
                pg_query_params($conn, $insert_img_sql, [$car_id, $image_path]);

                $_SESSION['message'] = "Car added successfully and pending admin approval.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $message = "Failed to save car data.";
            }
        }
    }
}

// Fetch pending cars for this owner (with their images)
$pending_sql = "
    SELECT p.car_id, p.car_name, p.model, p.brand, p.model_year, p.price_per_day, p.status, p.created_at,
           ci.image_path
    FROM pending_cars p
    LEFT JOIN car_images ci ON p.car_id = ci.car_id
    WHERE p.owner_id = $1
    ORDER BY p.created_at DESC
";
$res_pending = pg_query_params($conn, $pending_sql, [$owner_id]);
$pending_cars = $res_pending ? pg_fetch_all($res_pending) : [];

// Fetch approved cars for this owner (with their images)
$approved_sql = "
    SELECT c.id AS car_id, c.car_name, c.model, c.brand, c.model_year, c.price_per_day, c.available, c.created_at,
           ci.image_path
    FROM cars c
    LEFT JOIN car_images ci ON c.id = ci.car_id
    WHERE c.owner_id = $1
    ORDER BY c.created_at DESC
";
$res_approved = pg_query_params($conn, $approved_sql, [$owner_id]);
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
<title>Owner Dashboard - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'owner_header.php'; ?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Your Cars</h1>

    <?php if ($message): 
        $isSuccess = strpos($message, 'successfully') !== false;
        $alertClass = $isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
    ?>
        <div class="mb-4 p-3 <?= $alertClass ?> rounded">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add New Car Form -->
    <section class="mb-12 bg-white p-6 rounded shadow max-w-xl">
        <h2 class="text-2xl font-semibold mb-4">Add New Car</h2>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6" novalidate>
            <div>
                <label for="car_name" class="block font-semibold mb-1">Car Name *</label>
                <input id="car_name" type="text" name="car_name" value="<?= htmlspecialchars($_POST['car_name'] ?? '') ?>" required class="w-full p-2 border rounded" />
            </div>
            <div>
                <label for="model" class="block font-semibold mb-1">Model</label>
                <input id="model" type="text" name="model" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" class="w-full p-2 border rounded" />
            </div>
            <div>
                <label for="brand" class="block font-semibold mb-1">Brand *</label>
                <input id="brand" type="text" name="brand" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" required class="w-full p-2 border rounded" />
            </div>
            <div>
                <label for="model_year" class="block font-semibold mb-1">Model Year *</label>
                <input id="model_year" type="number" name="model_year" value="<?= htmlspecialchars($_POST['model_year'] ?? '') ?>" min="1900" max="<?= date('Y') ?>" required class="w-full p-2 border rounded" />
            </div>
            <div>
                <label for="price_per_day" class="block font-semibold mb-1">Price per Day (₹) *</label>
                <input id="price_per_day" type="number" step="0.01" name="price_per_day" value="<?= htmlspecialchars($_POST['price_per_day'] ?? '') ?>" min="0" required class="w-full p-2 border rounded" />
            </div>
            <div>
                <label for="image" class="block font-semibold mb-1">Car Image *</label>
                <input id="image" type="file" name="image" accept="image/*" required class="w-full" />
            </div>
            <div class="md:col-span-2">
                <label for="image_url" class="block font-semibold mb-1">Or Image URL</label>
                <input id="image_url" type="url" name="image_url" placeholder="https://example.com/image.jpg" class="w-full p-2 border rounded" />
                <p class="text-sm text-gray-500 mt-1">You can either upload a file or provide an image URL</p>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded">Add Car</button>
            </div>
        </form>
    </section>

    <!-- Pending Cars -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Pending Cars (Awaiting Approval)</h2>
        <?php if (!$pending_cars): ?>
            <p class="text-gray-600">You have no pending cars.</p>
        <?php else: ?>
            <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($pending_cars as $car): ?>
                    <?php
                        // For safety, escape output once here
                        $imgSrc = htmlspecialchars(getImageUrl($car['image_path']));
                        $carName = htmlspecialchars($car['car_name'] ?? '');
                        $brand = htmlspecialchars($car['brand'] ?? '');
                        $model = htmlspecialchars($car['model'] ?? '');
                        $price = number_format($car['price_per_day'] ?? 0, 2);
                        $status = htmlspecialchars(ucfirst($car['status'] ?? 'pending'));
                    ?>
                    <div class="bg-white p-4 rounded shadow flex flex-col">
                        <img
                            src="<?= $imgSrc ?>"
                            alt="<?= $carName ?>"
                            class="h-40 w-full object-cover rounded mb-4"
                            loading="lazy"
                            onerror="this.src='/assets/images/no-image.png';"
                        />
                        <h3 class="text-lg font-semibold"><?= $carName ?></h3>
                        <p class="text-sm text-gray-600"><?= $brand ?> - <?= $model ?></p>
                        <p class="mb-2 font-bold">₹<?= $price ?> / day</p>
                        <p class="text-yellow-600 font-semibold">Status: <?= $status ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Approved Cars -->
    <section>
        <h2 class="text-2xl font-semibold mb-4">Approved Cars</h2>
        <?php if (!$approved_cars): ?>
            <p class="text-gray-600">You have no approved cars yet.</p>
        <?php else: ?>
            <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($approved_cars as $car): ?>
                    <?php
                        $imgSrc = htmlspecialchars(getImageUrl($car['image_path']));
                        $carName = htmlspecialchars($car['car_name'] ?? '');
                        $brand = htmlspecialchars($car['brand'] ?? '');
                        $model = htmlspecialchars($car['model'] ?? '');
                        $price = number_format($car['price_per_day'] ?? 0, 2);
                        $available = $car['available'] ?? null;
                        $availability_text = ($available === 't' || $available === true || $available === 1) ? 'Available' : 'Unavailable';
                        $availability_class = ($availability_text === 'Available') ? 'text-green-600' : 'text-red-600';
                    ?>
                    <div class="bg-white p-4 rounded shadow flex flex-col">
                        <img
                            src="<?= $imgSrc ?>"
                            alt="<?= htmlspecialchars($car['model'] ?? '') ?>"
                            class="h-40 w-full object-cover rounded mb-4"
                            loading="lazy"
                            onerror="this.src='/assets/images/no-image.png';"
                        />
                        <h3 class="text-lg font-semibold"><?= $carName ?></h3>
                        <p class="text-sm text-gray-600"><?= $brand ?> - <?= $model ?></p>
                        <p class="mb-2 font-bold">₹<?= $price ?> / day</p>
                        <p class="<?= $availability_class ?> font-semibold"><?= $availability_text ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
