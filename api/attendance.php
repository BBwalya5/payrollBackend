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
if ($resource === "attendance") {
    switch ($method) {
        case "GET":
            // Fetch attendance records
            $employeeId = $_GET['employeeId'] ?? null;
            $month = $_GET['month'] ?? null;

            $query = "SELECT * FROM attendance WHERE 1=1";
            $params = [];
            $types = "";

            if ($employeeId) {
                $query .= " AND employee_id = ?";
                $params[] = $employeeId;
                $types .= "s";
            }

            if ($month) {
                $query .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
                $params[] = $month;
                $types .= "s";
            }

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $attendance = [];
            while ($row = $result->fetch_assoc()) {
                $attendance[] = $row;
            }
            echo json_encode($attendance);
            break;

        case "POST":
            // Mark attendance
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, check_in, check_out, work_hours, overtime, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssddss",
                $data['employeeId'],
                $data['date'],
                $data['checkIn'],
                $data['checkOut'],
                $data['workHours'],
                $data['overtime'],
                $data['status'],
                $data['notes']
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => "Attendance marked successfully"]);
            } else {
                echo json_encode(["error" => "Failed to mark attendance"]);
            }
            break;

        case "PUT":
            // Update attendance
            $data = json_decode(file_get_contents("php://input"), true);
            $stmt = $conn->prepare("UPDATE attendance SET check_in = ?, check_out = ?, work_hours = ?, overtime = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param(
                "ssddssi",
                $data['checkIn'],
                $data['checkOut'],
                $data['workHours'],
                $data['overtime'],
                $data['status'],
                $data['notes'],
                $id
            );
            if ($stmt->execute()) {
                echo json_encode(["success" => "Attendance updated successfully"]);
            } else {
                echo json_encode(["error" => "Failed to update attendance"]);
            }
            break;

        case "POST":
            if ($id === "bulk-upload") {
                // Bulk upload attendance
                if (isset($_FILES['file'])) {
                    $file = $_FILES['file']['tmp_name'];
                    $handle = fopen($file, "r");
                    $row = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if ($row > 0) { // Skip header row
                            $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, check_in, check_out, work_hours, overtime, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param(
                                "ssssddss",
                                $data[0], // Employee ID
                                $data[1], // Date
                                $data[2], // Check In
                                $data[3], // Check Out
                                $data[4], // Work Hours
                                $data[5], // Overtime
                                $data[6], // Status
                                $data[7]  // Notes
                            );
                            $stmt->execute();
                        }
                        $row++;
                    }
                    fclose($handle);
                    echo json_encode(["success" => "Bulk upload completed successfully"]);
                } else {
                    echo json_encode(["error" => "No file uploaded"]);
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