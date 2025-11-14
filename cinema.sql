-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 11:03 AM
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
-- Database: `cinema`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `OrderID` int(11) NOT NULL,
  `CustName` varchar(100) NOT NULL,
  `CustEmail` varchar(100) NOT NULL,
  `CustPhone` varchar(30) NOT NULL,
  `PaymentMethod` enum('cash','card') NOT NULL,
  `PaidAmount` decimal(10,2) NOT NULL,
  `UserID` int(10) UNSIGNED DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`OrderID`, `CustName`, `CustEmail`, `CustPhone`, `PaymentMethod`, `PaidAmount`, `UserID`, `CreatedAt`) VALUES
(13, 'f31ee', 'f31ee@localhost.com', '93838287', 'cash', 32.00, NULL, '2025-11-06 07:09:42'),
(14, 'f31ee', 'f31ee@localhost.com', '92828131', 'cash', 24.00, NULL, '2025-11-06 07:31:25'),
(15, 'f31ee', 'f31ee@localhost.com', '87999979', 'cash', 16.00, NULL, '2025-11-06 09:19:50'),
(16, 'yqyq1', 'yqyq1@gmail.com', '98765432', 'cash', 16.00, 4, '2025-11-06 09:32:11'),
(18, 'f31ee', 'f31ee@localhost.com', '92829182', 'cash', 8.00, NULL, '2025-11-06 09:44:55'),
(20, 'f31ee', 'f31ee@localhost.com', '82927182', 'cash', 12.80, NULL, '2025-11-10 03:12:14'),
(22, 'f31ee', 'f31ee@localhost.com', '89281672', 'cash', 30.00, NULL, '2025-11-11 06:25:12'),
(24, 'f33ee', 'f33ee@localhost.com', '82917215', 'cash', 16.00, 8, '2025-11-13 01:50:19'),
(25, 'f31ee', 'f31ee@localhost.com', '82928123', 'cash', 30.00, 7, '2025-11-13 02:15:00'),
(26, 'f34ee', 'f34ee@localhost.com', '89754256', 'cash', 40.00, 9, '2025-11-13 02:58:05'),
(27, 'f34ee', 'f34ee@localhost.com', '8776557', 'cash', 8.00, 9, '2025-11-13 03:00:35');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `birthday` date NOT NULL,
  `experience` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `name`, `email`, `start_date`, `birthday`, `experience`) VALUES
(1, 'johnwank', 'johnwank@hotmail.com', '2025-11-19', '2005-06-07', 'na asdasdknsnd 212jnansdasdn'),
(2, 'custone demu', 'f31ee@localhost.com', '2025-11-28', '2005-11-24', 'experienceadadasd 2121'),
(3, 'custfourfour demo', 'f34ee@localhost.com', '2025-11-25', '2003-11-19', 'rtttttttttttttttttttttttttttttttttdad');

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `MovieCode` varchar(20) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `PosterPath` varchar(255) DEFAULT NULL,
  `Synopsis` text NOT NULL,
  `Genre` varchar(30) NOT NULL,
  `TicketPrice` float NOT NULL,
  `Rating` varchar(10) NOT NULL,
  `ReleaseDate` date DEFAULT NULL,
  `DurationMinutes` int(11) NOT NULL,
  `Trending` tinyint(1) NOT NULL DEFAULT 0,
  `OnSale` tinyint(1) NOT NULL DEFAULT 0,
  `Language` varchar(30) NOT NULL DEFAULT 'English'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`MovieCode`, `Title`, `PosterPath`, `Synopsis`, `Genre`, `TicketPrice`, `Rating`, `ReleaseDate`, `DurationMinutes`, `Trending`, `OnSale`, `Language`) VALUES
