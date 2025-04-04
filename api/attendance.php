<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

validateToken();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = 'SELECT * FROM attendance WHERE 1=1';
    $params = [];

    if (isset($_GET['employeeId'])) {
        $query .= ' AND employee_id = ?';
        $params[] = $_GET['employeeId'];
    }

    if (isset($_GET['month'])) {
        $query .= ' AND MONTH(date) = ? AND YEAR(date) = ?';
        $params[] = date('m', strtotime($_GET['month']));
        $params[] = date('Y', strtotime($_GET['month']));
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['employee_id']) || !isset($data['date']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $stmt = $pdo->prepare('
        INSERT INTO attendance (employee_id, date, time_in, time_out, status) 
        VALUES (?, ?, ?, ?, ?)
    ');
    
    try {
        $stmt->execute([
            $data['employee_id'],
            $data['date'],
            $data['time_in'] ?? null,
            $data['time_out'] ?? null,
            $data['status']
        ]);
        echo json_encode(['message' => 'Attendance record created successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create attendance record']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['time_out'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $stmt = $pdo->prepare('UPDATE attendance SET time_out = ? WHERE id = ?');
    
    try {
        $stmt->execute([$data['time_out'], $data['id']]);
        echo json_encode(['message' => 'Attendance record updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update attendance record']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 