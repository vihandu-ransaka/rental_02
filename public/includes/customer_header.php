<?php
// No session_start() here, it's already started in the main file
$username = $_SESSION['name'] ?? 'Customer';
?>
<header class="bg-indigo-600 text-white p-4 flex justify-between items-center">
  <div class="text-lg font-semibold">
    Welcome, <?php echo htmlspecialchars($username); ?>
  </div>
  <div>
    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded font-semibold">Logout</a>
  </div>
</header>