('001_TA', 'Tron: Ares', './images/movies/tron_ares.jpg', 'Mankind encounters AI beings for the first time when a highly sophisticated programme, Ares, leaves the digital world for a dangerous mission in the real world', 'Sci-Fi', 10, 'PG-13', '2025-10-09', 119, 1, 0, 'English'),
('002_BP2', 'Black Phone 2', './images/movies/bp2.jpg', 'Bad dreams haunt 15-year-old Gwen as she receives calls from the black phone and sees disturbing visions of three boys being stalked at a winter camp', 'Horror/Thriller', 10, 'R21', '2025-10-16', 114, 1, 0, 'English'),
('003_731', '731test', './images/movies/731.jpg', 'In Northeast China, dark and mysterious truths unravel about the horrific experimentation on prison inmates conducted at Unit 731', 'Historical', 10, 'R21', '2025-09-18', 123, 1, 0, 'Japanese/Chinese'),
('004_NOC', 'No Other Choices', './images/movies/noc.jpg', 'After years of unemployment, a man creates a unique and dark plan to secure a job, eliminating his competition.', 'Comedy', 10, 'PG-13', '2025-10-23', 139, 1, 0, 'Korean'),
('005_DSIC', 'Demon Slayer: Infinity Castle', './images/movies/dsic.jpg', 'Tanjiro Kamado and other members of the Demon Slayer Corps find themselves in an epic battle at Infinity Castle.', 'Anime', 10, 'PG-13', '2025-08-14', 155, 1, 0, 'Japanese'),
('006_TCLR', 'The Conjuring: Last Rites', './images/movies/tclr.jpg', 'In 1986 paranormal investigators Ed and Lorraine Warren travel to Pennsylvania to vanquish a demon from a family home.', 'Horror/Thriller', 10, 'PG-13', '2025-09-04', 135, 1, 0, 'English'),
('007_JOK2', 'Joker: Folie À Deux', './images/movies/joker2.jpg', 'Joker: Folie À Deux finds Arthur Fleck institutionalised at Arkham awaiting trial for his crimes as Joker. While struggling with his dual identity, Arthur not only stumbles upon true love, but also finds the music that always been inside him.', 'Drama/Thriller', 10, 'NC-16', '2024-10-03', 138, 0, 0, 'English'),
('008_JWC4', 'John Wick: Chapter 4', './images/movies/jwc4.jpg', 'John Wick (Keanu Reeves) uncovers a path to defeating The High Table. But before he can earn his freedom, Wick must face off against a new enemy with powerful alliances across the globe and forces that turn old friends into foes.', 'Action/Thriller', 10, 'M18', '2023-03-23', 170, 0, 0, 'English'),
('009_JWD', 'Jurassic World Dominion', './images/movies/jwd.jpg', 'In the epic conclusion to the Jurassic era, two generations unite for the first time as Chris Pratt and Bryce Dallas Howard are joined by Laura Dern, Jeff Goldblum and Sam Neill in a bold, timely and breathtaking new adventure that spans the globe.', 'Action/Adventure', 10, 'PG-13', '2022-06-09', 147, 0, 0, 'English'),
('010_SMNWH', 'Spider-Man: No Way Home', './images/movies/spmnwh.jpg', 'For the first time in the cinematic history of Spider-Man, our friendly neighborhood hero is unmasked and no longer able to separate his normal life from the high-stakes of being a Super Hero. When he asks for help from Doctor Strange the stakes become even more dangerous, forcing him to discover what it truly means to be Spider-Man.', 'Action/Adventure', 10, 'PG', '2021-12-16', 148, 0, 0, 'English'),
('011_SONIC', 'Sonic The Hedgehog', './images/movies/sonic.jpg', 'Sonic and Tom join forces to try and stop the villainous Dr. Robotnik (Jim Carrey) from capturing Sonic and using his immense powers for world domination.', 'Action/Adventure', 10, 'PG', '2020-02-20', 99, 0, 0, 'English'),
('012_AVGEG', 'Marvel Studios Avengers: Endgame', './images/movies/avengers.jpg', 'The grave course of events set in motion by Thanos that wiped out half the universe and fractured the Avengers ranks compels the remaining Avengers to take one final stand in Marvel Studios’ grand conclusion to twenty-two films', 'Action/Adventure', 10, 'PG-13', '2019-04-24', 181, 0, 0, 'English');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `PromoID` int(11) NOT NULL,
  `PromoName` varchar(255) NOT NULL,
  `PromoImage` varchar(512) DEFAULT NULL,
  `PromoDescription` text DEFAULT NULL,
  `PromoCode` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`PromoID`, `PromoName`, `PromoImage`, `PromoDescription`, `PromoCode`) VALUES
(1, 'Returning Customer Discount', './images/promotions/promo1.jpg', 'Returning Customers get to enjoy 20% off...', 'SECOND1');

-- --------------------------------------------------------

--
-- Table structure for table `screentime`
--

CREATE TABLE `screentime` (
  `hall_code` varchar(4) NOT NULL,
  `timeslot` time NOT NULL,
  `movie_code` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `screentime`
--

INSERT INTO `screentime` (`hall_code`, `timeslot`, `movie_code`) VALUES
('H1', '17:00:00', '001_TA'),
('H2', '09:30:00', '002_BP2'),
('H2', '17:00:00', '002_BP2'),
('H3', '09:30:00', '003_731'),
('H3', '17:00:00', '003_731'),
('H4', '09:30:00', '004_NOC'),
('H4', '17:00:00', '004_NOC'),
('H5', '09:30:00', '005_DSIC'),
('H5', '17:00:00', '005_DSIC'),
('H6', '09:30:00', '006_TCLR'),
('H6', '17:00:00', '006_TCLR'),
('H1', '13:30:00', '007_JOK2'),
('H1', '20:30:00', '007_JOK2'),
('H2', '13:30:00', '008_JWC4'),
('H2', '20:30:00', '008_JWC4'),
('H3', '13:30:00', '009_JWD'),
('H3', '20:30:00', '009_JWD'),
('H1', '09:30:00', '010_SMNWH'),
('H4', '13:30:00', '010_SMNWH'),
('H4', '20:30:00', '010_SMNWH'),
('H5', '13:30:00', '011_SONIC'),
('H5', '20:30:00', '011_SONIC'),
('H6', '13:30:00', '012_AVGEG'),
('H6', '20:30:00', '012_AVGEG'),
('H7', '21:59:00', '012_AVGEG');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `TicketID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `HallID` varchar(10) NOT NULL,
  `ShowDate` date NOT NULL,
  `TimeSlot` time NOT NULL,
  `SeatCode` varchar(10) NOT NULL,
  `MovieCode` varchar(20) DEFAULT NULL,
  `UserID` int(10) UNSIGNED DEFAULT NULL,
  `BookingTime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`TicketID`, `OrderID`, `HallID`, `ShowDate`, `TimeSlot`, `SeatCode`, `MovieCode`, `UserID`, `BookingTime`) VALUES
