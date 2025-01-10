-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 10, 2025 at 01:21 PM
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
(29, 'Announcement!', 'Players and Team Registrations are now open!!', '../uploadsEye_catching_ways_to_make_announcements.2aee7ba1.5d605628.jpg', 8, 1, '2024-12-09 05:59:59'),
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

--
-- Dumping data for table `brackets`
--

INSERT INTO `brackets` (`bracket_id`, `game_id`, `department_id`, `grade_level`, `total_teams`, `rounds`, `status`, `created_at`, `bracket_type`) VALUES
(87, 18, 1, NULL, 8, 3, 'ongoing', '2025-01-08 13:35:19', 'single');

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
(0, 'System', NULL, NULL, 'Indoor', '2024-12-01 00:28:11', 0),
(2, 'Volleyball', 15, 'Team Sports', 'Outdoor', '2024-10-04 16:02:54', 8),
(18, 'Basketball', 15, 'Team Sports', 'Outdoor', '2024-10-15 06:47:47', 8),
(22, 'Dama', 2, 'Individual Sports', 'Indoor', '2024-10-15 10:48:18', 8),
(26, 'Dart', 2, 'Individual Sports', 'Indoor', '2024-10-15 22:14:06', 8),
(27, 'Chess', 2, 'Individual Sports', 'Indoor', '2024-10-23 16:54:28', 8),
(28, 'Badminton', 2, 'Individual Sports', 'Outdoor', '2024-10-23 16:55:47', 8),
(29, 'Patintero', 8, 'Team Sports', 'Outdoor', '2024-10-23 16:55:56', 8),
(30, 'Word Factory', 2, 'Individual Sports', 'Indoor', '2024-10-23 16:56:12', 8);

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
  `timeouts_per_period` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_scoring_rules`
--

INSERT INTO `game_scoring_rules` (`id`, `game_id`, `department_id`, `school_id`, `scoring_unit`, `score_increment_options`, `period_type`, `number_of_periods`, `duration_per_period`, `time_limit`, `point_cap`, `max_fouls`, `timeouts_per_period`) VALUES
(7, 18, 1, 8, 'Point', '1,2,3', 'Quarter', 4, 10, 1, 0, 5, 4),
(12, 27, 1, 8, 'Point', '1', 'Set', 4, 5, 1, 0, 0, 0);

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
(19, 18, 'Assists'),
(15, 18, 'Fouls'),
(18, 18, 'Rebounds'),
(17, 18, 'Scores'),
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
(70, 2, 'Grade 11', 'STEM', '11-A', NULL, 0),
(71, 2, 'Grade 12', 'ABM', '12-A', NULL, 0),
(72, 3, 'Grade 8', NULL, 'Obedience', NULL, 0),
(73, 3, 'Grade 9', NULL, 'Nickel', NULL, 0),
(76, 1, NULL, NULL, NULL, 'BSBA', 0),
(77, 1, NULL, NULL, NULL, 'EDUC', 0),
(78, 1, NULL, NULL, NULL, 'BSHM', 0),
(79, 2, 'Grade 11', 'STEM', '11-B', NULL, 0),
(84, 2, 'Grade 11', 'STEM', 'C', NULL, 0),
(85, 2, 'Grade 11', 'ABM', 'D', NULL, 0),
(86, 2, 'Grade 11', 'HUMSS', 'A', NULL, 0);

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

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`match_id`, `match_identifier`, `bracket_id`, `teamA_id`, `teamB_id`, `round`, `match_number`, `next_match_number`, `status`, `match_type`) VALUES
(376, 'bracket87-round1-match1', 87, 320, 248, 1, 1, 5, 'Finished', 'regular'),
(377, 'bracket87-round1-match2', 87, 256, -1, 1, 2, 5, 'Finished', 'regular'),
(378, 'bracket87-round1-match3', 87, 312, 240, 1, 3, 6, 'Finished', 'regular'),
(379, 'bracket87-round1-match4', 87, 328, -1, 1, 4, 6, 'Finished', 'regular'),
(380, 'bracket87-round2-match5', 87, 248, 256, 2, 5, 7, 'Upcoming', 'semifinal'),
(381, 'bracket87-round2-match6', 87, 240, 328, 2, 6, 7, 'Pending', 'semifinal'),
(382, 'bracket87-round3-match7', 87, -2, -2, 3, 7, 0, 'Pending', 'final'),
(383, 'bracket87-third-place-match', 87, -2, -2, -1, -1, 0, 'Pending', 'third_place');

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
(10, 380, 248, 'next_match_teamA_update', 'From match 376', '2025-01-10 09:30:16');

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

--
-- Dumping data for table `match_results`
--

INSERT INTO `match_results` (`result_id`, `match_id`, `game_id`, `team_A_id`, `team_B_id`, `score_teamA`, `score_teamB`, `winning_team_id`, `losing_team_id`, `last_updated`) VALUES
(50, 378, 18, 312, 240, 9, 18, 240, 312, '2025-01-08 17:00:37'),
(51, 376, 18, 320, 248, 9, 15, 248, 320, '2025-01-10 01:30:16');

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
(251, 'Doe', 'John', '', 312, '2025-01-08 14:54:06', 44);

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
(28, 251, 'johndoe@gmail.com', '+639754136498', '2005-05-08', NULL, '', '', 'Small Forward');

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

--
-- Dumping data for table `player_match_stats`
--

INSERT INTO `player_match_stats` (`stat_record_id`, `player_id`, `match_id`, `game_id`, `stat_name`, `stat_value`, `created_at`) VALUES
(57, 251, 378, 18, 'Fouls', '3', '2025-01-08 15:01:10'),
(58, 251, 378, 18, 'Assists', '3', '2025-01-08 17:00:18'),
(59, 236, 378, 18, 'Assists', '7', '2025-01-08 17:00:20'),
(60, 236, 378, 18, 'Fouls', '4', '2025-01-08 17:00:21'),
(61, 236, 378, 18, 'Rebounds', '3', '2025-01-08 17:00:21'),
(62, 236, 378, 18, 'Scores', '5', '2025-01-08 17:00:22'),
(63, 237, 378, 18, 'Scores', '5', '2025-01-08 17:00:26'),
(64, 237, 378, 18, 'Assists', '1', '2025-01-08 17:00:28'),
(65, 237, 378, 18, 'Fouls', '1', '2025-01-08 17:00:28'),
(66, 237, 378, 18, 'Rebounds', '1', '2025-01-08 17:00:28'),
(67, 251, 378, 18, 'Rebounds', '1', '2025-01-08 17:00:30'),
(68, 251, 378, 18, 'Scores', '1', '2025-01-08 17:00:30');

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
(1, 8, 10, 5, 3, '2024-12-17 01:13:48');

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

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `match_id`, `schedule_date`, `schedule_time`, `venue`) VALUES
(88, 376, '2025-01-10', '06:10:00', 'Gym'),
(89, 378, '2025-01-08', '23:00:00', 'Closed Gym'),
(90, 380, '2025-01-08', '23:00:00', 'Open Gym');

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

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `user_id`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(132, 90, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', '2024-12-20 17:15:24', '2024-12-20 18:15:24'),
(152, 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', '2025-01-09 22:06:59', '2025-01-09 23:06:59');

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
(240, 'BSCS - Basketball', 18, '2024-11-02 11:14:51', 67, 1, 0),
(241, 'BSCS - Dama', 22, '2024-11-02 11:14:51', 67, 0, 0),
(242, 'BSCS - Dart', 26, '2024-11-02 11:14:51', 67, 0, 0),
(243, 'BSCS - Chess', 27, '2024-11-02 11:14:51', 67, 0, 0),
(244, 'BSCS - Badminton', 28, '2024-11-02 11:14:51', 67, 0, 0),
(245, 'BSCS - Patintero', 29, '2024-11-02 11:14:51', 67, 0, 0),
(246, 'BSCS - Word Factory', 30, '2024-11-02 11:14:51', 67, 0, 0),
(247, 'CRIM - Volleyball', 2, '2024-11-02 11:14:55', 68, 0, 0),
(248, 'CRIM - Basketball', 18, '2024-11-02 11:14:55', 68, 1, 0),
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
(263, '11-A - STEM - Volleyball', 2, '2024-11-02 14:25:54', 70, 0, 0),
(264, '11-A - STEM - Basketball', 18, '2024-11-02 14:25:54', 70, 0, 0),
(265, '11-A - STEM - Dama', 22, '2024-11-02 14:25:54', 70, 0, 0),
(266, '11-A - STEM - Dart', 26, '2024-11-02 14:25:54', 70, 0, 0),
(267, '11-A - STEM - Chess', 27, '2024-11-02 14:25:54', 70, 0, 0),
(268, '11-A - STEM - Badminton', 28, '2024-11-02 14:25:54', 70, 0, 0),
(269, '11-A - STEM - Patintero', 29, '2024-11-02 14:25:54', 70, 0, 0),
(270, '11-A - STEM - Word Factory', 30, '2024-11-02 14:25:54', 70, 0, 0),
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
(287, 'Nickel - Volleyball', 2, '2024-11-02 14:26:33', 73, 0, 0),
(288, 'Nickel - Basketball', 18, '2024-11-02 14:26:33', 73, 0, 0),
(289, 'Nickel - Dama', 22, '2024-11-02 14:26:33', 73, 0, 0),
(290, 'Nickel - Dart', 26, '2024-11-02 14:26:33', 73, 0, 0),
(291, 'Nickel - Chess', 27, '2024-11-02 14:26:33', 73, 0, 0),
(292, 'Nickel - Badminton', 28, '2024-11-02 14:26:33', 73, 0, 0),
(293, 'Nickel - Patintero', 29, '2024-11-02 14:26:33', 73, 0, 0),
(294, 'Nickel - Word Factory', 30, '2024-11-02 14:26:33', 73, 0, 0),
(311, 'BSBA - Volleyball', 2, '2024-11-09 05:25:00', 76, 0, 0),
(312, 'BSBA - Basketball', 18, '2024-11-09 05:25:00', 76, 0, 1),
(313, 'BSBA - Dama', 22, '2024-11-09 05:25:00', 76, 0, 0),
(314, 'BSBA - Dart', 26, '2024-11-09 05:25:00', 76, 0, 0),
(315, 'BSBA - Chess', 27, '2024-11-09 05:25:00', 76, 0, 0),
(316, 'BSBA - Badminton', 28, '2024-11-09 05:25:00', 76, 0, 0),
(317, 'BSBA - Patintero', 29, '2024-11-09 05:25:00', 76, 0, 0),
(318, 'BSBA - Word Factory', 30, '2024-11-09 05:25:00', 76, 0, 0),
(319, 'EDUC - Volleyball', 2, '2024-11-09 05:25:19', 77, 0, 0),
(320, 'EDUC - Basketball', 18, '2024-11-09 05:25:19', 77, 0, 1),
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
(369, 'A - HUMSS - Word Factory', 30, '2024-12-02 18:19:21', 86, 0, 0);

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
(1, '', '', '', 0, '', 'admin@gmail.com', '$2y$10$DW/oDAXMGiG462kSEGgDjuxW260hugHr9agzALTrnduNJJxt0pioS', 'superadmin', NULL, 'ed4f4c6f286cd0d91c1056bac7f3237186fcdedce090002e9342e81f0e54be3f', '2024-10-06 02:51:55', NULL, NULL),
(56, '', '', '', 0, '', 'hcc@gmail.com', '$2y$10$8R6T3bdAyXwloo098OB6suCHoHXniSeZhqgD6mtstPJXzX8jgfISO', 'School Admin', NULL, NULL, NULL, NULL, 8),
(67, 'Buce', 'Andrew', 'B', 22, '0', 'committee@gmail.com', '$2y$10$g1ZaROoolXsxtimnfv5mA.z3y/PpL4IlO1nT02pf0z7VrwfV/mGKK', 'Committee', 1, NULL, NULL, 18, 8),
(68, 'De Guzman', 'Andrew', 'B', 22, 'Male', 'aaa@gmail.com', '$2y$10$g/T3J0JGHACYXUPlCxA6x.ZhaBfWvPobfl47Au1Qp8Ohi3nZtoohK', 'Department Admin', 1, NULL, NULL, NULL, 8),
(90, 'Buce', 'Drew', '', 22, 'Male', 'a@gmail.com', '$2y$10$VB9qD42kB4TDXYgRUu38weWuVjItTi424oygdgdPyBdMbXCuRcDI.', 'Committee', 1, NULL, NULL, 27, 8);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `brackets`
--
ALTER TABLE `brackets`
  MODIFY `bracket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `game_scoring_rules`
