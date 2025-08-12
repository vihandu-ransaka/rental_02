<?php
// admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['name'] ?? 'Admin';
?>

<header class="bg-indigo-700 text-white p-4 flex justify-between items-center shadow-md">
  <div class="text-lg font-semibold">
    DriveMint Admin Dashboard — Welcome, <?php echo htmlspecialchars($admin_name); ?>
  </div>
  <nav>
    <a href="admin.php" class="mr-4 hover:underline">Dashboard</a>
    <a href="logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold">Logout</a>
  </nav>
</header>