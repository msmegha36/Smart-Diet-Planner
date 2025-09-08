<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>



<!-- Hero Section -->
<section class="pt-32 pb-16 text-center bg-emerald-100">
  <h1 class="text-4xl md:text-5xl font-extrabold text-emerald-700">Personalized Diet Plans</h1>
  <p class="mt-4 text-gray-700 max-w-2xl mx-auto text-lg">
    Discover science-backed diet strategies tailored for your fitness, health, and lifestyle goals.
  </p>
</section>

<!-- Importance of Diet -->
<section class="max-w-6xl mx-auto py-12 px-6">
  <div class="bg-white shadow-lg rounded-2xl p-8 text-center">
    <h2 class="text-2xl font-bold text-emerald-600 mb-4">🌱 Why Diet is Important?</h2>
    <p class="text-gray-700 leading-relaxed">
      A well-structured diet not only helps in weight management but also ensures proper nutrition, 
      boosts immunity, improves mental focus, and prevents lifestyle diseases. 
      The right diet is the foundation of a healthy life.
    </p>
  </div>
</section>

<!-- Diet Plan Sections -->
<section class="max-w-6xl mx-auto py-12 px-6 space-y-16">

  <!-- Plan 1 -->
  <div class="grid md:grid-cols-2 gap-10 items-center">
    <img src="images/saladbowl.jpg" 
         alt="Calorie Deficit" 
         class="rounded-2xl shadow-lg">
    <div>
      <h3 class="text-2xl font-bold text-emerald-700">Calorie Deficit Diet</h3>
      <p class="text-gray-700 mt-4">
        Ideal for weight loss, a calorie deficit diet focuses on consuming fewer calories than your body burns. 
        Balanced with proteins, healthy fats, and complex carbs, this diet ensures fat loss without losing energy.
      </p>
    </div>
  </div>

  <!-- Plan 2 -->
  <div class="grid md:grid-cols-2 gap-10 items-center md:flex-row-reverse">
    <div class="order-2 md:order-1">
      <h3 class="text-2xl font-bold text-emerald-700">High Protein Diet</h3>
      <p class="text-gray-700 mt-4">
        Perfect for muscle building and fitness enthusiasts. A protein-rich diet aids muscle repair, 
        boosts metabolism, and keeps you full longer. Includes lean meat, eggs, legumes, and dairy.
      </p>
    </div>
    <img src="images/highprotein.jpg" 
         alt="High Protein" 
         class="rounded-2xl shadow-lg order-1 md:order-2">
  </div>

  <!-- Plan 3 -->
  <div class="grid md:grid-cols-2 gap-10 items-center">
    <img src="images/fruits.jpg" 
         alt="Plant Based Diet" 
         class="rounded-2xl shadow-lg">
    <div>
      <h3 class="text-2xl font-bold text-emerald-700">Plant-Based Diet</h3>
      <p class="text-gray-700 mt-4">
        Focusing on whole grains, vegetables, fruits, and plant proteins, this diet promotes longevity 
        and heart health. It is nutrient-dense, eco-friendly, and ideal for those who prefer vegetarian or vegan lifestyles.
      </p>
    </div>
  </div>

  <!-- Plan 4 -->
  <div class="grid md:grid-cols-2 gap-10 items-center md:flex-row-reverse">
    <div class="order-2 md:order-1">
      <h3 class="text-2xl font-bold text-emerald-700">Mediterranean Diet</h3>
      <p class="text-gray-700 mt-4">
        Rich in olive oil, nuts, fish, vegetables, and whole grains, 
        this diet lowers the risk of heart disease and improves overall well-being. 
        Known as one of the healthiest diets in the world.
      </p>
    </div>
    <img src="images/greekbowl.jpg" 
         alt="Mediterranean Diet" 
         class="rounded-2xl shadow-lg order-1 md:order-2">
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

