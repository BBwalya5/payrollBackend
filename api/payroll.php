<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Payroll";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$resource = array_shift($request);
$id = array_shift($request);

// Handle the API
if ($resource === "payroll") {
    switch ($method) {
        case "GET":
            if ($id === "settings") {
                // Fetch payroll settings
                $result = $conn->query("SELECT * FROM payroll_settings LIMIT 1");
                echo json_encode($result->fetch_assoc());
            } elseif ($id === "history") {
                // Fetch payroll history
                $result = $conn->query("SELECT * FROM payroll_history ORDER BY created_at DESC");
                $history = [];
                while ($row = $result->fetch_assoc()) {
                    $history[] = $row;
                }
                echo json_encode($history);
            }
            break;

        case "PUT":
            if ($id === "settings") {
                // Update payroll settings
                $data = json_decode(file_get_contents("php://input"), true);
                $stmt = $conn->prepare("UPDATE payroll_settings SET pay_period = ?, tax_calculation = ?, auto_approve = ?, email_notifications = ?, default_currency = ?, napsa_rate = ?, napsa_ceiling = ?, nhima_employee_rate = ?, nhima_employer_rate = ?, mlife_employee_rate = ?, mlife_employer_rate = ?, wcf_rate = ?, skills_levy = ?, lasf_employee_rate = ?, lasf_employer_rate = ?, ps_employee_rate = ?, ps_employer_rate = ?, zalaamu_rate = ?, firesuz_rate = ? WHERE id = 1");
                $stmt->bind_param(
                    "ssissdddddddddddddd",
                    $data['payPeriod'],
                    $data['taxCalculation'],
                    $data['autoApprove'],
                    $data['emailNotifications'],
                    $data['defaultCurrency'],
                    $data['napsaRate'],
                    $data['napsaCeiling'],
                    $data['nhimaEmployeeRate'],
                    $data['nhimaEmployerRate'],
                    $data['mlifeEmployeeRate'],
                    $data['mlifeEmployerRate'],
                    $data['wcfRate'],
                    $data['skillsLevy'],
                    $data['lasfEmployeeRate'],
                    $data['lasfEmployerRate'],
                    $data['psEmployeeRate'],
                    $data['psEmployerRate'],
                    $data['zalaamuRate'],
                    $data['firesuzRate']
                );
                if ($stmt->execute()) {
                    echo json_encode(["success" => "Payroll settings updated successfully"]);
                } else {
                    echo json_encode(["error" => "Failed to update payroll settings"]);
                }
            }
            break;

        case "POST":
            if ($id === "process") {
                // Process payroll for a specific month
                $data = json_decode(file_get_contents("php://input"), true);
                $month = $data['payPeriod'];
                $totalEmployees = rand(50, 100); // Simulate total employees
                $totalPayroll = rand(50000, 100000); // Simulate total payroll

                $stmt = $conn->prepare("INSERT INTO payroll_history (month, total_employees, total_payroll) VALUES (?, ?, ?)");
                $stmt->bind_param("sid", $month, $totalEmployees, $totalPayroll);
                if ($stmt->execute()) {
                    echo json_encode(["success" => "Payroll processed successfully"]);
                } else {
                    echo json_encode(["error" => "Failed to process payroll"]);
                }
            }
            break;

        default:
            echo json_encode(["error" => "Invalid request method"]);
            break;
    }
} elseif ($resource === "payslips") {
    switch ($method) {
        case "POST":
            // Create a new payslip
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("INSERT INTO payslips (id, employee_id, period, issue_date, basic_salary, total_allowances, total_deductions, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssdddss",
                $data['id'],
                $data['employeeId'],
                $data['period'],
                $data['issueDate'],
                $data['basicSalary'],
                $data['totalAllowances'],
                $data['totalDeductions'],
                $data['netSalary'],
                $data['status']
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => "Payslip created successfully"]);
            } else {
                echo json_encode(["error" => "Failed to create payslip"]);
            }
            break;

        default:
            echo json_encode(["error" => "Invalid request method"]);
            break;
    }
} else {
    echo json_encode(["error" => "Invalid resource"]);
}

$conn->close();
?>