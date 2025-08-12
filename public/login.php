<?php
session_start();
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $result = pg_query_params($conn, "SELECT * FROM users WHERE email = $1", [$email]);
        if (pg_num_rows($result) === 1) {
            $user = pg_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                // Redirect by role
                switch ($user['role']) {
                    case 'admin':
                        $_SESSION['admin_id'] = $user['id']; // Set admin_id for admin dashboard
                        header('Location: admin.php');
                        exit;
                    case 'customer':
                        header('Location: dashboards/customer.php');
                        exit;
                    case 'driver':
                        header('Location: dashboards/driver.php');
                        exit;
                    case 'car_owner':
                        header('Location: owner_dashboard.php');
                        exit;
                    default:
                        $error = "Invalid role.";
                }
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No user found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
    <?php if ($error): ?>
      <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" novalidate>
        <input type="email" name="email" placeholder="Email" class="w-full p-2 border rounded mb-4" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        <input type="password" name="password" placeholder="Password" class="w-full p-2 border rounded mb-4" required>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white w-full py-2 rounded">Login</button>
    </form>
    <p class="mt-4 text-center text-sm">Don't have an account? <a href="register.php" class="text-indigo-600">Register</a></p>
</div>

</body>
</html>
