-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2026 at 04:47 PM
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
-- Database: `ums_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_bill` (IN `p_reading_id` INT, IN `p_tariff_id` INT, IN `p_due_days` INT)   BEGIN
    DECLARE v_units DECIMAL(10,2);
    DECLARE v_meter INT;
    DECLARE v_customer INT;
    DECLARE v_rate DECIMAL(10,2);
    DECLARE v_fixed DECIMAL(10,2);
    DECLARE v_date DATE;
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_due DATE;

    -- Get reading details
    SELECT meter_id, units_used, reading_date
    INTO v_meter, v_units, v_date
    FROM meter_readings
    WHERE id = p_reading_id;

    -- Get customer
    SELECT customer_id INTO v_customer
    FROM meters WHERE id = v_meter;

    -- Get tariff rate and fixed fee
    SELECT price_per_unit, fixed_charge
    INTO v_rate, v_fixed
    FROM tariffs
    WHERE id = p_tariff_id;

    -- Calculate amount
    SET v_amount = (v_units * v_rate) + v_fixed;

    -- Due date
    SET v_due = DATE_ADD(v_date, INTERVAL p_due_days DAY);

    -- Insert bill
    INSERT INTO bills(
        reading_id,
        customer_id,
        meter_id,
        tariff_id,
        billing_month,
        billing_year,
        bill_date,
        due_date,
        units,
        amount,
        late_fee,
        total_amount,
        amount_paid,
        outstanding,
        status
    ) VALUES (
        p_reading_id,
        v_customer,
        v_meter,
        p_tariff_id,
        MONTH(v_date),
        YEAR(v_date),
        v_date,
        v_due,
        v_units,
        v_amount,
        0,
        v_amount,
        0,
        v_amount,
        'Pending'
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_list_defaulters` (IN `p_days` INT)   BEGIN
    SELECT 
        c.customer_code,
        c.full_name,
        b.id AS bill_id,
        b.billing_month,
        b.billing_year,
        b.total_amount,
        b.amount_paid,
        b.outstanding,
        b.due_date
    FROM bills b
    JOIN customers c ON c.id = b.customer_id
    WHERE b.outstanding > 0
      AND DATEDIFF(CURDATE(), b.due_date) > p_days
    ORDER BY b.due_date;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_calc_bill` (`units` DECIMAL(10,2), `t_id` INT) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE v_rate DECIMAL(10,2);
    DECLARE v_fixed DECIMAL(10,2);
    SELECT rate_per_unit, fixed_charge
      INTO v_rate, v_fixed
      FROM tariffs
     WHERE id = t_id;
    RETURN v_fixed + (units * v_rate);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_late_fee` (`base_amount` DECIMAL(10,2)) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    RETURN ROUND(base_amount * 0.05, 2);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `reading_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `meter_id` int(11) NOT NULL,
  `tariff_id` int(11) NOT NULL,
  `billing_month` int(11) NOT NULL,
  `billing_year` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `units` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outstanding` decimal(10,2) NOT NULL,
  `status` enum('Unpaid','Partially Paid','Paid') NOT NULL DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `reading_id`, `customer_id`, `meter_id`, `tariff_id`, `billing_month`, `billing_year`, `bill_date`, `due_date`, `units`, `amount`, `late_fee`, `total_amount`, `amount_paid`, `outstanding`, `status`, `created_at`) VALUES
(1, NULL, 1, 2, 3, 12, 2025, '2025-12-02', '2025-12-16', 0.00, 100.00, 0.00, 100.00, 100.00, -100.00, 'Paid', '2025-12-02 07:43:48'),
(2, NULL, 3, 4, 4, 12, 2025, '2025-12-02', '2025-12-16', 1000.00, 40150.00, 0.00, 40150.00, 40150.00, 0.00, 'Paid', '2025-12-02 13:22:13'),
(3, NULL, 1, 2, 3, 12, 2025, '2025-12-02', '2025-12-16', 90.00, 2800.00, 0.00, 2800.00, 28000.00, 0.00, 'Paid', '2025-12-02 14:52:46'),
(4, NULL, 2, 3, 2, 12, 2025, '2025-12-02', '2025-12-16', 100.00, 5750.00, 0.00, 5750.00, 50000.00, 0.00, 'Paid', '2025-12-02 14:54:30'),
(5, 8, 3, 5, 3, 12, 2025, '2025-12-03', '2025-12-17', 90.00, 2700.00, 0.00, 2800.00, 28000.00, 0.00, 'Paid', '2025-12-02 23:04:13'),
(6, 9, 3, 5, 3, 12, 2025, '2025-12-03', '2025-12-17', 90.00, 2700.00, 0.00, 2800.00, 2800.00, 0.00, 'Paid', '2025-12-02 23:05:30'),
(10, 21, 3, 4, 4, 12, 2025, '2025-12-03', '2025-12-17', 19000.00, 760150.00, 0.00, 760150.00, 700000.00, 60150.00, 'Partially Paid', '2025-12-03 08:56:44'),
(11, 22, 2, 3, 1, 12, 2025, '2025-12-03', '2025-12-17', 500.00, 22650.00, 0.00, 22650.00, 23000.00, 0.00, 'Paid', '2025-12-03 09:12:29'),
(12, 23, 1, 6, 4, 12, 2025, '2025-12-03', '2025-12-17', 10.00, 550.00, 0.00, 550.00, 0.00, 550.00, '', '2025-12-03 17:23:34'),
(13, 24, 3, 4, 4, 12, 2025, '2025-12-03', '2025-12-17', 1000.00, 40150.00, 0.00, 40150.00, 0.00, 40150.00, '', '2025-12-03 17:24:45'),
(14, 25, 1, 6, 4, 12, 2025, '2025-12-03', '2025-12-17', 100.00, 4150.00, 0.00, 4150.00, 0.00, 4150.00, '', '2025-12-03 18:32:47'),
(15, 26, 6, 7, 1, 1, 2026, '2026-01-06', '2026-01-20', 10.00, 600.00, 0.00, 600.00, 0.00, 600.00, '', '2026-01-06 03:41:15'),
(16, 27, 7, 8, 1, 1, 2026, '2026-01-06', '2026-01-20', 20.00, 1050.00, 0.00, 1050.00, 0.00, 1050.00, '', '2026-01-06 03:45:27'),
(17, 28, 2, 3, 1, 1, 2026, '2026-01-06', '2026-01-20', 20.00, 1050.00, 0.00, 1050.00, 1050.00, 0.00, 'Paid', '2026-01-06 03:48:25'),
(19, 30, 2, 3, 1, 1, 2026, '2026-01-06', '2026-01-20', 5.00, 375.00, 0.00, 375.00, 0.00, 375.00, '', '2026-01-06 05:53:08'),
(20, 31, 6, 7, 1, 1, 2026, '2026-01-06', '2026-01-20', 70.00, 3300.00, 0.00, 3300.00, 0.00, 3300.00, '', '2026-01-06 10:21:59'),
(22, 33, 7, 8, 1, 1, 2026, '2026-01-06', '2026-01-20', 5.00, 375.00, 0.00, 375.00, 5000.00, 0.00, 'Paid', '2026-01-06 11:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `type` enum('Household','Business','Government') NOT NULL DEFAULT 'Household',
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `full_name`, `type`, `address`, `phone`, `email`, `created_at`) VALUES
(1, 'CUST001', 'ABC Family', 'Household', 'No.10, Main Road', '0771234567', 'abc@example.com', '2025-12-02 07:41:16'),
(2, 'CUST002', 'XYZ Traders', 'Business', 'No.45, Town', '0711111111', 'thisalchathnuka@gmail.com', '2025-12-02 07:41:16'),
(3, 'CUST003', 'thisal', 'Household', 'no/255 galle road   matara', '116', 'thisalchathnuka80@gmail.com', '2025-12-02 13:15:06'),
(4, 'CUST004', 'nimalsx', 'Household', 'no/255 galle road   matara', '0765498219', 'chathnsxukathisal@gamil.com', '2025-12-03 16:09:51'),
(5, 'CUST005', 'nimalsx', 'Household', 'no/255 galle road   matara', '0765498219', 'chathnsxukathisal@gamil.com', '2025-12-03 17:22:05'),
(6, 'CUST006', 'Banuka sineth nadun bandara', 'Business', 'Anuradhapura,Thirappane', '0717349032', 'sinethbanuka2@gmail.com', '2026-01-06 03:39:58'),
(7, 'CUST007', 'Shimal Rashmitha', 'Business', 'Madagama,Bandaragama', '0772662900', 'thisalchathnuka@gmail.com', '2026-01-06 03:44:04'),
(9, 'CUST008', 'Shimal rashmitha', 'Household', 'fergrtght5y', '0772662900', 'thisalchathnuka@gmail.com', '2026-01-06 11:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `meters`
--

CREATE TABLE `meters` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `utility_id` int(11) NOT NULL,
  `meter_number` varchar(50) NOT NULL,
  `install_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meters`
--

INSERT INTO `meters` (`id`, `customer_id`, `utility_id`, `meter_number`, `install_date`, `status`, `created_at`) VALUES
(1, 1, 1, 'ELEC-1001', '2024-01-01', 'Active', '2025-12-02 07:41:16'),
(2, 1, 2, 'WATR-2001', '2024-01-01', 'Active', '2025-12-02 07:41:16'),
(3, 2, 1, 'ELEC-1002', '2024-01-01', 'Active', '2025-12-02 07:41:16'),
(4, 3, 3, '02', '2025-12-02', 'Active', '2025-12-02 13:15:43'),
(5, 3, 2, '22', '2025-12-01', 'Active', '2025-12-02 22:53:31'),
(6, 1, 3, '1221', '2025-11-03', 'Active', '2025-12-03 17:22:40'),
(7, 6, 1, 'MTR-001222', '2026-01-06', 'Active', '2026-01-06 03:40:40'),
(8, 7, 1, 'MTR-001223', '2026-01-06', 'Active', '2026-01-06 03:44:49'),
(9, 9, 1, 'MTR-001224', '2026-01-06', 'Active', '2026-01-06 11:19:28'),
(10, 9, 1, 'MTR-001225', '2026-01-06', 'Active', '2026-01-06 11:22:27'),
(11, 9, 3, 'MTR-001226', '2026-01-08', 'Active', '2026-01-08 11:16:49'),
(12, 9, 3, 'MTR-001227', '2026-01-08', 'Active', '2026-01-08 11:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `meter_readings`
--

CREATE TABLE `meter_readings` (
  `id` int(11) NOT NULL,
  `meter_id` int(11) NOT NULL,
  `reading_date` date NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL,
  `current_reading` decimal(10,2) NOT NULL,
  `units_used` decimal(10,2) GENERATED ALWAYS AS (`current_reading` - `previous_reading`) STORED,
  `billing_month` int(11) NOT NULL,
  `billing_year` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meter_readings`
--

INSERT INTO `meter_readings` (`id`, `meter_id`, `reading_date`, `previous_reading`, `current_reading`, `billing_month`, `billing_year`, `created_at`) VALUES
(1, 2, '2025-12-02', 10.00, 10.00, 12, 2025, '2025-12-02 07:43:48'),
(2, 4, '2025-12-02', 0.00, 1000.00, 12, 2025, '2025-12-02 13:22:13'),
(3, 2, '2025-12-02', 10.00, 100.00, 12, 2025, '2025-12-02 14:52:46'),
(4, 3, '2025-12-02', 0.00, 100.00, 12, 2025, '2025-12-02 14:54:30'),
(5, 5, '2025-12-02', 0.00, 10.00, 12, 2025, '2025-12-02 22:53:48'),
(6, 5, '2025-12-02', 0.00, 10.00, 12, 2025, '2025-12-02 22:56:07'),
(7, 5, '2025-12-02', 0.00, 10.00, 12, 2025, '2025-12-02 22:56:11'),
(8, 5, '2025-12-03', 10.00, 100.00, 12, 2025, '2025-12-02 23:04:13'),
(9, 5, '2025-12-03', 10.00, 100.00, 12, 2025, '2025-12-02 23:05:30'),
(10, 5, '2025-12-03', 10.00, 100.00, 12, 2025, '2025-12-02 23:05:34'),
(11, 1, '2025-12-03', 0.00, 10.00, 12, 2025, '2025-12-03 05:53:31'),
(13, 1, '2025-12-03', 0.00, 10.00, 12, 2025, '2025-12-03 05:54:38'),
(14, 1, '2025-12-03', 0.00, 10.00, 12, 2025, '2025-12-03 06:02:30'),
(15, 3, '2025-12-03', 100.00, 1000.00, 12, 2025, '2025-12-03 06:11:06'),
(17, 3, '2025-12-03', 1000.00, 3000.00, 12, 2025, '2025-12-03 06:24:52'),
(19, 3, '2025-12-03', 1000.00, 3000.00, 12, 2025, '2025-12-03 06:27:16'),
(20, 3, '2025-12-03', 1000.00, 3000.00, 12, 2025, '2025-12-03 06:28:09'),
(21, 4, '2025-12-03', 1000.00, 20000.00, 12, 2025, '2025-12-03 08:56:44'),
(22, 3, '2025-12-03', 3000.00, 3500.00, 12, 2025, '2025-12-03 09:12:29'),
(23, 6, '2025-12-03', 0.00, 10.00, 12, 2025, '2025-12-03 17:23:34'),
(24, 4, '2025-12-03', 20000.00, 21000.00, 12, 2025, '2025-12-03 17:24:45'),
(25, 6, '2025-12-03', 10.00, 110.00, 12, 2025, '2025-12-03 18:32:47'),
(26, 7, '2026-01-06', 0.00, 10.00, 1, 2026, '2026-01-06 03:41:15'),
(27, 8, '2026-01-06', 0.00, 20.00, 1, 2026, '2026-01-06 03:45:27'),
(28, 3, '2026-01-06', 3500.00, 3520.00, 1, 2026, '2026-01-06 03:48:25'),
(29, 8, '2026-01-06', 20.00, 25.00, 1, 2026, '2026-01-06 05:17:08'),
(30, 3, '2026-01-06', 3520.00, 3525.00, 1, 2026, '2026-01-06 05:53:08'),
(31, 7, '2026-01-06', 10.00, 80.00, 1, 2026, '2026-01-06 10:21:59'),
(33, 8, '2026-01-06', 25.00, 30.00, 1, 2026, '2026-01-06 11:28:36');

--
-- Triggers `meter_readings`
--
DELIMITER $$
CREATE TRIGGER `trg_reading_before_insert` BEFORE INSERT ON `meter_readings` FOR EACH ROW BEGIN
    IF NEW.billing_month IS NULL OR NEW.billing_month = 0 THEN
        SET NEW.billing_month = MONTH(NEW.reading_date);
    END IF;
    IF NEW.billing_year IS NULL OR NEW.billing_year = 0 THEN
        SET NEW.billing_year = YEAR(NEW.reading_date);
    END IF;
    IF NEW.current_reading < NEW.previous_reading THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Current reading cannot be less than previous reading';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('Cash','Card','Online') NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `bill_id`, `payment_date`, `amount`, `method`, `reference_no`, `created_at`) VALUES
(1, 1, '2025-12-02', 100.00, 'Cash', '01', '2025-12-02 08:34:27'),
(2, 2, '2025-12-02', 40000.00, 'Cash', '02', '2025-12-02 13:39:22'),
(3, 2, '2025-12-02', 150.00, 'Cash', '02', '2025-12-02 14:38:12'),
(4, 3, '2025-12-02', 28000.00, 'Cash', '03', '2025-12-02 14:53:35'),
(5, 4, '2025-12-02', 50000.00, 'Cash', '05', '2025-12-02 14:54:49'),
(6, 6, '2025-12-03', 2800.00, 'Cash', '12', '2025-12-03 08:35:55'),
(7, 5, '2025-12-03', 28000.00, 'Cash', '45', '2025-12-03 08:49:50'),
(8, 10, '2025-12-03', 700000.00, 'Cash', '2115', '2025-12-03 17:27:40'),
(9, 17, '2026-01-06', 1050.00, 'Cash', 'REF-2026-01-06-0001', '2026-01-06 05:15:48'),
(10, 11, '2026-01-06', 23000.00, 'Cash', 'REF-2026-01-06-0002', '2026-01-06 05:56:51'),
(11, 22, '2026-01-06', 5000.00, 'Card', 'REF-2026-01-06-0003', '2026-01-06 11:30:52');

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `trg_payment_after_insert` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
    UPDATE bills
       SET amount_paid = amount_paid + NEW.amount,
           outstanding = total_amount - (amount_paid + NEW.amount),
           status = CASE 
                      WHEN total_amount <= (amount_paid + NEW.amount) THEN 'Paid'
                      WHEN (amount_paid + NEW.amount) > 0 THEN 'Partially Paid'
                      ELSE 'Unpaid'
                    END
     WHERE id = NEW.bill_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tariffs`
--

CREATE TABLE `tariffs` (
  `id` int(11) NOT NULL,
  `utility_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `fixed_charge` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tariffs`
--

INSERT INTO `tariffs` (`id`, `utility_id`, `name`, `price_per_unit`, `fixed_charge`, `is_active`, `created_at`) VALUES
(1, 1, 'Domestic', 45.00, 150.00, 1, '2025-12-02 07:41:16'),
(2, 1, 'Industrial', 55.00, 250.00, 1, '2025-12-02 07:41:16'),
(3, 2, 'Standard', 30.00, 100.00, 1, '2025-12-02 07:41:16'),
(4, 3, 'Standard', 40.00, 150.00, 1, '2025-12-02 07:41:16'),
(5, 2, 'tempale', 10.00, 100.00, 1, '2025-12-04 17:51:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff','manager') NOT NULL DEFAULT 'staff',
  `status` enum('active','disabled') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `otp` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `phone`, `password_hash`, `role`, `status`, `last_login`, `profile_image`, `created_by`, `created_at`, `updated_at`, `otp_code`, `otp_expiry`, `otp`) VALUES
(2, 'thisal', NULL, 'thisalchathnuka@gmail.com', NULL, '$2y$10$ReXuma9Dy.oZanZgGWmdiePOciDBNDZCXgQSRog7lP3Lm7n.1ifre', 'admin', 'active', '2025-12-03 03:46:55', NULL, NULL, '2025-12-02 19:05:43', '2026-01-06 11:07:40', NULL, '2026-01-06 12:17:40', '994059'),
(4, 'nimal', NULL, 'chathnukathisal@gamil.com', '0765498219', '$2y$10$Lpd5Xj6YPCBeRLoWG3zPMuZkxe.L7gMLqOGqi3OKkpuNOEmmtaZyy', 'admin', 'active', '2026-01-09 11:04:49', 'USER_4_1764773569.jpg', NULL, '2025-12-02 19:07:10', '2026-01-09 05:34:49', NULL, '2025-12-02 21:35:13', '869683'),
(6, 'henuka', NULL, 'henukapathirana2@gmail.com', NULL, '$2y$10$9tJEr4ThRQIrnjpeBGTjdekIN5asbMc8vE8.J.e1WWGzWss9pTD2K', 'admin', 'active', NULL, NULL, NULL, '2025-12-03 17:31:34', '2025-12-03 17:41:18', NULL, NULL, NULL),
(9, 'Banuka', NULL, 'cnukathisal@gamil.com', NULL, '$2y$10$om8fMNAt5R0wYXfoBYqAl.PVr6QGsp6kwGDK.FtXj6BbVvbtJSXaq', '', 'active', '2026-01-08 09:27:23', NULL, NULL, '2026-01-07 03:45:24', '2026-01-08 03:57:23', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilities`
--

CREATE TABLE `utilities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `unit_name` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilities`
--

INSERT INTO `utilities` (`id`, `name`, `unit_name`, `description`, `created_at`) VALUES
(1, 'Electricity', 'kWh', 'Electricity supply', '2025-12-02 07:41:16'),
(2, 'Water', 'm3', 'Water supply', '2025-12-02 07:41:16'),
(3, 'Gas', 'm3', 'Gas supply', '2025-12-02 07:41:16');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_customer_outstanding`
-- (See below for the actual view)
--
CREATE TABLE `v_customer_outstanding` (
`customer_id` int(11)
,`customer_code` varchar(20)
,`full_name` varchar(150)
,`total_outstanding` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_monthly_revenue`
-- (See below for the actual view)
--
CREATE TABLE `v_monthly_revenue` (
`billing_year` int(4)
,`billing_month` int(2)
,`total_collected` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_unpaid_bills`
-- (See below for the actual view)
--
CREATE TABLE `v_unpaid_bills` (
`bill_id` int(11)
,`customer_code` varchar(20)
,`full_name` varchar(150)
,`billing_month` int(11)
,`billing_year` int(11)
,`total_amount` decimal(10,2)
,`amount_paid` decimal(10,2)
,`outstanding` decimal(10,2)
,`due_date` date
,`status` enum('Unpaid','Partially Paid','Paid')
);

-- --------------------------------------------------------

--
-- Structure for view `v_customer_outstanding`
--
DROP TABLE IF EXISTS `v_customer_outstanding`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_customer_outstanding`  AS SELECT `c`.`id` AS `customer_id`, `c`.`customer_code` AS `customer_code`, `c`.`full_name` AS `full_name`, sum(`b`.`outstanding`) AS `total_outstanding` FROM (`customers` `c` left join `bills` `b` on(`b`.`customer_id` = `c`.`id`)) GROUP BY `c`.`id`, `c`.`customer_code`, `c`.`full_name` ;

-- --------------------------------------------------------

--
-- Structure for view `v_monthly_revenue`
--
DROP TABLE IF EXISTS `v_monthly_revenue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monthly_revenue`  AS SELECT year(`bills`.`bill_date`) AS `billing_year`, month(`bills`.`bill_date`) AS `billing_month`, sum(`bills`.`amount_paid`) AS `total_collected` FROM `bills` GROUP BY year(`bills`.`bill_date`), month(`bills`.`bill_date`) ORDER BY year(`bills`.`bill_date`) ASC, month(`bills`.`bill_date`) ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_unpaid_bills`
--
DROP TABLE IF EXISTS `v_unpaid_bills`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_unpaid_bills`  AS SELECT `b`.`id` AS `bill_id`, `c`.`customer_code` AS `customer_code`, `c`.`full_name` AS `full_name`, `b`.`billing_month` AS `billing_month`, `b`.`billing_year` AS `billing_year`, `b`.`total_amount` AS `total_amount`, `b`.`amount_paid` AS `amount_paid`, `b`.`outstanding` AS `outstanding`, `b`.`due_date` AS `due_date`, `b`.`status` AS `status` FROM (`bills` `b` join `customers` `c` on(`c`.`id` = `b`.`customer_id`)) WHERE `b`.`outstanding` > 0 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bill_customer` (`customer_id`),
  ADD KEY `fk_bill_meter` (`meter_id`),
  ADD KEY `fk_bill_tariff` (`tariff_id`),
  ADD KEY `fk_bills_reading` (`reading_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`);

--
-- Indexes for table `meters`
--
ALTER TABLE `meters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meter_number` (`meter_number`),
  ADD KEY `fk_meter_customer` (`customer_id`),
  ADD KEY `fk_meter_utility` (`utility_id`);

--
-- Indexes for table `meter_readings`
--
ALTER TABLE `meter_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reading_meter` (`meter_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_bill` (`bill_id`);

--
-- Indexes for table `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tariff_utility` (`utility_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `utilities`
--
ALTER TABLE `utilities`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `meters`
--
ALTER TABLE `meters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `meter_readings`
--
ALTER TABLE `meter_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `fk_bill_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_bill_meter` FOREIGN KEY (`meter_id`) REFERENCES `meters` (`id`),
  ADD CONSTRAINT `fk_bill_tariff` FOREIGN KEY (`tariff_id`) REFERENCES `tariffs` (`id`),
  ADD CONSTRAINT `fk_bills_reading` FOREIGN KEY (`reading_id`) REFERENCES `meter_readings` (`id`);

--
-- Constraints for table `meters`
--
ALTER TABLE `meters`
  ADD CONSTRAINT `fk_meter_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_meter_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`);

--
-- Constraints for table `meter_readings`
--
ALTER TABLE `meter_readings`
  ADD CONSTRAINT `fk_reading_meter` FOREIGN KEY (`meter_id`) REFERENCES `meters` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`);

--
-- Constraints for table `tariffs`
--
ALTER TABLE `tariffs`
  ADD CONSTRAINT `fk_tariff_utility` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
