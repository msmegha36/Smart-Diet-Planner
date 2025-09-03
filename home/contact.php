<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>



<!-- MAIN CONTENT -->
<main class="max-w-6xl mx-auto pt-32 px-6 space-y-12">

  <!-- Nutritionist Profile -->
  <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col md:flex-row items-center md:items-start md:space-x-10">
    
    <!-- Left Image -->
    <img id="nutri-img" src="images/nutritionist1.jpg" 
         alt="Nutritionist" 
         class="w-56 h-56 object-cover rounded-full shadow-md">

    <!-- Right Details -->
    <div class="mt-6 md:mt-0 text-center md:text-left">
      <h2 id="nutri-name" class="text-3xl font-bold text-green-600">Nutritionist Name</h2>
      <p id="nutri-qual" class="text-gray-500 text-lg mt-2">Qualification & Experience</p>
      <p id="nutri-bio" class="text-gray-600 mt-4 leading-relaxed max-w-xl">
        Short bio about specialization, expertise, and focus areas.
      </p>
    </div>
  </div>

  <!-- Appointment Form -->
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Book an Appointment</h3>
    
    <form action="#" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Name -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Your Name</label>
        <input type="text" name="name" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Email -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Email</label>
        <input type="email" name="email" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Phone -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Phone</label>
        <input type="tel" name="phone" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Date -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Preferred Date</label>
        <input type="date" name="date" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Time -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Preferred Time</label>
        <input type="time" name="time" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Notes -->
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Additional Notes</label>
        <textarea name="notes" rows="4" 
                  class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500"></textarea>
      </div>

      <!-- Submit -->
      <div class="md:col-span-2">
        <button type="submit" 
                class="w-full bg-green-600 text-white font-semibold py-3 rounded-lg hover:bg-green-700 transition">
          Confirm Appointment
        </button>
      </div>
    </form>
  </div>

</main>

<?php include 'components/footer.php'; ?>
<!-- JS -->
<script>
  const nutritionists = {
    "anjali-nair": {
      name: "Dr. Anjali Nair",
      qual: "M.Sc. Clinical Nutrition | 8+ years",
      img: "images/nutritionist1.jpg",
      bio: "Specializes in weight management, diabetes care, and balanced diet planning."
    },
    "rahul-menon": {
      name: "Mr. Rahul Menon",
      qual: "Certified Sports Nutritionist | 5+ years",
      img: "images/nutritionist2.jpg",
      bio: "Expert in sports diet, fitness nutrition, and high-performance meal planning."
    },
    "megha-ms": {
      name: "Megha M S",
      qual: "Freelance Dietitian | MCA Final Year",
      img: "images/nutritionist3.jpg",
      bio: "Passionate about creating sustainable meal plans and personalized diet solutions."
    }
  };

  const params = new URLSearchParams(window.location.search);
  const nutriKey = params.get("name");
  if (nutriKey && nutritionists[nutriKey]) {
    const data = nutritionists[nutriKey];
    document.getElementById("nutri-name").textContent = data.name;
    document.getElementById("nutri-qual").textContent = data.qual;
    document.getElementById("nutri-img").src = data.img;
    document.getElementById("nutri-bio").textContent = data.bio;
  }

  const menuBtn = document.getElementById("menu-btn");
  const mobileMenu = document.getElementById("mobile-menu");
  menuBtn.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
  });
</script>


