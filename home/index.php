<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>



<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-emerald-50 to-white">
  <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-12 px-6 pt-32 pb-20 min-h-screen">
    
    <!-- Left Text Content -->
    <div class="max-w-xl animate-fade-in-up text-center md:text-left">
      <h1 class="text-5xl font-extrabold text-green-600 mb-6 leading-tight">
        Smart Diet Planner with Personalized Nutrition
      </h1>
      <p class="text-lg text-gray-700 mb-6 leading-relaxed">
        Maintaining a balanced diet tailored to individual health conditions and lifestyle is a growing challenge.  
        Our platform generates **personalized diet plans** using BMI, activity level, and food preferences.
      </p>
      <p class="text-lg text-gray-700 mb-8 leading-relaxed">
        Get support whether you want to <span class="font-semibold text-green-600">lose weight</span>, 
        <span class="font-semibold text-green-600">gain muscle</span>, or 
        <span class="font-semibold text-green-600">stay healthy</span>.  
        Track your progress, consult dietitians, and follow meal plans designed for <strong>you</strong>.
      </p>

      <!-- Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
        <a href="register.php"
           class="bg-emerald-500 text-white px-8 py-4 rounded-full font-semibold hover:bg-emerald-600 transition transform hover:scale-110">
          Get Started
        </a>
        <a href="#about"
           class="border-2 border-emerald-500 text-emerald-600 px-8 py-4 rounded-full font-semibold hover:bg-emerald-50 transition transform hover:scale-105">
          Learn More
        </a>
      </div>
      
      <!-- Features Row -->
      <div class="grid grid-cols-2 gap-6 mt-12">
        <div class="flex items-center gap-3">
          <span class="text-green-500 text-3xl">⚖️</span>
          <p class="text-gray-700 font-medium">BMI Calculation</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-green-500 text-3xl">🥗</span>
          <p class="text-gray-700 font-medium">Personalized Meal Plans</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-green-500 text-3xl">📊</span>
          <p class="text-gray-700 font-medium">Progress Tracking</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-green-500 text-3xl">👩‍⚕️</span>
          <p class="text-gray-700 font-medium">Dietitian Support</p>
        </div>
      </div>
    </div>
    
    <!-- Right Image -->
    <div class="w-full md:w-1/2 animate-fade-in flex justify-center">
      <img src="images/home.png"
           alt="healthy food"
           class="w-[28rem] h-[28rem] object-cover rounded-full shadow-2xl border-4 border-emerald-400 animate-spin-slow">
    </div>
    
  </div>
</section>

<!-- Why Choose Us -->
<section id="features" class="bg-gray-50 py-20 flex items-center">
  <div class="container mx-auto text-center">
    <h2 class="text-4xl font-bold text-green-600 mb-6">Why Choose Us?</h2>
    <p class="text-gray-600 mb-14 text-lg max-w-2xl mx-auto">
      MyDiet is more than a meal planner—it’s your all-in-one health partner. 
      We combine smart technology, personalized nutrition, and community support 
      to make healthy living simple and sustainable.
    </p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
      
      <!-- Card -->
      <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-md hover:shadow-2xl hover:scale-105 transition transform duration-300">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-tr from-green-100 to-green-300 flex items-center justify-center shadow-md">
          <img src="images/plan.png" alt="Tailored Plans" class="w-14 h-14">
        </div>
        <h3 class="text-xl font-semibold text-green-600 mb-3">Personalized Plans</h3>
        <p class="text-gray-600">AI-powered diet plans customized to your health goals, lifestyle, and preferences.</p>
      </div>

      <!-- Card -->
      <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-md hover:shadow-2xl hover:scale-105 transition transform duration-300">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-tr from-yellow-100 to-yellow-300 flex items-center justify-center shadow-md">
          <img src="images/recipe.png" alt="Smart Recipes" class="w-14 h-14">
        </div>
        <h3 class="text-xl font-semibold text-green-600 mb-3">Healthy Recipes</h3>
        <p class="text-gray-600">Nutritious, easy-to-cook meals designed to match your taste and culture.</p>
      </div>

      <!-- Card -->
      <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-md hover:shadow-2xl hover:scale-105 transition transform duration-300">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-tr from-blue-100 to-blue-300 flex items-center justify-center shadow-md">
          <img src="images/grocery.png" alt="Easy Groceries" class="w-14 h-14">
        </div>
        <h3 class="text-xl font-semibold text-green-600 mb-3">Smart Grocery Lists</h3>
        <p class="text-gray-600">Auto-generated shopping lists that save time, money, and reduce food waste.</p>
      </div>

      <!-- Card -->
      <div class="bg-white/90 backdrop-blur-lg p-8 rounded-3xl shadow-md hover:shadow-2xl hover:scale-105 transition transform duration-300">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-tr from-pink-100 to-pink-300 flex items-center justify-center shadow-md">
          <img src="images/community.png" alt="Community" class="w-14 h-14">
        </div>
        <h3 class="text-xl font-semibold text-green-600 mb-3">Motivation & Support</h3>
        <p class="text-gray-600">Stay on track with reminders, progress tracking, and a supportive community.</p>
      </div>

    </div>

    <p class="text-gray-700 mt-12 max-w-3xl mx-auto text-lg leading-relaxed">
      We don’t just give you a meal plan—we provide the tools, insights, 
      and encouragement you need to make healthier choices every day.
    </p>
  </div>
