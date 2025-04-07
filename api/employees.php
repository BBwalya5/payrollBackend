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
if ($resource === "employees") {
    switch ($method) {
        case "GET":
            if ($id) {
                // Fetch a single employee
                $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                echo json_encode($result->fetch_assoc());
            } else {
                // Fetch all employees
                $result = $conn->query("SELECT * FROM employees");
                $employees = [];
                while ($row = $result->fetch_assoc()) {
                    $employees[] = $row;
                }
                echo json_encode($employees);
            }
            break;

        case "POST":
            // Add a new employee
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("INSERT INTO employees (id, userId, employeeId, firstName, lastName, otherName, email, phone, cellNumber, position, department, division, joinDate, engagementDate, dateOfBirth, gender, socialSecurityNo, status, basicSalary, basicPay, bankName, bankAccount, bankBranch, manNumber, nrc, jobTitle, taxId, unionType, contributionType, salaryScale, advanceBF, leaveDaysPerMonth, mlifeContributionPercentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssssssssssssssssssssssssssssdd",
                $data['id'],
                $data['userId'],
                $data['employeeId'],
                $data['firstName'],
                $data['lastName'],
                $data['otherName'],
                $data['email'],
                $data['phone'],
                $data['cellNumber'],
                $data['position'],
                $data['department'],
                $data['division'],
                $data['joinDate'],
                $data['engagementDate'],
                $data['dateOfBirth'],
                $data['gender'],
                $data['socialSecurityNo'],
                $data['status'],
                $data['basicSalary'],
                $data['basicPay'],
                $data['bankName'],
                $data['bankAccount'],
                $data['bankBranch'],
                $data['manNumber'],
                $data['nrc'],
                $data['jobTitle'],
                $data['taxId'],
                $data['unionType'],
                $data['contributionType'],
                $data['salaryScale'],
                $data['advanceBF'],
                $data['leaveDaysPerMonth'],
                $data['mlifeContributionPercentage']
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => "Employee added successfully"]);
            } else {
                echo json_encode(["error" => "Failed to add employee"]);
            }
            break;

        case "PUT":
            // Update an employee
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("UPDATE employees SET firstName = ?, lastName = ?, email = ?, phone = ?, position = ?, department = ?, status = ? WHERE id = ?");
            $stmt->bind_param(
                "ssssssss",
                $data['firstName'],
                $data['lastName'],
                $data['email'],
                $data['phone'],
                $data['position'],
                $data['department'],
                $data['status'],
                $id
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => "Employee updated successfully"]);
            } else {
                echo json_encode(["error" => "Failed to update employee"]);
            }
            break;

        case "DELETE":
            // Delete an employee
            $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => "Employee deleted successfully"]);
            } else {
                echo json_encode(["error" => "Failed to delete employee"]);
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