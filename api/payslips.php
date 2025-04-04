<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connection.php';
require_once '../includes/response.php';

// Get all payslips
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id'])) {
    try {
        $query = "SELECT p.*, e.first_name, e.last_name FROM payslips p 
                 JOIN employees e ON p.employee_id = e.id";
        
        if (isset($_GET['employeeId'])) {
            $query .= " WHERE p.employee_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$_GET['employeeId']]);
        } else {
            $stmt = $pdo->query($query);
        }
        
        $payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(200, 'Payslips retrieved successfully', $payslips);
    } catch (PDOException $e) {
        sendResponse(500, 'Error retrieving payslips: ' . $e->getMessage());
    }
}

// Get single payslip
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, e.first_name, e.last_name FROM payslips p 
                             JOIN employees e ON p.employee_id = e.id 
                             WHERE p.id = ?");
        $stmt->execute([$_GET['id']]);
        $payslip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payslip) {
            // Get allowance breakdown
            $stmt = $pdo->prepare("SELECT * FROM payslip_allowances WHERE payslip_id = ?");
            $stmt->execute([$payslip['id']]);
            $payslip['allowanceBreakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get deduction breakdown
            $stmt = $pdo->prepare("SELECT * FROM payslip_deductions WHERE payslip_id = ?");
            $stmt->execute([$payslip['id']]);
            $payslip['deductionBreakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse(200, 'Payslip retrieved successfully', $payslip);
        } else {
            sendResponse(404, 'Payslip not found');
        }
    } catch (PDOException $e) {
        sendResponse(500, 'Error retrieving payslip: ' . $e->getMessage());
    }
}

// Generate payslip
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['employeeId']) || !isset($data['payPeriod'])) {
        sendResponse(400, 'Employee ID and pay period are required');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get salary calculation
        $stmt = $pdo->prepare("SELECT * FROM salary_structures WHERE employee_id = ?");
        $stmt->execute([$data['employeeId']]);
        $salaryStructure = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$salaryStructure) {
            $pdo->rollBack();
            sendResponse(404, 'Salary structure not found');
            exit;
        }
        
        // Get allowances
        $stmt = $pdo->prepare("SELECT * FROM allowances WHERE salary_structure_id = ?");
        $stmt->execute([$salaryStructure['id']]);
        $allowances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get deductions
        $stmt = $pdo->prepare("SELECT * FROM deductions WHERE salary_structure_id = ?");
        $stmt->execute([$salaryStructure['id']]);
        $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate salary components
        $basicSalary = $salaryStructure['basic_salary'];
        $totalAllowances = 0;
        $totalDeductions = 0;
        
        // Calculate allowances
        foreach ($allowances as $allowance) {
            if ($allowance['type'] === 'fixed') {
                $totalAllowances += $allowance['amount'];
            } else {
                $totalAllowances += ($basicSalary * $allowance['amount'] / 100);
            }
        }
        
        // Calculate deductions
        foreach ($deductions as $deduction) {
            if ($deduction['type'] === 'fixed') {
                $totalDeductions += $deduction['amount'];
            } else {
                $totalDeductions += ($basicSalary * $deduction['amount'] / 100);
            }
        }
        
        // Calculate gross and net salary
        $grossSalary = $basicSalary + $totalAllowances;
        $netSalary = $grossSalary - $totalDeductions;
        
        // Create payslip
        $stmt = $pdo->prepare("INSERT INTO payslips (employee_id, pay_period, generated_date, basic_salary, gross_salary, total_allowances, total_deductions, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['employeeId'],
            $data['payPeriod'],
            date('Y-m-d'),
            $basicSalary,
            $grossSalary,
            $totalAllowances,
            $totalDeductions,
            $netSalary,
            'draft'
        ]);
        
        $payslipId = $pdo->lastInsertId();
        
        // Save allowance breakdown
        $stmt = $pdo->prepare("INSERT INTO payslip_allowances (payslip_id, name, amount, type) VALUES (?, ?, ?, ?)");
        foreach ($allowances as $allowance) {
            $amount = $allowance['type'] === 'fixed' ? $allowance['amount'] : ($basicSalary * $allowance['amount'] / 100);
            $stmt->execute([$payslipId, $allowance['name'], $amount, $allowance['type']]);
        }
        
        // Save deduction breakdown
        $stmt = $pdo->prepare("INSERT INTO payslip_deductions (payslip_id, name, amount, type) VALUES (?, ?, ?, ?)");
        foreach ($deductions as $deduction) {
            $amount = $deduction['type'] === 'fixed' ? $deduction['amount'] : ($basicSalary * $deduction['amount'] / 100);
            $stmt->execute([$payslipId, $deduction['name'], $amount, $deduction['type']]);
        }
        
        $pdo->commit();
        sendResponse(201, 'Payslip generated successfully', ['id' => $payslipId]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendResponse(500, 'Error generating payslip: ' . $e->getMessage());
    }
}

// Update payslip status
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['status'])) {
        sendResponse(400, 'Payslip ID and status are required');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE payslips SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['id']]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(200, 'Payslip status updated successfully');
        } else {
            sendResponse(404, 'Payslip not found');
        }
    } catch (PDOException $e) {
        sendResponse(500, 'Error updating payslip status: ' . $e->getMessage());
    }
}

// Delete payslip
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        sendResponse(400, 'Payslip ID is required');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete allowance breakdown
        $stmt = $pdo->prepare("DELETE FROM payslip_allowances WHERE payslip_id = ?");
        $stmt->execute([$data['id']]);
        
        // Delete deduction breakdown
        $stmt = $pdo->prepare("DELETE FROM payslip_deductions WHERE payslip_id = ?");
        $stmt->execute([$data['id']]);
        
        // Delete payslip
        $stmt = $pdo->prepare("DELETE FROM payslips WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            sendResponse(200, 'Payslip deleted successfully');
        } else {
            $pdo->rollBack();
            sendResponse(404, 'Payslip not found');
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        sendResponse(500, 'Error deleting payslip: ' . $e->getMessage());
    }
} 