<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/db.php';
require_once '../includes/response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single loan
                $query = "SELECT * FROM loans WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_GET['id']);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    sendError('Loan not found', 404);
                }

                $loan = $stmt->fetch(PDO::FETCH_ASSOC);
                sendSuccess($loan);
            } else {
                // Get loans with optional filters
                $query = "SELECT * FROM loans WHERE 1=1";
                $params = [];

                if (isset($_GET['employee_id'])) {
                    $query .= " AND employee_id = :employee_id";
                    $params[':employee_id'] = $_GET['employee_id'];
                }

                if (isset($_GET['status'])) {
                    $query .= " AND status = :status";
                    $params[':status'] = $_GET['status'];
                }

                $stmt = $db->prepare($query);
                $stmt->execute($params);

                $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendSuccess($loans);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!validateLoanData($data)) {
                sendError('Invalid loan data');
            }

            $query = "INSERT INTO loans (employee_id, amount, installment_amount, purpose, status) 
                     VALUES (:employee_id, :amount, :installment_amount, :purpose, 'pending')";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':employee_id', $data['employee_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':installment_amount', $data['installment_amount']);
            $stmt->bindParam(':purpose', $data['purpose']);

            if ($stmt->execute()) {
                $data['id'] = $db->lastInsertId();
                $data['status'] = 'pending';
                $data['remaining_amount'] = $data['amount'];
                sendSuccess($data, 'Loan request submitted successfully');
            } else {
                sendError('Failed to submit loan request');
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                sendError('Loan ID is required');
            }

            if (isset($data['status'])) {
                // Update loan status
                $query = "UPDATE loans 
                         SET status = :status, 
                             approval_date = CASE WHEN :status = 'approved' THEN NOW() ELSE NULL END 
                         WHERE id = :id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data['id']);
                $stmt->bindParam(':status', $data['status']);
            } elseif (isset($data['payment_amount'])) {
                // Record loan payment
                $query = "UPDATE loans 
                         SET remaining_amount = remaining_amount - :payment_amount,
                             last_payment_date = NOW()
                         WHERE id = :id AND remaining_amount >= :payment_amount";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data['id']);
                $stmt->bindParam(':payment_amount', $data['payment_amount']);

                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    // Check if loan is fully paid
                    $checkQuery = "UPDATE loans 
                                 SET status = 'paid' 
                                 WHERE id = :id AND remaining_amount <= 0";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':id', $data['id']);
                    $checkStmt->execute();
                } else {
                    sendError('Payment amount exceeds remaining loan amount');
                }
            } else {
                sendError('Invalid update data');
            }

            if ($stmt->execute()) {
                sendSuccess($data, 'Loan updated successfully');
            } else {
                sendError('Failed to update loan');
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                sendError('Loan ID is required');
            }

            $query = "DELETE FROM loans WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);

            if ($stmt->execute()) {
                sendSuccess(null, 'Loan deleted successfully');
            } else {
                sendError('Failed to delete loan');
            }
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}

function validateLoanData($data) {
    return isset($data['employee_id']) && 
           isset($data['amount']) && 
           isset($data['installment_amount']) && 
           isset($data['purpose']);
} 