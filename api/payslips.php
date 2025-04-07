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
if ($resource === "payslips") {
    switch ($method) {
        case "GET":
            if ($id) {
                // Fetch a single payslip by ID
                $stmt = $conn->prepare("SELECT * FROM payslips WHERE id = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                echo json_encode($result->fetch_assoc());
            } elseif (isset($_GET['employeeId'])) {
                // Fetch payslips for a specific employee
                $employeeId = $_GET['employeeId'];
                $stmt = $conn->prepare("SELECT * FROM payslips WHERE employee_id = ? ORDER BY created_at DESC");
                $stmt->bind_param("s", $employeeId);
                $stmt->execute();
                $result = $stmt->get_result();
                $payslips = [];
                while ($row = $result->fetch_assoc()) {
                    $payslips[] = $row;
                }
                echo json_encode($payslips);
            } else {
                // Fetch all payslips
                $result = $conn->query("SELECT * FROM payslips ORDER BY created_at DESC");
                $payslips = [];
                while ($row = $result->fetch_assoc()) {
                    $payslips[] = $row;
                }
                echo json_encode($payslips);
            }
            break;

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

        case "PUT":
            if ($id) {
                // Update payslip status (e.g., archive or approve)
                $data = json_decode(file_get_contents("php://input"), true);
                $stmt = $conn->prepare("UPDATE payslips SET status = ? WHERE id = ?");
                $stmt->bind_param("ss", $data['status'], $id);
                if ($stmt->execute()) {
                    echo json_encode(["success" => "Payslip updated successfully"]);
                } else {
                    echo json_encode(["error" => "Failed to update payslip"]);
                }
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