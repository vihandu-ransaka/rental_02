<?php
// about.php
include 'includes/header.php';
?>

<!-- Hero Section -->
<section
  class="relative w-full h-screen bg-cover bg-center"
  style="background-image: url('assets/images/about.jpg');"
>
  <div class="absolute inset-0 bg-black bg-opacity-60"></div>
  <div
    class="relative z-10 flex flex-col justify-center items-center text-center h-full px-6 text-white"
  >
    <h1 class="text-4xl sm:text-5xl font-extrabold mb-4 drop-shadow-lg">
      Welcome to DriveMint
    </h1>
    <p class="text-lg sm:text-xl max-w-3xl mb-8 drop-shadow-md">
      Revolutionizing the way you experience car rentals.
    </p>
    <a
      href="#our-story"
      class="bg-indigo-600 hover:bg-indigo-700 px-8 py-3 rounded-full font-semibold transition"
      >Learn More</a
    >
  </div>
</section>

<!-- Our Story -->
<section
  id="our-story"
  class="py-16 px-6 max-w-7xl mx-auto text-center text-gray-800"
>
  <h2 class="text-3xl sm:text-4xl font-semibold mb-6">Our Story</h2>
  <p class="text-lg sm:text-xl max-w-4xl mx-auto mb-6">
    Founded in 2025, DriveMint emerged from a passion to provide seamless
    and innovative car rental solutions. Our journey began with a simple
    idea: to make car rentals more accessible, efficient, and
    customer-friendly.
  </p>
  <p class="text-lg sm:text-xl max-w-4xl mx-auto">
    From humble beginnings, we've grown into a trusted platform that
    connects customers with a diverse fleet of vehicles, all while
    ensuring transparency, convenience, and exceptional service.
  </p>
</section>

<!-- Mission & Vision -->
<section class="bg-gray-100 py-16 px-6 max-w-7xl mx-auto text-center">
  <h2 class="text-3xl sm:text-4xl font-semibold mb-12">Mission & Vision</h2>
  <div
    class="grid grid-cols-1 sm:grid-cols-2 gap-12 max-w-5xl mx-auto text-gray-700"
  >
    <div class="bg-white p-8 rounded shadow hover:shadow-lg transition">
      <h3 class="text-xl font-semibold mb-4 text-indigo-600">Our Mission</h3>
      <p>
        To deliver a user-friendly and reliable car rental experience that
        empowers people to move freely and effortlessly.
      </p>
    </div>
    <div class="bg-white p-8 rounded shadow hover:shadow-lg transition">
      <h3 class="text-xl font-semibold mb-4 text-indigo-600">Our Vision</h3>
      <p>
        To be the most trusted car rental platform recognized for
        innovation, sustainability, and exceptional service worldwide.
      </p>
    </div>
  </div>
</section>

<!-- Core Values -->
<section class="py-16 px-6 max-w-7xl mx-auto text-center text-gray-800">
  <h2 class="text-3xl sm:text-4xl font-semibold mb-12">Our Core Values</h2>
  <div
    class="grid grid-cols-1 sm:grid-cols-3 gap-12 max-w-6xl mx-auto"
  >
    <div class="bg-white p-8 rounded shadow hover:shadow-lg transition">
      <div class="mb-4 text-indigo-600 text-5xl">
        <!-- Icon: Integrity -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          class="mx-auto h-12 w-12"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
      </div>
      <h3 class="text-xl font-semibold mb-2">Integrity</h3>
      <p>We operate with honesty and transparency in all we do.</p>
    </div>

    <div class="bg-white p-8 rounded shadow hover:shadow-lg transition">
      <div class="mb-4 text-indigo-600 text-5xl">
        <!-- Icon: Customer First -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          class="mx-auto h-12 w-12"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 10h.01M12 10h.01M16 10h.01M9 16h6M9 16a3 3 0 006 0M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"
          />
        </svg>
      </div>
      <h3 class="text-xl font-semibold mb-2">Customer First</h3>
      <p>Our customers' satisfaction drives every decision we make.</p>
    </div>

    <div class="bg-white p-8 rounded shadow hover:shadow-lg transition">
      <div class="mb-4 text-indigo-600 text-5xl">
        <!-- Icon: Innovation -->
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          class="mx-auto h-12 w-12"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 10V3L4 14h7v7l9-11h-7z"
          />
        </svg>
      </div>
      <h3 class="text-xl font-semibold mb-2">Innovation</h3>
      <p>We embrace new ideas to constantly improve our services.</p>
    </div>
  </div>
</section>

<!-- Our Team -->
<section
  class="bg-gray-100 py-16 px-6 max-w-7xl mx-auto text-gray-800 text-center"
>
  <h2 class="text-3xl sm:text-4xl font-semibold mb-12">Meet Our Team</h2>
  <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-8 max-w-6xl mx-auto">
    <!-- Team Member 1 -->
    <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
      <img
        src="assets/images/team1.jpg"
        alt="Team Member 1"
        class="w-32 h-32 mx-auto rounded-full mb-4 object-cover"
      />
      <h3 class="text-xl font-semibold">Alice Johnson</h3>
      <p class="text-indigo-600 mb-2">Founder & CEO</p>
      <p class="text-gray-600 text-sm">
        Passionate about creating seamless experiences for customers.
      </p>
    </div>
    <!-- Team Member 2 -->
    <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
      <img
        src="assets/images/team2.jpg"
        alt="Team Member 2"
        class="w-32 h-32 mx-auto rounded-full mb-4 object-cover"
      />
      <h3 class="text-xl font-semibold">Michael Smith</h3>
      <p class="text-indigo-600 mb-2">Head of Operations</p>
      <p class="text-gray-600 text-sm">
        Ensuring smooth day-to-day operations and customer satisfaction.
      </p>
    </div>
    <!-- Team Member 3 -->
    <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
      <img
        src="assets/images/team3.jpg"
        alt="Team Member 3"
        class="w-32 h-32 mx-auto rounded-full mb-4 object-cover"
      />
      <h3 class="text-xl font-semibold">Sara Lee</h3>
      <p class="text-indigo-600 mb-2">Lead Developer</p>
      <p class="text-gray-600 text-sm">
        Driving innovation through cutting-edge technology.
      </p>
    </div>
    <!-- Team Member 4 -->
    <div class="bg-white p-6 rounded shadow hover:shadow-lg transition">
      <img
        src="assets/images/team4.jpg"
        alt="Team Member 4"
        class="w-32 h-32 mx-auto rounded-full mb-4 object-cover"
      />
      <h3 class="text-xl font-semibold">David Kim</h3>
      <p class="text-indigo-600 mb-2">Marketing Lead</p>
      <p class="text-gray-600 text-sm">
        Connecting DriveMint with customers worldwide.
      </p>
    </div>
  </div>
</section>

<!-- Call To Action -->
<section class="py-16 px-6 max-w-7xl mx-auto text-center">
  <h2 class="text-3xl sm:text-4xl font-semibold mb-6 text-gray-800">
    Ready to Drive with Us?
  </h2>
  <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto mb-8">
    Join thousands of happy customers enjoying DriveMint’s seamless car rental experience.
  </p>
  <a
    href="register.php"
    class="bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-8 rounded-full font-semibold transition"
    >Get Started</a
  >
</section>

<?php include 'includes/footer.php'; ?>
