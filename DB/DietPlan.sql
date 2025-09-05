-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 05, 2025 at 09:46 PM
-- Server version: 8.0.43
-- PHP Version: 8.1.2-1ubuntu2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `DietPlan`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','moderator') DEFAULT 'superadmin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Main Admin', 'admin@example.com', '0192023a7bbd73250516f069df18b500', 'superadmin', '2025-09-05 15:54:51');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `nutritionist_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `notes` text,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `nutritionist_id`, `name`, `email`, `phone`, `appointment_date`, `appointment_time`, `notes`, `status`, `created_at`) VALUES
(1, 1, 3, 'Murali', 'akdmuralimk@gmail.com', '+91 8590179085', '2025-09-14', '11:04:00', 'gergeg', 'confirmed', '2025-09-05 05:45:08'),
(3, 1, 3, 'Murali', 'akdmuralimk@gmail.com', '1234567890', '2025-09-06', '11:51:00', 'appoinment', 'pending', '2025-09-05 06:21:13');

-- --------------------------------------------------------

--
-- Table structure for table `diet_plans`
--

CREATE TABLE `diet_plans` (
  `id` int NOT NULL,
  `goal` varchar(50) NOT NULL,
  `dietary` varchar(20) NOT NULL,
  `activity` varchar(20) NOT NULL,
  `meal_type` varchar(30) NOT NULL,
  `plan_text` text NOT NULL,
  `protein` int DEFAULT '0',
  `carbs` int DEFAULT '0',
  `fat` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `diet_plans`
--

INSERT INTO `diet_plans` (`id`, `goal`, `dietary`, `activity`, `meal_type`, `plan_text`, `protein`, `carbs`, `fat`) VALUES
(1, 'weight_loss', 'veg', 'sedentary', '3_meals', 'Breakfast: Oats + fruits\nLunch: Brown rice + dal + salad\nDinner: Vegetable soup + multigrain roti', 40, 120, 20),
(2, 'muscle_build', 'nonveg', 'active', '5_small', 'Meal 1: Eggs + oats\nMeal 2: Chicken + rice\nMeal 3: Protein shake\nMeal 4: Fish + quinoa\nMeal 5: Nuts + yogurt', 45, 110, 25),
(3, 'balanced', 'vegan', 'moderate', '3_meals', 'Breakfast: Smoothie (banana, almond milk)\nLunch: Lentils + brown rice + salad\nDinner: Grilled tofu + veggies + quinoa', 50, 100, 20),
(4, 'weight_loss', 'veg', 'sedentary', '3_meals', 'Breakfast: Oats with fruits\nLunch: Brown rice + dal + salad\nDinner: Vegetable soup + multigrain roti', 120, 180, 50),
(5, 'weight_loss', 'veg', 'light', '5_small', 'Meal 1: Sprouts\nMeal 2: Veg sandwich\nMeal 3: Soup\nMeal 4: Salad\nMeal 5: Roti + sabzi', 100, 150, 45),
(6, 'weight_loss', 'veg', 'moderate', 'intermittent', '12pm: Vegetable khichdi\n7pm: Paneer salad bowl', 90, 120, 40),
(7, 'muscle_build', 'nonveg', 'active', '5_small', 'Meal 1: Eggs + oats\nMeal 2: Chicken + rice\nMeal 3: Protein shake\nMeal 4: Fish + quinoa\nMeal 5: Nuts + yogurt', 60, 150, 40),
(8, 'muscle_build', 'nonveg', 'moderate', '3_meals', 'Breakfast: Egg omelet + bread\nLunch: Chicken curry + rice\nDinner: Fish + sweet potato', 65, 160, 35),
(9, 'muscle_build', 'nonveg', 'sedentary', 'intermittent', '12pm: Chicken rice bowl\n8pm: Fish + vegetables', 70, 170, 30),
(10, 'balanced', 'vegan', 'sedentary', '3_meals', 'Breakfast: Smoothie (banana + almond milk)\nLunch: Lentils + brown rice + salad\nDinner: Tofu + veggies + quinoa', 70, 200, 50),
(11, 'balanced', 'vegan', 'light', '5_small', 'Meal 1: Fruit bowl\r\nMeal 2: Veg wrap\r\nMeal 3: Smoothie\r\nMeal 4: Quinoa salad\r\nMeal 5: Lentil soup', 81, 220, 55),
(12, 'balanced', 'vegan', 'active', 'intermittent', '12pm: Chickpea curry + rice\n8pm: Grilled tofu + sweet potato', 75, 210, 60),
(13, 'weight_gain', 'veg', 'sedentary', '3_meals', 'Breakfast: Paratha + curd\nLunch: Rice + dal + paneer\nDinner: Chapati + sabzi + milkshake', 40, 120, 20),
(14, 'weight_gain', 'veg', 'active', '5_small', 'Meal 1: Dry fruits\nMeal 2: Paneer sandwich\nMeal 3: Rice + dal\nMeal 4: Smoothie\nMeal 5: Roti + sabzi', 120, 180, 50),
(15, 'weight_gain', 'veg', 'moderate', 'intermittent', '12pm: Paneer pulao\n8pm: Dal + roti + lassi', 65, 150, 35),
(16, 'weight_gain', 'nonveg', 'light', '3_meals', 'Breakfast: Eggs + Oats\nLunch: Chicken + Rice\nDinner: Fish + Vegetables', 120, 250, 80);

-- --------------------------------------------------------

--
-- Table structure for table `nutritionists`
--

CREATE TABLE `nutritionists` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience` int DEFAULT '0',
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nutritionists`
--

INSERT INTO `nutritionists` (`id`, `name`, `email`, `password`, `phone`, `specialization`, `experience`, `description`, `image`, `status`, `created_at`) VALUES
(3, 'murali krishna', 'sreesabareesam8055@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', '+91 8590179085', 'diabetes', 2, 'hii', 'uploads/1757052261_theyyam.jpeg', 'approved', '2025-09-05 05:06:48');

-- --------------------------------------------------------

--
-- Table structure for table `progress_history`
--

CREATE TABLE `progress_history` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `progress_history`
--

INSERT INTO `progress_history` (`id`, `user_id`, `weight`, `height`, `updated_at`) VALUES
(1, 1, 65, 172, '2025-09-04 02:30:12'),
(2, 1, 66, 172, '2025-09-04 02:30:31'),
(3, 1, 66, 172, '2025-09-04 03:16:24');

-- --------------------------------------------------------

--
-- Table structure for table `reg`
--

CREATE TABLE `reg` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `height` float DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `health_issues` text,
  `dietary` varchar(50) DEFAULT NULL,
  `goal` varchar(50) DEFAULT NULL,
  `activity` varchar(50) DEFAULT NULL,
  `meal_type` varchar(50) DEFAULT NULL,
  `type` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reg`
--

INSERT INTO `reg` (`id`, `name`, `age`, `weight`, `height`, `email`, `password`, `gender`, `health_issues`, `dietary`, `goal`, `activity`, `meal_type`, `type`, `created_at`) VALUES
(1, 'Murali', 23, 66, 172, 'akdmuralimk@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', 'male', 'nothing', 'nonveg', 'weight_gain', 'light', '3_meals', 1, '2025-09-04 02:09:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_appointment_user` (`user_id`),
  ADD KEY `fk_appointment_nutritionist` (`nutritionist_id`);

--
-- Indexes for table `diet_plans`
--
ALTER TABLE `diet_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nutritionists`
--
ALTER TABLE `nutritionists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `progress_history`
--
ALTER TABLE `progress_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reg`
--
ALTER TABLE `reg`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `diet_plans`
--
ALTER TABLE `diet_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `nutritionists`
--
ALTER TABLE `nutritionists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `progress_history`
--
ALTER TABLE `progress_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reg`
--
ALTER TABLE `reg`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointment_nutritionist` FOREIGN KEY (`nutritionist_id`) REFERENCES `nutritionists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointment_user` FOREIGN KEY (`user_id`) REFERENCES `reg` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progress_history`
--
ALTER TABLE `progress_history`
  ADD CONSTRAINT `progress_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `reg` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
