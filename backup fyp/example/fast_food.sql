-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-03-26 11:06:43
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `fast_food`
--

-- --------------------------------------------------------

--
-- 表的结构 `admins`
--

CREATE TABLE `admins` (
  `Admin_Id` int(11) NOT NULL,
  `admin_username` varchar(50) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `admin_picture` varchar(250) DEFAULT NULL,
  `admin_name` varchar(50) NOT NULL,
  `admin_role` varchar(50) NOT NULL,
  `admin_Gender` enum('Male','Female','Other') NOT NULL,
  `admin_phone_num` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 转存表中的数据 `admins`
--

INSERT INTO `admins` (`Admin_Id`, `admin_username`, `admin_password`, `admin_picture`, `admin_name`, `admin_role`, `admin_Gender`, `admin_phone_num`) VALUES
(1, 'admin123', 'admin@234', '/images/admin_profile.png', 'Tan Chun Hoong', 'admin', 'Male', '0123456789');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `User_id` int(11) NOT NULL,
  `Full_name` varchar(100) NOT NULL,
  `User_password` varchar(255) NOT NULL,
  `User_Email` varchar(100) NOT NULL,
  `User_phone_num` varchar(20) NOT NULL,
  `User_DOB` date DEFAULT NULL,
  `User_Address` text DEFAULT NULL,
  `User_Picture` varchar(255) DEFAULT NULL,
  `User_Gender` varchar(20) DEFAULT NULL,
  `User_Profile` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`User_id`, `Full_name`, `User_password`, `User_Email`, `User_phone_num`, `User_DOB`, `User_Address`, `User_Picture`, `User_Gender`, `User_Profile`) VALUES
(1, 'Tan Chun Hoong', '$2y$10$ERozrY35YR.AG21Ca0utd.woig7beP0WHTYpKqVigKmAXko97BN3S', 'chunhoongtan28@gmail.com', '0122282419', '2024-08-16', NULL, NULL, NULL, NULL);

--
-- 转储表的索引
--

--
-- 表的索引 `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`Admin_Id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admins`
--
ALTER TABLE `admins`
  MODIFY `Admin_Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
