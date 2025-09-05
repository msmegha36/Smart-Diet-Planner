<?php 
include(__DIR__ . '/../config/db_conn.php'); 
include 'components/head.php'; 
include 'components/navbar.php'; 
?>

<style>
/* Custom Animations */
@keyframes spin-slow {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.animate-spin-slow { animation: spin-slow 25s linear infinite; }

@keyframes fade-in {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 1s ease-in-out; }
.animate-fade-in-up { animation: fade-in 1.2s ease-in-out; }
</style>

<!-- Nutritionists Page -->
<section id="nutritionists" class="bg-gray-50 py-20 mt-15">
  <div class="container mx-auto px-6 text-center">
    <h2 class="text-4xl font-bold text-green-600 mb-6">Our Nutrition Experts</h2>
    <p class="text-gray-600 mb-12 max-w-2xl mx-auto">
      Connect with certified freelance nutritionists and dietitians who are here 
      to guide you on your personalized health journey.
    </p>

    <!-- Cards Grid -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-10">
      <?php
      $sql = "SELECT * FROM nutritionists WHERE status='approved' ORDER BY created_at DESC";
      $result = mysqli_query($connection, $sql);

      if ($result && mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
              $name = htmlspecialchars($row['name']);
              $specialization = htmlspecialchars($row['specialization']);
              $experience = (int)$row['experience'];
              $description = htmlspecialchars($row['description']);
              $image = !empty($row['image']) ? $row['image'] : 'images/default-nutritionist.jpg';
              $email = htmlspecialchars($row['email']);

             echo "
  <div class='bg-white shadow-lg rounded-2xl overflow-hidden hover:shadow-2xl transition'>
    <div class='w-full h-64 overflow-hidden'>
      <img src='../nutrionist/" . $row['image'] . "' 
           alt='" . htmlspecialchars($row['name'], ENT_QUOTES) . "' 
           class='w-full h-full object-cover object-top'>
    </div>
    <div class='p-6'>
      <h3 class='text-xl font-bold text-green-600'>" . htmlspecialchars($row['name']) . "</h3>
      <p class='text-gray-500 text-sm'>" . htmlspecialchars($row['specialization']) . " | " . intval($row['experience']) . "+ years</p>
      <p class='text-gray-600 mt-3 text-sm leading-relaxed'>" . htmlspecialchars($row['description']) . "</p>
      <a href='contact.php?nutritionist_id=" . $row['id'] . "' 
         class='mt-4 inline-block bg-green-600 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-700 transition'>
        Contact
      </a>
    </div>
  </div>
";

          }
      } else {
          echo "<p class='col-span-3 text-gray-500 text-lg'>No nutritionists available at the moment.</p>";
      }
      ?>
    </div>
  </div>
</section>

<?php include 'components/footer.php'; ?>

<script>
const menuBtn = document.getElementById("menu-btn");
const mobileMenu = document.getElementById("mobile-menu");
menuBtn.addEventListener("click", () => {
  mobileMenu.classList.toggle("hidden");
});
</script>
