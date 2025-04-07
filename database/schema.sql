CREATE DATABASE IF NOT EXISTS payroll;
USE payroll;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id VARCHAR(36) PRIMARY KEY,
    userId VARCHAR(36),
    employeeId VARCHAR(50) UNIQUE,
    firstName VARCHAR(100) NOT NULL,
    lastName VARCHAR(100) NOT NULL,
    otherName VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    cellNumber VARCHAR(20),
    position VARCHAR(100),
    department VARCHAR(100),
    division VARCHAR(100),
    joinDate DATETIME,
    engagementDate DATETIME,
    dateOfBirth DATE,
    gender ENUM('male', 'female'),
    socialSecurityNo VARCHAR(50),
    status ENUM('active', 'inactive', 'on_leave', 'terminated') DEFAULT 'active',
    basicSalary DECIMAL(12, 2),
    basicPay DECIMAL(12, 2),
    bankName VARCHAR(100),
    bankAccount VARCHAR(50),
    bankBranch VARCHAR(100),
    manNumber VARCHAR(50),
    nrc VARCHAR(50),
    jobTitle VARCHAR(100),
    taxId VARCHAR(50),
    unionType VARCHAR(100),
    contributionType VARCHAR(100),
    salaryScale VARCHAR(50),
    advanceBF DECIMAL(12, 2) DEFAULT 0.00,
    leaveDaysPerMonth INT DEFAULT 0,
    mlifeContributionPercentage DECIMAL(5, 2),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employeeId),
    INDEX idx_email (email),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payroll settings table
CREATE TABLE IF NOT EXISTS payroll_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pay_period ENUM('weekly', 'biweekly', 'monthly') DEFAULT 'monthly',
    tax_calculation ENUM('flat', 'progressive') DEFAULT 'progressive',
    auto_approve BOOLEAN DEFAULT FALSE,
    email_notifications BOOLEAN DEFAULT TRUE,
    default_currency VARCHAR(10) DEFAULT 'ZMW',
    napsa_rate DECIMAL(5, 2) DEFAULT 5.00,
    napsa_ceiling DECIMAL(10, 2) DEFAULT 1073.10,
    nhima_employee_rate DECIMAL(5, 2) DEFAULT 1.00,
    nhima_employer_rate DECIMAL(5, 2) DEFAULT 1.00,
    mlife_employee_rate DECIMAL(5, 2) DEFAULT 0.40,
    mlife_employer_rate DECIMAL(5, 2) DEFAULT 0.60,
    wcf_rate DECIMAL(5, 2) DEFAULT 1.00,
    skills_levy DECIMAL(5, 2) DEFAULT 0.50,
    lasf_employee_rate DECIMAL(5, 2) DEFAULT 2.00,
    lasf_employer_rate DECIMAL(5, 2) DEFAULT 2.00,
    ps_employee_rate DECIMAL(5, 2) DEFAULT 5.00,
    ps_employer_rate DECIMAL(5, 2) DEFAULT 5.00,
    zalaamu_rate DECIMAL(5, 2) DEFAULT 2.00,
    firesuz_rate DECIMAL(5, 2) DEFAULT 2.00
);

-- Payroll history table
CREATE TABLE IF NOT EXISTS payroll_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    total_employees INT NOT NULL,
    total_payroll DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payslips table
CREATE TABLE IF NOT EXISTS payslips (
    id VARCHAR(36) PRIMARY KEY,  -- Changed to standard UUID length
    employee_id VARCHAR(36) NOT NULL,  -- Match employees.id type
    period VARCHAR(7) NOT NULL,  -- Format: YYYY-MM
    issue_date DATE NOT NULL,
    basic_salary DECIMAL(15, 2) NOT NULL,
    total_allowances DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    total_deductions DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(15, 2) NOT NULL,
    status ENUM('draft', 'approved', 'rejected', 'paid') DEFAULT 'draft',  -- Added 'paid' status
    payment_method ENUM('bank', 'cash', 'mobile') DEFAULT 'bank',
    payment_reference VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_period (period),
    INDEX idx_status (status),
    CONSTRAINT fk_payslip_employee FOREIGN KEY (employee_id) 
        REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    work_hours DECIMAL(5, 2) DEFAULT 0.00,
    overtime DECIMAL(5, 2) DEFAULT 0.00,
    status ENUM('present', 'absent', 'half_day', 'leave') DEFAULT 'present',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Leave requests table (single version)
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(255) NOT NULL,
    leave_type ENUM('annual', 'sick', 'maternity', 'paternity', 'unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Loans table
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Salary structures table
CREATE TABLE IF NOT EXISTS salary_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(255) NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    hra DECIMAL(10,2),
    da DECIMAL(10,2),
    ta DECIMAL(10,2),
    medical_allowance DECIMAL(10,2),
    other_allowances DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Allowances table
CREATE TABLE IF NOT EXISTS allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    month DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Deductions table
CREATE TABLE IF NOT EXISTS deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    month DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);