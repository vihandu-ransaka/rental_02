<?php
// driver_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['name'])) {
    header('Location: ../login.php');
    exit;
}

$username = $_SESSION['name']; // use 'name' from session

?>

<header class="bg-indigo-600 text-white p-4 flex justify-between items-center">
  <div class="text-lg font-bold">
    DriveMint - Driver Dashboard
  </div>
  <div class="flex items-center space-x-4">
    <span>Welcome <strong><?= htmlspecialchars($username) ?></strong></span>
    <form action="../logout.php" method="POST" class="inline">
      <button type="submit" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-white font-semibold">
        Logout
      </button>
    </form>
  </div>
</header>
