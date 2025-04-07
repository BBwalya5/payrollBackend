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
if ($resource === "leave") {
    switch ($method) {
        case "GET":
            // Fetch leave requests
            $employeeId = $_GET['employeeId'] ?? null;
            $status = $_GET['status'] ?? null;

            $query = "SELECT * FROM leave_requests WHERE 1=1";
            $params = [];
            $types = "";

            if ($employeeId) {
                $query .= " AND employee_id = ?";
                $params[] = $employeeId;
                $types .= "s";
            }

            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
                $types .= "s";
            }

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $leaves = [];
            while ($row = $result->fetch_assoc()) {
                $leaves[] = $row;
            }
            echo json_encode($leaves);
            break;

        case "POST":
            // Create a new leave request
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssss",
                $data['employeeId'],
                $data['type'],
                $data['startDate'],
                $data['endDate'],
                $data['reason'],
                $data['status']
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => "Leave request created successfully"]);
            } else {
                echo json_encode(["error" => "Failed to create leave request"]);
            }
            break;

        case "PUT":
            // Update leave request (approve/reject)
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $data['status'], $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => "Leave request updated successfully"]);
            } else {
                echo json_encode(["error" => "Failed to update leave request"]);
            }
            break;

        case "DELETE":
            // Delete a leave request
            $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => "Leave request deleted successfully"]);
            } else {
                echo json_encode(["error" => "Failed to delete leave request"]);
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