</section>



<!-- Platform Benefits -->
<section id="platform" class="bg-green-50 py-20">
  <div class="container mx-auto text-center px-6">
    <!-- Heading -->
    <h2 class="text-4xl font-extrabold text-gray-800 mb-6">Why Choose <span class="text-green-600">MyDiet</span>?</h2>
    <p class="text-gray-600 mb-16 text-lg max-w-2xl mx-auto">
      More than a diet planner—MyDiet is your smart companion for healthier living.
    </p>

    <!-- Cards -->
    <div class="grid gap-10 md:grid-cols-3">
      
      <!-- Card 1 -->
      <div class="group bg-white rounded-2xl shadow-xl p-10 hover:bg-green-600 transition duration-300">
        <div class="w-20 h-20 flex items-center justify-center rounded-full bg-green-100 mx-auto mb-6 group-hover:bg-white">
          <img src="images/ai.png" alt="AI Powered" class="w-12 h-12">
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-white">Smart AI Guidance</h3>
        <p class="text-gray-600 group-hover:text-green-100">
          Get diet plans powered by AI that adapt to your goals, progress, and lifestyle changes.
        </p>
      </div>

      <!-- Card 2 -->
      <div class="group bg-white rounded-2xl shadow-xl p-10 hover:bg-green-600 transition duration-300">
        <div class="w-20 h-20 flex items-center justify-center rounded-full bg-green-100 mx-auto mb-6 group-hover:bg-white">
          <img src="images/local.png" alt="Local Recipes" class="w-12 h-12">
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-white">Personalized Meals</h3>
        <p class="text-gray-600 group-hover:text-green-100">
          Enjoy meal plans tailored to your taste, culture, and nutrition needs with local recipes.
        </p>
      </div>

      <!-- Card 3 -->
      <div class="group bg-white rounded-2xl shadow-xl p-10 hover:bg-green-600 transition duration-300">
        <div class="w-20 h-20 flex items-center justify-center rounded-full bg-green-100 mx-auto mb-6 group-hover:bg-white">
          <img src="images/allinone.png" alt="All in One" class="w-12 h-12">
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-white">Complete Wellness</h3>
        <p class="text-gray-600 group-hover:text-green-100">
          Track your meals, generate shopping lists, and monitor progress—all in one platform.
        </p>
      </div>
    </div>

    <!-- Closing line -->
    <p class="text-gray-700 mt-16 max-w-3xl mx-auto text-lg leading-relaxed">
      With <span class="font-semibold text-green-600">MyDiet</span>, staying healthy is easier, smarter, 
      and completely personalized to you.
    </p>
  </div>
</section>




<!-- How It Works -->
<section class="py-20 bg-gray-50">
  <div class="container mx-auto text-center px-6">
    <h2 class="text-4xl font-bold text-green-600 mb-6">How It Works</h2>
    <p class="text-gray-600 mb-14 text-lg">Start your healthy journey in just 4 simple steps with <span class="font-semibold text-green-600">MyDiet</span>.</p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-12">
      <!-- Step 1 -->
      <div class="relative bg-white p-8 rounded-2xl shadow-md hover:shadow-2xl transition transform hover:-translate-y-2">
        <!-- Badge -->
        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 flex items-center justify-center rounded-full bg-green-600 text-white font-bold text-lg shadow-lg">
          1
        </div>
        <h3 class="text-xl font-bold text-green-600 mb-2 mt-6">Create Your Account</h3>
        <p class="text-gray-600">Sign up for free and join our health-focused community.</p>
      </div>

      <!-- Step 2 -->
      <div class="relative bg-white p-8 rounded-2xl shadow-md hover:shadow-2xl transition transform hover:-translate-y-2">
        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 flex items-center justify-center rounded-full bg-green-600 text-white font-bold text-lg shadow-lg">
          2
        </div>
        <h3 class="text-xl font-bold text-green-600 mb-2 mt-6">Share Your Details</h3>
        <p class="text-gray-600">Enter your age, weight, goals, and food preferences.</p>
      </div>

      <!-- Step 3 -->
      <div class="relative bg-white p-8 rounded-2xl shadow-md hover:shadow-2xl transition transform hover:-translate-y-2">
        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 flex items-center justify-center rounded-full bg-green-600 text-white font-bold text-lg shadow-lg">
          3
        </div>
        <h3 class="text-xl font-bold text-green-600 mb-2 mt-6">Get Your Diet Plan</h3>
        <p class="text-gray-600">Receive a personalized meal plan with grocery suggestions.</p>
      </div>

      <!-- Step 4 -->
      <div class="relative bg-white p-8 rounded-2xl shadow-md hover:shadow-2xl transition transform hover:-translate-y-2">
        <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 flex items-center justify-center rounded-full bg-green-600 text-white font-bold text-lg shadow-lg">
          4
        </div>
        <h3 class="text-xl font-bold text-green-600 mb-2 mt-6">Track & Improve</h3>
        <p class="text-gray-600">Monitor your progress and stay motivated with reminders.</p>
      </div>
    </div>
  </div>
