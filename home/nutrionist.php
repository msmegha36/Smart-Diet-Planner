<?php include 'components/head.php'; ?>


  <style>
    /* Custom Animations */
   @keyframes spin-slow {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.animate-spin-slow {
  animation: spin-slow 25s linear infinite;
}

    @keyframes fade-in {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 1s ease-in-out; }
    .animate-fade-in-up { animation: fade-in 1.2s ease-in-out; }

   
  </style>

<?php include 'components/navbar.php'; ?>






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
      
     <!-- Card 1 -->
<div class="bg-white shadow-lg rounded-2xl overflow-hidden hover:shadow-2xl transition">
  <div class="w-full h-64 overflow-hidden">
    <img src="images/nutritionist1.jpg" alt="Nutritionist 1" class="w-full h-full object-cover object-top">
  </div>
  <div class="p-6">
    <h3 class="text-xl font-bold text-green-600">Dr. Anjali Nair</h3>
    <p class="text-gray-500 text-sm">M.Sc. Clinical Nutrition | 8+ years</p>
    <p class="text-gray-600 mt-3 text-sm leading-relaxed">
      Specializes in weight management, diabetes care, and balanced diet planning.
    </p>
    <a href="contact.php?name=anjali-nair" 
       class="mt-4 inline-block bg-green-600 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-700 transition">
      Contact
    </a>
  </div>
</div>

<!-- Card 2 -->
<div class="bg-white shadow-lg rounded-2xl overflow-hidden hover:shadow-2xl transition">
  <div class="w-full h-64 overflow-hidden">
    <img src="images/nutritionist2.jpg" alt="Nutritionist 2" class="w-full h-full object-cover object-top">
  </div>
  <div class="p-6">
    <h3 class="text-xl font-bold text-green-600">Mr. Rahul Menon</h3>
    <p class="text-gray-500 text-sm">Certified Sports Nutritionist | 5+ years</p>
    <p class="text-gray-600 mt-3 text-sm leading-relaxed">
      Expert in sports diet, fitness nutrition, and high-performance meal planning.
    </p>
    <a href="contact.php?name=rahul-menon" 
       class="mt-4 inline-block bg-green-600 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-700 transition">
      Contact
    </a>
  </div>
</div>

<!-- Card 3 -->
<div class="bg-white shadow-lg rounded-2xl overflow-hidden hover:shadow-2xl transition">
  <div class="w-full h-64 overflow-hidden">
    <img src="images/nutritionist3.jpg" alt="Nutritionist 3" class="w-full h-full object-cover object-top">
  </div>
  <div class="p-6">
    <h3 class="text-xl font-bold text-green-600">Megha M S</h3>
    <p class="text-gray-500 text-sm">Freelance Dietitian | MCA Final Year</p>
    <p class="text-gray-600 mt-3 text-sm leading-relaxed">
      Passionate about creating sustainable meal plans and personalized diet solutions.
    </p>
    <a href="contact.php?name=megha-ms" 
       class="mt-4 inline-block bg-green-600 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-700 transition">
      Contact
    </a>
  </div>
</div>

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
