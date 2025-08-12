<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DriveMint</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
</head>
<body class="bg-gray-100 font-sans">

<!-- Navbar -->
<header class="bg-white shadow">
  <div class="container mx-auto px-6 py-4 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-bold text-indigo-600">DriveMint</a>
    <nav class="space-x-6 hidden md:flex">
      <a href="index.php" class="text-gray-700 hover:text-indigo-600">Home</a>
      <a href="cars.php" class="text-gray-700 hover:text-indigo-600">Cars</a>
      <a href="about.php" class="text-gray-700 hover:text-indigo-600">About</a>
      <a href="contact.php" class="text-gray-700 hover:text-indigo-600">Contact</a>
    </nav>
    <a href="login.php" class="hidden md:inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Login</a>

    <!-- Mobile menu button -->
    <div class="md:hidden">
      <button id="mobile-menu-button" aria-label="Open Menu" class="text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        <i class="fas fa-bars fa-lg"></i>
      </button>
    </div>
  </div>

  <!-- Mobile menu, hidden by default -->
  <nav id="mobile-menu" class="hidden md:hidden bg-white px-6 pb-4 space-y-2">
    <a href="index.php" class="block text-gray-700 hover:text-indigo-600">Home</a>
    <a href="cars.php" class="block text-gray-700 hover:text-indigo-600">Cars</a>
    <a href="about.php" class="block text-gray-700 hover:text-indigo-600">About</a>
    <a href="contact.php" class="block text-gray-700 hover:text-indigo-600">Contact</a>
    <a href="login.php" class="block bg-indigo-600 text-white text-center py-2 rounded hover:bg-indigo-700">Login</a>
  </nav>
</header>

<script>
  const btn = document.getElementById('mobile-menu-button');
  const menu = document.getElementById('mobile-menu');

  btn.addEventListener('click', () => {
    menu.classList.toggle('hidden');
  });
</script>
