-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 03, 2025 at 08:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `intramurals`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`id`, `title`, `message`, `image`, `school_id`, `department_id`, `created_at`) VALUES
(28, 'Volleyball Tryout', 'Join us for volleyball tryouts. All skill levels welcome! Bring your enthusiasm and gear. Don’t miss your chance to be part of the team!\r\n\r\nSee you on the court! 🏆', '../uploadsBVW-volleyball-tryouts-copy.png', 8, 1, '2024-12-09 05:59:59'),
(29, 'Announcement!', 'Players and Team Registrations are now open!!!', '../uploadsEye_catching_ways_to_make_announcements.2aee7ba1.5d605628.jpg', 8, 1, '2024-12-09 05:59:59'),
(34, 'awda', 'wdsasss', NULL, NULL, 0, '2024-12-18 21:22:37'),
(38, 'Urgent!!!', 'System Maintenance', NULL, 0, 0, '2025-01-09 06:10:22');

-- --------------------------------------------------------

--
-- Table structure for table `brackets`
--

CREATE TABLE `brackets` (
  `bracket_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `grade_level` varchar(64) DEFAULT NULL,
  `total_teams` int(11) NOT NULL,
  `rounds` int(11) NOT NULL,
  `status` enum('ongoing','Completed') DEFAULT 'ongoing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bracket_type` enum('single','double') NOT NULL DEFAULT 'single'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` enum('Elementary','JHS','SHS','College','System') NOT NULL,
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `school_id`) VALUES
(0, 'System', 0),
(1, 'College', 8),
(2, 'SHS', 8),
(3, 'JHS', 8),
(4, 'Elementary', 8);

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(255) NOT NULL,
  `number_of_players` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `environment` enum('Indoor','Outdoor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`game_id`, `game_name`, `number_of_players`, `category`, `environment`, `created_at`, `school_id`) VALUES
(0, 'System', 0, 'System', 'Indoor', '2024-12-01 00:28:11', 0),
(2, 'Volleyball', 15, 'Team Sports', 'Outdoor', '2024-10-04 16:02:54', 8),
(18, 'Basketball', 15, 'Team Sports', 'Outdoor', '2024-10-15 06:47:47', 8),
(22, 'Dama', 2, 'Individual Sports', 'Indoor', '2024-10-15 10:48:18', 8),
(26, 'Dart', 2, 'Individual Sports', 'Indoor', '2024-10-15 22:14:06', 8),
(27, 'Chess', 2, 'Individual Sports', 'Indoor', '2024-10-23 16:54:28', 8),
(28, 'Badminton', 2, 'Individual Sports', 'Outdoor', '2024-10-23 16:55:47', 8),
(29, 'Patintero', 8, 'Team Sports', 'Outdoor', '2024-10-23 16:55:56', 8),
(30, 'Word Factory', 4, 'Individual Sports', 'Indoor', '2024-10-23 16:56:12', 8);

-- --------------------------------------------------------

--
-- Table structure for table `game_scoring_rules`
--

CREATE TABLE `game_scoring_rules` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL,
  `scoring_unit` varchar(255) DEFAULT NULL,
  `score_increment_options` varchar(255) DEFAULT NULL,
  `period_type` varchar(255) DEFAULT NULL,
  `number_of_periods` int(11) DEFAULT NULL,
  `duration_per_period` int(11) DEFAULT NULL,
  `time_limit` tinyint(1) DEFAULT NULL,
  `point_cap` int(11) DEFAULT NULL,
  `max_fouls` int(11) DEFAULT NULL,
  `timeouts_per_period` int(11) DEFAULT 0,
  `game_type` enum('point','set','default') NOT NULL DEFAULT 'point'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_scoring_rules`
--

INSERT INTO `game_scoring_rules` (`id`, `game_id`, `department_id`, `school_id`, `scoring_unit`, `score_increment_options`, `period_type`, `number_of_periods`, `duration_per_period`, `time_limit`, `point_cap`, `max_fouls`, `timeouts_per_period`, `game_type`) VALUES
(7, 18, 1, 8, 'Point', '1,2,3', '0', 4, 10, 1, 0, 5, 4, 'set'),
(12, 27, 1, 8, 'Point', '1', 'Set', 4, 5, 1, 0, 0, 0, 'point');

-- --------------------------------------------------------

--
-- Table structure for table `game_stats_config`
--

CREATE TABLE `game_stats_config` (
  `config_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `stat_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_stats_config`
--

INSERT INTO `game_stats_config` (`config_id`, `game_id`, `stat_name`) VALUES
(23, 18, 'Assists'),
(15, 18, 'Fouls'),
(18, 18, 'Rebounds'),
(22, 27, 'Points');

-- --------------------------------------------------------

--
-- Table structure for table `grade_section_course`
--

CREATE TABLE `grade_section_course` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `grade_level` enum('Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') DEFAULT NULL,
  `strand` varchar(64) DEFAULT NULL,
  `section_name` varchar(50) DEFAULT NULL,
  `course_name` varchar(100) DEFAULT NULL,
  `Points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_section_course`
--

INSERT INTO `grade_section_course` (`id`, `department_id`, `grade_level`, `strand`, `section_name`, `course_name`, `Points`) VALUES
(0, 0, NULL, NULL, NULL, 'System', 0),
(67, 1, NULL, NULL, NULL, 'BSCS', 0),
(68, 1, NULL, NULL, NULL, 'CRIM', 0),
(69, 1, NULL, NULL, NULL, 'BSA', 0),
(70, 2, 'Grade 11', 'STEMss', '11-Aa', NULL, 0),
(71, 2, 'Grade 12', 'ABM', '12-A', NULL, 0),
(72, 3, 'Grade 8', NULL, 'Obedience', NULL, 0),
(76, 1, NULL, NULL, NULL, 'BSBA', 0),
(77, 1, NULL, NULL, NULL, 'EDUC', 0),
(78, 1, NULL, NULL, NULL, 'BSHM', 0),
(79, 2, 'Grade 11', 'STEM', '11-B', NULL, 0),
(84, 2, 'Grade 11', 'STEM', 'C', NULL, 0),
(85, 2, 'Grade 11', 'ABM', 'D', NULL, 0),
(86, 2, 'Grade 11', 'HUMSS', 'A', NULL, 0),
(100, 2, 'Grade 11', 'STEM', 'mm', NULL, 0),
(101, 3, 'Grade 8', NULL, 'nickel', NULL, 0),
(105, 3, 'Grade 8', NULL, 'johndoes', NULL, 0),
(107, 3, 'Grade 9', NULL, 'asas', NULL, 0),
(114, 1, NULL, NULL, NULL, 'ff', 0),
(115, 1, NULL, NULL, NULL, 'll', 0),
(121, 1, NULL, NULL, NULL, 'asasa', 0),
(122, 1, NULL, NULL, NULL, 'wda', 0),
(123, 1, NULL, NULL, NULL, 'adwawd', 0),
(124, 1, NULL, NULL, NULL, 'awdawda', 0);

-- --------------------------------------------------------

--
-- Table structure for table `live_default_scores`
--

CREATE TABLE `live_default_scores` (
  `live_default_score_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `teamA_score` int(11) DEFAULT 0,
  `teamB_score` int(11) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `teamA_id` int(11) NOT NULL,
  `teamB_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_scores`
--

CREATE TABLE `live_scores` (
  `live_score_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `teamA_score` int(11) DEFAULT 0,
  `teamB_score` int(11) DEFAULT 0,
  `timeout_teamA` int(11) DEFAULT NULL,
  `timeout_teamB` int(11) DEFAULT NULL,
  `foul_teamA` int(11) DEFAULT NULL,
  `foul_teamB` int(11) DEFAULT NULL,
  `period` varchar(10) DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `teamA_id` int(11) DEFAULT NULL,
  `teamB_id` int(11) DEFAULT NULL,
  `time_remaining` int(11) DEFAULT NULL COMMENT 'Remaining time in seconds for current period',
  `timer_status` enum('running','paused','ended') DEFAULT 'paused' COMMENT 'Current timer status',
  `last_timer_update` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Last time the timer was updated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_set_scores`
--

CREATE TABLE `live_set_scores` (
  `live_set_score_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `teamA_score` int(11) DEFAULT 0,
  `teamB_score` int(11) DEFAULT 0,
  `teamA_sets_won` int(11) DEFAULT 0,
  `teamB_sets_won` int(11) DEFAULT 0,
  `current_set` int(11) DEFAULT 1,
  `timeout_teamA` int(11) DEFAULT 0,
  `timeout_teamB` int(11) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `teamA_id` int(11) NOT NULL,
  `teamB_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `operation` varchar(155) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `previous_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `table_name`, `operation`, `record_id`, `user_id`, `timestamp`, `description`, `previous_data`, `new_data`) VALUES
(4, 'announcements', 'CREATE', 46, 56, '2025-01-24 02:09:24', 'Added Announcement titled \"test\"', NULL, '{\"title\":\"test\",\"message\":\"test\"}'),
(5, 'announcements', 'DELETE', 46, 56, '2025-01-24 02:29:41', 'Deleted Announcement titled \"\"', '{\"id\":46,\"title\":\"\"}', NULL),
(6, 'announcements', 'CREATE', 47, 56, '2025-01-24 02:31:13', 'Added Announcement titled \"qq\"', NULL, '{\"title\":\"qq\",\"message\":\"qq\"}'),
(7, 'announcements', 'DELETE', 47, 56, '2025-01-24 02:31:16', 'Deleted Announcement titled \"qq\"', '{\"id\":47,\"title\":\"qq\"}', NULL),
(8, 'announcements', 'CREATE', 48, 56, '2025-01-24 02:33:12', 'Added Announcement titled \"ss\"', NULL, '{\"title\":\"ss\",\"message\":\"ss\"}'),
(9, 'announcements', 'UPDATE', 48, 56, '2025-01-24 02:34:56', 'Edited title from \"ss\" to \"sssss\". ', '{\"title\":\"ss\",\"message\":\"ss\",\"image\":null}', '{\"title\":\"sssss\",\"message\":\"ss\",\"image\":null}'),
(10, 'announcements', 'UPDATE', 48, 56, '2025-01-24 02:35:06', 'Edited content. ', '{\"title\":\"sssss\",\"message\":\"ss\",\"image\":null}', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"image\":null}'),
(11, 'announcements', 'UPDATE', 48, 56, '2025-01-24 02:35:19', '', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"image\":null}', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"image\":null}'),
(12, 'announcements', 'UPDATE', 48, 56, '2025-01-24 02:36:17', 'Changed department from \"2\" to \"3\". ', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"department_id\":2,\"image\":null}', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"department_id\":3,\"image\":null}'),
(13, 'announcements', 'UPDATE', 48, 56, '2025-01-24 02:37:25', 'Changed department from \"JHS\" to \"College\". ', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"department_id\":3,\"image\":null}', '{\"title\":\"sssss\",\"message\":\"ssaaaa\",\"department_id\":1,\"image\":null}'),
(14, 'announcements', 'DELETE', 48, 56, '2025-01-24 02:37:33', 'Deleted Announcement titled \"sssss\"', '{\"id\":48,\"title\":\"sssss\"}', NULL),
(15, 'pointing_system', 'UPDATE', 8, 56, '2025-01-24 02:45:50', 'Updated first_place_points from 10 to 11. ', '{\"first_place_points\":10,\"second_place_points\":5,\"third_place_points\":3}', '{\"first_place_points\":\"11\",\"second_place_points\":\"5\",\"third_place_points\":\"3\"}'),
(16, 'pointing_system', 'UPDATE', 8, 56, '2025-01-24 02:46:01', 'Updated second_place_points from 5 to 6. ', '{\"first_place_points\":11,\"second_place_points\":5,\"third_place_points\":3}', '{\"first_place_points\":\"11\",\"second_place_points\":\"6\",\"third_place_points\":\"3\"}'),
(17, 'pointing_system', 'UPDATE', 8, 56, '2025-01-24 02:46:14', 'Updated second_place_points from 6 to 7. Updated third_place_points from 3 to 2. ', '{\"first_place_points\":11,\"second_place_points\":6,\"third_place_points\":3}', '{\"first_place_points\":\"11\",\"second_place_points\":\"7\",\"third_place_points\":\"2\"}'),
(18, 'Pointing System', 'UPDATE', 8, 56, '2025-01-24 02:48:07', 'Updated first_place_points from 11 to 10. ', '{\"first_place_points\":11,\"second_place_points\":7,\"third_place_points\":2}', '{\"first_place_points\":\"10\",\"second_place_points\":\"7\",\"third_place_points\":\"2\"}'),
(19, 'users', 'CREATE', 104, 56, '2025-01-24 03:05:31', 'User registration', NULL, '{\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M\",\"age\":22,\"gender\":\"Male\",\"email\":\"andrae@gmail.com\",\"role\":\"Committee\",\"department\":\"2\",\"game_id\":\"26\",\"school_id\":8}'),
(20, 'Users', 'DELETE', 104, 56, '2025-01-24 03:16:21', 'Deleted \"Andrae De Guzman\" (Committee) from the \"2\" department.', '{\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"role\":\"Committee\",\"department\":2}', NULL),
(21, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:22:44', 'Updated user details for \"Jb De Guzman\" (Committee): Game ID: 29 → 18', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M\",\"age\":24,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":4,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":29,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M\",\"age\":\"24\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"18\",\"department\":\"4\"}'),
(22, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:28:05', 'Updated user details for \"Jb De Guzman\" (Committee): Game: Word Factory → Word Factory', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M\",\"age\":24,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":4,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":18,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M\",\"age\":\"24\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"30\",\"department\":\"4\"}'),
(23, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:28:18', 'Updated user details for \"Jb De Guzman\" (Committee): Game: Basketball → Basketball, Department: 4 → College', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M\",\"age\":24,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":4,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":30,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M\",\"age\":\"24\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"18\",\"department\":\"1\"}'),
(24, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:28:54', 'Updated user details for \"Jb De Guzman\" (Committee): Department: 1 → SHS', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M\",\"age\":24,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":18,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M\",\"age\":\"24\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"18\",\"department\":\"2\"}'),
(25, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:29:27', 'Updated user details for \"Jb De Guzman\" (Committee): Middle Initial: M → M., Age: 24 → 25, Department: 2 → College', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M\",\"age\":24,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":2,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":18,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"JB\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M.\",\"age\":\"25\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"18\",\"department\":\"1\"}'),
(26, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:30:06', 'Updated user details for \"Jb De Guzman\" (Committee): Department: 1 → SHS', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M.\",\"age\":25,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":18,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M.\",\"age\":\"25\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"18\",\"department\":\"2\"}'),
(27, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:31:10', 'Updated user details for \"Jb De Guzman\" (Committee): Department: 2 → College', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M.\",\"age\":25,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":2,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":18,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M.\",\"age\":\"25\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"18\",\"department\":\"1\"}'),
(28, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:32:21', 'Updated user details for \"Jb De Guzman\" (Committee): Game: Badminton → Badminton, Department: 1 → College', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M.\",\"age\":25,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":18,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M.\",\"age\":\"25\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"28\",\"department\":\"1\"}'),
(29, 'Users', 'UPDATE', 103, 56, '2025-01-24 03:33:18', 'Updated user details for \"Jb De Guzman\" (Committee): Department: College → SHS', '{\"id\":103,\"lastname\":\"De Guzman\",\"firstname\":\"Jb\",\"middleinitial\":\"M.\",\"age\":25,\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"password\":\"$2y$10$bfsZID8.gLJRh\\/LBQMJ3Z.Y0KXnzeX4RH3byt96ywl9JGNT3bQkuG\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":28,\"school_id\":8}', '{\"user_id\":\"103\",\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"middleinitial\":\"M.\",\"age\":\"25\",\"gender\":\"Male\",\"email\":\"jb@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"28\",\"department\":\"2\"}'),
(30, 'users', 'CREATE', NULL, 56, '2025-01-24 03:33:54', 'Registered \"Andrae De Guzman\" as a department admin for the \"1\" department.', NULL, NULL),
(31, 'Users', 'DELETE', 105, 56, '2025-01-24 03:42:51', 'Deleted \"Andrae De Guzman\" (Department Admin) from the \"1\" department.', '{\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"role\":\"Department Admin\",\"department\":1}', NULL),
(32, 'users', 'CREATE', NULL, 56, '2025-01-24 03:47:46', 'Registered \"Andrae De Guzman\" as a department admin for the \"1\" department.', NULL, NULL),
(33, 'Users', 'DELETE', 106, 56, '2025-01-24 03:50:06', 'Deleted \"Andrae De Guzman\" (Department Admin) from the \"College\" department.', '{\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"role\":\"Department Admin\",\"department\":1,\"game_id\":null,\"department_name\":\"College\",\"game_name\":null}', NULL),
(34, 'Users', 'DELETE', 107, 56, '2025-01-24 03:53:06', 'Deleted \"Andrae De Guzman\" (Department Admin) from the \"College\" department.', '{\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"role\":\"Department Admin\",\"department\":1,\"game_id\":null,\"department_name\":\"College\",\"game_name\":null}', NULL),
(35, 'users', 'CREATE', NULL, 56, '2025-01-24 03:53:23', 'Registered \"Andrae De Guzman\" as a department admin for the \"College\" department.', NULL, NULL),
(36, 'Users', 'UPDATE', 108, 56, '2025-01-24 03:53:41', 'Updated user details for \"Andrae De Guzman\" (Committee): Role: Department Admin → Committee, Game: Word Factory → Word Factory', '{\"id\":108,\"lastname\":\"De Guzman\",\"firstname\":\"Andrae\",\"middleinitial\":\"N\",\"age\":22,\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"password\":\"$2y$10$2EI9mdJS6j1.cJzIWHekA.RIvSu39NKJtaBtpRRv1J7uXS.Ed4XDi\",\"role\":\"Department Admin\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":null,\"school_id\":8}', '{\"user_id\":\"108\",\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"middleinitial\":\"N\",\"age\":\"22\",\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"30\",\"department\":\"1\"}'),
(37, 'Users', 'DELETE', 108, 56, '2025-01-24 03:53:49', 'Deleted \"Andrae De Guzman\" (Committee) from the \"College\" department for the \"Word Factory\" game.', '{\"firstname\":\"Andrae\",\"lastname\":\"De Guzman\",\"role\":\"Committee\",\"department\":1,\"game_id\":30,\"department_name\":\"College\",\"game_name\":\"Word Factory\"}', NULL),
(38, 'users', 'CREATE', NULL, 56, '2025-01-24 03:54:07', 'Registered \"Andre De Guzman\" as a committee member for \"\" in the \"College\" department.', NULL, NULL),
(39, 'Users', 'UPDATE', 109, 56, '2025-01-24 03:55:00', 'Updated user details for \"Andre De Guzman\" (Committee): Game: Badminton → Badminton', '{\"id\":109,\"lastname\":\"De Guzman\",\"firstname\":\"Andre\",\"middleinitial\":\"N\",\"age\":22,\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"password\":\"$2y$10$BGjTYc4YXMEqPLIuqqA9rOmvdv\\/QcL0miLcpc.0sukHWanMQpSMIy\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":30,\"school_id\":8}', '{\"user_id\":\"109\",\"firstname\":\"Andre\",\"lastname\":\"De Guzman\",\"middleinitial\":\"N\",\"age\":\"22\",\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"28\",\"department\":\"1\"}'),
(40, 'Users', 'UPDATE', 109, 56, '2025-01-24 03:57:01', 'Updated user details for \"Andre De Guzman\" (Committee): Game: Dart → Dart', '{\"id\":109,\"lastname\":\"De Guzman\",\"firstname\":\"Andre\",\"middleinitial\":\"N\",\"age\":22,\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"password\":\"$2y$10$BGjTYc4YXMEqPLIuqqA9rOmvdv\\/QcL0miLcpc.0sukHWanMQpSMIy\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":28,\"school_id\":8}', '{\"user_id\":\"109\",\"firstname\":\"Andre\",\"lastname\":\"De Guzman\",\"middleinitial\":\"N\",\"age\":\"22\",\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"26\",\"department\":\"1\"}'),
(41, 'Users', 'UPDATE', 109, 56, '2025-01-24 04:01:18', 'Updated user details for \"Andre De Guzman\" (Committee): Game: Word Factory → Word Factory', '{\"id\":109,\"lastname\":\"De Guzman\",\"firstname\":\"Andre\",\"middleinitial\":\"N\",\"age\":22,\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"password\":\"$2y$10$BGjTYc4YXMEqPLIuqqA9rOmvdv\\/QcL0miLcpc.0sukHWanMQpSMIy\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":26,\"school_id\":8}', '{\"user_id\":\"109\",\"firstname\":\"Andre\",\"lastname\":\"De Guzman\",\"middleinitial\":\"N\",\"age\":\"22\",\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"30\",\"department\":\"1\"}'),
(42, 'Users', 'UPDATE', 109, 56, '2025-01-24 04:06:47', 'Updated user details for \"Andre De Guzman\" (Committee): Game: Word Factory → Badminton', '{\"id\":109,\"lastname\":\"De Guzman\",\"firstname\":\"Andre\",\"middleinitial\":\"N\",\"age\":22,\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"password\":\"$2y$10$BGjTYc4YXMEqPLIuqqA9rOmvdv\\/QcL0miLcpc.0sukHWanMQpSMIy\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":30,\"school_id\":8}', '{\"user_id\":\"109\",\"firstname\":\"Andre\",\"lastname\":\"De Guzman\",\"middleinitial\":\"N\",\"age\":\"22\",\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"28\",\"department\":\"1\"}'),
(43, 'Users', 'UPDATE', 109, 56, '2025-01-24 04:06:57', 'Updated user details for \"Andre De Guzman\" (Committee): Department: College → SHS', '{\"id\":109,\"lastname\":\"De Guzman\",\"firstname\":\"Andre\",\"middleinitial\":\"N\",\"age\":22,\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"password\":\"$2y$10$BGjTYc4YXMEqPLIuqqA9rOmvdv\\/QcL0miLcpc.0sukHWanMQpSMIy\",\"role\":\"Committee\",\"department\":1,\"reset_token_hash\":null,\"reset_token_expires_at\":null,\"game_id\":28,\"school_id\":8}', '{\"user_id\":\"109\",\"firstname\":\"Andre\",\"lastname\":\"De Guzman\",\"middleinitial\":\"N\",\"age\":\"22\",\"gender\":\"Male\",\"email\":\"an@gmail.com\",\"role\":\"Committee\",\"assign_game\":\"28\",\"department\":\"2\"}'),
(44, 'Users', 'DELETE', 103, 56, '2025-01-24 04:07:06', 'Deleted \"Jb De Guzman\" (Committee) from the \"SHS\" department for the \"Badminton\" game.', '{\"firstname\":\"Jb\",\"lastname\":\"De Guzman\",\"role\":\"Committee\",\"department\":2,\"game_id\":28,\"department_name\":\"SHS\",\"game_name\":\"Badminton\"}', NULL),
(45, 'Users', 'DELETE', 109, 56, '2025-01-24 04:07:08', 'Deleted \"Andre De Guzman\" (Committee) from the \"SHS\" department for the \"Badminton\" game.', '{\"firstname\":\"Andre\",\"lastname\":\"De Guzman\",\"role\":\"Committee\",\"department\":2,\"game_id\":28,\"department_name\":\"SHS\",\"game_name\":\"Badminton\"}', NULL),
(46, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:13:40', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(47, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:18:38', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(48, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:19:54', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(49, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:21:47', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(50, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:24:24', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(51, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:27:28', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(52, 'leaderboard', 'RESET', NULL, 56, '2025-01-24 04:32:02', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(53, 'brackets', 'UPDATE', NULL, 56, '2025-01-24 04:58:49', 'Resets the Badminton College department', NULL, NULL),
(54, 'brackets', 'RESET', NULL, 56, '2025-01-24 04:59:36', 'Resets the Word Factory SHS department at grade level Grade 11', NULL, NULL),
(55, 'departments', 'CREATE', 99, 56, '2025-01-24 05:24:48', 'Added College Department: Course Name = test', NULL, NULL),
(56, 'departments', 'CREATE', 100, 56, '2025-01-24 05:25:06', 'Added SHS Department: Grade Level = Grade 11, Section = mm, Strand = STEM', NULL, NULL),
(57, 'departments', 'CREATE', 101, 56, '2025-01-24 05:27:13', 'Added JHS Department: Grade Level = Grade 8, Section = ss - Teams have been automatically created for all existing games.', NULL, NULL),
(58, 'departments', 'CREATE', 102, 56, '2025-01-24 05:43:49', ' - Teams have been automatically created for all existing games.', NULL, NULL),
(59, 'departments', 'CREATE', 103, 56, '2025-01-24 05:46:45', 'Added JHS Department: Grade Level = Grade 8, Section = ddd - Teams have been automatically created for all existing games.', NULL, NULL),
(60, 'grade_section_course', 'DELETE', 103, NULL, '2025-01-24 06:03:05', 'Attempting to delete course/section with ID: 103', NULL, NULL),
(61, 'grade_section_course', 'DELETE', 103, NULL, '2025-01-24 06:03:05', 'Successfully deleted course/section with ID: 103', NULL, NULL),
(62, 'grade_section_course', 'DELETE', 102, NULL, '2025-01-24 06:04:24', 'Attempting to delete course/section with ID: 102', NULL, NULL),
(63, 'grade_section_course', 'DELETE', 102, NULL, '2025-01-24 06:04:24', 'Successfully deleted course/section with ID: 102', NULL, NULL),
(64, 'departments', 'CREATE', 104, 56, '2025-01-24 06:09:38', 'Added JHS Department: Grade Level = Grade 9, Section = testing - Teams have been automatically created for all existing games.', NULL, NULL),
(65, 'departments', 'DELETE', 104, NULL, '2025-01-24 06:09:47', 'Deleted JHS Department: Grade Level = Grade 9, Section = testing', NULL, NULL),
(66, 'departments', 'CREATE', 105, 56, '2025-01-24 06:10:12', 'Added JHS Department: Grade Level = Grade 8, Section = awd - Teams have been automatically created for all existing games.', NULL, NULL),
(67, 'departments', 'DELETE', 73, NULL, '2025-01-24 06:10:15', 'Deleted JHS Department: Grade Level = Grade 9, Section = Nickel', NULL, NULL),
(68, 'departments', 'CREATE', 106, 56, '2025-01-24 06:11:29', 'Added JHS Department: Grade Level = Grade 9, Section = sdawda - Teams have been automatically created for all existing games.', NULL, NULL),
(69, 'departments', 'DELETE', 106, 56, '2025-01-24 06:11:45', 'Deleted JHS Department: Grade Level = Grade 9, Section = sdawda', NULL, NULL),
(70, 'departments', 'CREATE', 107, 56, '2025-01-24 06:53:48', 'Added JHS Department: Grade Level = Grade 9, Section = asas - Teams have been automatically created for all existing games.', NULL, NULL),
(73, 'grade_section_course', 'UPDATE', 105, 56, '2025-01-24 07:04:07', 'Updated grade section/course', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"aaaaa\",\"course_name\":null,\"strand\":null}', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"aaa\",\"course_name\":null,\"strand\":null}'),
(74, 'grade_section_course', 'UPDATE', 105, 56, '2025-01-24 07:07:33', 'Updated grade section/course', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"aaaa\",\"course_name\":null,\"strand\":null}', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"aaa\",\"course_name\":null,\"strand\":null}'),
(75, 'grade_section_course', 'UPDATE', 105, 56, '2025-01-24 07:08:14', 'Updated grade section/course', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"aaa\",\"course_name\":null,\"strand\":null}', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"johndoe\",\"course_name\":null,\"strand\":null}'),
(76, 'grade_section_course', 'UPDATE', 105, 56, '2025-01-24 07:10:27', 'Section name changed from \'johndoe\' to \'johndoes\'. ', '{\"department_id\":3,\"grade_level\":\"Grade 8\",\"section_name\":\"johndoe\",\"course_name\":null,\"strand\":null}', '{\"section_name\":\"johndoes\",\"grade_level\":\"Grade 8\",\"strand\":null}'),
(77, 'grade_section_course', 'UPDATE', 77, 56, '2025-01-24 07:10:53', 'Course name changed from \'EDUC?\' to \'EDUC\'. ', '{\"department_id\":1,\"grade_level\":null,\"section_name\":null,\"course_name\":\"EDUC?\",\"strand\":null}', '{\"course_name\":\"EDUC\"}'),
(78, 'grade_section_course', 'UPDATE', 70, 56, '2025-01-24 07:11:13', 'Strand changed from \'STEM\' to \'STEMs\'. ', '{\"department_id\":2,\"grade_level\":\"Grade 11\",\"section_name\":\"11-A\",\"course_name\":null,\"strand\":\"STEM\"}', '{\"section_name\":\"11-A\",\"grade_level\":\"Grade 11\",\"strand\":\"STEMs\"}'),
(79, 'grade_section_course', 'UPDATE', 70, 56, '2025-01-24 07:11:43', 'Section name changed from \'11-A\' to \'11-Aa\'. Strand changed from \'STEMs\' to \'STEMss\'. ', '{\"department_id\":2,\"grade_level\":\"Grade 11\",\"section_name\":\"11-A\",\"course_name\":null,\"strand\":\"STEMs\"}', '{\"section_name\":\"11-Aa\",\"grade_level\":\"Grade 11\",\"strand\":\"STEMss\"}'),
(80, 'games', 'CREATE', 38, 56, '2025-01-24 07:46:33', 'Added a new game: Name = \'test\', Number of Players = 2, Category = \'Team Sports\', Environment = \'Indoor\', School ID = 8.', NULL, NULL),
(81, 'games', 'CREATE', 39, 56, '2025-01-24 07:47:31', 'Added a new game: Name = \'testt\', Number of Players = 3, Category = \'Team Sports\', Environment = \'Indoor\'.', NULL, NULL),
(82, 'games', 'DELETE', 39, 56, '2025-01-24 07:48:39', 'Deleted game: Name = \'testt\', Number of Players = 3, Category = \'Team Sports\', Environment = \'Indoor\', School ID = 8.', NULL, NULL),
(83, 'games', 'UPDATE', 38, 56, '2025-01-24 07:50:50', 'Updated game (ID: 38): Number of Players: \'2\' → \'3\'', NULL, NULL),
(84, 'games', 'UPDATE', 38, 56, '2025-01-24 07:52:23', 'Updated game \'test\': Number of Players: \'3\' → \'2\'', NULL, NULL),
(85, 'games', 'UPDATE', 38, 56, '2025-01-24 07:52:32', 'Updated game \'test\': Game Name: \'test\' → \'tests\'', NULL, NULL),
(86, 'games', 'UPDATE', 38, 56, '2025-01-24 07:52:42', 'Updated game \'tests\': Number of Players: \'2\' → \'3\', Category: \'Team Sports\' → \'Individual Sports\'', NULL, NULL),
(87, 'games', 'DELETE', 38, 56, '2025-01-24 07:52:53', 'Deleted game: Name = \'tests\', Number of Players = 3, Category = \'Individual Sports\', Environment = \'Indoor\'.', NULL, NULL),
(88, 'schedules', 'CREATE', 420, 67, '2025-01-25 05:25:00', 'To Be Determined vs BSCS - Basketball - Basketball | Scheduled on 2025-01-28 at 01:00:00, Venue: Open Gym', NULL, '{\"match_id\":\"420\",\"schedule_date\":\"2025-01-28\",\"schedule_time\":\"01:00:00\",\"venue\":\"Open Gym\"}'),
(89, 'schedules', 'CREATE', 421, 67, '2025-01-25 05:29:20', 'To Be Determined vs To Be Determined - System | Scheduled on 2025-01-25 at 07:00:00, Venue: Open Gym', NULL, NULL),
(90, 'schedules', 'UPDATE', 98, 67, '2025-01-25 05:32:09', 'Modified the schedule for To Be Determined vs To Be Determined - System \n                            from 2025-01-25 at 07:00:00, Venue: School Gym \n                            to 2025-01-25 at 08:00, Venue: School Gym', NULL, NULL),
(91, 'schedules', 'DELETE', 93, 67, '2025-01-25 05:35:52', 'Canceled schedule for Match #416: BSA - Basketball vs BSBA - Basketball (Basketball) scheduled on 2025-01-25 at 8:00 AM, Venue: Closed Gym', NULL, NULL),
(92, 'schedules', 'SEND_SMS', 94, NULL, '2025-01-25 06:51:16', 'Notified players for match BSHM - Basketball vs CRIM - Basketball. Total number of players notified is 1.', NULL, NULL),
(93, 'schedules', 'Player Notification', 94, NULL, '2025-01-25 06:51:51', 'Notified players for match BSHM - Basketball vs CRIM - Basketball. Total number of players notified is 1.', NULL, NULL),
(94, 'schedules', 'UPDATE', 98, 67, '2025-01-25 06:53:02', 'Modified the schedule for To Be Determined vs To Be Determined - System \n                            from 2025-01-25 at 08:00:00, Venue: School Gym \n                            to 2025-01-25 at 04:00, Venue: School Gym', NULL, NULL),
(95, 'schedules', 'Player Notification', 95, 67, '2025-01-25 06:57:05', 'Notified players for match EDUC - Basketball vs testtest - Basketball. Total number of players notified is 1.', NULL, NULL),
(96, 'announcements', 'UPDATE', 29, 56, '2025-01-25 07:01:54', 'Edited content. ', NULL, NULL),
(97, 'announcements', 'CREATE', 49, 56, '2025-01-25 07:02:13', 'Added Announcement titled \"awd\"', NULL, NULL),
(98, 'announcements', 'DELETE', 49, 56, '2025-01-25 07:02:45', 'Deleted Announcement titled \"awd\"', NULL, NULL),
(99, 'Users', 'UPDATE', 67, 67, '2025-01-25 07:53:29', 'Updated user profile details.', NULL, NULL),
(100, 'Users', 'UPDATE', 67, 67, '2025-01-25 07:53:34', 'Updated user profile details.', NULL, NULL),
(101, 'Users', 'UPDATE', 67, 67, '2025-01-25 07:53:42', 'Updated user profile details.', NULL, NULL),
(102, 'Users', 'UPDATE', 67, 67, '2025-01-25 07:53:52', 'Updated user profile details.', NULL, NULL),
(103, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:55:54', 'Updated user profile details.', NULL, NULL),
(104, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:56:00', 'Updated user profile details.', NULL, NULL),
(105, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:56:10', 'Updated user profile details.', NULL, NULL),
(106, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:57:48', 'Updated user profile details.', NULL, NULL),
(107, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:57:49', 'Updated user profile details.', NULL, NULL),
(108, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:57:51', 'Updated user profile details.', NULL, NULL),
(109, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:57:52', 'Updated user profile details.', NULL, NULL),
(110, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:57:53', 'Updated user profile details.', NULL, NULL),
(111, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:57:54', 'Updated user profile details.', NULL, NULL),
(112, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:58:02', 'Updated user profile details.', NULL, NULL),
(113, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:58:11', 'Updated user profile details.', NULL, NULL),
(114, 'Profile', 'UPDATE', 67, 67, '2025-01-25 07:58:14', 'Updated user profile details.', NULL, NULL),
(115, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:32', 'Updated user profile details.', NULL, NULL),
(116, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:52', 'Updated user profile details.', NULL, NULL),
(117, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:57', 'Updated user profile details.', NULL, NULL),
(118, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:57', 'Updated user profile details.', NULL, NULL),
(119, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:58', 'Updated user profile details.', NULL, NULL),
(120, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:59', 'Updated user profile details.', NULL, NULL),
(121, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:01:59', 'Updated user profile details.', NULL, NULL),
(122, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:03:37', 'Updated user profile details.', NULL, NULL),
(123, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:05:21', 'Updated user profile details.', NULL, NULL),
(124, 'Profile', 'UPDATE', 67, 67, '2025-01-25 08:05:30', 'Updated user profile details.', NULL, NULL),
(125, 'brackets', 'CREATE', 93, 67, '2025-01-25 10:24:01', 'Created a bracket for Game ID: 18, Department ID: 1, Bracket Type: single, Total Teams: 8, Rounds: 3.', NULL, NULL),
(126, 'brackets', 'CREATE', 95, 67, '2025-01-25 10:28:15', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 8.', NULL, NULL),
(127, 'Brackets', 'DELETE', 93, 67, '2025-01-25 10:39:54', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(128, 'Brackets', 'DELETE', 94, 67, '2025-01-25 10:41:08', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(129, 'Team', 'CREATE', 99, 67, '2025-01-25 12:02:00', 'Registered team \'aa\'', NULL, NULL),
(130, 'Brackets', 'CREATE', 98, 67, '2025-01-26 05:57:36', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(131, 'Team', 'CREATE', 99, 67, '2025-01-26 05:58:10', 'Registered team \'9th\'', NULL, NULL),
(132, 'Brackets', 'DELETE', 98, 67, '2025-01-27 14:46:12', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(133, 'Brackets', 'DELETE', 95, 67, '2025-01-27 14:46:17', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(134, 'Players', 'Register', 256, 67, '2025-01-27 15:02:31', 'Registered player Doe, John to team 256', NULL, NULL),
(135, 'Players', 'Register', 256, 67, '2025-01-27 15:13:39', 'Registered player Doe, Jane to team \'BSA - Basketball\'', NULL, NULL),
(136, 'Players', 'Register', 256, 67, '2025-01-27 15:21:02', 'Removed player ,  from team \'\'', NULL, NULL),
(137, 'Players', 'Delete', 256, 67, '2025-01-27 15:22:44', 'Removed player Doe, John from team \'BSA - Basketball\'', NULL, NULL),
(138, 'Players', 'Update', 226, NULL, '2025-01-27 15:34:02', 'Updated player details for Bryant, Kobe. Changes: {\"Old Data\":{\"player_id\":226,\"player_lastname\":\"Bryant\",\"player_firstname\":\"Kobe\",\"player_middlename\":\"B.\",\"team_id\":256,\"created_at\":\"2024-11-03 04:10:00\",\"jersey_number\":22,\"player_info_id\":3,\"email\":\"Kobe@gmail.com\",\"phone_number\":\"+63999999999\",\"date_of_birth\":\"2024-10-24\",\"picture\":\"..\\/uploads\\/players\\/kobe.jpg\",\"height\":\"6&#039;6\",\"weight\":\"250lbs\",\"position\":\"Shooting Guard\"},\"New Data\":{\"player_lastname\":\"Bryant\",\"player_firstname\":\"Kobe\",\"player_middlename\":\"D.\",\"jersey_number\":22,\"email\":\"Kobe@gmail.com\",\"phone_number\":\"+63999999999\",\"date_of_birth\":\"2024-10-24\",\"height\":\"6&#039;6\",\"weight\":\"250lbs\",\"position\":\"Shooting Guard\",\"picture\":\"..\\/uploads\\/players\\/kobe.jpg\"}}', NULL, NULL),
(139, 'Players', 'Update', 226, NULL, '2025-01-27 15:41:09', 'Updated player details for Bryant, Kobe. Changes: {\"player_middlename\":{\"old\":\"D.\",\"new\":\"b\"}}', NULL, NULL),
(140, 'Players', 'Update', 226, NULL, '2025-01-27 15:42:01', 'Updated player details for Bryant, Kobe. Changes: {\"player_middlename\":{\"old\":\"b\",\"new\":\"D.\"}}', NULL, NULL),
(141, 'Players', 'Update', 226, 67, '2025-01-27 15:43:21', 'Updated player details for Bryant, Kobe. Changes: {\"player_middlename\":{\"old\":\"D.\",\"new\":\"B.\"}}', NULL, NULL),
(142, 'Brackets', 'CREATE', 99, 67, '2025-01-27 16:01:05', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(143, 'Brackets', 'DELETE', 99, 67, '2025-01-28 05:56:35', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(144, 'departments', 'CREATE', 108, 56, '2025-01-28 05:57:35', 'Added College Department: Course Name = test1 - Teams have been automatically created for all existing games.', NULL, NULL),
(145, 'departments', 'CREATE', 109, 56, '2025-01-28 05:57:46', 'Added College Department: Course Name = test2 - Teams have been automatically created for all existing games.', NULL, NULL),
(146, 'departments', 'DELETE', 108, 56, '2025-01-28 05:58:40', 'Deleted College Department: Course Name = test1', NULL, NULL),
(147, 'departments', 'CREATE', 110, 56, '2025-01-28 05:58:57', 'Added College Department: Course Name = testing - Teams have been automatically created for all existing games.', NULL, NULL),
(148, 'departments', 'CREATE', 111, 56, '2025-01-28 05:59:08', 'Added College Department: Course Name = testn - Teams have been automatically created for all existing games.', NULL, NULL),
(149, 'Game Stats', 'DELETE', NULL, 67, '2025-01-28 11:25:33', 'Removed Game Stat \'Scores\' (ID: 17) for Game ID: ', NULL, NULL),
(150, 'Game Stats', 'DELETE', 19, 67, '2025-01-28 11:30:24', 'Removed Game Stat \'Assists\' for Game: \'Basketball\' (Game ID: 18)', NULL, NULL),
(151, 'Game Stats', 'CREATE', 23, 67, '2025-01-28 11:43:28', 'Added Game Stat \'Assists\' for Game: \'Basketball\'', NULL, NULL),
(152, 'departments', 'DELETE', 99, 56, '2025-01-29 02:10:13', 'Deleted College Department: Course Name = testtest', NULL, NULL),
(153, 'departments', 'DELETE', 111, 56, '2025-01-29 02:10:17', 'Deleted College Department: Course Name = testn', NULL, NULL),
(154, 'Brackets', 'CREATE', 100, 67, '2025-01-29 02:10:33', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(155, 'schedules', 'CREATE', 482, 67, '2025-01-29 02:13:18', 'EDUC - Basketball vs BSA - Basketball - Basketball | Scheduled on 2025-01-29 at 02:20:00, Venue: Gym', NULL, NULL),
(156, 'schedules', 'CREATE', 483, 67, '2025-01-29 02:14:17', 'BSBA - Basketball vs BSCS - Basketball - Basketball | Scheduled on 2025-01-29 at 02:20:00, Venue: School Gym', NULL, NULL),
(157, 'departments', 'CREATE', 112, 56, '2025-01-29 03:51:06', 'Added College Department: Course Name = 1th - Teams have been automatically created for all existing games.', NULL, NULL),
(158, 'departments', 'CREATE', 113, 56, '2025-01-29 03:51:14', 'Added College Department: Course Name = nnn - Teams have been automatically created for all existing games.', NULL, NULL),
(159, 'departments', 'CREATE', 114, 56, '2025-01-29 03:51:33', 'Added College Department: Course Name = ff - Teams have been automatically created for all existing games.', NULL, NULL),
(160, 'departments', 'CREATE', 115, 56, '2025-01-29 03:52:32', 'Added College Department: Course Name = ll - Teams have been automatically created for all existing games.', NULL, NULL),
(161, 'departments', 'CREATE', 116, 56, '2025-01-29 04:09:43', 'Added College Department: Course Name = was - Teams have been automatically created for all existing games.', NULL, NULL),
(162, 'departments', 'CREATE', 117, 56, '2025-01-29 04:09:55', 'Added College Department: Course Name = adwada - Teams have been automatically created for all existing games.', NULL, NULL),
(163, 'departments', 'CREATE', 118, 56, '2025-01-29 04:10:06', 'Added College Department: Course Name = awd - Teams have been automatically created for all existing games.', NULL, NULL),
(164, 'departments', 'CREATE', 119, 56, '2025-01-29 04:10:28', 'Added College Department: Course Name = wdawd - Teams have been automatically created for all existing games.', NULL, NULL),
(165, 'departments', 'CREATE', 120, 56, '2025-01-29 04:10:36', 'Added College Department: Course Name = wdadwad - Teams have been automatically created for all existing games.', NULL, NULL),
(166, 'schedules', 'UPDATE', 99, 67, '2025-01-29 05:21:35', 'Modified the schedule for EDUC - Basketball vs BSA - Basketball - Basketball \n                            from 2025-01-29 at 02:20:00, Venue: Gym \n                            to 2025-01-29 at 05:30, Venue: Gym', NULL, NULL),
(167, 'Live Scores', 'CREATE', 99, 67, '2025-01-29 05:21:43', 'Started the match between BSA - Basketball vs EDUC - Basketball', NULL, NULL),
(168, 'departments', 'DELETE', 112, 56, '2025-01-29 17:04:38', 'Deleted College Department: Course Name = 1th', NULL, NULL),
(169, 'Brackets', 'DELETE', 100, 67, '2025-01-29 17:11:44', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(170, 'departments', 'DELETE', 117, 56, '2025-01-29 17:19:52', 'Deleted College Department: Course Name = adwada', NULL, NULL),
(171, 'departments', 'DELETE', 118, 56, '2025-01-29 17:20:08', 'Deleted College Department: Course Name = awd', NULL, NULL),
(172, 'departments', 'DELETE', 119, 56, '2025-01-29 17:20:22', 'Deleted College Department: Course Name = wdawd', NULL, NULL),
(173, 'departments', 'DELETE', 120, 56, '2025-01-29 17:20:34', 'Deleted College Department: Course Name = wdadwad', NULL, NULL),
(174, 'departments', 'DELETE', 116, 56, '2025-01-29 17:20:43', 'Deleted College Department: Course Name = was', NULL, NULL),
(175, 'departments', 'DELETE', 110, 56, '2025-01-29 17:20:52', 'Deleted College Department: Course Name = testing', NULL, NULL),
(176, 'departments', 'DELETE', 109, 56, '2025-01-29 17:21:10', 'Deleted College Department: Course Name = test2', NULL, NULL),
(177, 'departments', 'DELETE', 113, 56, '2025-01-29 17:21:18', 'Deleted College Department: Course Name = nnn', NULL, NULL),
(178, 'Brackets', 'CREATE', 115, 67, '2025-01-29 17:21:23', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(179, 'Brackets', 'CREATE', 116, 67, '2025-01-29 17:30:28', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(180, 'Brackets', 'DELETE', 116, 67, '2025-01-29 17:30:32', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(181, 'Brackets', 'DELETE', 115, 67, '2025-01-29 17:30:38', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(182, 'Brackets', 'CREATE', 117, 67, '2025-01-29 17:30:41', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(183, 'Brackets', 'DELETE', 117, 67, '2025-01-29 17:31:08', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(184, 'Brackets', 'CREATE', 118, 67, '2025-01-29 17:31:12', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(185, 'Brackets', 'DELETE', 118, 67, '2025-01-29 17:32:03', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(186, 'Brackets', 'CREATE', 119, 67, '2025-01-29 17:32:06', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(187, 'Brackets', 'CREATE', 120, 67, '2025-01-29 17:35:39', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 7.', NULL, NULL),
(188, 'departments', 'CREATE', 121, 56, '2025-01-29 17:35:57', 'Added College Department: Course Name = asasa - Teams have been automatically created for all existing games.', NULL, NULL),
(189, 'Brackets', 'DELETE', 119, 67, '2025-01-29 17:37:11', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(190, 'departments', 'CREATE', 122, 56, '2025-01-29 17:42:01', 'Added College Department: Course Name = wda - Teams have been automatically created for all existing games.', NULL, NULL),
(191, 'Brackets', 'CREATE', 131, 67, '2025-01-29 18:15:10', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(192, 'Brackets', 'DELETE', 120, 67, '2025-01-29 18:15:46', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(193, 'Brackets', 'CREATE', 136, 67, '2025-01-29 18:29:51', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(194, 'Brackets', 'DELETE', 131, 67, '2025-01-29 18:31:42', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(195, 'schedules', 'CREATE', 708, 67, '2025-01-29 18:32:05', 'BSA - Basketball vs wda - Basketball - Basketball | Scheduled on 2025-01-29 at 18:38:00, Venue: Open Gym', NULL, NULL),
(196, 'Matches', 'Match Start', 101, 67, '2025-01-29 18:32:11', 'Started the match between BSA - Basketball vs wda - Basketball', NULL, NULL),
(197, 'Brackets', 'DELETE', 136, 67, '2025-01-29 18:41:53', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(198, 'Brackets', 'CREATE', 137, 67, '2025-01-29 18:41:56', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(199, 'schedules', 'CREATE', 725, 67, '2025-01-29 18:43:14', 'll - Basketball vs asasa - Basketball - Basketball | Scheduled on 2025-01-29 at 19:00:00, Venue: gym', NULL, NULL),
(200, 'Matches', 'Match Start', 102, 67, '2025-01-29 18:43:20', 'Started the match between ll - Basketball vs asasa - Basketball', NULL, NULL),
(201, 'Brackets', 'DELETE', 137, 67, '2025-01-29 18:48:45', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(202, 'Brackets', 'CREATE', 138, 67, '2025-01-29 18:48:52', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(203, 'Brackets', 'DELETE', 138, 67, '2025-01-29 18:51:00', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(204, 'Brackets', 'CREATE', 139, 67, '2025-01-29 18:51:04', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(205, 'Brackets', 'DELETE', 139, 67, '2025-01-29 18:52:30', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(206, 'Brackets', 'CREATE', 140, 67, '2025-01-29 18:52:33', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(207, 'Brackets', 'DELETE', 140, 67, '2025-01-29 18:56:22', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(208, 'Brackets', 'CREATE', 141, 67, '2025-01-29 18:56:40', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(209, 'Brackets', 'DELETE', 141, 67, '2025-01-29 18:57:22', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(210, 'Brackets', 'CREATE', 142, 67, '2025-01-29 18:57:31', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(211, 'Brackets', 'DELETE', 142, 67, '2025-01-29 19:01:01', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(212, 'Brackets', 'CREATE', 143, 67, '2025-01-29 19:01:10', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(213, 'Brackets', 'DELETE', 143, 67, '2025-01-29 19:06:06', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(214, 'Brackets', 'CREATE', 144, 67, '2025-01-29 19:06:10', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(215, 'Brackets', 'CREATE', 153, 67, '2025-01-29 22:05:09', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(216, 'Brackets', 'CREATE', 154, 67, '2025-01-29 22:13:06', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(217, 'Brackets', 'CREATE', 155, 67, '2025-01-29 22:14:31', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(218, 'Brackets', 'CREATE', 156, 67, '2025-01-29 22:21:44', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(219, 'Brackets', 'CREATE', 157, 67, '2025-01-29 22:24:56', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(220, 'Brackets', 'CREATE', 158, 67, '2025-01-29 22:26:43', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(221, 'Brackets', 'CREATE', 159, 67, '2025-01-29 22:27:04', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(222, 'Brackets', 'CREATE', 160, 67, '2025-01-29 22:29:28', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(223, 'Brackets', 'CREATE', 161, 67, '2025-01-29 22:29:49', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(224, 'Brackets', 'CREATE', 162, 67, '2025-01-29 22:36:46', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(225, 'Brackets', 'CREATE', 163, 67, '2025-01-29 22:39:16', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(226, 'Brackets', 'CREATE', 164, 67, '2025-01-29 22:39:58', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(227, 'Brackets', 'CREATE', 165, 67, '2025-01-29 22:42:41', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(228, 'Brackets', 'CREATE', 166, 67, '2025-01-29 22:53:12', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(229, 'Brackets', 'CREATE', 167, 67, '2025-01-29 22:53:14', 'Created a bracket for Game: Basketball, Department: College, Total Teams: -1.', NULL, NULL),
(230, 'departments', 'CREATE', 123, 56, '2025-01-29 23:02:22', 'Added College Department: Course Name = adwawd - Teams have been automatically created for all existing games.', NULL, NULL),
(231, 'departments', 'CREATE', 124, 56, '2025-01-29 23:02:50', 'Added College Department: Course Name = awdawda - Teams have been automatically created for all existing games.', NULL, NULL),
(232, 'Brackets', 'DELETE', 178, 67, '2025-01-30 01:24:21', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(233, 'Brackets', 'DELETE', 177, 67, '2025-01-30 01:24:25', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(234, 'Brackets', 'DELETE', 176, 67, '2025-01-30 01:24:27', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(235, 'Brackets', 'DELETE', 183, 67, '2025-01-30 02:26:28', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(236, 'Brackets', 'DELETE', 184, 67, '2025-01-30 02:26:30', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(237, 'Brackets', 'DELETE', 185, 67, '2025-01-30 02:26:33', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(238, 'Brackets', 'DELETE', 186, 67, '2025-01-30 02:26:57', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(239, 'Brackets', 'DELETE', 187, 67, '2025-01-30 02:27:00', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(240, 'Brackets', 'Create', 0, 67, '2025-01-30 02:29:27', NULL, NULL, NULL),
(241, 'Brackets', 'Create', 190, 67, '2025-01-30 02:31:11', 'Created new bracket', NULL, NULL),
(242, 'Brackets', 'DELETE', 190, 67, '2025-01-30 02:31:26', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(243, 'Brackets', 'DELETE', 189, 67, '2025-01-30 02:31:29', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(244, 'Brackets', 'DELETE', 188, 67, '2025-01-30 02:31:31', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(245, 'Brackets', 'Create', 191, 67, '2025-01-30 02:31:35', 'Created new bracket', NULL, NULL),
(246, 'Brackets', 'Create', 192, 67, '2025-01-30 02:35:43', 'Created new bracket', NULL, NULL),
(247, 'Brackets', 'DELETE', 191, 67, '2025-01-30 02:36:06', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(248, 'Brackets', 'DELETE', 192, 67, '2025-01-30 02:37:11', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(249, 'Brackets', 'Create', 193, 67, '2025-01-30 02:37:14', 'Created new bracket', NULL, NULL),
(250, 'Brackets', 'DELETE', 193, 67, '2025-01-30 02:38:45', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(251, 'Brackets', 'Create', 194, 67, '2025-01-30 02:38:50', 'Created new bracket', NULL, NULL),
(252, 'Brackets', 'DELETE', 194, 67, '2025-01-30 02:43:25', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(253, 'Brackets', 'Create', 195, 67, '2025-01-30 02:43:30', 'Created new bracket', NULL, NULL),
(254, 'Brackets', 'DELETE', 195, 67, '2025-01-30 02:49:34', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(255, 'Brackets', 'Create', 200, 67, '2025-01-30 02:51:30', 'Created new bracket', NULL, NULL),
(256, 'Brackets', 'DELETE', 200, 67, '2025-01-30 02:54:12', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(257, 'Brackets', 'Create', 201, 67, '2025-01-30 02:54:16', 'Created new bracket', NULL, NULL),
(258, 'Brackets', 'DELETE', 201, 67, '2025-01-30 02:57:36', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL);
INSERT INTO `logs` (`log_id`, `table_name`, `operation`, `record_id`, `user_id`, `timestamp`, `description`, `previous_data`, `new_data`) VALUES
(259, 'Brackets', 'Create', 202, 67, '2025-01-30 02:57:39', 'Created new bracket', NULL, NULL),
(260, 'Brackets', 'DELETE', 202, 67, '2025-01-30 02:59:32', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(261, 'Brackets', 'Create', 203, 67, '2025-01-30 02:59:34', 'Created new bracket', NULL, NULL),
(262, 'Brackets', 'DELETE', 203, 67, '2025-01-30 03:01:19', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(263, 'Brackets', 'Create', 204, 67, '2025-01-30 03:08:51', 'Created new bracket', NULL, NULL),
(264, 'Brackets', 'DELETE', 204, 67, '2025-01-30 03:09:16', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(265, 'Brackets', 'Create', 205, 67, '2025-01-30 03:09:45', 'Created new bracket', NULL, NULL),
(266, 'Brackets', 'Create', 206, 67, '2025-01-30 03:14:32', 'Created new bracket', NULL, NULL),
(267, 'Brackets', 'CREATE', 207, 67, '2025-01-30 03:38:20', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(268, 'Brackets', 'DELETE', 205, 67, '2025-01-30 03:38:57', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(269, 'Brackets', 'DELETE', 206, 67, '2025-01-30 03:39:00', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(270, 'Brackets', 'DELETE', 207, 67, '2025-01-30 03:45:36', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(271, 'Brackets', 'DELETE', 210, 67, '2025-01-30 03:48:53', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(272, 'Brackets', 'CREATE', 222, 67, '2025-01-30 04:32:20', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(273, 'Brackets', 'DELETE', 222, 67, '2025-01-30 04:33:55', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(274, 'Brackets', 'CREATE', 223, 67, '2025-01-30 04:34:03', 'Created a bracket for Game: Basketball, Department: College, Total Teams: 15.', NULL, NULL),
(275, 'Brackets', 'DELETE', 223, 67, '2025-01-30 04:39:45', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(276, 'Brackets', 'DELETE', 226, 67, '2025-01-30 05:10:04', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(277, 'Brackets', 'DELETE', 228, 67, '2025-01-30 06:27:24', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(278, 'Brackets', 'DELETE', 229, 67, '2025-01-30 06:51:01', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(279, 'Brackets', 'DELETE', 243, 67, '2025-01-30 07:14:09', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(280, 'Brackets', 'DELETE', 242, 67, '2025-01-30 07:14:15', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(281, 'Brackets', 'DELETE', 244, 67, '2025-01-30 07:22:32', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(282, 'Brackets', 'DELETE', 245, 67, '2025-01-30 07:36:26', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(283, 'Brackets', 'DELETE', 246, 67, '2025-01-30 07:42:29', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(284, 'Brackets', 'DELETE', 247, 67, '2025-01-30 07:42:44', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(285, 'Brackets', 'DELETE', 248, 67, '2025-01-30 08:53:53', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(286, 'Brackets', 'DELETE', 249, 67, '2025-01-30 08:56:07', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(287, 'Brackets', 'DELETE', 250, 67, '2025-01-30 08:59:05', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(288, 'Brackets', 'DELETE', 251, 67, '2025-01-30 09:01:14', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(289, 'Brackets', 'DELETE', 252, 67, '2025-01-30 09:03:12', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(290, 'Brackets', 'DELETE', 253, 67, '2025-01-30 09:03:46', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(291, 'Brackets', 'DELETE', 256, 67, '2025-01-30 09:04:07', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(292, 'Brackets', 'DELETE', 257, 67, '2025-01-30 09:06:30', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(293, 'Brackets', 'DELETE', 258, 67, '2025-01-30 09:10:14', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(294, 'Brackets', 'DELETE', 259, 67, '2025-01-30 09:13:21', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(295, 'Brackets', 'DELETE', 260, 67, '2025-01-30 09:16:04', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(296, 'Brackets', 'DELETE', 261, 67, '2025-01-30 09:19:55', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(297, 'Brackets', 'DELETE', 262, 67, '2025-01-30 09:23:33', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(298, 'Brackets', 'DELETE', 263, 67, '2025-01-30 09:26:47', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(299, 'Brackets', 'DELETE', 264, 67, '2025-01-30 09:29:21', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(300, 'Brackets', 'DELETE', 265, 67, '2025-01-30 09:33:05', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(301, 'Brackets', 'DELETE', 266, 67, '2025-01-30 09:34:08', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(302, 'Brackets', 'DELETE', 267, 67, '2025-01-30 09:39:23', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(303, 'Brackets', 'DELETE', 268, 67, '2025-01-30 09:42:28', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(304, 'Brackets', 'DELETE', 269, 67, '2025-01-30 09:55:26', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(305, 'Brackets', 'DELETE', 270, 67, '2025-01-30 09:56:04', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(306, 'Brackets', 'DELETE', 276, 67, '2025-01-30 12:58:41', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(307, 'Brackets', 'DELETE', 277, 67, '2025-01-30 12:59:04', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(308, 'Brackets', 'DELETE', 278, 67, '2025-01-30 13:00:59', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(309, 'Brackets', 'DELETE', 279, 67, '2025-01-30 13:02:00', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(310, 'Brackets', 'DELETE', 280, 67, '2025-01-30 13:02:24', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(311, 'Brackets', 'DELETE', 281, 67, '2025-01-30 13:05:13', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(312, 'Brackets', 'DELETE', 282, 67, '2025-01-30 13:13:16', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(313, 'Brackets', 'DELETE', 283, 67, '2025-01-30 13:16:37', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(314, 'Brackets', 'DELETE', 284, 67, '2025-01-30 13:18:37', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(315, 'Brackets', 'DELETE', 285, 67, '2025-01-30 13:21:21', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(316, 'Brackets', 'DELETE', 286, 67, '2025-01-30 13:23:25', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(317, 'schedules', 'CREATE', 2042, 67, '2025-01-31 02:08:50', 'BSCS - Basketball vs ff - Basketball - Basketball | Scheduled on 2025-01-31 at 02:00:00, Venue: Gym', NULL, NULL),
(318, 'Brackets', 'DELETE', 287, 67, '2025-01-31 03:02:43', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(319, 'Brackets', 'CREATE', 292, NULL, '2025-01-31 03:02:48', 'Created a bracket', NULL, NULL),
(320, 'Brackets', 'DELETE', 292, 67, '2025-01-31 03:03:06', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(321, 'Brackets', 'DELETE', 293, 67, '2025-01-31 03:03:50', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(322, 'schedules', 'CREATE', 2094, 67, '2025-01-31 04:50:11', 'CRIM - Basketball vs BSCS - Basketball - Basketball | Scheduled on 2025-01-31 at 07:00:00, Venue: gym', NULL, NULL),
(323, 'schedules', 'UPDATE', 104, 67, '2025-01-31 05:00:10', 'Modified the schedule for CRIM - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 07:00:00, Venue: gym \n                            to 2025-01-31 at 05:00, Venue: gym', NULL, NULL),
(324, 'games', 'CREATE', 40, 56, '2025-01-31 17:58:48', 'Added a new game: Name = \'aededse\', Number of Players = 22, Category = \'Individual Sports\', Environment = \'Indoor\'.', NULL, NULL),
(325, 'games', 'DELETE', 40, 56, '2025-01-31 18:05:59', 'Deleted game: Name = \'aededse\', Number of Players = 22, Category = \'Individual Sports\', Environment = \'Indoor\'.', NULL, NULL),
(326, 'games', 'UPDATE', 30, 56, '2025-01-31 18:16:58', 'Updated game \'Word Factory\': Number of Players: \'2\' → \'4\'', NULL, NULL),
(327, 'announcements', 'CREATE', 50, 56, '2025-01-31 18:28:56', 'Added Announcement titled \"srfdfvdfv\"', NULL, NULL),
(328, 'announcements', 'UPDATE', 50, 56, '2025-01-31 18:29:05', 'Edited content. ', NULL, NULL),
(329, 'announcements', 'DELETE', 50, 56, '2025-01-31 18:29:13', 'Deleted Announcement titled \"srfdfvdfv\"', NULL, NULL),
(330, 'Pointing System', 'UPDATE', 8, 56, '2025-01-31 18:29:20', 'Updated third_place_points from 2 to 3. ', NULL, NULL),
(331, 'Brackets', 'DELETE', 294, 67, '2025-01-31 21:16:06', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(332, 'schedules', 'CREATE', 2110, 67, '2025-01-31 21:16:44', 'CRIM - Basketball vs EDUC - Basketball - Basketball | Scheduled on 2025-01-31 at 09:20:00, Venue: Gym', NULL, NULL),
(333, 'schedules', 'CREATE', 2111, 67, '2025-01-31 21:17:11', 'll - Basketball vs wda - Basketball - Basketball | Scheduled on 2025-01-31 at 21:20:00, Venue: Gym', NULL, NULL),
(334, 'schedules', 'DELETE', 105, 67, '2025-01-31 21:17:18', 'Canceled schedule for Match #2110: CRIM - Basketball vs EDUC - Basketball (Basketball) scheduled on 2025-01-31 at 9:20 AM, Venue: Gym', NULL, NULL),
(335, 'schedules', 'CREATE', 2110, 67, '2025-01-31 21:17:37', 'CRIM - Basketball vs EDUC - Basketball - Basketball | Scheduled on 2025-01-31 at 21:20:00, Venue: Gym', NULL, NULL),
(336, 'schedules', 'UPDATE', 106, 67, '2025-01-31 21:18:05', 'Modified the schedule for ll - Basketball vs wda - Basketball - Basketball \n                            from 2025-01-31 at 21:20:00, Venue: Gym \n                            to 2025-02-01 at 21:20, Venue: Gym', NULL, NULL),
(337, 'schedules', 'UPDATE', 106, 67, '2025-01-31 21:18:15', 'Modified the schedule for ll - Basketball vs wda - Basketball - Basketball \n                            from 2025-02-01 at 21:20:00, Venue: Gym \n                            to 2025-01-31 at 21:20, Venue: Gym', NULL, NULL),
(338, 'schedules', 'CREATE', 2116, 67, '2025-01-31 21:55:45', 'BSBA - Basketball vs BSCS - Basketball - Basketball | Scheduled on 2025-01-31 at 09:20:00, Venue: Gym', NULL, NULL),
(339, 'schedules', 'DELETE', 108, 67, '2025-01-31 21:55:52', 'Canceled schedule for Match #2116: BSBA - Basketball vs BSCS - Basketball (Basketball) scheduled on 2025-01-31 at 9:20 AM, Venue: Gym', NULL, NULL),
(340, 'schedules', 'CREATE', 2116, 67, '2025-01-31 21:56:24', 'BSBA - Basketball vs BSCS - Basketball - Basketball | Scheduled on 2025-01-31 at 21:40:00, Venue: Gym', NULL, NULL),
(341, 'schedules', 'UPDATE', 109, 67, '2025-01-31 21:58:39', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:40:00, Venue: Gym \n                            to 2025-01-31 at 21:58:39, Venue: Gym', NULL, NULL),
(342, 'schedules', 'UPDATE', 109, 67, '2025-01-31 21:58:42', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:58:39, Venue: Gym \n                            to 2025-01-31 at 21:58:42, Venue: Gym', NULL, NULL),
(343, 'schedules', 'UPDATE', 109, 67, '2025-01-31 21:58:49', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:58:42, Venue: Gym \n                            to 2025-01-31 at 21:58:49, Venue: Gym', NULL, NULL),
(344, 'schedules', 'UPDATE', 109, 67, '2025-01-31 21:58:50', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:58:49, Venue: Gym \n                            to 2025-01-31 at 21:58:50, Venue: Gym', NULL, NULL),
(345, 'schedules', 'UPDATE', 109, 67, '2025-01-31 21:59:22', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:58:50, Venue: Gym \n                            to 2025-01-31 at 21:59:22, Venue: Gym', NULL, NULL),
(346, 'schedules', 'UPDATE', 109, 67, '2025-01-31 21:59:27', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:59:22, Venue: Gym \n                            to 2025-01-31 at 21:59:27, Venue: Gym', NULL, NULL),
(347, 'schedules', 'UPDATE', 109, 67, '2025-01-31 22:12:33', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 21:59:27, Venue: Gym \n                            to 2025-01-31 at 22:20, Venue: Gym', NULL, NULL),
(348, 'schedules', 'UPDATE', 109, 67, '2025-01-31 22:20:47', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 22:20:00, Venue: Gym \n                            to 2025-01-31 at 22:25, Venue: Gym', NULL, NULL),
(349, 'schedules', 'UPDATE', 106, 67, '2025-01-31 22:27:00', 'Modified the schedule for ll - Basketball vs wda - Basketball - Basketball \n                            from 2025-01-31 at 9:20 PM, Venue: Gym \n                            to January 31, 2025 at 10:34 PM, Venue: Gym', NULL, NULL),
(350, 'schedules', 'CREATE', 2112, 67, '2025-01-31 22:27:34', 'Created schedule for match between ff - Basketball and asasa - Basketball', NULL, NULL),
(351, 'schedules', 'CREATE', 2113, 67, '2025-01-31 22:30:13', 'adwawd - Basketball vs BSA - Basketball - Basketball | Scheduled on January 31, 2025 at 7:00 PM, Venue: School Gym', NULL, NULL),
(352, 'Matches', 'Match Start', 106, 67, '2025-01-31 22:32:12', 'Started the match between ll - Basketball vs wda - Basketball', NULL, NULL),
(353, 'schedules', 'UPDATE', 107, 67, '2025-01-31 23:06:01', 'Modified the schedule for CRIM - Basketball vs EDUC - Basketball - Basketball \n                            from 2025-01-31 at 9:20 PM, Venue: Gym \n                            to January 31, 2025 at 11:20 PM, Venue: Gym', NULL, NULL),
(354, 'Matches', 'Match Start', 107, 67, '2025-01-31 23:06:30', 'Started the match between CRIM - Basketball vs EDUC - Basketball', NULL, NULL),
(355, 'Users', 'UPDATE', 67, 56, '2025-02-01 01:16:33', 'Updated user details for \"John Doe\" (Committee): Department: College → SHS', NULL, NULL),
(356, 'Brackets', 'DELETE', 305, 67, '2025-02-01 01:56:20', 'Deleted a bracket for Game: Basketball, Department: SHS', NULL, NULL),
(357, 'Brackets', 'DELETE', 304, 67, '2025-02-01 01:56:22', 'Deleted a bracket for Game: Basketball, Department: SHS', NULL, NULL),
(358, 'Brackets', 'DELETE', 303, 67, '2025-02-01 02:06:23', 'Deleted a bracket for Game: Basketball, Department: SHS', NULL, NULL),
(359, 'Brackets', 'DELETE', 306, 67, '2025-02-01 02:06:26', 'Deleted a bracket for Game: Basketball, Department: SHS', NULL, NULL),
(360, 'Users', 'UPDATE', 67, 56, '2025-02-01 02:07:26', 'Updated user details for \"John Doe\" (Committee): Department: SHS → College', NULL, NULL),
(361, 'Brackets', 'DELETE', 308, 67, '2025-02-01 02:08:27', 'Deleted a bracket for Game: Basketball, Department: College', NULL, NULL),
(362, 'sessions', 'Logged out', 56, 56, '2025-02-01 05:10:54', 'User Logged out', NULL, NULL),
(363, 'sessions', 'Logged in', 56, 56, '2025-02-01 14:54:04', 'User Logged in', NULL, NULL),
(364, 'sessions', 'Logged out', 56, 56, '2025-02-01 17:38:18', 'User Logged out', NULL, NULL),
(365, 'sessions', 'Logged in', 68, 68, '2025-02-01 17:38:25', 'User Logged in', NULL, NULL),
(366, 'sessions', 'Logged out', 68, 68, '2025-02-01 17:46:19', 'User Logged out', NULL, NULL),
(367, 'sessions', 'Logged in', 56, 56, '2025-02-01 17:46:25', 'User Logged in', NULL, NULL),
(368, 'sessions', 'Logged out', 56, 56, '2025-02-01 18:27:11', 'User Logged out', NULL, NULL),
(369, 'schedules', 'UPDATE', 110, 67, '2025-02-01 18:57:49', 'Modified the schedule for ff - Basketball vs asasa - Basketball - Basketball \n                            from 2025-01-31 at 10:00 AM, Venue: School Gym \n                            to February 01, 2025 at 7:12 AM, Venue: School Gym', NULL, NULL),
(370, 'schedules', 'UPDATE', 109, 67, '2025-02-01 18:58:28', 'Modified the schedule for BSBA - Basketball vs BSCS - Basketball - Basketball \n                            from 2025-01-31 at 10:25 PM, Venue: Gym \n                            to February 01, 2025 at 7:09 PM, Venue: Gym', NULL, NULL),
(371, 'Matches', 'Match Start', 109, 67, '2025-02-01 18:59:07', 'Started the match between BSCS - Basketball vs BSBA - Basketball', NULL, NULL),
(372, 'sessions', 'Logged in', 1, 1, '2025-02-02 07:13:49', 'User Logged in', NULL, NULL),
(373, 'sessions', 'Logged out', 1, 1, '2025-02-02 16:33:08', 'User Logged out', NULL, NULL),
(374, 'Matches', 'Match Start', 109, 67, '2025-02-02 16:34:04', 'Started the match between BSCS - Basketball vs BSBA - Basketball', NULL, NULL),
(375, 'Game Rules', 'CREATE/UPDATE', NULL, 67, '2025-02-02 18:06:52', 'Updated the game scoring rules for Basketball under the College department.', NULL, NULL),
(376, 'Game Rules', 'CREATE/UPDATE', NULL, 67, '2025-02-02 18:07:07', 'Updated the game scoring rules for Basketball under the College department.', NULL, NULL),
(377, 'Game Rules', 'CREATE/UPDATE', NULL, 67, '2025-02-02 18:07:15', 'Updated the game scoring rules for Basketball under the College department.', NULL, NULL),
(378, 'Game Rules', 'CREATE/UPDATE', NULL, 67, '2025-02-02 18:19:20', 'Updated the game scoring rules for Basketball under the College department.', NULL, NULL),
(379, 'Matches', 'Match Start', 109, 67, '2025-02-02 18:19:27', 'Started the match between BSCS - Basketball vs BSBA - Basketball', NULL, NULL),
(380, 'schedules', 'UPDATE', 110, 67, '2025-02-02 20:43:20', 'Modified the schedule for ff - Basketball vs asasa - Basketball - Basketball \n                            from 2025-02-01 at 7:12 AM, Venue: School Gym \n                            to February 02, 2025 at 8:30 PM, Venue: School Gym', NULL, NULL),
(381, 'Matches', 'Match Start', 110, 67, '2025-02-02 20:43:27', 'Started the match between ff - Basketball vs asasa - Basketball', NULL, NULL),
(382, 'schedules', 'UPDATE', 111, 67, '2025-02-02 20:57:08', 'Modified the schedule for adwawd - Basketball vs BSA - Basketball - Basketball \n                            from 2025-01-31 at 7:00 PM, Venue: School Gym \n                            to February 02, 2025 at 9:00 PM, Venue: School Gym', NULL, NULL),
(383, 'Matches', 'Match Start', 111, 67, '2025-02-02 20:57:16', 'Started the match between BSA - Basketball vs adwawd - Basketball', NULL, NULL),
(384, 'Matches', 'Match Ended', 111, 67, '2025-02-02 20:57:40', 'Ended the match between  vs ', NULL, NULL),
(385, 'Matches', 'Match Ended', 111, 67, '2025-02-02 20:58:48', 'Ended the match between  vs ', NULL, NULL),
(386, 'Matches', 'Match Ended', 111, 67, '2025-02-02 20:59:20', 'Ended the match between  vs ', NULL, NULL),
(387, 'schedules', 'CREATE', 2117, 67, '2025-02-02 21:15:43', 'CRIM - Basketball vs ll - Basketball - Basketball | Scheduled on February 02, 2025 at 9:15 PM, Venue: Closed Gym', NULL, NULL),
(388, 'Matches', 'Match Start', 112, 67, '2025-02-02 21:17:12', 'Started the match between CRIM - Basketball vs ll - Basketball', NULL, NULL),
(389, 'schedules', 'CREATE', 2118, 67, '2025-02-02 21:26:51', 'ff - Basketball vs BSHM - Basketball - Basketball | Scheduled on February 02, 2025 at 9:29 PM, Venue: Open Gym', NULL, NULL),
(390, 'Players', 'Register', 328, 67, '2025-02-02 21:28:57', 'Registered player De Guzman, Andrew to team \'BSHM - Basketball\'', NULL, NULL),
(391, 'Notification', 'Player Notification', 113, 67, '2025-02-02 21:37:08', 'Notified players for match ff - Basketball vs BSHM - Basketball. Total number of players notified is 1.', NULL, NULL),
(392, 'Notification', 'Player Notification', 113, 67, '2025-02-02 21:38:36', 'Notified players for match ff - Basketball vs BSHM - Basketball. Total number of players notified is 1.', NULL, NULL),
(393, 'schedules', 'UPDATE', 113, 67, '2025-02-03 01:03:59', 'Modified the schedule for ff - Basketball vs BSHM - Basketball - Basketball \n                            from 2025-02-02 at 9:29 PM, Venue: Open Gym \n                            to February 03, 2025 at 1:29 AM, Venue: Open Gym', NULL, NULL),
(394, 'Matches', 'Match Start', 113, 67, '2025-02-03 01:04:07', 'Started the match between BSHM - Basketball vs ff - Basketball', NULL, NULL),
(395, 'Game Rules', 'CREATE/UPDATE', NULL, 67, '2025-02-03 01:05:11', 'Updated the game scoring rules for Basketball under the College department.', NULL, NULL),
(396, 'Matches', 'Match Start', 113, 67, '2025-02-03 01:05:52', 'Started the match between BSHM - Basketball vs ff - Basketball', NULL, NULL),
(397, 'Matches', 'Match Start', 113, 67, '2025-02-03 02:56:47', 'Started the match between BSHM - Basketball vs ff - Basketball', NULL, NULL),
(398, 'schedules', 'CREATE', 2119, 67, '2025-02-03 05:11:46', 'awdawda - Basketball vs BSHM - Basketball - Basketball | Scheduled on February 03, 2025 at 5:15 AM, Venue: Closed Gym', NULL, NULL),
(399, 'schedules', 'UPDATE', 114, 67, '2025-02-03 06:20:33', 'Modified the schedule for awdawda - Basketball vs BSHM - Basketball - Basketball \n                            from 2025-02-03 at 5:15 AM, Venue: Closed Gym \n                            to February 03, 2025 at 6:15 AM, Venue: Closed Gym', NULL, NULL),
(400, 'Matches', 'Match Start', 114, 67, '2025-02-03 06:20:45', 'Started the match between BSHM - Basketball vs awdawda - Basketball', NULL, NULL),
(401, 'Game Rules', 'CREATE/UPDATE', NULL, 67, '2025-02-03 06:22:02', 'Updated the game scoring rules for Basketball under the College department.', NULL, NULL),
(402, 'Matches', 'Match Start', 114, 67, '2025-02-03 06:22:11', 'Started the match between BSHM - Basketball vs awdawda - Basketball', NULL, NULL),
(403, 'schedules', 'UPDATE', 114, 67, '2025-02-03 06:54:44', 'Modified the schedule for awdawda - Basketball vs BSHM - Basketball - Basketball \n                            from 2025-02-03 at 6:15 AM, Venue: Closed Gym \n                            to February 03, 2025 at 6:31 AM, Venue: Closed Gym', NULL, NULL),
(404, 'Matches', 'Match Start', 114, 67, '2025-02-03 06:54:49', 'Started the match between BSHM - Basketball vs awdawda - Basketball', NULL, NULL),
(405, 'schedules', 'CREATE', 2120, 67, '2025-02-03 14:32:51', 'BSBA - Basketball vs CRIM - Basketball - Basketball | Scheduled on February 03, 2025 at 2:31 PM, Venue: Open Gym', NULL, NULL),
(406, 'Matches', 'Match Start', 115, 67, '2025-02-03 14:32:56', 'Started the match between CRIM - Basketball vs BSBA - Basketball', NULL, NULL),
(407, 'schedules', 'CREATE', 2121, 67, '2025-02-03 14:37:08', 'BSHM - Basketball vs awdawda - Basketball - Basketball | Scheduled on February 04, 2025 at 2:30 PM, Venue: Closed Gym', NULL, NULL),
(408, 'schedules', 'UPDATE', 116, 67, '2025-02-03 14:37:25', 'Modified the schedule for BSHM - Basketball vs awdawda - Basketball - Basketball \n                            from 2025-02-04 at 2:30 PM, Venue: Closed Gym \n                            to February 03, 2025 at 2:30 PM, Venue: Closed Gym', NULL, NULL),
(409, 'Matches', 'Match Start', 116, 67, '2025-02-03 14:37:31', 'Started the match between BSHM - Basketball vs awdawda - Basketball', NULL, NULL),
(410, 'sessions', 'Logged out', 67, 67, '2025-02-03 14:59:56', 'User Logged out', NULL, NULL),
(411, 'sessions', 'Logged in', 56, 56, '2025-02-03 15:00:10', 'User Logged in', NULL, NULL),
(412, 'leaderboard', 'RESET', NULL, 56, '2025-02-03 15:00:22', 'Reset leaderboards: points, wins, losses for teams, and deleted all brackets for the school.', NULL, NULL),
(413, 'sessions', 'Logged out', 56, 56, '2025-02-03 15:00:31', 'User Logged out', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `match_id` int(11) NOT NULL,
  `match_identifier` varchar(50) DEFAULT NULL,
  `bracket_id` int(11) DEFAULT NULL,
  `teamA_id` int(11) DEFAULT NULL,
  `teamB_id` int(11) DEFAULT NULL,
  `round` int(11) NOT NULL,
  `match_number` int(11) DEFAULT NULL,
  `next_match_number` int(11) DEFAULT NULL,
  `status` enum('Pending','Upcoming','Ongoing','Finished') DEFAULT 'Pending',
  `match_type` enum('regular','semifinal','final','third_place') DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_periods_info`
--

CREATE TABLE `match_periods_info` (
  `period_info_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `period_number` int(11) NOT NULL,
  `teamA_id` int(11) NOT NULL,
  `teamB_id` int(11) NOT NULL,
  `score_teamA` int(11) DEFAULT 0,
  `score_teamB` int(11) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_progression_logs`
--

CREATE TABLE `match_progression_logs` (
  `log_id` int(11) NOT NULL,
  `match_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `update_type` varchar(50) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `log_timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `match_progression_logs`
--

INSERT INTO `match_progression_logs` (`log_id`, `match_id`, `team_id`, `update_type`, `additional_info`, `log_timestamp`) VALUES
(1, 333, 312, 'next_match_teamA_update', 'From match 330', '2024-12-20 09:01:37'),
(2, 332, 240, 'next_match_teamB_update', 'From match 329', '2024-12-20 09:20:07'),
(3, 340, 240, 'next_match_teamA_update', 'From match 336', '2024-12-20 17:34:59'),
(4, 341, 256, 'next_match_teamA_update', 'From match 338', '2024-12-20 19:29:31'),
(5, 348, 259, 'next_match_teamA_update', 'From match 344', '2024-12-21 10:29:33'),
(6, 356, 312, 'next_match_teamA_update', 'From match 352', '2024-12-21 10:31:55'),
(7, 357, 256, 'next_match_teamA_update', 'From match 354', '2024-12-21 11:21:04'),
(8, 349, 243, 'next_match_teamA_update', 'From match 346', '2024-12-21 11:24:32'),
(9, 381, 240, 'next_match_teamA_update', 'From match 378', '2025-01-09 01:00:37'),
(10, 380, 248, 'next_match_teamA_update', 'From match 376', '2025-01-10 09:30:16'),
(11, 486, 240, 'next_match_teamB_update', 'From match 483', '2025-01-29 02:17:14'),
(12, 486, 320, 'next_match_teamA_update', 'From match 482', '2025-01-29 17:11:37'),
(13, 2117, 596, 'next_match_teamB_update', 'From match 2111', '2025-01-31 22:33:01'),
(14, 2117, 248, 'next_match_teamA_update', 'From match 2110', '2025-01-31 23:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `match_results`
--

CREATE TABLE `match_results` (
  `result_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `team_A_id` int(11) NOT NULL,
  `team_B_id` int(11) NOT NULL,
  `score_teamA` int(11) NOT NULL DEFAULT 0,
  `score_teamB` int(11) NOT NULL DEFAULT 0,
  `winning_team_id` int(11) NOT NULL,
  `losing_team_id` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `player_id` int(11) NOT NULL,
  `player_lastname` varchar(255) NOT NULL,
  `player_firstname` varchar(255) NOT NULL,
  `player_middlename` varchar(255) NOT NULL,
  `team_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jersey_number` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`player_id`, `player_lastname`, `player_firstname`, `player_middlename`, `team_id`, `created_at`, `jersey_number`) VALUES
(226, 'Bryant', 'Kobe', 'B.', 256, '2024-11-02 20:10:00', 22),
(227, 'Jordan', 'Michael', '', 256, '2024-11-02 20:55:45', 32),
(228, 'James', 'Lebron', '', 256, '2024-11-02 23:00:59', 23),
(229, 'De Guzman', 'Lebron', 'B.', 259, '2024-11-07 08:13:35', 10),
(230, 'awedwa', 'awdsa', 'wdsa', 259, '2024-11-07 08:16:30', 33),
(236, 'Dizon', 'Hanes', '', 240, '2024-12-05 12:47:00', 1),
(237, 'De Guzman', 'Andrew', 'B.', 240, '2024-12-05 12:49:50', 22),
(249, 'Capistrano', 'Charlotte', '', 320, '2024-12-20 11:28:55', 77),
(250, 'Guevarra', 'Jamie', '', 248, '2024-12-21 02:36:48', 4),
(251, 'Doe', 'John', '', 312, '2025-01-08 14:54:06', 44),
(252, 'Smith', 'John', 'A', 256, '2025-01-23 12:30:24', 10),
(256, 'De Guzman', 'Andrew', 'B.', 328, '2025-02-02 13:28:57', 1);

-- --------------------------------------------------------

--
-- Table structure for table `players_info`
--

CREATE TABLE `players_info` (
  `player_info_id` int(11) NOT NULL,
  `player_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players_info`
--

INSERT INTO `players_info` (`player_info_id`, `player_id`, `email`, `phone_number`, `date_of_birth`, `picture`, `height`, `weight`, `position`) VALUES
(3, 226, 'Kobe@gmail.com', '+63999999999', '2024-10-24', '../uploads/players/kobe.jpg', '6&#039;6', '250lbs', 'Shooting Guard'),
(4, 227, 'John@gmail.com', '+63232323232', '2024-11-23', '../uploads/players/jordan.png', '6\'5', '250lbs', 'Shooting Guard'),
(5, 228, 'lebron@gmail.com', '+63323232323', '2024-10-28', '../uploads/players/lebron.jpg', '6&#039;9', '250lbs', 'Small Forward'),
(6, 229, 'awdsa@gmail.com', '+63912122121', '2024-11-06', NULL, '', '', ''),
(7, 230, 'aaa@gmail.com', '+63213212131', '2024-10-29', NULL, '', '', ''),
(13, 236, 'hanes@gmail.com', '+639887378278', '1990-05-14', '../uploads/players/hanes.jpg', '5\'9', '150lbs', 'Shooting Guard'),
(14, 237, 'andrewbucedeguzman@gmail.com', '', '2002-04-30', NULL, '5&#039;9', '180lbs', 'Small Forward'),
(26, 249, 'charlotte@gmail.com', '+639261769542', '2010-02-20', NULL, '', '', ''),
(27, 250, 'jamie@gmail.com', '+639121212121', '2010-02-15', NULL, '', '', ''),
(28, 251, 'johndoe@gmail.com', '+639754136498', '2005-05-08', NULL, '', '', 'Small Forward'),
(29, 252, 'john.smith@test.com', '9876543210', '2000-01-01', NULL, '6\"0', '160 lbs', 'Guard'),
(33, 256, 'test@gmail.com', '+639754136497', '1990-11-30', NULL, '', '', 'Small Forward');

-- --------------------------------------------------------

--
-- Table structure for table `player_match_stats`
--

CREATE TABLE `player_match_stats` (
  `stat_record_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `stat_name` varchar(50) NOT NULL,
  `stat_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pointing_system`
--

CREATE TABLE `pointing_system` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `first_place_points` int(11) NOT NULL,
  `second_place_points` int(11) NOT NULL,
  `third_place_points` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pointing_system`
--

INSERT INTO `pointing_system` (`id`, `school_id`, `first_place_points`, `second_place_points`, `third_place_points`, `created_at`) VALUES
(1, 8, 10, 7, 3, '2024-12-17 01:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `schedule_time` time NOT NULL,
  `venue` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(11) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_code` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `logo`, `school_name`, `school_code`, `address`, `created_at`, `updated_at`) VALUES
(0, 'system.jpg', 'System', 'SYS', 'System', '2024-12-01 00:24:03', '2024-12-01 00:25:52'),
(8, 'school_67394da7bb6341.51360178.jpg', 'Holy Cross College', 'HCC', 'Santa Rosa', '2024-10-31 13:37:18', '2024-11-17 01:57:59');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `game_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade_section_course_id` int(11) DEFAULT NULL,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `game_id`, `created_at`, `grade_section_course_id`, `wins`, `losses`) VALUES
(-2, 'To Be Determined', 0, '2024-12-01 00:29:16', 0, 0, 0),
(-1, 'BYE', 0, '2024-12-01 19:26:39', 0, 0, 0),
(239, 'BSCS - Volleyball', 2, '2024-11-02 11:14:51', 67, 0, 0),
(240, 'BSCS - Basketball', 18, '2024-11-02 11:14:51', 67, 0, 0),
(241, 'BSCS - Dama', 22, '2024-11-02 11:14:51', 67, 0, 0),
(242, 'BSCS - Dart', 26, '2024-11-02 11:14:51', 67, 0, 0),
(243, 'BSCS - Chess', 27, '2024-11-02 11:14:51', 67, 0, 0),
(244, 'BSCS - Badminton', 28, '2024-11-02 11:14:51', 67, 0, 0),
(245, 'BSCS - Patintero', 29, '2024-11-02 11:14:51', 67, 0, 0),
(246, 'BSCS - Word Factory', 30, '2024-11-02 11:14:51', 67, 0, 0),
(247, 'CRIM - Volleyball', 2, '2024-11-02 11:14:55', 68, 0, 0),
(248, 'CRIM - Basketball', 18, '2024-11-02 11:14:55', 68, 0, 0),
(249, 'CRIM - Dama', 22, '2024-11-02 11:14:55', 68, 0, 0),
(250, 'CRIM - Dart', 26, '2024-11-02 11:14:55', 68, 0, 0),
(251, 'CRIM - Chess', 27, '2024-11-02 11:14:55', 68, 0, 0),
(252, 'CRIM - Badminton', 28, '2024-11-02 11:14:55', 68, 0, 0),
(253, 'CRIM - Patintero', 29, '2024-11-02 11:14:55', 68, 0, 0),
(254, 'CRIM - Word Factory', 30, '2024-11-02 11:14:55', 68, 0, 0),
(255, 'BSA - Volleyball', 2, '2024-11-02 11:15:21', 69, 0, 0),
(256, 'BSA - Basketball', 18, '2024-11-02 11:15:21', 69, 0, 0),
(257, 'BSA - Dama', 22, '2024-11-02 11:15:21', 69, 0, 0),
(258, 'BSA - Dart', 26, '2024-11-02 11:15:21', 69, 0, 0),
(259, 'BSA - Chess', 27, '2024-11-02 11:15:21', 69, 0, 0),
(260, 'BSA - Badminton', 28, '2024-11-02 11:15:21', 69, 0, 0),
(261, 'BSA - Patintero', 29, '2024-11-02 11:15:21', 69, 0, 0),
(262, 'BSA - Word Factory', 30, '2024-11-02 11:15:21', 69, 0, 0),
(263, '11-Aa - STEMss - Volleyball', 2, '2024-11-02 14:25:54', 70, 0, 0),
(264, '11-Aa - STEMss - Basketball', 18, '2024-11-02 14:25:54', 70, 0, 0),
(265, '11-Aa - STEMss - Dama', 22, '2024-11-02 14:25:54', 70, 0, 0),
(266, '11-Aa - STEMss - Dart', 26, '2024-11-02 14:25:54', 70, 0, 0),
(267, '11-Aa - STEMss - Chess', 27, '2024-11-02 14:25:54', 70, 0, 0),
(268, '11-Aa - STEMss - Badminton', 28, '2024-11-02 14:25:54', 70, 0, 0),
(269, '11-Aa - STEMss - Patintero', 29, '2024-11-02 14:25:54', 70, 0, 0),
(270, '11-Aa - STEMss - Word Factory', 30, '2024-11-02 14:25:54', 70, 0, 0),
(271, '12-A - ABM - Volleyball', 2, '2024-11-02 14:26:05', 71, 0, 0),
(272, '12-A - ABM - Basketball', 18, '2024-11-02 14:26:05', 71, 0, 0),
(273, '12-A - ABM - Dama', 22, '2024-11-02 14:26:05', 71, 0, 0),
(274, '12-A - ABM - Dart', 26, '2024-11-02 14:26:05', 71, 0, 0),
(275, '12-A - ABM - Chess', 27, '2024-11-02 14:26:05', 71, 0, 0),
(276, '12-A - ABM - Badminton', 28, '2024-11-02 14:26:05', 71, 0, 0),
(277, '12-A - ABM - Patintero', 29, '2024-11-02 14:26:05', 71, 0, 0),
(278, '12-A - ABM - Word Factory', 30, '2024-11-02 14:26:05', 71, 0, 0),
(279, 'Obedience - Volleyball', 2, '2024-11-02 14:26:20', 72, 0, 0),
(280, 'Obedience - Basketball', 18, '2024-11-02 14:26:20', 72, 0, 0),
(281, 'Obedience - Dama', 22, '2024-11-02 14:26:20', 72, 0, 0),
(282, 'Obedience - Dart', 26, '2024-11-02 14:26:20', 72, 0, 0),
(283, 'Obedience - Chess', 27, '2024-11-02 14:26:20', 72, 0, 0),
(284, 'Obedience - Badminton', 28, '2024-11-02 14:26:20', 72, 0, 0),
(285, 'Obedience - Patintero', 29, '2024-11-02 14:26:20', 72, 0, 0),
(286, 'Obedience - Word Factory', 30, '2024-11-02 14:26:20', 72, 0, 0),
(311, 'BSBA - Volleyball', 2, '2024-11-09 05:25:00', 76, 0, 0),
(312, 'BSBA - Basketball', 18, '2024-11-09 05:25:00', 76, 0, 0),
(313, 'BSBA - Dama', 22, '2024-11-09 05:25:00', 76, 0, 0),
(314, 'BSBA - Dart', 26, '2024-11-09 05:25:00', 76, 0, 0),
(315, 'BSBA - Chess', 27, '2024-11-09 05:25:00', 76, 0, 0),
(316, 'BSBA - Badminton', 28, '2024-11-09 05:25:00', 76, 0, 0),
(317, 'BSBA - Patintero', 29, '2024-11-09 05:25:00', 76, 0, 0),
(318, 'BSBA - Word Factory', 30, '2024-11-09 05:25:00', 76, 0, 0),
(319, 'EDUC - Volleyball', 2, '2024-11-09 05:25:19', 77, 0, 0),
(320, 'EDUC - Basketball', 18, '2024-11-09 05:25:19', 77, 0, 0),
(321, 'EDUC - Dama', 22, '2024-11-09 05:25:19', 77, 0, 0),
(322, 'EDUC - Dart', 26, '2024-11-09 05:25:19', 77, 0, 0),
(323, 'EDUC - Chess', 27, '2024-11-09 05:25:19', 77, 0, 0),
(324, 'EDUC - Badminton', 28, '2024-11-09 05:25:19', 77, 0, 0),
(325, 'EDUC - Patintero', 29, '2024-11-09 05:25:19', 77, 0, 0),
(326, 'EDUC - Word Factory', 30, '2024-11-09 05:25:19', 77, 0, 0),
(327, 'BSHM - Volleyball', 2, '2024-11-09 05:25:41', 78, 0, 0),
(328, 'BSHM - Basketball', 18, '2024-11-09 05:25:41', 78, 0, 0),
(329, 'BSHM - Dama', 22, '2024-11-09 05:25:41', 78, 0, 0),
(330, 'BSHM - Dart', 26, '2024-11-09 05:25:41', 78, 0, 0),
(331, 'BSHM - Chess', 27, '2024-11-09 05:25:41', 78, 0, 0),
(332, 'BSHM - Badminton', 28, '2024-11-09 05:25:41', 78, 0, 0),
(333, 'BSHM - Patintero', 29, '2024-11-09 05:25:41', 78, 0, 0),
(334, 'BSHM - Word Factory', 30, '2024-11-09 05:25:41', 78, 0, 0),
(335, '11-B - STEM - Volleyball', 2, '2024-11-12 04:20:34', 79, 0, 0),
(336, '11-B - STEM - Basketball', 18, '2024-11-12 04:20:34', 79, 0, 0),
(337, '11-B - STEM - Dama', 22, '2024-11-12 04:20:34', 79, 0, 0),
(338, '11-B - STEM - Dart', 26, '2024-11-12 04:20:34', 79, 0, 0),
(339, '11-B - STEM - Chess', 27, '2024-11-12 04:20:34', 79, 0, 0),
(340, '11-B - STEM - Badminton', 28, '2024-11-12 04:20:34', 79, 0, 0),
(341, '11-B - STEM - Patintero', 29, '2024-11-12 04:20:34', 79, 0, 0),
(342, '11-B - STEM - Word Factory', 30, '2024-11-12 04:20:34', 79, 0, 0),
(346, 'C - STEM - Volleyball', 2, '2024-12-02 18:18:54', 84, 0, 0),
(347, 'C - STEM - Basketball', 18, '2024-12-02 18:18:54', 84, 0, 0),
(348, 'C - STEM - Dama', 22, '2024-12-02 18:18:54', 84, 0, 0),
(349, 'C - STEM - Dart', 26, '2024-12-02 18:18:54', 84, 0, 0),
(350, 'C - STEM - Chess', 27, '2024-12-02 18:18:54', 84, 0, 0),
(351, 'C - STEM - Badminton', 28, '2024-12-02 18:18:54', 84, 0, 0),
(352, 'C - STEM - Patintero', 29, '2024-12-02 18:18:54', 84, 0, 0),
(353, 'C - STEM - Word Factory', 30, '2024-12-02 18:18:54', 84, 0, 0),
(354, 'D - ABM - Volleyball', 2, '2024-12-02 18:19:06', 85, 0, 0),
(355, 'D - ABM - Basketball', 18, '2024-12-02 18:19:06', 85, 0, 0),
(356, 'D - ABM - Dama', 22, '2024-12-02 18:19:06', 85, 0, 0),
(357, 'D - ABM - Dart', 26, '2024-12-02 18:19:06', 85, 0, 0),
(358, 'D - ABM - Chess', 27, '2024-12-02 18:19:06', 85, 0, 0),
(359, 'D - ABM - Badminton', 28, '2024-12-02 18:19:06', 85, 0, 0),
(360, 'D - ABM - Patintero', 29, '2024-12-02 18:19:06', 85, 0, 0),
(361, 'D - ABM - Word Factory', 30, '2024-12-02 18:19:06', 85, 0, 0),
(362, 'A - HUMSS - Volleyball', 2, '2024-12-02 18:19:21', 86, 0, 0),
(363, 'A - HUMSS - Basketball', 18, '2024-12-02 18:19:21', 86, 0, 0),
(364, 'A - HUMSS - Dama', 22, '2024-12-02 18:19:21', 86, 0, 0),
(365, 'A - HUMSS - Dart', 26, '2024-12-02 18:19:21', 86, 0, 0),
(366, 'A - HUMSS - Chess', 27, '2024-12-02 18:19:21', 86, 0, 0),
(367, 'A - HUMSS - Badminton', 28, '2024-12-02 18:19:21', 86, 0, 0),
(368, 'A - HUMSS - Patintero', 29, '2024-12-02 18:19:21', 86, 0, 0),
(369, 'A - HUMSS - Word Factory', 30, '2024-12-02 18:19:21', 86, 0, 0),
(467, 'mm - STEM - Volleyball', 2, '2025-01-23 21:25:06', 100, 0, 0),
(468, 'mm - STEM - Basketball', 18, '2025-01-23 21:25:06', 100, 0, 0),
(469, 'mm - STEM - Dama', 22, '2025-01-23 21:25:06', 100, 0, 0),
(470, 'mm - STEM - Dart', 26, '2025-01-23 21:25:06', 100, 0, 0),
(471, 'mm - STEM - Chess', 27, '2025-01-23 21:25:06', 100, 0, 0),
(472, 'mm - STEM - Badminton', 28, '2025-01-23 21:25:06', 100, 0, 0),
(473, 'mm - STEM - Patintero', 29, '2025-01-23 21:25:06', 100, 0, 0),
(474, 'mm - STEM - Word Factory', 30, '2025-01-23 21:25:06', 100, 0, 0),
(475, 'nickel - Volleyball', 2, '2025-01-23 21:27:13', 101, 0, 0),
(476, 'nickel - Basketball', 18, '2025-01-23 21:27:13', 101, 0, 0),
(477, 'nickel - Dama', 22, '2025-01-23 21:27:13', 101, 0, 0),
(478, 'nickel - Dart', 26, '2025-01-23 21:27:13', 101, 0, 0),
(479, 'nickel - Chess', 27, '2025-01-23 21:27:13', 101, 0, 0),
(480, 'nickel - Badminton', 28, '2025-01-23 21:27:13', 101, 0, 0),
(481, 'nickel - Patintero', 29, '2025-01-23 21:27:13', 101, 0, 0),
(482, 'nickel - Word Factory', 30, '2025-01-23 21:27:13', 101, 0, 0),
(507, 'johndoes - Volleyball', 2, '2025-01-23 22:10:12', 105, 0, 0),
(508, 'johndoes - Basketball', 18, '2025-01-23 22:10:12', 105, 0, 0),
(509, 'johndoes - Dama', 22, '2025-01-23 22:10:12', 105, 0, 0),
(510, 'johndoes - Dart', 26, '2025-01-23 22:10:12', 105, 0, 0),
(511, 'johndoes - Chess', 27, '2025-01-23 22:10:12', 105, 0, 0),
(512, 'johndoes - Badminton', 28, '2025-01-23 22:10:12', 105, 0, 0),
(513, 'johndoes - Patintero', 29, '2025-01-23 22:10:12', 105, 0, 0),
(514, 'johndoes - Word Factory', 30, '2025-01-23 22:10:12', 105, 0, 0),
(523, 'asas - Volleyball', 2, '2025-01-23 22:53:48', 107, 0, 0),
(524, 'asas - Basketball', 18, '2025-01-23 22:53:48', 107, 0, 0),
(525, 'asas - Dama', 22, '2025-01-23 22:53:48', 107, 0, 0),
(526, 'asas - Dart', 26, '2025-01-23 22:53:48', 107, 0, 0),
(527, 'asas - Chess', 27, '2025-01-23 22:53:48', 107, 0, 0),
(528, 'asas - Badminton', 28, '2025-01-23 22:53:48', 107, 0, 0),
(529, 'asas - Patintero', 29, '2025-01-23 22:53:48', 107, 0, 0),
(530, 'asas - Word Factory', 30, '2025-01-23 22:53:48', 107, 0, 0),
(587, 'ff - Volleyball', 2, '2025-01-28 19:51:33', 114, 0, 0),
(588, 'ff - Basketball', 18, '2025-01-28 19:51:33', 114, 0, 0),
(589, 'ff - Dama', 22, '2025-01-28 19:51:33', 114, 0, 0),
(590, 'ff - Dart', 26, '2025-01-28 19:51:33', 114, 0, 0),
(591, 'ff - Chess', 27, '2025-01-28 19:51:33', 114, 0, 0),
(592, 'ff - Badminton', 28, '2025-01-28 19:51:33', 114, 0, 0),
(593, 'ff - Patintero', 29, '2025-01-28 19:51:33', 114, 0, 0),
(594, 'ff - Word Factory', 30, '2025-01-28 19:51:33', 114, 0, 0),
(595, 'll - Volleyball', 2, '2025-01-28 19:52:32', 115, 0, 0),
(596, 'll - Basketball', 18, '2025-01-28 19:52:32', 115, 0, 0),
(597, 'll - Dama', 22, '2025-01-28 19:52:32', 115, 0, 0),
(598, 'll - Dart', 26, '2025-01-28 19:52:32', 115, 0, 0),
(599, 'll - Chess', 27, '2025-01-28 19:52:32', 115, 0, 0),
(600, 'll - Badminton', 28, '2025-01-28 19:52:32', 115, 0, 0),
(601, 'll - Patintero', 29, '2025-01-28 19:52:32', 115, 0, 0),
(602, 'll - Word Factory', 30, '2025-01-28 19:52:32', 115, 0, 0),
(643, 'asasa - Volleyball', 2, '2025-01-29 09:35:57', 121, 0, 0),
(644, 'asasa - Basketball', 18, '2025-01-29 09:35:57', 121, 0, 0),
(645, 'asasa - Dama', 22, '2025-01-29 09:35:57', 121, 0, 0),
(646, 'asasa - Dart', 26, '2025-01-29 09:35:57', 121, 0, 0),
(647, 'asasa - Chess', 27, '2025-01-29 09:35:57', 121, 0, 0),
(648, 'asasa - Badminton', 28, '2025-01-29 09:35:57', 121, 0, 0),
(649, 'asasa - Patintero', 29, '2025-01-29 09:35:57', 121, 0, 0),
(650, 'asasa - Word Factory', 30, '2025-01-29 09:35:57', 121, 0, 0),
(651, 'wda - Volleyball', 2, '2025-01-29 09:42:01', 122, 0, 0),
(652, 'wda - Basketball', 18, '2025-01-29 09:42:01', 122, 0, 0),
(653, 'wda - Dama', 22, '2025-01-29 09:42:01', 122, 0, 0),
(654, 'wda - Dart', 26, '2025-01-29 09:42:01', 122, 0, 0),
(655, 'wda - Chess', 27, '2025-01-29 09:42:01', 122, 0, 0),
(656, 'wda - Badminton', 28, '2025-01-29 09:42:01', 122, 0, 0),
(657, 'wda - Patintero', 29, '2025-01-29 09:42:01', 122, 0, 0),
(658, 'wda - Word Factory', 30, '2025-01-29 09:42:01', 122, 0, 0),
(659, 'adwawd - Volleyball', 2, '2025-01-29 15:02:22', 123, 0, 0),
(660, 'adwawd - Basketball', 18, '2025-01-29 15:02:22', 123, 0, 0),
(661, 'adwawd - Dama', 22, '2025-01-29 15:02:22', 123, 0, 0),
(662, 'adwawd - Dart', 26, '2025-01-29 15:02:22', 123, 0, 0),
(663, 'adwawd - Chess', 27, '2025-01-29 15:02:22', 123, 0, 0),
(664, 'adwawd - Badminton', 28, '2025-01-29 15:02:22', 123, 0, 0),
(665, 'adwawd - Patintero', 29, '2025-01-29 15:02:22', 123, 0, 0),
(666, 'adwawd - Word Factory', 30, '2025-01-29 15:02:22', 123, 0, 0),
(667, 'awdawda - Volleyball', 2, '2025-01-29 15:02:50', 124, 0, 0),
(668, 'awdawda - Basketball', 18, '2025-01-29 15:02:50', 124, 0, 0),
(669, 'awdawda - Dama', 22, '2025-01-29 15:02:50', 124, 0, 0),
(670, 'awdawda - Dart', 26, '2025-01-29 15:02:50', 124, 0, 0),
(671, 'awdawda - Chess', 27, '2025-01-29 15:02:50', 124, 0, 0),
(672, 'awdawda - Badminton', 28, '2025-01-29 15:02:50', 124, 0, 0),
(673, 'awdawda - Patintero', 29, '2025-01-29 15:02:50', 124, 0, 0),
(674, 'awdawda - Word Factory', 30, '2025-01-29 15:02:50', 124, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middleinitial` varchar(255) NOT NULL,
  `age` int(3) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `department` int(11) DEFAULT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `game_id` int(11) DEFAULT NULL,
  `school_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `lastname`, `firstname`, `middleinitial`, `age`, `gender`, `email`, `password`, `role`, `department`, `reset_token_hash`, `reset_token_expires_at`, `game_id`, `school_id`) VALUES
(1, 'De Guzman', 'Andrew', 'B.', 22, 'Male', 'andrewbucedeguzman@gmail.com', '$2y$10$DW/oDAXMGiG462kSEGgDjuxW260hugHr9agzALTrnduNJJxt0pioS', 'superadmin', NULL, 'ed4f4c6f286cd0d91c1056bac7f3237186fcdedce090002e9342e81f0e54be3f', '2024-10-06 02:51:55', NULL, NULL),
(56, '', '', '', 0, '', 'hcc@gmail.com', '$2y$10$8R6T3bdAyXwloo098OB6suCHoHXniSeZhqgD6mtstPJXzX8jgfISO', 'School Admin', NULL, NULL, NULL, NULL, 8),
(67, 'Doe', 'John', 'B', 22, '', 'committee@gmail.com', '$2y$10$g1ZaROoolXsxtimnfv5mA.z3y/PpL4IlO1nT02pf0z7VrwfV/mGKK', 'Committee', 1, NULL, NULL, 18, 8),
(68, 'Smith', 'John', 'B', 22, 'Male', 'aaa@gmail.com', '$2y$10$g/T3J0JGHACYXUPlCxA6x.ZhaBfWvPobfl47Au1Qp8Ohi3nZtoohK', 'Department Admin', 1, NULL, NULL, NULL, 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_announcement_school` (`school_id`),
  ADD KEY `fk_department_id` (`department_id`);

--
-- Indexes for table `brackets`
--
ALTER TABLE `brackets`
  ADD PRIMARY KEY (`bracket_id`),
  ADD KEY `brackets_ibfk_1` (`game_id`),
  ADD KEY `brackets_ibfk_2` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_school` (`school_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`),
  ADD KEY `fk_games_school` (`school_id`);

--
-- Indexes for table `game_scoring_rules`
--
ALTER TABLE `game_scoring_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`department_id`,`school_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `game_stats_config`
--
ALTER TABLE `game_stats_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `unique_game_stat` (`game_id`,`stat_name`);

--
-- Indexes for table `grade_section_course`
--
ALTER TABLE `grade_section_course`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_grade_level` (`grade_level`),
  ADD KEY `idx_department_grade` (`department_id`,`grade_level`);

--
-- Indexes for table `live_default_scores`
--
ALTER TABLE `live_default_scores`
  ADD PRIMARY KEY (`live_default_score_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `teamA_id` (`teamA_id`),
  ADD KEY `teamB_id` (`teamB_id`);

--
-- Indexes for table `live_scores`
--
ALTER TABLE `live_scores`
  ADD PRIMARY KEY (`live_score_id`),
  ADD UNIQUE KEY `unique_live_score` (`schedule_id`,`game_id`,`teamA_id`,`teamB_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `fk_teamA_id` (`teamA_id`),
  ADD KEY `fk_teamB_id` (`teamB_id`),
  ADD KEY `idx_timer_status` (`timer_status`);

--
-- Indexes for table `live_set_scores`
--
ALTER TABLE `live_set_scores`
  ADD PRIMARY KEY (`live_set_score_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `teamA_id` (`teamA_id`),
  ADD KEY `teamB_id` (`teamB_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD UNIQUE KEY `unique_match_identifier` (`match_identifier`),
  ADD KEY `idx_match_identifier` (`match_identifier`),
  ADD KEY `matches_ibfk_4` (`bracket_id`),
  ADD KEY `matches_ibfk_2` (`teamA_id`),
  ADD KEY `matches_ibfk_3` (`teamB_id`);

--
-- Indexes for table `match_periods_info`
--
ALTER TABLE `match_periods_info`
  ADD PRIMARY KEY (`period_info_id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `teamA_id` (`teamA_id`),
  ADD KEY `teamB_id` (`teamB_id`);

--
-- Indexes for table `match_progression_logs`
--
ALTER TABLE `match_progression_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `match_results`
--
ALTER TABLE `match_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `team_A_id` (`team_A_id`),
  ADD KEY `team_B_id` (`team_B_id`),
  ADD KEY `fk_match_results_matches` (`match_id`),
  ADD KEY `match_results_ibfk_1` (`game_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `players_info`
--
ALTER TABLE `players_info`
  ADD PRIMARY KEY (`player_info_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `player_match_stats`
--
ALTER TABLE `player_match_stats`
  ADD PRIMARY KEY (`stat_record_id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `game_id` (`game_id`,`stat_name`);

--
-- Indexes for table `pointing_system`
--
ALTER TABLE `pointing_system`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_match_id` (`match_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`),
  ADD UNIQUE KEY `school_code` (`school_code`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `idx_system_teams` (`grade_section_course_id`,`team_name`),
  ADD KEY `idxs_system_teams` (`grade_section_course_id`,`team_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`),
  ADD KEY `fk_user_department` (`department`),
  ADD KEY `fk_game` (`game_id`),
  ADD KEY `fk_users_school` (`school_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `brackets`
--
ALTER TABLE `brackets`
  MODIFY `bracket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=309;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `game_scoring_rules`
--
ALTER TABLE `game_scoring_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `game_stats_config`
--
ALTER TABLE `game_stats_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `grade_section_course`
--
ALTER TABLE `grade_section_course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `live_default_scores`
--
ALTER TABLE `live_default_scores`
  MODIFY `live_default_score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `live_scores`
--
ALTER TABLE `live_scores`
  MODIFY `live_score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4131;

--
-- AUTO_INCREMENT for table `live_set_scores`
--
ALTER TABLE `live_set_scores`
  MODIFY `live_set_score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=414;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2187;

--
-- AUTO_INCREMENT for table `match_periods_info`
--
ALTER TABLE `match_periods_info`
  MODIFY `period_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `match_progression_logs`
--
ALTER TABLE `match_progression_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `match_results`
--
ALTER TABLE `match_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `players_info`
--
ALTER TABLE `players_info`
  MODIFY `player_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `player_match_stats`
--
ALTER TABLE `player_match_stats`
  MODIFY `stat_record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `pointing_system`
--
ALTER TABLE `pointing_system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=675;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `fk_announcement_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `brackets`
--
ALTER TABLE `brackets`
  ADD CONSTRAINT `brackets_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `brackets_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `fk_games_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `game_scoring_rules`
--
ALTER TABLE `game_scoring_rules`
  ADD CONSTRAINT `game_scoring_rules_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`),
  ADD CONSTRAINT `game_scoring_rules_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`),
  ADD CONSTRAINT `game_scoring_rules_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `game_stats_config`
--
ALTER TABLE `game_stats_config`
  ADD CONSTRAINT `game_stats_config_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_section_course`
--
ALTER TABLE `grade_section_course`
  ADD CONSTRAINT `grade_section_course_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `live_default_scores`
--
ALTER TABLE `live_default_scores`
  ADD CONSTRAINT `live_default_scores_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_default_scores_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_default_scores_ibfk_3` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_default_scores_ibfk_4` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `live_scores`
--
ALTER TABLE `live_scores`
  ADD CONSTRAINT `fk_teamA_id` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `fk_teamB_id` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `live_scores_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`),
  ADD CONSTRAINT `live_scores_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`);

--
-- Constraints for table `live_set_scores`
--
ALTER TABLE `live_set_scores`
  ADD CONSTRAINT `live_set_scores_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_set_scores_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_set_scores_ibfk_3` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_set_scores_ibfk_4` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`bracket_id`) REFERENCES `brackets` (`bracket_id`) ON DELETE CASCADE;

--
-- Constraints for table `match_periods_info`
--
ALTER TABLE `match_periods_info`
  ADD CONSTRAINT `match_periods_info_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_periods_info_ibfk_2` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_periods_info_ibfk_3` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `match_results`
--
ALTER TABLE `match_results`
  ADD CONSTRAINT `fk_match_results_matches` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_results_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_results_ibfk_2` FOREIGN KEY (`team_A_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `match_results_ibfk_3` FOREIGN KEY (`team_B_id`) REFERENCES `teams` (`team_id`);

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `players_info`
--
ALTER TABLE `players_info`
  ADD CONSTRAINT `players_info_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE;

--
-- Constraints for table `player_match_stats`
--
ALTER TABLE `player_match_stats`
  ADD CONSTRAINT `player_match_stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_match_stats_ibfk_2` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_match_stats_ibfk_3` FOREIGN KEY (`game_id`,`stat_name`) REFERENCES `game_stats_config` (`game_id`, `stat_name`) ON DELETE CASCADE;

--
-- Constraints for table `pointing_system`
--
ALTER TABLE `pointing_system`
  ADD CONSTRAINT `pointing_system_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_match_id` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_grade_section_course` FOREIGN KEY (`grade_section_course_id`) REFERENCES `grade_section_course` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`),
  ADD CONSTRAINT `fk_user_department` FOREIGN KEY (`department`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
