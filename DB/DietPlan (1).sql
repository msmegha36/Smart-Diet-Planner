-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 08, 2025 at 01:01 PM
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
  `day_number` tinyint NOT NULL,
  `meal_time` varchar(30) NOT NULL,
  `meal_text` text NOT NULL,
  `protein` int DEFAULT '0',
  `carbs` int DEFAULT '0',
  `fat` int DEFAULT '0',
  `calories` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `diet_plans`
--

INSERT INTO `diet_plans` (`id`, `goal`, `dietary`, `activity`, `meal_type`, `day_number`, `meal_time`, `meal_text`, `protein`, `carbs`, `fat`, `calories`) VALUES
(1, 'weight_loss', 'veg', 'moderate', '3_meals', 1, 'breakfast', 'Oats with fruits', 10, 40, 5, 250),
(2, 'weight_loss', 'veg', 'moderate', '3_meals', 1, 'lunch', 'Brown rice with veg curry', 15, 50, 10, 400),
(3, 'weight_loss', 'veg', 'moderate', '3_meals', 1, 'dinner', 'Grilled paneer salad', 20, 30, 12, 350),
(4, 'weight_loss', 'veg', 'moderate', '3_meals', 2, 'breakfast', 'Poha with sprouts', 12, 35, 5, 230),
(5, 'weight_loss', 'veg', 'moderate', '3_meals', 2, 'lunch', 'Veg pulao with dal', 15, 55, 8, 420),
(6, 'weight_loss', 'veg', 'moderate', '3_meals', 2, 'dinner', 'Tofu stir fry with salad', 18, 25, 10, 320),
(7, 'weight_loss', 'veg', 'moderate', '3_meals', 3, 'breakfast', 'Smoothie with nuts and oats', 10, 30, 8, 270),
(8, 'weight_loss', 'veg', 'moderate', '3_meals', 3, 'lunch', 'Quinoa salad with veggies', 15, 45, 7, 360),
(9, 'weight_loss', 'veg', 'moderate', '3_meals', 3, 'dinner', 'Chickpea curry with brown rice', 18, 50, 10, 400),
(10, 'weight_loss', 'veg', 'moderate', '3_meals', 4, 'breakfast', 'Whole wheat toast with peanut butter', 12, 35, 10, 300),
(11, 'weight_loss', 'veg', 'moderate', '3_meals', 4, 'lunch', 'Vegetable soup with bread', 10, 40, 5, 280),
(12, 'weight_loss', 'veg', 'moderate', '3_meals', 4, 'dinner', 'Lentil curry with quinoa', 20, 45, 8, 370),
(13, 'weight_loss', 'veg', 'moderate', '3_meals', 5, 'breakfast', 'Fruit salad with yogurt', 8, 30, 5, 220),
(14, 'weight_loss', 'veg', 'moderate', '3_meals', 5, 'lunch', 'Vegetable stir fry with rice', 15, 50, 10, 400),
(15, 'weight_loss', 'veg', 'moderate', '3_meals', 5, 'dinner', 'Grilled vegetables with tofu', 18, 30, 8, 350),
(16, 'weight_loss', 'veg', 'moderate', '3_meals', 6, 'breakfast', 'Chia pudding with berries', 10, 25, 6, 230),
(17, 'weight_loss', 'veg', 'moderate', '3_meals', 6, 'lunch', 'Veg sandwich with salad', 12, 40, 8, 300),
(18, 'weight_loss', 'veg', 'moderate', '3_meals', 6, 'dinner', 'Paneer curry with brown rice', 18, 50, 12, 400),
(19, 'weight_loss', 'veg', 'moderate', '3_meals', 7, 'breakfast', 'Muesli with almond milk', 10, 35, 6, 260),
(20, 'weight_loss', 'veg', 'moderate', '3_meals', 7, 'lunch', 'Spinach and chickpea salad', 15, 45, 7, 360),
(21, 'weight_loss', 'veg', 'moderate', '3_meals', 7, 'dinner', 'Vegetable stew with quinoa', 18, 40, 10, 370),
(22, 'weight_loss', 'veg', 'sedentary', '3_meals', 1, 'breakfast', 'Oats with fruits', 10, 40, 5, 250),
(23, 'weight_loss', 'veg', 'sedentary', '3_meals', 1, 'lunch', 'Brown rice with veg curry', 15, 50, 10, 400),
(24, 'weight_loss', 'veg', 'sedentary', '3_meals', 1, 'dinner', 'Grilled paneer salad', 20, 30, 12, 350),
(25, 'weight_loss', 'veg', 'sedentary', '3_meals', 2, 'breakfast', 'Poha with sprouts', 12, 35, 5, 230),
(26, 'weight_loss', 'veg', 'sedentary', '3_meals', 2, 'lunch', 'Veg pulao with dal', 15, 55, 8, 420),
(27, 'weight_loss', 'veg', 'sedentary', '3_meals', 2, 'dinner', 'Tofu stir fry with salad', 18, 25, 10, 320),
(28, 'weight_loss', 'veg', 'sedentary', '3_meals', 3, 'breakfast', 'Smoothie with nuts and oats', 10, 30, 8, 270),
(29, 'weight_loss', 'veg', 'sedentary', '3_meals', 3, 'lunch', 'Quinoa salad with veggies', 15, 45, 7, 360),
(30, 'weight_loss', 'veg', 'sedentary', '3_meals', 3, 'dinner', 'Chickpea curry with brown rice', 18, 50, 10, 400),
(31, 'weight_loss', 'veg', 'sedentary', '3_meals', 4, 'breakfast', 'Whole wheat toast with peanut butter', 12, 35, 10, 300),
(32, 'weight_loss', 'veg', 'sedentary', '3_meals', 4, 'lunch', 'Vegetable soup with bread', 10, 40, 5, 280),
(33, 'weight_loss', 'veg', 'sedentary', '3_meals', 4, 'dinner', 'Lentil curry with quinoa', 20, 45, 8, 370),
(34, 'weight_loss', 'veg', 'sedentary', '3_meals', 5, 'breakfast', 'Fruit salad with yogurt', 8, 30, 5, 220),
(35, 'weight_loss', 'veg', 'sedentary', '3_meals', 5, 'lunch', 'Vegetable stir fry with rice', 15, 50, 10, 400),
(36, 'weight_loss', 'veg', 'sedentary', '3_meals', 5, 'dinner', 'Grilled vegetables with tofu', 18, 30, 8, 350),
(37, 'weight_loss', 'veg', 'sedentary', '3_meals', 6, 'breakfast', 'Chia pudding with berries', 10, 25, 6, 230),
(38, 'weight_loss', 'veg', 'sedentary', '3_meals', 6, 'lunch', 'Veg sandwich with salad', 12, 40, 8, 300),
(39, 'weight_loss', 'veg', 'sedentary', '3_meals', 6, 'dinner', 'Paneer curry with brown rice', 18, 50, 12, 400),
(40, 'weight_loss', 'veg', 'sedentary', '3_meals', 7, 'breakfast', 'Muesli with almond milk', 10, 35, 6, 260),
(41, 'weight_loss', 'veg', 'sedentary', '3_meals', 7, 'lunch', 'Spinach and chickpea salad', 15, 45, 7, 360),
(42, 'weight_loss', 'veg', 'sedentary', '3_meals', 7, 'dinner', 'Vegetable stew with quinoa', 18, 40, 10, 370),
(43, 'weight_loss', 'veg', 'light', '3_meals', 1, 'breakfast', 'Oats with fruits', 10, 40, 5, 250),
(44, 'weight_loss', 'veg', 'light', '3_meals', 1, 'lunch', 'Brown rice with veg curry', 15, 50, 10, 400),
(45, 'weight_loss', 'veg', 'light', '3_meals', 1, 'dinner', 'Grilled paneer salad', 20, 30, 12, 350),
(46, 'weight_loss', 'veg', 'light', '3_meals', 2, 'breakfast', 'Poha with sprouts', 12, 35, 5, 230),
(47, 'weight_loss', 'veg', 'light', '3_meals', 2, 'lunch', 'Veg pulao with dal', 15, 55, 8, 420),
(48, 'weight_loss', 'veg', 'light', '3_meals', 2, 'dinner', 'Tofu stir fry with salad', 18, 25, 10, 320),
(49, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 1, 'breakfast', 'Egg omelette with toast', 12, 35, 10, 300),
(50, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 1, 'lunch', 'Grilled chicken with rice', 25, 50, 12, 500),
(51, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 1, 'dinner', 'Grilled fish with salad', 22, 40, 10, 400),
(52, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 2, 'breakfast', 'Chicken sandwich', 20, 30, 8, 350),
(53, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 2, 'lunch', 'Fish curry with brown rice', 25, 55, 12, 500),
(54, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 2, 'dinner', 'Chicken curry with quinoa', 22, 45, 10, 420),
(55, 'weight_loss', 'vegan', 'sedentary', '3_meals', 1, 'breakfast', 'Vegan smoothie bowl', 8, 35, 5, 250),
(56, 'weight_loss', 'vegan', 'sedentary', '3_meals', 1, 'lunch', 'Quinoa salad with beans', 15, 50, 8, 400),
(57, 'weight_loss', 'vegan', 'sedentary', '3_meals', 1, 'dinner', 'Tofu stir fry with vegetables', 20, 40, 10, 400),
(58, 'weight_loss', 'vegan', 'sedentary', '3_meals', 2, 'breakfast', 'Tofu scramble with veggies', 10, 30, 6, 270),
(59, 'weight_loss', 'vegan', 'sedentary', '3_meals', 2, 'lunch', 'Vegan burger with salad', 15, 55, 10, 420),
(60, 'weight_loss', 'vegan', 'sedentary', '3_meals', 2, 'dinner', 'Vegan curry with rice', 20, 45, 10, 400),
(61, 'weight_loss', 'veg', 'sedentary', '3_meals', 1, 'breakfast', 'Oats with fruits', 10, 40, 5, 250),
(62, 'weight_loss', 'veg', 'sedentary', '3_meals', 1, 'lunch', 'Brown rice with veg curry', 15, 50, 10, 400),
(63, 'weight_loss', 'veg', 'sedentary', '3_meals', 1, 'dinner', 'Grilled paneer salad', 20, 30, 12, 350),
(64, 'weight_loss', 'veg', 'sedentary', '3_meals', 2, 'breakfast', 'Poha with sprouts', 12, 35, 5, 230),
(65, 'weight_loss', 'veg', 'sedentary', '3_meals', 2, 'lunch', 'Veg pulao with dal', 15, 55, 8, 420),
(66, 'weight_loss', 'veg', 'sedentary', '3_meals', 2, 'dinner', 'Tofu stir fry with salad', 18, 25, 10, 320),
(67, 'weight_loss', 'veg', 'sedentary', '3_meals', 3, 'breakfast', 'Smoothie with nuts and oats', 10, 30, 8, 270),
(68, 'weight_loss', 'veg', 'sedentary', '3_meals', 3, 'lunch', 'Quinoa salad with veggies', 15, 45, 7, 360),
(69, 'weight_loss', 'veg', 'sedentary', '3_meals', 3, 'dinner', 'Chickpea curry with brown rice', 18, 50, 10, 400),
(70, 'weight_loss', 'veg', 'sedentary', '3_meals', 4, 'breakfast', 'Whole wheat toast with peanut butter', 12, 35, 10, 300),
(71, 'weight_loss', 'veg', 'sedentary', '3_meals', 4, 'lunch', 'Vegetable soup with bread', 10, 40, 5, 280),
(72, 'weight_loss', 'veg', 'sedentary', '3_meals', 4, 'dinner', 'Lentil curry with quinoa', 20, 45, 8, 370),
(73, 'weight_loss', 'veg', 'sedentary', '3_meals', 5, 'breakfast', 'Fruit salad with yogurt', 8, 30, 5, 220),
(74, 'weight_loss', 'veg', 'sedentary', '3_meals', 5, 'lunch', 'Vegetable stir fry with rice', 15, 50, 10, 400),
(75, 'weight_loss', 'veg', 'sedentary', '3_meals', 5, 'dinner', 'Grilled vegetables with tofu', 18, 30, 8, 350),
(76, 'weight_loss', 'veg', 'sedentary', '3_meals', 6, 'breakfast', 'Chia pudding with berries', 10, 25, 6, 230),
(77, 'weight_loss', 'veg', 'sedentary', '3_meals', 6, 'lunch', 'Veg sandwich with salad', 12, 40, 8, 300),
(78, 'weight_loss', 'veg', 'sedentary', '3_meals', 6, 'dinner', 'Paneer curry with brown rice', 18, 50, 12, 400),
(79, 'weight_loss', 'veg', 'sedentary', '3_meals', 7, 'breakfast', 'Muesli with almond milk', 10, 35, 6, 260),
(80, 'weight_loss', 'veg', 'sedentary', '3_meals', 7, 'lunch', 'Spinach and chickpea salad', 15, 45, 7, 360),
(81, 'weight_loss', 'veg', 'sedentary', '3_meals', 7, 'dinner', 'Vegetable stew with quinoa', 18, 40, 10, 370),
(82, 'weight_gain', 'veg', 'sedentary', '3_meals', 1, 'breakfast', 'Oatmeal with nuts and honey', 15, 50, 10, 450),
(83, 'weight_gain', 'veg', 'sedentary', '3_meals', 1, 'lunch', 'Paneer butter masala with rice', 25, 60, 15, 600),
(84, 'weight_gain', 'veg', 'sedentary', '3_meals', 1, 'dinner', 'Vegetable biryani with raita', 20, 55, 12, 550),
(85, 'muscle_build', 'veg', 'sedentary', '3_meals', 1, 'breakfast', 'Protein smoothie with soy milk', 20, 35, 8, 400),
(86, 'muscle_build', 'veg', 'sedentary', '3_meals', 1, 'lunch', 'Chickpea salad with quinoa', 25, 50, 10, 500),
(87, 'muscle_build', 'veg', 'sedentary', '3_meals', 1, 'dinner', 'Paneer tikka with vegetables', 30, 45, 12, 520),
(88, 'balanced', 'veg', 'sedentary', '3_meals', 1, 'breakfast', 'Mixed fruit bowl with yogurt', 12, 35, 5, 300),
(89, 'balanced', 'veg', 'sedentary', '3_meals', 1, 'lunch', 'Vegetable pulao with dal', 15, 50, 8, 400),
(90, 'balanced', 'veg', 'sedentary', '3_meals', 1, 'dinner', 'Grilled tofu with salad', 18, 40, 10, 400),
(91, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 1, 'breakfast', 'Egg omelette with toast', 12, 35, 10, 350),
(92, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 1, 'lunch', 'Grilled chicken with rice', 25, 50, 12, 500),
(93, 'weight_loss', 'nonveg', 'sedentary', '3_meals', 1, 'dinner', 'Grilled fish with salad', 22, 40, 10, 420),
(94, 'weight_loss', 'vegan', 'sedentary', '3_meals', 1, 'breakfast', 'Vegan smoothie bowl', 8, 35, 5, 250),
(95, 'weight_loss', 'vegan', 'sedentary', '3_meals', 1, 'lunch', 'Quinoa salad with beans', 15, 50, 8, 400),
(96, 'weight_loss', 'vegan', 'sedentary', '3_meals', 1, 'dinner', 'Tofu stir fry with vegetables', 20, 40, 10, 400),
(97, 'balanced', 'vegan', 'sedentary', '3_meals', 1, 'breakfast', 'Vegan smoothie bowl', 12, 35, 5, 300),
(98, 'balanced', 'vegan', 'sedentary', '3_meals', 1, 'lunch', 'Quinoa salad with beans', 15, 50, 8, 400),
(99, 'balanced', 'vegan', 'sedentary', '3_meals', 1, 'dinner', 'Tofu stir fry with vegetables', 18, 40, 10, 400),
(100, 'muscle_build', 'vegan', 'sedentary', '3_meals', 1, 'breakfast', 'Protein smoothie with soy milk', 20, 35, 8, 400),
(101, 'muscle_build', 'vegan', 'sedentary', '3_meals', 1, 'lunch', 'Chickpea salad with quinoa', 25, 50, 10, 500),
(102, 'muscle_build', 'vegan', 'sedentary', '3_meals', 1, 'dinner', 'Tofu stir fry with vegetables', 28, 45, 12, 520),
(103, 'weight_gain', 'vegan', 'sedentary', '3_meals', 1, 'breakfast', 'Vegan smoothie bowl with peanut butter', 15, 45, 10, 450),
(104, 'weight_gain', 'vegan', 'sedentary', '3_meals', 1, 'lunch', 'Tofu curry with rice', 20, 55, 12, 500),
(105, 'weight_gain', 'vegan', 'sedentary', '3_meals', 1, 'dinner', 'Vegan pasta with vegetables', 18, 50, 10, 480),
(106, 'balanced', 'nonveg', 'sedentary', '3_meals', 1, 'breakfast', 'Boiled eggs with toast', 15, 30, 8, 300),
(107, 'balanced', 'nonveg', 'sedentary', '3_meals', 1, 'lunch', 'Chicken salad with brown rice', 25, 50, 10, 450),
(108, 'balanced', 'nonveg', 'sedentary', '3_meals', 1, 'dinner', 'Grilled fish with veggies', 22, 40, 10, 400),
(109, 'muscle_build', 'veg', 'sedentary', '3_meals', 1, 'breakfast', 'Protein smoothie with soy milk', 20, 35, 8, 400),
(110, 'muscle_build', 'veg', 'sedentary', '3_meals', 1, 'lunch', 'Chickpea salad with quinoa', 25, 50, 10, 500),
(111, 'muscle_build', 'veg', 'sedentary', '3_meals', 1, 'dinner', 'Paneer tikka with vegetables', 30, 45, 12, 520),
(112, 'muscle_build', 'veg', 'sedentary', '3_meals', 2, 'breakfast', 'Scrambled tofu with spinach', 22, 30, 8, 380),
(113, 'muscle_build', 'veg', 'sedentary', '3_meals', 2, 'lunch', 'Lentil curry with brown rice', 25, 50, 10, 500),
(114, 'muscle_build', 'veg', 'sedentary', '3_meals', 2, 'dinner', 'Grilled paneer with quinoa', 28, 45, 12, 520),
(115, 'weight_loss', 'veg', 'light', '3_meals', 1, 'breakfast', 'Oatmeal with skim milk and berries', 10, 35, 5, 220),
(116, 'weight_loss', 'veg', 'light', '3_meals', 1, 'breakfast', 'Green smoothie with spinach, banana, and almond milk', 8, 30, 4, 200),
(117, 'weight_loss', 'veg', 'light', '3_meals', 1, 'lunch', 'Grilled vegetable salad with chickpeas', 15, 40, 8, 350),
(118, 'weight_loss', 'veg', 'light', '3_meals', 1, 'lunch', 'Quinoa salad with tofu and veggies', 18, 35, 10, 360),
(119, 'weight_loss', 'veg', 'light', '3_meals', 1, 'dinner', 'Steamed vegetables with lentil soup', 12, 30, 5, 250),
(120, 'weight_loss', 'veg', 'light', '3_meals', 1, 'dinner', 'Vegetable stir-fry with brown rice', 14, 40, 6, 320),
(121, 'muscle_build', 'veg', 'moderate', '3_meals', 1, 'breakfast', 'Scrambled eggs with spinach and toast', 20, 30, 10, 350),
(122, 'muscle_build', 'veg', 'moderate', '3_meals', 1, 'breakfast', 'Protein smoothie with soy milk and oats', 25, 40, 8, 400),
(123, 'muscle_build', 'veg', 'moderate', '3_meals', 1, 'lunch', 'Grilled paneer with quinoa and veggies', 30, 50, 12, 550),
(124, 'muscle_build', 'veg', 'moderate', '3_meals', 1, 'lunch', 'Chickpea curry with brown rice', 28, 55, 10, 520),
(125, 'muscle_build', 'veg', 'moderate', '3_meals', 1, 'dinner', 'Tofu stir-fry with mixed vegetables', 25, 45, 12, 500),
(126, 'muscle_build', 'veg', 'moderate', '3_meals', 1, 'dinner', 'Lentil soup with whole grain bread', 22, 50, 8, 480),
(127, 'weight_loss', 'nonveg', 'light', '3_meals', 1, 'breakfast', 'Egg white omelette with vegetables', 18, 5, 6, 180),
(128, 'weight_loss', 'nonveg', 'light', '3_meals', 1, 'breakfast', 'Greek yogurt with berries and honey', 12, 25, 2, 180),
(129, 'weight_loss', 'nonveg', 'light', '3_meals', 1, 'lunch', 'Grilled chicken salad with olive oil dressing', 30, 10, 8, 300),
(130, 'weight_loss', 'nonveg', 'light', '3_meals', 1, 'lunch', 'Turkey wrap with vegetables', 28, 25, 6, 320),
(131, 'weight_loss', 'nonveg', 'light', '3_meals', 1, 'dinner', 'Baked fish with steamed vegetables', 32, 5, 7, 300),
(132, 'weight_loss', 'nonveg', 'light', '3_meals', 1, 'dinner', 'Chicken stir-fry with broccoli and carrots', 30, 15, 8, 330),
(133, 'weight_loss', 'vegan', 'light', '3_meals', 1, 'breakfast', 'Chia seed pudding with almond milk and berries', 8, 30, 5, 180),
(134, 'weight_loss', 'vegan', 'light', '3_meals', 1, 'lunch', 'Lentil and quinoa salad with mixed greens', 18, 45, 7, 350),
(135, 'weight_loss', 'vegan', 'light', '3_meals', 1, 'dinner', 'Tofu and vegetable stir-fry with brown rice', 20, 50, 10, 400),
(136, 'weight_loss', 'vegan', 'light', '3_meals', 1, 'snack', 'Hummus with carrot and cucumber sticks', 5, 15, 4, 120);

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
(6, 'Dr. Arjun Menon', 'arjun.menon@example.com', '29a664b898da5448abfc35f06bd0fb1f', '9876543210', 'Sports Nutrition', 7, 'Passionate about helping athletes achieve peak performance through customized diet strategies.', 'uploads/diet-1.jpg', 'approved', '2025-09-08 04:18:11'),
(7, 'Dr. Rahul Sharma', 'rahul.sharma@example.com', '7b7f71bff78951c020e9c647a32bb839', '9123456780', 'Diabetes & Lifestyle Management', 10, 'Expert in creating sustainable nutrition plans for diabetic patients.', 'uploads/diet-2.jpg', 'approved', '2025-09-08 04:18:11'),
(8, 'Dr. Vikram Nair', 'vikram.nair@example.com', 'a8de3a9ece95b09b7925d80cef2713ea', '9988776655', 'Clinical Dietetics', 5, 'Dedicated to designing evidence-based diet therapies for chronic conditions.', 'uploads/diet-3.jpg', 'approved', '2025-09-08 04:18:11'),
(9, 'Dr. Neha Kapoor', 'neha.kapoor@example.com', 'f3de5e16d00fe7056839f6018f1f52ca', '9012345678', 'Weight Management', 8, 'Specializes in balanced diet plans for healthy weight loss and lifestyle transformation.', 'uploads/diet-4.jpg', 'approved', '2025-09-08 04:18:11'),
(10, 'Dr. Priya Iyer', 'priya.iyer@example.com', '3e17f774c90d60dc17444c0423470e04', '9871203345', 'Women’s Health & PCOS', 6, 'Focused on women’s nutrition, PCOS-friendly diets, and holistic wellness.', 'uploads/diet-5.jpg', 'approved', '2025-09-08 04:18:11'),
(11, 'Dr. Anjali Singh', 'anjali.singh@example.com', '1ecf290aee9e92438caf013a359f55f1', '9812234567', 'Pediatric Nutrition', 9, 'Helping children build strong foundations with proper nutrition and growth-focused meal plans.', 'uploads/diet-6.jpg', 'approved', '2025-09-08 04:18:11');

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
(1, 'Murali', 23, 66, 172, 'akdmuralimk@gmail.com', '81dc9bdb52d04dc20036dbd8313ed055', 'male', 'nothing', 'veg', 'weight_loss', 'moderate', '3_meals', 1, '2025-09-04 02:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_diet_plans`
--

CREATE TABLE `user_diet_plans` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `day_number` tinyint NOT NULL,
  `meal_time` varchar(30) NOT NULL,
  `meal_text` text NOT NULL,
  `protein` int DEFAULT '0',
  `carbs` int DEFAULT '0',
  `fat` int DEFAULT '0',
  `calories` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_diet_plans`
--

INSERT INTO `user_diet_plans` (`id`, `user_id`, `day_number`, `meal_time`, `meal_text`, `protein`, `carbs`, `fat`, `calories`) VALUES
(1, 1, 1, 'breakfast', 'Oats with fruits', 10, 40, 5, 250),
(2, 1, 1, 'lunch', 'Brown rice with veg curry', 15, 50, 10, 400),
(3, 1, 1, 'dinner', 'Grilled paneer salad', 20, 30, 12, 350),
(4, 1, 2, 'breakfast', 'Poha with sprouts', 12, 35, 5, 230),
(5, 1, 2, 'lunch', 'Veg pulao with dal', 15, 55, 8, 420),
(6, 1, 2, 'dinner', 'Tofu stir fry with salad', 18, 25, 10, 320),
(7, 1, 3, 'breakfast', 'Smoothie with nuts and oats', 10, 30, 8, 270),
(8, 1, 3, 'lunch', 'Quinoa salad with veggies', 15, 45, 7, 360),
(9, 1, 3, 'dinner', 'Chickpea curry with brown rice', 18, 50, 10, 400),
(10, 1, 4, 'breakfast', 'Whole wheat toast with peanut butter', 12, 35, 10, 300),
(11, 1, 4, 'lunch', 'Vegetable soup with bread', 10, 40, 5, 280),
(12, 1, 4, 'dinner', 'Lentil curry with quinoa', 20, 45, 8, 370),
(13, 1, 5, 'breakfast', 'Fruit salad with yogurt', 8, 30, 5, 220),
(14, 1, 5, 'lunch', 'Vegetable stir fry with rice', 15, 50, 10, 400),
(15, 1, 5, 'dinner', 'Grilled vegetables with tofu', 18, 30, 8, 350),
(16, 1, 6, 'breakfast', 'Chia pudding with berries', 10, 25, 6, 230),
(17, 1, 6, 'lunch', 'Veg sandwich with salad', 12, 40, 8, 300),
(18, 1, 6, 'dinner', 'Paneer curry with brown rice', 18, 50, 12, 400),
(19, 1, 7, 'breakfast', 'Muesli with almond milk', 10, 35, 6, 260),
(20, 1, 7, 'lunch', 'Spinach and chickpea salad', 15, 45, 7, 360),
(21, 1, 7, 'dinner', 'Vegetable stew with quinoa', 18, 40, 10, 370);

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
-- Indexes for table `user_diet_plans`
--
ALTER TABLE `user_diet_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `diet_plans`
--
ALTER TABLE `diet_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `nutritionists`
--
ALTER TABLE `nutritionists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- AUTO_INCREMENT for table `user_diet_plans`
--
ALTER TABLE `user_diet_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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

--
-- Constraints for table `user_diet_plans`
--
ALTER TABLE `user_diet_plans`
  ADD CONSTRAINT `user_diet_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `reg` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
