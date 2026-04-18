-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 02:56 AM
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
-- Database: `projdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `xp_reward` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`id`, `name`, `description`, `title`, `icon`, `xp_reward`) VALUES
('all_done', 'SQL Graduate', 'Complete all challenges', 'SQL Graduate', '🎓', 100),
('first_query', 'First Query', 'Run your very first SQL query', 'First Query', '🎯', 25),
('five_done', 'Halfway There', 'Complete 5 challenges', 'Halfway There', '⭐', 50),
('used_count', 'Row Counter', 'Use COUNT() to tally rows', 'Row Counter', '🔢', 25),
('used_group_by', 'Group Thinker', 'Use GROUP BY to aggregate data', 'Group Thinker', '👥', 25),
('used_order_by', 'Sorted Out', 'Sort results using ORDER BY', 'Sorted Out', '📊', 25),
('used_where', 'Filter Master', 'Successfully use a WHERE clause', 'Filter Master', '🔍', 25);

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `prompt` text NOT NULL,
  `hint` text DEFAULT NULL,
  `required_keyword` varchar(50) DEFAULT NULL,
  `xp_reward` int(11) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `required_keywords` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `challenges`
--

INSERT INTO `challenges` (`id`, `slug`, `title`, `prompt`, `hint`, `required_keyword`, `xp_reward`, `display_order`, `required_keywords`) VALUES
(1, 'select_all', 'Welcome to SQL', 'SQL (Structured Query Language) is a programming language used to create, organize and search different databases to better manage your data!\nTo get started, let\'s look into our \"movies\" data set. ', 'Use SELECT * FROM tablename', 'SELECT', 50, 1, 'SELECT'),
(2, 'select_columns', 'Pick Your Columns', 'Wow! That\'s a lot of data, but we don\'t need to look at all of it right now. Let\'s just grab the title and release year. Try listing column names after SELECT separated by a comma.', 'Remember SQL is CASE SENSITIVE', 'release_year', 50, 2, 'release_year'),
(3, 'where_genre', 'WHERE is the Drama', 'We now have a refined data structure, but let\'s narrow it down even more! We can use the WHERE keyword ', 'Use WHERE genre = \'Drama\' — text values need quotes.', 'WHERE', 75, 3, 'WHERE'),
(4, 'where_rating', 'High Rated', 'This is great, but what if we want to narrow it down even further? Le\'ts find all the dramatic movies with an imdb_rating greater than 8.5. You can add another WHERE condition with the && operator.', 'The > operator means greater than.', 'WHERE', 75, 4, 'WHERE'),
(5, 'order_by_year', 'ORDER BY', 'Now that we\'ve sufficiently delved into the drama, let\'s refresh! List ALL movies again, ordered by release_year, newest first.', 'Use ORDER BY column DESC for descending order.', 'ORDER BY', 75, 5, 'ORDER BY'),
(6, 'count_all', 'How Many Movies?', 'It\'s great to have them in order, but how can we find out how many movies there are, total?Write a query that returns the total number of movies in the table.', 'Use COUNT(*) — it counts rows.', 'COUNT', 100, 6, 'COUNT'),
(7, 'avg_rating', 'Average Rating', 'That\'s a lot of movies! I wonder how well they\'ve all scored? Let\'s find the average imdb_rating across all movies.', 'Use AVG(column_name).', 'AVG', 100, 7, 'AVG'),
(8, 'group_by_genre', 'GROUP BY Genre', 'That\'s a pretty high number! Let\'s make things a little more complicated. Say we want to see how many of each genre there are, we\'d use a handy thing called GROUP BY. GROUP BY let\'s you group your data into groups with equal values! Let\'s try it now!', 'Use GROUP BY after your FROM clause.', 'GROUP BY', 125, 8, 'GROUP BY'),
(9, '', 'The Grand Finale', 'Time to put it all together, show us what you\'ve leared! For movies released after 2000, count how many movies each genre has, and show only the genres with the most movies first.', 'You\'ll need WHERE to filter by year, GROUP BY to group by genre, COUNT to count per group, and ORDER BY to sort by the count.', 'GROUP BY', 100, 9, 'WHERE,GROUP BY,COUNT,ORDER BY');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `z_index` int(11) DEFAULT 10,
  `unlock_achievement_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `icon`, `image_path`, `z_index`, `unlock_achievement_id`) VALUES
('beard', 'Beard', '〰️', 'items/BeepoBeard.png', 30, 'used_count'),
('bow', 'Bow', '🎀', 'items/BeepoBow.png', 25, 'used_order_by'),
('glasses', 'Glasses', '👓', 'items/BeepoGlasses.png', 20, 'first_query'),
('grad', 'Grad Cap', '🎓', 'items/BeepoGrad.png', 35, 'all_done'),
('lashes', 'Lashes', '👀', 'items/BeepoLashes.png', 22, 'used_where'),
('wings', 'Wings', '🪽', 'items/BeepoWings.png', -1, 'used_group_by');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `release_year` int(11) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `imdb_rating` decimal(3,1) NOT NULL,
  `director` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `release_year`, `genre`, `imdb_rating`, `director`) VALUES
