-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 12 nov. 2025 à 20:46
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `chifamaroc`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 11:27:00'),
(2, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 11:27:49'),
(3, 1, 'logout', 'تسجيل خروج المسؤول', '::1', NULL, '2025-11-07 11:27:58'),
(4, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 12:01:22'),
(5, 1, 'logout', 'تسجيل خروج المسؤول', '::1', NULL, '2025-11-07 12:01:24'),
(6, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 12:04:14'),
(7, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 12:04:35'),
(8, 1, 'logout', 'تسجيل خروج المسؤول', '::1', NULL, '2025-11-07 12:04:36'),
(9, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 12:04:42'),
(10, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 12:05:11'),
(11, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 12:06:03'),
(12, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-07 16:17:31'),
(13, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-12 19:37:17'),
(14, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-12 19:37:48'),
(15, 1, 'dashboard_access', 'وصول إلى لوحة التحكم', '::1', NULL, '2025-11-12 19:38:13');

-- --------------------------------------------------------

--
-- Structure de la table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator','support') DEFAULT 'admin',
  `permissions` text DEFAULT NULL COMMENT 'JSON encoded permissions',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `account_locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `permissions`, `is_active`, `last_login`, `login_attempts`, `account_locked_until`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'aissazahoum6@gmail.com', 'admin123', 'System', 'Administrator', 'super_admin', NULL, 1, NULL, 2, NULL, '2025-11-07 09:59:20', '2025-11-07 11:57:57');

-- --------------------------------------------------------

--
-- Structure de la table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `export_history`
--

CREATE TABLE `export_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `export_type` enum('treatment_plan','clinics_list','medical_profile') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `content_summary` text DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `export_history`
--

INSERT INTO `export_history` (`id`, `user_id`, `export_type`, `file_name`, `file_path`, `content_summary`, `download_count`, `created_at`) VALUES
(1, 2, 'treatment_plan', 'treatment_plan_2025-11-12_20-05-08.pdf', 'exports/treatment_plan_2025-11-12_20-05-08.pdf', 'تصدير خطة علاجية - نزلة البرد', 0, '2025-11-12 19:05:08'),
(2, 2, 'treatment_plan', 'treatment_plan_2025-11-12_20-09-16.pdf', 'exports/treatment_plan_2025-11-12_20-09-16.pdf', 'تصدير خطة علاجية - نزلة البرد', 0, '2025-11-12 19:09:16'),
(3, 2, 'treatment_plan', 'treatment_plan_2025-11-12_20-12-44.pdf', 'exports/treatment_plan_2025-11-12_20-12-44.pdf', 'تصدير خطة علاجية - نزلة البرد', 0, '2025-11-12 19:12:44'),
(4, 2, 'treatment_plan', 'treatment_plan_2025-11-12_20-13-39.pdf', 'exports/treatment_plan_2025-11-12_20-13-39.pdf', 'تصدير خطة علاجية - نزلة البرد', 0, '2025-11-12 19:13:39'),
(5, 2, 'treatment_plan', 'treatment_plan_2025-11-12_20-18-10.pdf', 'exports/treatment_plan_2025-11-12_20-18-10.pdf', 'تصدير خطة علاجية - نزلة البرد', 0, '2025-11-12 19:18:10');

-- --------------------------------------------------------

--
-- Structure de la table `facility_reviews`
--

CREATE TABLE `facility_reviews` (
  `id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `medical_facilities`
--

CREATE TABLE `medical_facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('pharmacy','clinic','hospital','laboratory','medical_center') NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `opening_hours` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `services` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `specialties` text DEFAULT NULL,
  `amenities` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medical_facilities`
--

INSERT INTO `medical_facilities` (`id`, `name`, `type`, `address`, `city`, `latitude`, `longitude`, `phone`, `email`, `website`, `opening_hours`, `description`, `services`, `is_verified`, `is_active`, `created_by`, `created_at`, `updated_at`, `rating`, `review_count`, `specialties`, `amenities`) VALUES
(1, 'صيدلية النجاح', 'pharmacy', 'شارع محمد الخامس، الدار البيضاء', 'Casablanca', 33.57310000, -7.58980000, '0522-123456', NULL, NULL, '8:00-20:00', NULL, NULL, 0, 1, NULL, '2025-11-07 09:59:20', '2025-11-07 09:59:20', 0.00, 0, NULL, NULL),
(2, 'مستشفى ابن سينا', 'hospital', 'حي الرياض، الدار البيضاء', 'Casablanca', 33.56890000, -7.58760000, '0522-456789', NULL, NULL, 'مفتوح 24/7', NULL, NULL, 0, 1, NULL, '2025-11-07 09:59:20', '2025-11-07 09:59:20', 0.00, 0, NULL, NULL),
(3, 'عيادة الأمل', 'clinic', 'شارع الحسن الثاني، الدار البيضاء', 'Casablanca', 33.57540000, -7.59230000, '0537-987654', NULL, NULL, '9:00-17:00', NULL, NULL, 0, 1, NULL, '2025-11-07 09:59:20', '2025-11-07 09:59:20', 0.00, 0, NULL, NULL),
(4, 'مختبر التحاليل الطبية', 'laboratory', 'شارع فلسطين، الدار البيضاء', 'Casablanca', 33.57020000, -7.59510000, '0522-555555', NULL, NULL, '7:00-19:00', NULL, NULL, 0, 1, NULL, '2025-11-07 09:59:20', '2025-11-07 09:59:20', 0.00, 0, NULL, NULL),
(5, 'صيدلية السلام', 'pharmacy', 'شارع علال الفاسي، الرباط', 'Rabat', 34.02090000, -6.84160000, '0537-111111', NULL, NULL, '8:30-21:00', NULL, NULL, 0, 1, NULL, '2025-11-07 09:59:20', '2025-11-07 09:59:20', 0.00, 0, NULL, NULL),
(6, 'مستشفى الشيخ زايد', 'hospital', 'حي الرياض، الرباط', 'Rabat', 34.01540000, -6.83270000, '0537-222222', NULL, NULL, 'مفتوح 24/7', NULL, NULL, 0, 1, NULL, '2025-11-07 09:59:20', '2025-11-07 09:59:20', 0.00, 0, NULL, NULL),
(7, 'صيدلية المركز', 'pharmacy', 'شارع المركز التجاري، مراكش', 'Marrakech', 31.62950000, -7.98110000, '0524-123456', NULL, NULL, '8:00-22:00', NULL, 'أدوية، مستحضرات تجميل، مستلزمات طبية', 0, 1, NULL, '2025-11-07 10:30:39', '2025-11-07 10:30:39', 0.00, 0, NULL, NULL),
(8, 'عيادة النخيل', 'clinic', 'حي النخيل، مراكش', 'Marrakech', 31.63320000, -7.98830000, '0524-654321', NULL, NULL, '9:00-18:00', NULL, 'استشارات طبية، فحوصات، تحاليل', 0, 1, NULL, '2025-11-07 10:30:39', '2025-11-07 10:30:39', 0.00, 0, NULL, NULL),
(9, 'مستشفى المواساة', 'hospital', 'شارع محمد السادس، فاس', 'Fes', 34.01810000, -5.00780000, '0535-111222', NULL, NULL, 'مفتوح 24/7', NULL, 'طوارئ، عمليات، استشارات متخصصة', 0, 1, NULL, '2025-11-07 10:30:39', '2025-11-07 10:30:39', 0.00, 0, NULL, NULL),
(10, 'مختبر التحاليل الطبية', 'laboratory', 'شارع الحسن الثاني، فاس', 'Fes', 34.02560000, -5.00030000, '0535-333444', NULL, NULL, '7:00-20:00', NULL, 'تحاليل الدم، تحاليل البول، فحوصات خاصة', 0, 1, NULL, '2025-11-07 10:30:39', '2025-11-07 10:30:39', 0.00, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `medical_profiles`
--

CREATE TABLE `medical_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL COMMENT 'in cm',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'in kg',
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(100) DEFAULT NULL,
  `insurance_provider` varchar(255) DEFAULT NULL,
  `insurance_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medical_profiles`
--

INSERT INTO `medical_profiles` (`id`, `user_id`, `blood_type`, `height`, `weight`, `allergies`, `chronic_conditions`, `current_medications`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`, `insurance_provider`, `insurance_number`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 10:54:38', '2025-11-07 10:54:38'),
(2, 2, 'B-', 185.00, 70.00, 'nothing', 'none', 'none', '19', '19', NULL, '', '', '2025-11-07 12:03:28', '2025-11-12 19:22:56');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'ChifaMaroc', 'string', 'Website name', 1, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(2, 'site_description', 'نظام المساعدة الطبية في المغرب - العيادات، الصيدليات، وخطط العلاج', 'string', 'Website description', 1, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(3, 'contact_email', 'contact@chifamaroc.ma', 'string', 'Contact email address', 1, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(4, 'max_login_attempts', '5', 'integer', 'Maximum login attempts before lockout', 0, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(5, 'account_lockout_duration', '30', 'integer', 'Account lockout duration in minutes', 0, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(6, 'search_radius_default', '5000', 'integer', 'Default search radius in meters', 0, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(7, 'export_pdf_enabled', '1', 'boolean', 'Enable PDF export feature', 1, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(8, 'map_provider', 'openstreetmap', 'string', 'Default map provider', 0, '2025-11-07 09:59:20', '2025-11-07 09:59:20'),
(9, 'max_file_size', '10485760', 'integer', 'أقصى حجم للملفات المرفوعة (بايت)', 0, '2025-11-07 10:30:39', '2025-11-07 10:30:39'),
(10, 'allowed_file_types', 'image/jpeg,image/png,image/gif,application/pdf', 'string', 'أنواع الملفات المسموح برفعها', 0, '2025-11-07 10:30:39', '2025-11-07 10:30:39'),
(11, 'session_timeout', '3600', 'integer', 'مدة انتهاء الجلسة (ثانية)', 0, '2025-11-07 10:30:39', '2025-11-07 10:30:39'),
(12, 'password_min_length', '8', 'integer', 'أقل طول لكلمة المرور', 1, '2025-11-07 10:30:39', '2025-11-07 10:30:39'),
(13, 'require_strong_password', '1', 'boolean', 'اشتراط كلمة مرور قوية', 1, '2025-11-07 10:30:39', '2025-11-07 10:30:39'),
(14, 'enable_two_factor', '0', 'boolean', 'تفعيل المصادقة الثنائية', 0, '2025-11-07 10:30:39', '2025-11-07 10:30:39');

-- --------------------------------------------------------

--
-- Structure de la table `treatment_medications`
--

CREATE TABLE `treatment_medications` (
  `id` int(11) NOT NULL,
  `treatment_plan_id` int(11) NOT NULL,
  `medication_name` varchar(255) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `treatment_medications`
--

INSERT INTO `treatment_medications` (`id`, `treatment_plan_id`, `medication_name`, `dosage`, `frequency`, `duration`, `instructions`, `created_at`) VALUES
(1, 1, 'باراسيتامول للألم والحمى', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:04:49'),
(2, 1, 'مضادات الاحتقان', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:04:49'),
(3, 1, 'أدوية السعال', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:04:49'),
(4, 2, 'باراسيتامول للألم والحمى', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:08:58'),
(5, 2, 'مضادات الاحتقان', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:08:58'),
(6, 2, 'أدوية السعال', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:08:58'),
(7, 3, 'باراسيتامول للألم والحمى', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:12:39'),
(8, 3, 'مضادات الاحتقان', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:12:39'),
(9, 3, 'أدوية السعال', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:12:39'),
(10, 4, 'باراسيتامول للألم والحمى', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:13:27'),
(11, 4, 'مضادات الاحتقان', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:13:27'),
(12, 4, 'أدوية السعال', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:13:27'),
(13, 5, 'باراسيتامول للألم والحمى', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:13:35'),
(14, 5, 'مضادات الاحتقان', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:13:35'),
(15, 5, 'أدوية السعال', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:13:35'),
(16, 6, 'باراسيتامول للألم والحمى', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:18:04'),
(17, 6, 'مضادات الاحتقان', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:18:04'),
(18, 6, 'أدوية السعال', 'حسب الإرشادات', 'حسب الحاجة', 'حتى زوال الأعراض', 'يؤخذ حسب الحاجة بعد استشارة الطبيب', '2025-11-12 19:18:04');

-- --------------------------------------------------------

--
-- Structure de la table `treatment_plans`
--

CREATE TABLE `treatment_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `symptoms` text DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `health_condition` varchar(50) DEFAULT NULL,
  `symptom_duration` varchar(50) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `follow_up_instructions` text DEFAULT NULL,
  `vitamins_supplements` text DEFAULT NULL,
  `disease_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `treatment_plans`
--

INSERT INTO `treatment_plans` (`id`, `user_id`, `plan_name`, `symptoms`, `age`, `health_condition`, `symptom_duration`, `diagnosis`, `recommendations`, `medications`, `follow_up_instructions`, `vitamins_supplements`, `disease_name`, `created_at`) VALUES
(1, 2, 'خطة علاجية 2025-11-12', 'سيلان الأنف', 20, 'average', '1-3', 'نزلة برد فيروسية شائعة\n- الأعراض: سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف\n- الحالة الصحية: متوسطة\n\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.', 'التوصيات العلاجية:\n- الراحة الكافية\n- شرب السوائل الدافئة\n- الغرغرة بالماء والملح\n- استخدام مرطب الجو\n\nالتوصيات العامة:\n- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n', 'الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n- باراسيتامول للألم والحمى\n- مضادات الاحتقان\n- أدوية السعال\n', 'تعليمات المتابعة:\nإذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب\n\nتعليمات إضافية:\n- إذا ساءت الأعراض، يرجى مراجعة الطبيب فوراً\n- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n  * صعوبة في التنفس\n  * ألم شديد في الصدر\n  * ارتفاع درجة الحرارة فوق 39°C\n  * تشوش ذهني أو فقدان الوعي\n', 'الفيتامينات والمكملات الغذائية:\n- فيتامين C 1000mg يومياً\n- الزنك 50mg يومياً\n- فيتامين D 1000IU\n\nمكملات داعمة عامة:\n- فيتامين C 1000mg يومياً لدعم المناعة\n- الزنك 50mg يومياً لمقاومة العدوى\n- فيتامين D 1000-2000IU يومياً حسب المستويات\n', 'نزلة البرد', '2025-11-12 19:04:49'),
(2, 2, 'خطة علاجية 2025-11-12', 'سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف', 20, 'good', '1-3', 'نزلة برد فيروسية شائعة\n- الأعراض: سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف\n- الحالة الصحية: جيدة\n\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.', 'التوصيات العلاجية:\n- الراحة الكافية\n- شرب السوائل الدافئة\n- الغرغرة بالماء والملح\n- استخدام مرطب الجو\n\nالتوصيات العامة:\n- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n', 'الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n- باراسيتامول للألم والحمى\n- مضادات الاحتقان\n- أدوية السعال\n', 'تعليمات المتابعة:\nإذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب\n\nتعليمات إضافية:\n- إذا ساءت الأعراض، يرجى مراجعة الطبيب فوراً\n- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n  * صعوبة في التنفس\n  * ألم شديد في الصدر\n  * ارتفاع درجة الحرارة فوق 39°C\n  * تشوش ذهني أو فقدان الوعي\n', 'الفيتامينات والمكملات الغذائية:\n- فيتامين C 1000mg يومياً\n- الزنك 50mg يومياً\n- فيتامين D 1000IU\n\nمكملات داعمة عامة:\n- فيتامين C 1000mg يومياً لدعم المناعة\n- الزنك 50mg يومياً لمقاومة العدوى\n- فيتامين D 1000-2000IU يومياً حسب المستويات\n', 'نزلة البرد', '2025-11-12 19:08:58'),
(3, 2, 'خطة علاجية 2025-11-12', 'سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف', 67, 'good', 'more_than_2', 'نزلة برد فيروسية شائعة\n- الأعراض: سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف\n- الحالة الصحية: جيدة\n\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.', 'التوصيات العلاجية:\n- الراحة الكافية\n- شرب السوائل الدافئة\n- الغرغرة بالماء والملح\n- استخدام مرطب الجو\n\nالتوصيات العامة:\n- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n- العناية الإضافية لكبار السن والمتابعة الطبية الدورية\n', 'الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n- باراسيتامول للألم والحمى\n- مضادات الاحتقان\n- أدوية السعال\n', 'تعليمات المتابعة:\nإذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب\n\nتعليمات إضافية:\n- يرجى مراجعة الطبيب في أقرب وقت ممكن\n- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n  * صعوبة في التنفس\n  * ألم شديد في الصدر\n  * ارتفاع درجة الحرارة فوق 39°C\n  * تشوش ذهني أو فقدان الوعي\n', 'الفيتامينات والمكملات الغذائية:\n- فيتامين C 1000mg يومياً\n- الزنك 50mg يومياً\n- فيتامين D 1000IU\n\nمكملات داعمة عامة:\n- فيتامين C 1000mg يومياً لدعم المناعة\n- الزنك 50mg يومياً لمقاومة العدوى\n- فيتامين D 1000-2000IU يومياً حسب المستويات\n', 'نزلة البرد', '2025-11-12 19:12:39'),
(4, 2, 'خطة علاجية 2025-11-12', 'سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف', 67, 'good', 'more_than_2', 'نزلة برد فيروسية شائعة\n- الأعراض: سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف\n- الحالة الصحية: جيدة\n\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.', 'التوصيات العلاجية:\n- الراحة الكافية\n- شرب السوائل الدافئة\n- الغرغرة بالماء والملح\n- استخدام مرطب الجو\n\nالتوصيات العامة:\n- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n- العناية الإضافية لكبار السن والمتابعة الطبية الدورية\n', 'الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n- باراسيتامول للألم والحمى\n- مضادات الاحتقان\n- أدوية السعال\n', 'تعليمات المتابعة:\nإذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب\n\nتعليمات إضافية:\n- يرجى مراجعة الطبيب في أقرب وقت ممكن\n- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n  * صعوبة في التنفس\n  * ألم شديد في الصدر\n  * ارتفاع درجة الحرارة فوق 39°C\n  * تشوش ذهني أو فقدان الوعي\n', 'الفيتامينات والمكملات الغذائية:\n- فيتامين C 1000mg يومياً\n- الزنك 50mg يومياً\n- فيتامين D 1000IU\n\nمكملات داعمة عامة:\n- فيتامين C 1000mg يومياً لدعم المناعة\n- الزنك 50mg يومياً لمقاومة العدوى\n- فيتامين D 1000-2000IU يومياً حسب المستويات\n', 'نزلة البرد', '2025-11-12 19:13:27'),
(5, 2, 'خطة علاجية 2025-11-12', 'سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف', 67, 'average', 'more_than_2', 'نزلة برد فيروسية شائعة\n- الأعراض: سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف\n- الحالة الصحية: متوسطة\n\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.', 'التوصيات العلاجية:\n- الراحة الكافية\n- شرب السوائل الدافئة\n- الغرغرة بالماء والملح\n- استخدام مرطب الجو\n\nالتوصيات العامة:\n- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n- العناية الإضافية لكبار السن والمتابعة الطبية الدورية\n', 'الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n- باراسيتامول للألم والحمى\n- مضادات الاحتقان\n- أدوية السعال\n', 'تعليمات المتابعة:\nإذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب\n\nتعليمات إضافية:\n- يرجى مراجعة الطبيب في أقرب وقت ممكن\n- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n  * صعوبة في التنفس\n  * ألم شديد في الصدر\n  * ارتفاع درجة الحرارة فوق 39°C\n  * تشوش ذهني أو فقدان الوعي\n', 'الفيتامينات والمكملات الغذائية:\n- فيتامين C 1000mg يومياً\n- الزنك 50mg يومياً\n- فيتامين D 1000IU\n\nمكملات داعمة عامة:\n- فيتامين C 1000mg يومياً لدعم المناعة\n- الزنك 50mg يومياً لمقاومة العدوى\n- فيتامين D 1000-2000IU يومياً حسب المستويات\n', 'نزلة البرد', '2025-11-12 19:13:35'),
(6, 2, 'خطة علاجية 2025-11-12', 'سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف', 67, 'average', 'more_than_2', 'نزلة برد فيروسية شائعة\n- الأعراض: سيلان الأنف, احتقان, عطس, سعال خفيف, تهاب الحلق, صداع خفيف\n- الحالة الصحية: متوسطة\n\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.', 'التوصيات العلاجية:\n- الراحة الكافية\n- شرب السوائل الدافئة\n- الغرغرة بالماء والملح\n- استخدام مرطب الجو\n\nالتوصيات العامة:\n- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n- العناية الإضافية لكبار السن والمتابعة الطبية الدورية\n', 'الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n- باراسيتامول للألم والحمى\n- مضادات الاحتقان\n- أدوية السعال\n', 'تعليمات المتابعة:\nإذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب\n\nتعليمات إضافية:\n- يرجى مراجعة الطبيب في أقرب وقت ممكن\n- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n  * صعوبة في التنفس\n  * ألم شديد في الصدر\n  * ارتفاع درجة الحرارة فوق 39°C\n  * تشوش ذهني أو فقدان الوعي\n', 'الفيتامينات والمكملات الغذائية:\n- فيتامين C 1000mg يومياً\n- الزنك 50mg يومياً\n- فيتامين D 1000IU\n\nمكملات داعمة عامة:\n- فيتامين C 1000mg يومياً لدعم المناعة\n- الزنك 50mg يومياً لمقاومة العدوى\n- فيتامين D 1000-2000IU يومياً حسب المستويات\n', 'نزلة البرد', '2025-11-12 19:18:04');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Morocco',
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `account_locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone_verified` tinyint(1) DEFAULT 0,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `last_password_change` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `date_of_birth`, `gender`, `address`, `city`, `country`, `profile_image`, `is_active`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `last_login`, `login_attempts`, `account_locked_until`, `created_at`, `updated_at`, `phone_verified`, `two_factor_enabled`, `two_factor_secret`, `last_password_change`) VALUES
(1, 'aissa', 'zahoum', 'aissazahoum6@gmail.com', '$2y$10$eB.m.8sF0RSCphg9OzYPleG2p1.5Lxrrdv13hJnIlFRQylPeHxEQG', '0649339948', NULL, NULL, NULL, NULL, 'Morocco', NULL, 1, 0, '30c35ec215e4433877ef434993903f30d007d138faa4c7c29080f77307bf0a23', NULL, NULL, '2025-11-12 20:36:39', 0, NULL, '2025-11-07 10:54:38', '2025-11-12 19:36:39', 0, 0, NULL, NULL),
(2, 'ahmed', 'kal', 'a.zahoum8425@uca.ac.ma', '$2y$10$gcUU.sxSviA2aLLDoRDGFuos6TdGFaPXFdjxTMFgYxvNKlb1ZvC4y', '', NULL, NULL, NULL, NULL, 'Morocco', NULL, 1, 0, 'b520a650aa55c27a7e54a37ba5193cbd9225dacaf47b3bec1bf9ade46cdb6197', NULL, NULL, '2025-11-12 19:45:56', 0, NULL, '2025-11-07 12:03:28', '2025-11-12 18:45:56', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_searches`
--

CREATE TABLE `user_searches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `search_type` enum('clinics','pharmacies','hospitals','laboratories') NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `search_radius` int(11) DEFAULT NULL COMMENT 'in meters',
  `results_count` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `export_history`
--
ALTER TABLE `export_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_export_type` (`export_type`),
  ADD KEY `idx_export_history_user_type` (`user_id`,`export_type`);

--
-- Index pour la table `facility_reviews`
--
ALTER TABLE `facility_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `email` (`email`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Index pour la table `medical_facilities`
--
ALTER TABLE `medical_facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_facilities_city_type` (`city`,`type`);

--
-- Index pour la table `medical_profiles`
--
ALTER TABLE `medical_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event` (`event`),
  ADD KEY `created_at` (`created_at`);

--
-- Index pour la table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `treatment_medications`
--
ALTER TABLE `treatment_medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `treatment_plan_id` (`treatment_plan_id`);

--
-- Index pour la table `treatment_plans`
--
ALTER TABLE `treatment_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_users_email_active` (`email`,`is_active`);

--
-- Index pour la table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `user_searches`
--
ALTER TABLE `user_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_search_type` (`search_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `export_history`
--
ALTER TABLE `export_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `facility_reviews`
--
ALTER TABLE `facility_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `medical_facilities`
--
ALTER TABLE `medical_facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `medical_profiles`
--
ALTER TABLE `medical_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `treatment_medications`
--
ALTER TABLE `treatment_medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `treatment_plans`
--
ALTER TABLE `treatment_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_searches`
--
ALTER TABLE `user_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `medical_facilities` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `export_history`
--
ALTER TABLE `export_history`
  ADD CONSTRAINT `export_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `facility_reviews`
--
ALTER TABLE `facility_reviews`
  ADD CONSTRAINT `facility_reviews_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `medical_facilities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `facility_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `medical_facilities`
--
ALTER TABLE `medical_facilities`
  ADD CONSTRAINT `medical_facilities_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `medical_profiles`
--
ALTER TABLE `medical_profiles`
  ADD CONSTRAINT `medical_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `security_logs`
--
ALTER TABLE `security_logs`
  ADD CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `treatment_medications`
--
ALTER TABLE `treatment_medications`
  ADD CONSTRAINT `treatment_medications_ibfk_1` FOREIGN KEY (`treatment_plan_id`) REFERENCES `treatment_plans` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `treatment_plans`
--
ALTER TABLE `treatment_plans`
  ADD CONSTRAINT `treatment_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_activities`
--
ALTER TABLE `user_activities`
  ADD CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_searches`
--
ALTER TABLE `user_searches`
  ADD CONSTRAINT `user_searches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
