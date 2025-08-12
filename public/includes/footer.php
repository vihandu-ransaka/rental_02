<!-- Footer -->
<footer class="bg-gray-900 text-gray-300 pt-10 mt-12 relative">

  <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-8">

    <!-- Brand -->
    <div>
      <h2 class="text-2xl font-bold text-white">DriveMint</h2>
      <p class="mt-3 text-sm">
        Reliable and affordable car rentals for your every journey.<br />
        Your comfort is our priority.
      </p>
    </div>

    <!-- Quick Links -->
    <div>
      <h3 class="text-lg font-semibold text-white mb-3">Quick Links</h3>
      <ul class="space-y-2">
        <li><a href="index.php" class="hover:text-indigo-400">Home</a></li>
        <li><a href="available_cars.php" class="hover:text-indigo-400">Available Cars</a></li>
        <li><a href="bookings.php" class="hover:text-indigo-400">My Bookings</a></li>
        <li><a href="contact.php" class="hover:text-indigo-400">Contact Us</a></li>
      </ul>
    </div>

    <!-- Contact -->
    <div>
      <h3 class="text-lg font-semibold text-white mb-3">Contact</h3>
      <ul class="space-y-2 text-sm">
        <li><i class="fas fa-map-marker-alt mr-2"></i> Colombo, Sri Lanka</li>
        <li><i class="fas fa-phone mr-2"></i> +94 71 234 5678</li>
        <li><i class="fas fa-envelope mr-2"></i> support@drivemint.com</li>
      </ul>
    </div>

    <!-- Social Media -->
    <div>
      <h3 class="text-lg font-semibold text-white mb-3">Follow Us</h3>
      <div class="flex space-x-4">
        <a href="#" class="hover:text-indigo-400" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="hover:text-indigo-400" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" class="hover:text-indigo-400" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" class="hover:text-indigo-400" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>

  </div>

  <!-- Bottom Bar -->
  <div class="border-t border-gray-700 mt-8 py-4 text-center text-sm text-gray-500">
    &copy; <?php echo date("Y"); ?> DriveMint. All Rights Reserved.
  </div>

  <!-- Back to Top Button -->
  <button
    id="backToTop"
    class="hidden fixed bottom-6 right-6 bg-indigo-600 text-white p-3 rounded-full shadow-lg hover:bg-indigo-700 transition"
    aria-label="Back to Top"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
    </svg>
  </button>

</footer>

<script>
  // Show/hide back to top button
  const backToTopButton = document.getElementById('backToTop');

  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
      backToTopButton.classList.remove('hidden');
    } else {
      backToTopButton.classList.add('hidden');
    }
  });

  // Smooth scroll back to top
  backToTopButton.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
</script>

</body>
</html>