(1, 'The Shawshank Redemption', 1994, 'Drama', 9.3, 'Frank Darabont'),
(2, 'The Godfather', 1972, 'Crime', 9.2, 'Francis Ford Coppola'),
(3, 'The Dark Knight', 2008, 'Action', 9.0, 'Christopher Nolan'),
(4, 'Schindler\'s List', 1993, 'Drama', 8.9, 'Steven Spielberg'),
(5, 'Pulp Fiction', 1994, 'Crime', 8.9, 'Quentin Tarantino'),
(6, 'Forrest Gump', 1994, 'Drama', 8.8, 'Robert Zemeckis'),
(7, 'Inception', 2010, 'Sci-Fi', 9.0, 'Christopher Nolan'),
(8, 'The Matrix', 1999, 'Sci-Fi', 8.7, 'The Wachowskis'),
(9, 'Goodfellas', 1990, 'Crime', 8.7, 'Martin Scorsese'),
(10, 'Interstellar', 2014, 'Sci-Fi', 9.0, 'Christopher Nolan'),
(11, 'The Silence of the Lambs', 1991, 'Thriller', 8.6, 'Jonathan Demme'),
(12, 'Saving Private Ryan', 1998, 'Drama', 8.9, 'Steven Spielberg'),
(13, 'Gladiator', 2000, 'Action', 8.5, 'Ridley Scott'),
(14, 'The Lion King', 1994, 'Animation', 8.5, 'Roger Allers'),
(15, 'Whiplash', 2014, 'Drama', 8.5, 'Damien Chazelle'),
(16, 'Mad Max: Fury Road', 2015, 'Action', 8.1, 'George Miller'),
(17, 'Get Out', 2017, 'Thriller', 7.7, 'Jordan Peele'),
(18, 'La La Land', 2016, 'Drama', 8.5, 'Damien Chazelle'),
(19, 'Parasite', 2019, 'Thriller', 8.5, 'Bong Joon-ho'),
(20, 'Everything Everywhere All at Once', 2022, 'Sci-Fi', 7.8, 'Daniel Kwan');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `xp` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `xp`) VALUES
(1, 'nicos10517', 'nicomlprivate@gmail.com', '$2y$10$SxL8m6n1e4/0qCAPKiLgpObPi.GoJqQIB45RCrt/Kd/1sqzpsG1US', '2026-04-13 14:12:17', 1025);

-- --------------------------------------------------------

--
-- Table structure for table `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `achievement_id` varchar(50) NOT NULL,
  `earned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_id`, `earned_at`) VALUES
(1, 1, 'first_query', '2026-04-17 15:36:44'),
(2, 1, 'used_where', '2026-04-17 16:00:16'),
(4, 1, 'used_order_by', '2026-04-17 16:04:36'),
(5, 1, 'five_done', '2026-04-17 16:04:36'),
(6, 1, 'used_count', '2026-04-17 16:14:13'),
(9, 1, 'used_group_by', '2026-04-17 16:21:45'),
(16, 1, 'all_done', '2026-04-17 17:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_items`
--

CREATE TABLE `user_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `equipped` tinyint(1) DEFAULT 0,
  `unlocked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_items`
--

INSERT INTO `user_items` (`id`, `user_id`, `item_id`, `equipped`, `unlocked_at`) VALUES
(8, 1, 'glasses', 1, '2026-04-17 15:36:44'),
(9, 1, 'lashes', 0, '2026-04-17 16:00:16'),
(10, 1, 'bow', 1, '2026-04-17 16:04:36'),
(11, 1, 'beard', 1, '2026-04-17 16:14:13'),
(12, 1, 'wings', 1, '2026-04-17 16:21:45'),
(13, 1, 'grad', 1, '2026-04-17 17:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `challenge_id`, `completed_at`) VALUES
(1, 1, 1, '2026-04-17 15:36:44'),
(2, 1, 2, '2026-04-17 15:49:53'),
(3, 1, 3, '2026-04-17 16:00:16'),
(4, 1, 4, '2026-04-17 16:01:00'),
(5, 1, 5, '2026-04-17 16:04:36'),
(6, 1, 6, '2026-04-17 16:14:13'),
(7, 1, 7, '2026-04-17 16:16:07'),
(8, 1, 8, '2026-04-17 16:21:45'),
(9, 1, 9, '2026-04-17 17:20:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `achievement_id` (`achievement_id`);

--
-- Indexes for table `user_items`
--
ALTER TABLE `user_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_item_unique` (`user_id`,`item_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_challenge` (`user_id`,`challenge_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_items`
--
ALTER TABLE `user_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_achievements_ibfk_2` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