</section>



<!-- About Us -->
<section id="about" class="container mx-auto flex flex-col md:flex-row items-center gap-12 px-6 py-20">
  <!-- Image -->
  <div class="w-full md:w-1/2 animate-fade-in">
    <img src="images/about_img.png" 
         alt="Our Team" 
         class="max-w-md h-auto object-cover rounded-2xl shadow-xl hover:scale-105 transition mx-auto">
  </div>

  <!-- Text Content -->
  <div class="w-full md:w-1/2 ">
    <h2 class="text-4xl font-bold text-green-600 mb-6">About Us</h2>
    <p class="text-gray-700 mb-8 text-lg leading-relaxed">
      We’re a team of health enthusiasts and developers passionate about making nutrition simple and accessible. 
      Our mission is to empower people with smart, personalized tools that promote sustainable health improvements. 
      <span class="font-semibold text-green-600">MyDiet</span> isn’t just a tool—it’s your daily companion for better living.
    </p>

    <!-- Developer Info Card -->
<div class="bg-white/90 backdrop-blur-lg shadow-lg rounded-2xl p-6 mb-8 hover:shadow-2xl transition transform hover:-translate-y-1">
  <h3 class="text-2xl font-bold text-green-600 mb-4">Developer Info</h3>
  <ul class="text-gray-700 space-y-2 text-lg">
    <li><span class="font-semibold">Name:</span> Megha M S</li>
    <li><span class="font-semibold">Roll No:</span> CHN24MCA-2036</li>
    <li><span class="font-semibold">About:</span> MCA Final Year Student, College of Engineering Chengannur</li>
    <li class="flex items-center space-x-6">
      <!-- GitHub -->
      <a href="https://github.com/msmegha36" target="_blank" class="flex items-center space-x-2 text-gray-800 hover:text-black">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 .5C5.7.5.5 5.7.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.3.8-.6v-2.1c-3.2.7-3.9-1.4-3.9-1.4-.6-1.6-1.5-2-1.5-2-1.2-.9.1-.9.1-.9 1.3.1 2 .9 2 .9 1.2 2 3.1 1.5 3.9 1.1.1-.9.5-1.5.9-1.9-2.6-.3-5.4-1.3-5.4-6 0-1.3.5-2.4 1.2-3.2-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.3 11.3 0 0 1 6 0c2.3-1.5 3.3-1.2 3.3-1.2.6 1.6.2 2.8.1 3.1.7.8 1.2 1.9 1.2 3.2 0 4.7-2.8 5.7-5.4 6 .5.4 1 .8 1 2v3c0 .3.2.7.8.6A10.9 10.9 0 0 0 23.5 12C23.5 5.7 18.3.5 12 .5z"/>
        </svg>
        <span>GitHub</span>
      </a>
      <!-- LinkedIn -->
      <a href="https://www.linkedin.com/in/megha-m-s-a6056127b/" target="_blank" class="flex items-center space-x-2 text-blue-700 hover:text-blue-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M20.45 20.45h-3.55v-5.6c0-1.34 0-3.05-1.86-3.05-1.87 0-2.16 1.46-2.16 2.96v5.7H9.34V9h3.4v1.56h.05c.47-.88 1.62-1.81 3.34-1.81 3.57 0 4.23 2.35 4.23 5.41v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.11 20.45H3.57V9h3.54v11.45zM22.23 0H1.77C.8 0 0 .77 0 1.72v20.55C0 23.23.8 24 1.77 24h20.46c.97 0 1.77-.77 1.77-1.73V1.72C24 .77 23.2 0 22.23 0z"/>
        </svg>
        <span>LinkedIn</span>
      </a>
    </li>
    <li>
      <span class="font-semibold">Project Repo:</span>
      <a href="https://github.com/msmegha36/Smart-Diet-Planner" target="_blank" class="text-green-600 hover:underline">
        github.com/msmegha36/Smart-Diet-Planner
      </a>
    </li>
  </ul>
</div>



    <!-- CTA 
    <a href="#team" 
       class="bg-green-500 text-white px-8 py-4 rounded-full font-bold hover:bg-green-600 transition transform hover:scale-110">
      Meet The Team -->
    </a>
  </div>
</section>


<?php include 'components/footer.php'; ?>
