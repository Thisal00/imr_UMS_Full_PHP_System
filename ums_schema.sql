CREATE DATABASE IF NOT EXISTS ums_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE ums_db;

-- USERS
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff','manager') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (full_name, email, password_hash, role)
VALUES ('Admin User', 'admin@ums.local', SHA2('admin123',256), 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- UTILITIES
CREATE TABLE IF NOT EXISTS utilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    unit_name VARCHAR(20) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO utilities (name, unit_name, description) VALUES
('Electricity', 'kWh', 'Electricity supply'),
('Water', 'm3', 'Water supply'),
('Gas', 'm3', 'Gas supply')
ON DUPLICATE KEY UPDATE name=name;

-- CUSTOMERS
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    type ENUM('Household','Business','Government') NOT NULL DEFAULT 'Household',
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO customers (customer_code, full_name, type, address, phone, email) VALUES
('CUST001','ABC Family','Household','No.10, Main Road','0771234567','abc@example.com'),
('CUST002','XYZ Traders','Business','No.45, Town','0711111111','xyz@biz.com')
ON DUPLICATE KEY UPDATE customer_code=customer_code;

-- TARIFFS
CREATE TABLE IF NOT EXISTS tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utility_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    rate_per_unit DECIMAL(10,2) NOT NULL,
    fixed_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tariff_utility FOREIGN KEY (utility_id) REFERENCES utilities(id)
);

INSERT INTO tariffs (utility_id, name, rate_per_unit, fixed_charge) VALUES
(1,'Domestic',45.00,150.00),
(1,'Industrial',55.00,250.00),
(2,'Standard',30.00,100.00),
(3,'Standard',40.00,150.00)
ON DUPLICATE KEY UPDATE name=name;

-- METERS
CREATE TABLE IF NOT EXISTS meters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    utility_id INT NOT NULL,
    meter_number VARCHAR(50) NOT NULL UNIQUE,
    install_date DATE,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_meter_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_meter_utility FOREIGN KEY (utility_id) REFERENCES utilities(id)
);

-- SAMPLE METERS
INSERT INTO meters (customer_id, utility_id, meter_number, install_date, status) VALUES
(1,1,'ELEC-1001','2024-01-01','Active'),
(1,2,'WATR-2001','2024-01-01','Active'),
(2,1,'ELEC-1002','2024-01-01','Active')
ON DUPLICATE KEY UPDATE meter_number=meter_number;

-- METER READINGS
CREATE TABLE IF NOT EXISTS meter_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id INT NOT NULL,
    reading_date DATE NOT NULL,
    previous_reading DECIMAL(10,2) NOT NULL,
    current_reading DECIMAL(10,2) NOT NULL,
    units_used DECIMAL(10,2) GENERATED ALWAYS AS (current_reading - previous_reading) STORED,
    billing_month INT NOT NULL,
    billing_year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reading_meter FOREIGN KEY (meter_id) REFERENCES meters(id)
);

-- BILLS
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    meter_id INT NOT NULL,
    tariff_id INT NOT NULL,
    billing_month INT NOT NULL,
    billing_year INT NOT NULL,
    bill_date DATE NOT NULL,
    due_date DATE NOT NULL,
    units DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    outstanding DECIMAL(10,2) NOT NULL,
    status ENUM('Unpaid','Partially Paid','Paid') NOT NULL DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bill_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_bill_meter FOREIGN KEY (meter_id) REFERENCES meters(id),
    CONSTRAINT fk_bill_tariff FOREIGN KEY (tariff_id) REFERENCES tariffs(id)
);

-- PAYMENTS
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Cash','Card','Online') NOT NULL,
    reference_no VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_bill FOREIGN KEY (bill_id) REFERENCES bills(id)
);

-- FUNCTIONS
DELIMITER $$

