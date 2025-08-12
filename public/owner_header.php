<?php
// Assuming session_start() is already called before including this file
$owner_name = $_SESSION['name'] ?? 'Owner';
?>
<header class="bg-indigo-700 text-white p-4 flex justify-between items-center shadow-md">
  <div class="text-lg font-semibold">
    DriveMint Owner Dashboard — Welcome, <?php echo htmlspecialchars($owner_name); ?>
  </div>
  <nav>
    <a href="owner_dashboard.php" class="mr-4 hover:underline">Dashboard</a>
    <a href="logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold">Logout</a>
  </nav>
</header>
