<?php
session_start();
require 'includes/config.php';

$name = $email = $subject = $message = "";
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $name = trim(pg_escape_string($conn, $_POST['name']));
    $email = trim(pg_escape_string($conn, $_POST['email']));
    $subject = trim(pg_escape_string($conn, $_POST['subject']));
    $message = trim(pg_escape_string($conn, $_POST['message']));

    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields (Name, Email, Message).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Prepare insert query
        $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";
        $result = pg_query($conn, $query);

        if ($result) {
            $success = "Thank you for contacting us. We will get back to you shortly.";
            // Clear inputs after success
            $name = $email = $subject = $message = "";
        } else {
            $error = "An error occurred while submitting your message. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contact Us - DriveMint</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<?php include 'includes/header.php'; ?>

<div class="max-w-3xl mx-auto p-6 bg-white shadow rounded mt-12">
  <h1 class="text-3xl font-semibold mb-6 text-gray-800">Contact Us</h1>

  <?php if ($success): ?>
    <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
  <?php endif; ?>
  
  <?php if ($error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST" action="" class="space-y-6">
    <div>
      <label for="name" class="block mb-2 font-medium text-gray-700">Name<span class="text-red-500">*</span></label>
      <input
        type="text"
        id="name"
        name="name"
        value="<?php echo htmlspecialchars($name); ?>"
        required
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div>
      <label for="email" class="block mb-2 font-medium text-gray-700">Email<span class="text-red-500">*</span></label>
      <input
        type="email"
        id="email"
        name="email"
        value="<?php echo htmlspecialchars($email); ?>"
        required
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div>
      <label for="subject" class="block mb-2 font-medium text-gray-700">Subject</label>
      <input
        type="text"
        id="subject"
        name="subject"
        value="<?php echo htmlspecialchars($subject); ?>"
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <div>
      <label for="message" class="block mb-2 font-medium text-gray-700">Message<span class="text-red-500">*</span></label>
      <textarea
        id="message"
        name="message"
        rows="5"
        required
        class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      ><?php echo htmlspecialchars($message); ?></textarea>
    </div>

    <button
      type="submit"
      class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded font-semibold transition"
    >
      Send Message
    </button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