(39, 16, 'H1', '2025-11-06', '09:30:00', 'B7', '001_TA', 4, '2025-11-06 09:32:11'),
(40, 16, 'H1', '2025-11-06', '09:30:00', 'D7', '001_TA', 4, '2025-11-06 09:32:11'),
(70, 24, 'H3', '2025-11-29', '13:30:00', 'B2', '009_JWD', 8, '2025-11-13 01:50:19'),
(71, 24, 'H3', '2025-11-29', '13:30:00', 'D3', '009_JWD', 8, '2025-11-13 01:50:19'),
(72, 25, 'H4', '2025-11-30', '20:30:00', 'D5', '010_SMNWH', 7, '2025-11-13 02:15:00'),
(73, 25, 'H4', '2025-11-30', '20:30:00', 'E3', '010_SMNWH', 7, '2025-11-13 02:15:00'),
(74, 25, 'H4', '2025-11-30', '20:30:00', 'E6', '010_SMNWH', 7, '2025-11-13 02:15:00'),
(75, 26, 'H4', '2025-11-19', '17:00:00', 'B4', '004_NOC', 9, '2025-11-13 02:58:05'),
(76, 26, 'H4', '2025-11-19', '17:00:00', 'C6', '004_NOC', 9, '2025-11-13 02:58:05'),
(77, 26, 'H4', '2025-11-19', '17:00:00', 'D6', '004_NOC', 9, '2025-11-13 02:58:05'),
(78, 26, 'H4', '2025-11-19', '17:00:00', 'D7', '004_NOC', 9, '2025-11-13 02:58:05'),
(79, 27, 'H6', '2025-11-25', '13:30:00', 'E8', '012_AVGEG', 9, '2025-11-13 03:00:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(10) UNSIGNED NOT NULL,
  `FirstName` char(40) DEFAULT NULL,
  `LastName` char(40) DEFAULT NULL,
  `Username` varchar(20) NOT NULL,
  `Email` varchar(40) NOT NULL,
  `UserPassword` varchar(40) NOT NULL,
  `isAdmin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FirstName`, `LastName`, `Username`, `Email`, `UserPassword`, `isAdmin`) VALUES
(1, 'The', 'Admin', 'admin', 'admin01@gmail.com', 'P@ssw0rd', 1),
(2, 'dsn', 'yo', 'dsn1', 'dydawson2@gmail.com', 'fer1!', 0),
(3, 'john', 'wang', 'john1', 'john1@gmail.com', 'asd123!', 0),
(4, 'yunqi', 'lim', 'yqyq1', 'yqyq1@gmail.com', 'qwe123!', 0),
(6, 'custtwo', 'demo', 'f32ee', 'f32ee@localhost.com', 'qwe12345!', 0),
(7, 'custone', 'demu', 'f31ee', 'f31ee@localhost.com', 'qwe12345!', 0),
(8, 'custthree', 'demo', 'f33ee', 'f33ee@localhost.com', 'qwe12345!', 0),
(9, 'custfourfour', 'demo', 'f34ee', 'f34ee@localhost.com', 'qwe12345!', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `fk_booking_user` (`UserID`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_jobs_email` (`email`),
  ADD KEY `idx_jobs_start_date` (`start_date`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`MovieCode`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`PromoID`);

--
-- Indexes for table `screentime`
--
ALTER TABLE `screentime`
  ADD PRIMARY KEY (`hall_code`,`timeslot`),
  ADD KEY `fk_movie` (`movie_code`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`TicketID`),
  ADD UNIQUE KEY `unique_seat` (`HallID`,`ShowDate`,`TimeSlot`,`SeatCode`),
  ADD KEY `fk_ticket_user` (`UserID`),
  ADD KEY `fk_ticket_movie` (`MovieCode`),
  ADD KEY `fk_ticket_order` (`OrderID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `PromoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `TicketID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `screentime`
--
ALTER TABLE `screentime`
  ADD CONSTRAINT `fk_movie` FOREIGN KEY (`movie_code`) REFERENCES `movies` (`MovieCode`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_ticket_movie` FOREIGN KEY (`MovieCode`) REFERENCES `movies` (`MovieCode`),
  ADD CONSTRAINT `fk_ticket_order` FOREIGN KEY (`OrderID`) REFERENCES `bookings` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