--
ALTER TABLE `game_scoring_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `game_stats_config`
--
ALTER TABLE `game_stats_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `grade_section_course`
--
ALTER TABLE `grade_section_course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `live_scores`
--
ALTER TABLE `live_scores`
  MODIFY `live_score_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4111;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=384;

--
-- AUTO_INCREMENT for table `match_progression_logs`
--
ALTER TABLE `match_progression_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `match_results`
--
ALTER TABLE `match_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=252;

--
-- AUTO_INCREMENT for table `players_info`
--
ALTER TABLE `players_info`
  MODIFY `player_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `player_match_stats`
--
ALTER TABLE `player_match_stats`
  MODIFY `stat_record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `pointing_system`
--
ALTER TABLE `pointing_system`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=458;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

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
-- Constraints for table `live_scores`
--
ALTER TABLE `live_scores`
  ADD CONSTRAINT `fk_teamA_id` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `fk_teamB_id` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`),
  ADD CONSTRAINT `live_scores_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`),
  ADD CONSTRAINT `live_scores_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`);

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`teamA_id`) REFERENCES `teams` (`team_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`teamB_id`) REFERENCES `teams` (`team_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`bracket_id`) REFERENCES `brackets` (`bracket_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_teams_grade_section_course` FOREIGN KEY (`grade_section_course_id`) REFERENCES `grade_section_course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE NO ACTION ON UPDATE CASCADE;

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
