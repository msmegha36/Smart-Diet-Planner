<!-- Footer -->
<footer class="bg-emerald-700 text-white py-12 mt-16">
  <div class="container mx-auto px-6">
    <div class="grid md:grid-cols-3 gap-10 text-center md:text-left">
      
      <!-- Brand -->
      <div>
        <h3 class="text-2xl font-bold mb-4">🍏 Smart Diet Planner</h3>
        <p class="text-emerald-100 text-sm leading-relaxed">
          Smart Diet Planner with Personalized Nutrition.  
          A web-based platform that tailors diet plans using BMI, health data, 
          and preferences to promote healthier living.
        </p>
      </div>

      <!-- Quick Links -->
      <div>
        <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
        <ul class="space-y-2 text-sm">
          <li><a href="#" class="hover:underline">Home</a></li>
          <li><a href="#features" class="hover:underline">Features</a></li>
          <li><a href="#platform" class="hover:underline">Platform</a></li>
          <li><a href="#about" class="hover:underline">About Us</a></li>
          
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <h3 class="text-xl font-semibold mb-4">Contact</h3>
        <p class="text-sm">📧 Smart Diet Planner.support@gmail.com</p>
        <p class="text-sm">📞 +91 98765 43210</p>
        <p class="text-sm mt-2">📍 College of Engineering Chengannur, Kerala</p>
        <div class="flex justify-center md:justify-start space-x-6 mt-4 text-2xl">
          <a href="#" class="hover:text-emerald-200"><i class="fab fa-facebook"></i></a>
          <a href="#" class="hover:text-emerald-200"><i class="fab fa-twitter"></i></a>
          <a href="#" class="hover:text-emerald-200"><i class="fab fa-github"></i></a>
        </div>
      </div>
    </div>

    <!-- Bottom -->
    <div class="border-t border-emerald-500 my-8"></div>
    <div class="text-center text-sm">
      <p>&copy; 2025 <span class="font-bold">MyDiet</span> | Developed as part of MCA  Project</p>
      <p class="text-emerald-200 mt-1"> Developer: Megha M S (CHN24MCA-2036)</p>
    </div>
  </div>
</footer>


</body>
<script>
  const menuBtn = document.getElementById("menu-btn");
  const mobileMenu = document.getElementById("mobile-menu");

  menuBtn.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
  });
</script>
</html>