CREATE FUNCTION fn_calc_bill(units DECIMAL(10,2), t_id INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE v_rate DECIMAL(10,2);
    DECLARE v_fixed DECIMAL(10,2);
    SELECT rate_per_unit, fixed_charge
      INTO v_rate, v_fixed
      FROM tariffs
     WHERE id = t_id;
    RETURN v_fixed + (units * v_rate);
END$$

CREATE FUNCTION fn_late_fee(base_amount DECIMAL(10,2))
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    RETURN ROUND(base_amount * 0.05, 2);
END$$

DELIMITER ;

-- VIEWS
CREATE OR REPLACE VIEW v_unpaid_bills AS
SELECT 
    b.id AS bill_id,
    c.customer_code,
    c.full_name,
    b.billing_month,
    b.billing_year,
    b.total_amount,
    b.amount_paid,
    b.outstanding,
    b.due_date,
    b.status
FROM bills b
JOIN customers c ON c.id = b.customer_id
WHERE b.outstanding > 0;

CREATE OR REPLACE VIEW v_monthly_revenue AS
SELECT 
    billing_year,
    billing_month,
    SUM(amount_paid) AS total_collected
FROM bills
GROUP BY billing_year, billing_month
ORDER BY billing_year DESC, billing_month DESC;

CREATE OR REPLACE VIEW v_customer_outstanding AS
SELECT 
    c.id AS customer_id,
    c.customer_code,
    c.full_name,
    SUM(b.outstanding) AS total_outstanding
FROM customers c
LEFT JOIN bills b ON b.customer_id = c.id
GROUP BY c.id, c.customer_code, c.full_name;

-- TRIGGERS
DELIMITER $$

CREATE TRIGGER trg_payment_after_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    UPDATE bills
       SET amount_paid = amount_paid + NEW.amount,
           outstanding = total_amount - (amount_paid + NEW.amount),
           status = CASE 
                      WHEN total_amount <= (amount_paid + NEW.amount) THEN 'Paid'
                      WHEN (amount_paid + NEW.amount) > 0 THEN 'Partially Paid'
                      ELSE 'Unpaid'
                    END
     WHERE id = NEW.bill_id;
END$$

CREATE TRIGGER trg_reading_before_insert
BEFORE INSERT ON meter_readings
FOR EACH ROW
BEGIN
    IF NEW.billing_month IS NULL OR NEW.billing_month = 0 THEN
        SET NEW.billing_month = MONTH(NEW.reading_date);
    END IF;
    IF NEW.billing_year IS NULL OR NEW.billing_year = 0 THEN
        SET NEW.billing_year = YEAR(NEW.reading_date);
    END IF;
    IF NEW.current_reading < NEW.previous_reading THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Current reading cannot be less than previous reading';
    END IF;
END$$

DELIMITER ;

-- STORED PROCEDURES
DELIMITER $$

CREATE PROCEDURE sp_generate_bill(IN p_reading_id INT, IN p_tariff_id INT, IN p_due_days INT)
BEGIN
    DECLARE v_meter_id INT;
    DECLARE v_customer_id INT;
    DECLARE v_units DECIMAL(10,2);
    DECLARE v_month INT;
    DECLARE v_year INT;
    DECLARE v_base DECIMAL(10,2);
    DECLARE v_late DECIMAL(10,2);
    DECLARE v_bill_date DATE;
    DECLARE v_due_date DATE;

    SELECT 
        mr.meter_id,
        m.customer_id,
        mr.units_used,
        mr.billing_month,
        mr.billing_year
    INTO v_meter_id, v_customer_id, v_units, v_month, v_year
    FROM meter_readings mr
    JOIN meters m ON m.id = mr.meter_id
    WHERE mr.id = p_reading_id;

    SET v_bill_date = CURDATE();
    SET v_due_date = DATE_ADD(v_bill_date, INTERVAL p_due_days DAY);

    SET v_base = fn_calc_bill(v_units, p_tariff_id);
    SET v_late = 0.00;

    INSERT INTO bills (
        customer_id, meter_id, tariff_id,
        billing_month, billing_year,
        bill_date, due_date,
        units, amount, late_fee, total_amount,
        amount_paid, outstanding, status
    ) VALUES (
        v_customer_id, v_meter_id, p_tariff_id,
        v_month, v_year,
        v_bill_date, v_due_date,
        v_units, v_base, v_late, v_base,
        0.00, v_base, 'Unpaid'
    );
END$$

CREATE PROCEDURE sp_list_defaulters(IN p_days INT)
BEGIN
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

DELIMITER ;
