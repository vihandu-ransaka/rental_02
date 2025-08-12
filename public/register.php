<?php
require_once 'includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!in_array($role, ['customer', 'driver', 'car_owner'])) {
        $error = "Invalid role selected.";
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill all fields.";
    } else {
        // Check if email exists
        $result = pg_query_params($conn, "SELECT * FROM users WHERE email = $1", [$email]);
        if (pg_num_rows($result) > 0) {
            $error = "Email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = pg_query_params($conn,
                "INSERT INTO users (name, email, password, role) VALUES ($1, $2, $3, $4)",
                [$name, $email, $hashed, $role]
            );

            if ($insert) {
                header('Location: login.php');
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register - DriveMint</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center">Create an Account</h2>
    <?php if ($error): ?>
      <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" novalidate>
        <input type="text" name="name" placeholder="Full Name" class="w-full p-2 border rounded mb-4" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
        <input type="email" name="email" placeholder="Email" class="w-full p-2 border rounded mb-4" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        <input type="password" name="password" placeholder="Password" class="w-full p-2 border rounded mb-4" required>
        
        <select name="role" class="w-full p-2 border rounded mb-4" required>
            <option value="">Select Role</option>
            <option value="customer" <?php if(isset($role) && $role === 'customer') echo 'selected'; ?>>Customer</option>
            <option value="driver" <?php if(isset($role) && $role === 'driver') echo 'selected'; ?>>Driver</option>
            <option value="car_owner" <?php if(isset($role) && $role === 'car_owner') echo 'selected'; ?>>Car Owner</option>
        </select>

        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white w-full py-2 rounded">Register</button>
    </form>
    <p class="mt-4 text-center text-sm">Already have an account? <a href="login.php" class="text-indigo-600">Login</a></p>
</div>

</body>
</html>
